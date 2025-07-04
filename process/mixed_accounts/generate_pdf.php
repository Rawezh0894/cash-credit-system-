<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if mixed account ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Mixed Account ID is required.');
}

$account_id = intval($_GET['id']);

// Fetch mixed account info (only for title)
$conn = Database::getInstance();
$stmt = $conn->prepare("SELECT name, they_owe, we_owe, they_advance, we_advance, phone1, phone2 FROM mixed_accounts WHERE id = ?");
$stmt->execute([$account_id]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$account) die('Mixed Account not found.');

// Get phones
$phones = [];
if (!empty($account['phone1'])) $phones[] = $account['phone1'];
if (!empty($account['phone2'])) $phones[] = $account['phone2'];
$account_phones = implode(' - ', $phones);

// Handle date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch all transactions for this mixed account, with date filter if set
if ($start_date && $end_date) {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE mixed_account_id = ? AND date BETWEEN ? AND ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY date ASC, id ASC");
    $stmt->execute([$account_id, $start_date, $end_date]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE mixed_account_id = ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY date ASC, id ASC");
    $stmt->execute([$account_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get receipt files for each transaction
foreach ($transactions as $key => $transaction) {
    $fileStmt = $conn->prepare("SELECT file_path FROM transaction_files WHERE transaction_id = ?");
    $fileStmt->execute([$transaction['id']]);
    $receipt_files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
    $transactions[$key]['receipt_files'] = $receipt_files;
}

// Calculate balance from non-deleted transactions
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE 
            WHEN type = 'credit' AND direction = 'sale' THEN amount
            ELSE 0 
        END) as credit_sale_amount,
        
        SUM(CASE 
            WHEN type = 'credit' AND direction = 'purchase' THEN amount
            ELSE 0 
        END) as credit_purchase_amount,
        
        SUM(CASE 
            WHEN type = 'payment' THEN amount
            ELSE 0 
        END) as payment_amount,
        
        SUM(CASE 
            WHEN type = 'collection' THEN amount
            ELSE 0 
        END) as collection_amount,
        
        SUM(CASE 
            WHEN type = 'advance' AND direction = 'advance_give' THEN amount
            ELSE 0 
        END) as advance_give_amount,
        
        SUM(CASE 
            WHEN type = 'advance' AND direction = 'advance_receive' THEN amount
            ELSE 0 
        END) as advance_receive_amount,
        
        SUM(CASE 
            WHEN type = 'advance_refund' THEN amount
            ELSE 0 
        END) as advance_refund_amount,
        
        SUM(CASE 
            WHEN type = 'advance_collection' THEN amount
            ELSE 0 
        END) as advance_collection_amount
    FROM transactions 
    WHERE mixed_account_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)
");
$stmt->execute([$account_id]);
$calculated_balance = $stmt->fetch(PDO::FETCH_ASSOC);

// We need to get the actual current balances from database
$stmt = $conn->prepare("SELECT they_owe, we_owe, they_advance, we_advance FROM mixed_accounts WHERE id = ?");
$stmt->execute([$account_id]);
$current_balance = $stmt->fetch(PDO::FETCH_ASSOC);

// Use the actual current values from the database
$they_owe = floatval($current_balance['they_owe']);
$we_owe = floatval($current_balance['we_owe']);
$they_advance = floatval($current_balance['they_advance']);
$we_advance = floatval($current_balance['we_advance']);

if ($they_owe < 0) $they_owe = 0;
if ($we_owe < 0) $we_owe = 0;
if ($they_advance < 0) $they_advance = 0;
if ($we_advance < 0) $we_advance = 0;

// Calculate customer balance (they owe us)
$customer_balance = ($they_owe - $they_advance);

// Calculate supplier balance (we owe them)
$supplier_balance = ($we_owe - $we_advance);

// Log the calculated values for debugging
error_log("Customer transactions balance: " . $customer_balance);
error_log("Supplier transactions balance: " . $supplier_balance);
error_log("they_owe: " . $they_owe . ", they_advance: " . $they_advance . ", we_owe: " . $we_owe . ", we_advance: " . $we_advance);

// We'll show two balances: customer and supplier, depending on direction/type
$final_customer_balance = max(0, $they_owe - $they_advance);
$final_supplier_balance = max(0, $we_owe - $we_advance);
$prev_customer_balances = [];
$prev_supplier_balances = [];
$curr_customer_balance = $final_customer_balance;
$curr_supplier_balance = $final_supplier_balance;
for ($i = count($transactions) - 1; $i >= 0; $i--) {
    $t = $transactions[$i];
    // Store balances before this transaction
    $prev_customer_balances[$i] = $curr_customer_balance;
    $prev_supplier_balances[$i] = $curr_supplier_balance;
    // Undo this transaction for next iteration
    if ($t['direction'] === 'sale') {
        if ($t['type'] === 'credit') {
            $curr_customer_balance -= $t['amount'];
        } elseif ($t['type'] === 'collection' || $t['type'] === 'payment') {
            $curr_customer_balance += $t['amount'];
        } elseif ($t['type'] === 'advance') {
            $curr_customer_balance += $t['amount'];
        } elseif ($t['type'] === 'advance_refund') {
            $curr_customer_balance -= $t['amount'];
        }
    } elseif ($t['direction'] === 'purchase') {
        if ($t['type'] === 'credit') {
            $curr_supplier_balance -= $t['amount'];
        } elseif ($t['type'] === 'payment' || $t['type'] === 'collection') {
            $curr_supplier_balance += $t['amount'];
        } elseif ($t['type'] === 'advance') {
            $curr_supplier_balance += $t['amount'];
        } elseif ($t['type'] === 'advance_collection') {
            $curr_supplier_balance -= $t['amount'];
        }
    }
}

$previous_before_last = null;
if (count($transactions) > 1) {
    $last = count($transactions) - 2;
    $t = $transactions[$last];
    if ($t['direction'] === 'sale') {
        $previous_before_last = $prev_customer_balances[$last];
    } elseif ($t['direction'] === 'purchase') {
        $previous_before_last = $prev_supplier_balances[$last];
    } else {
        $previous_before_last = null;
    }
} elseif (count($transactions) === 1) {
    $t = $transactions[0];
    if ($t['direction'] === 'sale') {
        $previous_before_last = $prev_customer_balances[0];
    } elseif ($t['direction'] === 'purchase') {
        $previous_before_last = $prev_supplier_balances[0];
    } else {
        $previous_before_last = null;
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی ئەکاونتی تێکەڵە - <?php echo $account['name']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/receipt.css">
</head>
<body>
    <div class="watermark">ئەکاونتی تێکەڵە</div>
    <div class="receipt-container">
        <div class="business-header">
            <div class="business-title">کۆگای احمد و ئەشکان</div>
            <div class="business-contacts">ژمارە مۆبایل: 07712255656 - 07501478786</div>
            <div class="business-address">کۆگاکانی غرفة التجارة-کۆگای 288</div>
        </div>
        
        <div class="account-info mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>ناوی هەژماری تێکەڵ:</strong> <?php echo htmlspecialchars($account['name']); ?>
                </div>
                <?php if (!empty($account_phones)): ?>
                <div>
                    <strong>ژمارەی مۆبایل:</strong> <?php echo htmlspecialchars($account_phones); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Date Filter Form -->
        <form method="get" class="row g-2 align-items-end mt-3 mb-2 no-print">
            <input type="hidden" name="id" value="<?php echo $account_id; ?>">
            <div class="col-auto">
                <label for="start_date" class="form-label mb-0">لە بەروار</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-auto">
                <label for="end_date" class="form-label mb-0">بۆ بەروار</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">فلتەرکردن</button>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-secondary" id="reset-filters-btn">پاککردنەوە</button>
            </div>
        </form>
        <!-- Initial Balance (after filter) -->
        
        <div class="transaction-details">
            <h5 class="mb-3">لیستی هەموو مامەڵەکان</h5>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>بەروار</th>
                        <th>جۆر</th>
                        <th>ئاراستە</th>
                        <th>بڕ</th>
                        <th>تێبینی</th>
                        <th>وێنەکە</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $i => $t): ?>
                    <?php
                        $rowClass = '';
                        if ($t['type'] === 'credit') {
                            $rowClass = 'table-danger';
                        } elseif ($t['type'] === 'advance' || $t['type'] === 'cash') {
                            $rowClass = 'table-success';
                        }
                        
                        // Check if transaction has receipt files
                        $has_receipt_files = !empty($t['receipt_files']) && is_array($t['receipt_files']) && count($t['receipt_files']) > 0;
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo $t['date']; ?><?php if ($t['type'] === 'credit' && !empty($t['due_date'])): ?><br><small class="text-muted">بەرواری گەڕاندنەوە: <?php echo $t['due_date']; ?></small><?php endif; ?></td>
                        <td>
                            <?php
                                if ($t['type'] === 'credit') echo 'قەرز';
                                elseif ($t['type'] === 'advance') echo 'پێشەکی';
                                elseif ($t['type'] === 'payment') echo 'قەرز دانەوە';
                                elseif ($t['type'] === 'collection') echo 'قەرز وەرگرتنەوە';
                                elseif ($t['type'] === 'advance_refund') echo 'گەڕاندنەوەی پێشەکی';
                                elseif ($t['type'] === 'advance_collection') echo 'پێشەکی وەرگرتنەوە';
                                else echo 'نەقد';
                            ?>
                        </td>
                        <td>
                            <?php
                                if ($t['direction'] === 'sale') echo 'فرۆشتن' ;
                                elseif ($t['direction'] === 'purchase') echo 'کڕین';
                                elseif ($t['direction'] === 'advance_give') echo 'پێشەکی دان';
                                elseif ($t['direction'] === 'advance_receive') echo 'پێشەکی وەرگرتن';
                                else echo '-';
                            ?>
                        </td>
                        <td><?php echo number_format($t['amount']); ?> د.ع</td>
                        <td><?php echo $t['notes'] ? $t['notes'] : '-'; ?></td>
                        <td>
                            <?php if ($has_receipt_files): ?>
                                <?php foreach ($t['receipt_files'] as $index => $file_path): ?>
                                    <a href="../../<?php echo $file_path; ?>" target="_blank" class="image-link">
                                        <i class="bi bi-image image-icon"></i>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                نییە
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-info">
                    <td colspan="4" class="text-end">
                        <strong>
                            <?php if ($previous_before_last !== null): ?>
                                باڵانسی پێش کۆتا مامەڵە: <?php echo number_format($previous_before_last); ?> د.ع<br>
                            <?php endif; ?>
                         
                        </strong>
                    </td>
                    <td colspan="3">
                        <strong>
                            <?php 
                            // Display final balances as before
                            if ($final_customer_balance > 0): ?>
                               باڵانسی کۆتایی:
                                <?php echo number_format($final_customer_balance); ?> د.ع
                                <span class="text-danger">(قەرزارە)</span>
                            <?php endif;
                            if ($final_supplier_balance > 0): ?>
                              باڵانسی کۆتایی:
                                <?php echo number_format($final_supplier_balance); ?> د.ع
                                <span class="text-danger">(قەرزارم)</span>
                            <?php endif;
                            if ($remaining_they_advance > 0): ?>
                              باڵانسی کۆتایی:
                                <?php echo number_format($remaining_they_advance); ?> د.ع
                                <span class="text-success">(پێشەکی ئەوان)</span>
                            <?php endif;
                            if ($remaining_we_advance > 0): ?>
                              باڵانسی کۆتایی:
                                <?php echo number_format($remaining_we_advance); ?> د.ع
                                <span class="text-success">(پێشەکی ئێمە)</span>
                            <?php endif;
                            if ($final_customer_balance <= 0 && $final_supplier_balance <= 0 && $remaining_they_advance <= 0 && $remaining_we_advance <= 0): ?>
                                0 د.ع
                            <?php endif; ?>
                        </strong>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="button-container">
            <button class="action-button" onclick="window.print()">
                <i class="bi bi-printer"></i> چاپکردن
            </button>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var resetBtn = document.getElementById('reset-filters-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
                this.form.submit();
            });
        }
    });
    </script>
    <style>
    @media print {
        .no-print { display: none !important; }
    }
    </style>
</body>
</html> 