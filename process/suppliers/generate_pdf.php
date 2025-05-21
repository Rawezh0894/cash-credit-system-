<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if supplier ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Supplier ID is required.');
}

$supplier_id = intval($_GET['id']);

// Fetch supplier info (only for title)
$conn = Database::getInstance();
$stmt = $conn->prepare("SELECT name, we_owe, advance_payment, phone1, phone2 FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$supplier) die('Supplier not found.');

// Get phones
$phones = [];
if (!empty($supplier['phone1'])) $phones[] = $supplier['phone1'];
if (!empty($supplier['phone2'])) $phones[] = $supplier['phone2'];
$supplier_phones = implode(' - ', $phones);

// Handle date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch all transactions for this supplier, with date filter if set
if ($start_date && $end_date) {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE supplier_id = ? AND date BETWEEN ? AND ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY date ASC, id ASC");
    $stmt->execute([$supplier_id, $start_date, $end_date]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE supplier_id = ? AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY date ASC, id ASC");
    $stmt->execute([$supplier_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get receipt files for each transaction
foreach ($transactions as $key => $transaction) {
    $fileStmt = $conn->prepare("SELECT file_path FROM transaction_files WHERE transaction_id = ?");
    $fileStmt->execute([$transaction['id']]);
    $receipt_files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
    $transactions[$key]['receipt_files'] = $receipt_files;
}

// Calculate final balance
// First get the initial balances
$initial_we_owe = floatval($supplier['we_owe']);
$initial_advance_payment = floatval($supplier['advance_payment']);

// Calculate balance directly from non-deleted transactions
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE 
            WHEN type = 'credit' THEN amount 
            ELSE 0 
        END) as debit,
        SUM(CASE 
            WHEN type = 'payment' THEN amount
            WHEN type = 'advance' THEN amount
            ELSE 0 
        END) as credit,
        SUM(CASE
            WHEN type = 'advance_collection' THEN amount
            ELSE 0
        END) as advance_collection
    FROM transactions 
    WHERE supplier_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)
");
$stmt->execute([$supplier_id]);
$calculated_balance = $stmt->fetch(PDO::FETCH_ASSOC);

// We need to get the actual current balances from database, as the balance is calculated
// and adjusted during transaction processing (especially for credit vs advance interaction)
$stmt = $conn->prepare("SELECT we_owe, advance_payment FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$current_balance = $stmt->fetch(PDO::FETCH_ASSOC);

// Use the actual current values from the database
$we_owe = floatval($current_balance['we_owe']);
$advance_payment = floatval($current_balance['advance_payment']);
$balance = $we_owe - $advance_payment;
if ($balance > 0) {
    $balance_text = number_format($balance) . ' د.ع (قەرز)';
} elseif ($balance < 0) {
    $balance_text = number_format(abs($balance)) . ' د.ع (پێشەکی زیادە)';
} else {
    $balance_text = '0 د.ع (هیچ)';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی دابینکەر - <?php echo $supplier['name']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/receipt.css">
</head>
<body>
    <div class="watermark">دابینکەر</div>
    <div class="receipt-container">
        <div class="business-header">
            <div class="business-title">کۆگای احمد و ئەشکان</div>
            <div class="business-contacts">ژمارە مۆبایل: 07712255656 - 07501478786</div>
            <div class="business-address">کۆگاکانی غرفة التجارة-کۆگای 288</div>
        </div>
        
        <div class="supplier-info mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>ناوی دابینکەر:</strong> <?php echo htmlspecialchars($supplier['name']); ?>
                </div>
                <?php if (!empty($supplier_phones)): ?>
                <div>
                    <strong>ژمارەی مۆبایل:</strong> <?php echo htmlspecialchars($supplier_phones); ?>
                </div>
                <?php endif; ?>
            </div>
            <!-- Date Filter Form -->
            <form method="get" class="row g-2 align-items-end mt-3 mb-2 no-print">
                <input type="hidden" name="id" value="<?php echo $supplier_id; ?>">
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
                                elseif ($t['type'] === 'advance_collection') echo 'پێشەکی وەرگرتنەوە';
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
                            <?php echo $balance_text; ?>
                        </strong>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="mt-4 text-end">
                <strong>
                    <?php echo $balance_text; ?>
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