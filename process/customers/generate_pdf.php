<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if customer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Customer ID is required.');
}

$customer_id = intval($_GET['id']);

// Fetch customer info (only for title)
$conn = Database::getInstance();
$stmt = $conn->prepare("SELECT name, owed_amount, advance_payment, phone1, phone2 FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) die('Customer not found.');

// Get phones
$phones = [];
if (!empty($customer['phone1'])) $phones[] = $customer['phone1'];
if (!empty($customer['phone2'])) $phones[] = $customer['phone2'];
$customer_phones = implode(' - ', $phones);

// Handle date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch all transactions for this customer, with date filter if set
$transactions = [];
if ($start_date && $end_date) {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE customer_id = ? AND date BETWEEN ? AND ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY date ASC, id ASC");
    $stmt->execute([$customer_id, $start_date, $end_date]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE customer_id = ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY date ASC, id ASC");
    $stmt->execute([$customer_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get receipt files for each transaction
foreach ($transactions as $key => $transaction) {
    $fileStmt = $conn->prepare("SELECT file_path FROM transaction_files WHERE transaction_id = ?");
    $fileStmt->execute([$transaction['id']]);
    $receipt_files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
    $transactions[$key]['receipt_files'] = $receipt_files;
}

// Calculate balance directly from non-deleted transactions
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credit_amount,
        SUM(CASE WHEN type = 'collection' THEN amount ELSE 0 END) as collection_amount,
        SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as payment_amount,
        SUM(CASE WHEN type = 'advance' THEN amount ELSE 0 END) as advance_amount,
        SUM(CASE WHEN type = 'advance_refund' THEN amount ELSE 0 END) as advance_refund
    FROM transactions 
    WHERE customer_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)
");
$stmt->execute([$customer_id]);
$calculated_balance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current owed_amount and advance_payment from database
$stmt = $conn->prepare("SELECT owed_amount, advance_payment FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$current_balance = $stmt->fetch(PDO::FETCH_ASSOC);

$has_credit_transaction = floatval($calculated_balance['credit_amount']) > 0;

if ($has_credit_transaction) {
    // If there are credit transactions, calculate based on transactions only
    $owed_amount = floatval($calculated_balance['credit_amount']) - (floatval($calculated_balance['collection_amount']) + floatval($calculated_balance['payment_amount']));
    $advance_payment = floatval($calculated_balance['advance_amount']) - floatval($calculated_balance['advance_refund']);
} else {
    // If no credit transactions, use the initial owed_amount from the database
    $owed_amount = floatval($current_balance['owed_amount']);
    $advance_payment = floatval($current_balance['advance_payment']);
}

if ($owed_amount < 0) $owed_amount = 0;
if ($advance_payment < 0) $advance_payment = 0;

$balance = $owed_amount - $advance_payment;

// Show debug information - hidden in HTML comment
echo "<!-- \n";
echo "Database values: \n";
echo "owed_amount: " . $current_balance['owed_amount'] . "\n";
echo "advance_payment: " . $current_balance['advance_payment'] . "\n\n";

echo "Calculated values: \n";
echo "credit_amount: " . $calculated_balance['credit_amount'] . "\n";
echo "collection_amount: " . $calculated_balance['collection_amount'] . "\n";
echo "payment_amount: " . $calculated_balance['payment_amount'] . "\n";
echo "advance_amount: " . $calculated_balance['advance_amount'] . "\n";
echo "advance_refund: " . $calculated_balance['advance_refund'] . "\n";
echo "-->\n";

// More debug info
echo "<!-- \n";
echo "Final calculated values: \n";
echo "owed_amount: " . $owed_amount . "\n";
echo "advance_payment: " . $advance_payment . "\n";
echo "balance: " . $balance . "\n";
echo "-->\n";



header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی کڕیار - <?php echo $customer['name']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/receipt.css">
</head>
<body>
    <div class="watermark">کڕیار</div>
    <div class="receipt-container">
        <div class="business-header">
            <div class="business-title">کۆگای احمد و ئەشکان</div>
            <div class="business-contacts">ژمارە مۆبایل: 07712255656 - 07501478786</div>
            <div class="business-address">کۆگاکانی غرفة التجارة-کۆگای 288</div>
        </div>
        
        <div class="customer-info mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>ناوی کڕیار:</strong> <?php echo htmlspecialchars($customer['name']); ?>
                </div>
                <?php if (!empty($customer_phones)): ?>
                <div>
                    <strong>ژمارەی مۆبایل:</strong> <?php echo htmlspecialchars($customer_phones); ?>
                </div>
                <?php endif; ?>
            </div>
            <!-- Date Filter Form -->
            <form method="get" class="row g-2 align-items-end mt-3 mb-2 no-print">
                <input type="hidden" name="id" value="<?php echo $customer_id; ?>">
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
        </div>
        
        <div class="transaction-details">
            <h5 class="mb-3">لیستی هەموو مامەڵەکان</h5>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>بەروار</th>
                        <th>جۆر</th>
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
                                else echo 'نەقد';
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
                    <td colspan="3" class="text-end"><strong>باڵانسی کۆتایی</strong></td>
                    <td colspan="3">
                        <strong>
                            <?php if ($balance > 0): ?>
                                <?php echo number_format($balance); ?> د.ع
                                <span class="text-danger">(قەرزارە)</span>
                            <?php elseif ($balance < 0): ?>
                                <?php echo number_format(abs($balance)); ?> د.ع
                                <span class="text-success">(پارەی پێشەکی)</span>
                            <?php else: ?>
                                0 د.ع
                            <?php endif; ?>
                        </strong>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="mt-4 text-end">
                <strong>
                    باڵانسی کۆتایی: 
                    <?php if ($balance > 0): ?>
                        <?php echo number_format($balance); ?> د.ع
                        <span class="text-danger">(قەرزارە)</span>
                    <?php elseif ($balance < 0): ?>
                        <?php echo number_format(abs($balance)); ?> د.ع
                        <span class="text-success">(پارەی پێشەکی)</span>
                    <?php else: ?>
                        0 د.ع
                    <?php endif; ?>
                </strong>
            </div>
        </div>
        <div class="button-container">
            <button class="action-button" onclick="window.print()">
                <i class="bi bi-printer"></i> چاپکردن
            </button>
        </div>
    </div>
</body>
</html> 