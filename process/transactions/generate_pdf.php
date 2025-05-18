<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if transaction ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Transaction ID is required.');
}

$transaction_id = intval($_GET['id']);

// Fetch transaction details
$conn = Database::getInstance();
$stmt = $conn->prepare("
    SELECT 
        t.*, 
        c.name AS customer_name, c.phone1 AS customer_phone,
        s.name AS supplier_name, s.phone1 AS supplier_phone,
        m.name AS mixed_account_name, m.phone1 AS mixed_account_phone
    FROM transactions t
    LEFT JOIN customers c ON t.customer_id = c.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    LEFT JOIN mixed_accounts m ON t.mixed_account_id = m.id
    WHERE t.id = ?
");
$stmt->execute([$transaction_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    die('Transaction not found.');
}

// Check if transaction is deleted
$is_deleted = isset($transaction['is_deleted']) && $transaction['is_deleted'] == 1;
$deleted_date = isset($transaction['deleted_at']) ? $transaction['deleted_at'] : null;

// Get receipt files
$fileStmt = $conn->prepare("
    SELECT file_path 
    FROM transaction_files 
    WHERE transaction_id = ?
");
$fileStmt->execute([$transaction_id]);
$receipt_files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
$transaction['receipt_files'] = $receipt_files;

// Format transaction details
$account_name = '';
$account_phone = '';
$account_type = '';
$direction_text = '';

if ($transaction['customer_id']) {
    $account_name = $transaction['customer_name'];
    $account_phone = $transaction['customer_phone'];
    $account_type = 'کڕیار';
} elseif ($transaction['supplier_id']) {
    $account_name = $transaction['supplier_name'];
    $account_phone = $transaction['supplier_phone'];
    $account_type = 'دابینکەر';
} elseif ($transaction['mixed_account_id']) {
    $account_name = $transaction['mixed_account_name'];
    $account_phone = $transaction['mixed_account_phone'];
    $account_type = 'هەژماری تێکەڵ';
    if ($transaction['direction'] === 'sale') {
        $direction_text = 'فرۆشتن';
    } elseif ($transaction['direction'] === 'purchase') {
        $direction_text = 'کڕین';
    }
}

// Format transaction type
$type_text = '';
switch ($transaction['type']) {
    case 'cash':
        $type_text = 'نەقد';
        break;
    case 'credit':
        $type_text = 'قەرز';
        break;
    case 'advance':
        $type_text = 'پێشەکی';
        break;
    case 'payment':
        $type_text = 'قەرز دانەوە';
        break;
    case 'collection':
        $type_text = 'قەرز وەرگرتنەوە';
        break;
    case 'advance_refund':
        $type_text = 'گەڕاندنەوەی پێشەکی';
        break;
    case 'advance_collection':
        $type_text = 'پێشەکی وەرگرتنەوە';
        break;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی مامەڵە</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/receipt.css">
    <style>
        .deleted-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(255, 0, 0, 0.2);
            font-weight: bold;
            z-index: 1000;
            pointer-events: none;
        }
        .deleted-banner {
            background-color: #ff6b6b;
            color: white;
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="watermark">پسووڵە</div>
    <?php if ($is_deleted): ?>
    <div class="deleted-watermark">سڕاوەتەوە</div>
    <?php endif; ?>
    <div class="receipt-container">
        <div class="business-header">
            <div class="business-title">کۆگای احمد و ئەشکان</div>
            <div class="business-contacts">ژمارە مۆبایل: 07712255656 - 07501478786</div>
            <div class="business-address">کۆگاکانی غرفة التجارة-کۆگای 288</div>
        </div>
        
        <?php if ($is_deleted): ?>
        <div class="deleted-banner">
            ئەم مامەڵەیە سڕاوەتەوە لە <?php echo $deleted_date ? date('Y-m-d H:i', strtotime($deleted_date)) : 'بەرواری نادیار'; ?>
        </div>
        <?php endif; ?>
        
        <div class="account-info mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>ناوی <?php echo $account_type; ?>:</strong> <?php echo htmlspecialchars($account_name); ?>
                </div>
                <?php if (!empty($account_phone)): ?>
                <div>
                    <strong>ژمارەی مۆبایل:</strong> <?php echo htmlspecialchars($account_phone); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($account_type === 'هەژماری تێکەڵ' && !empty($direction_text)): ?>
            <div class="mt-2">
                <strong>ئاراستە:</strong> <?php echo htmlspecialchars($direction_text); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="transaction-details">
            <h5 class="mb-3">زانیاری مامەڵە</h5>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>بەروار</th>
                        <th>جۆر</th>
                        <?php if ($account_type === 'هەژماری تێکەڵ'): ?><th>ئاراستە</th><?php endif; ?>
                        <th>بڕ</th>
                        <th>کەس/کۆمپانیا</th>
                        <th>ژمارەی تەلەفۆن</th>
                        <th>جۆری هەژمار</th>
                        <th>تێبینی</th>
                        <th>وێنەکە</th>
                        <?php if ($is_deleted): ?><th>دۆخ</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr <?php if ($is_deleted): ?>style="opacity: 0.7;"<?php endif; ?>>
                        <td>1</td>
                        <td><?php echo $transaction['date']; ?></td>
                        <td><?php echo $type_text; ?><?php if ($transaction['type'] === 'credit' && !empty($transaction['due_date'])): ?><br><small class="text-muted">بەرواری گەڕاندنەوە: <?php echo $transaction['due_date']; ?></small><?php endif; ?></td>
                        <?php if ($account_type === 'هەژماری تێکەڵ'): ?><td><?php echo $direction_text ?: '-'; ?></td><?php endif; ?>
                        <td><?php echo number_format($transaction['amount']); ?> د.ع</td>
                        <td><?php echo $account_name; ?></td>
                        <td><?php echo $account_phone; ?></td>
                        <td><?php echo $account_type; ?></td>
                        <td><?php echo $transaction['notes'] ? $transaction['notes'] : '-'; ?></td>
                        <td>
                            <?php if (!empty($transaction['receipt_files'])): ?>
                                <?php foreach ($transaction['receipt_files'] as $index => $file_path): ?>
                                    <a href="../../<?php echo $file_path; ?>" target="_blank" class="image-link">
                                        <i class="bi bi-image image-icon"></i>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                نییە
                            <?php endif; ?>
                        </td>
                        <?php if ($is_deleted): ?>
                        <td><span class="badge bg-danger">سڕاوەتەوە</span></td>
                        <?php endif; ?>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="button-container">
            <button class="action-button" onclick="window.print()">
                <i class="bi bi-printer"></i> چاپکردن
            </button>
            <?php if ($is_deleted): ?>
            <div class="alert alert-warning mt-3">
                <i class="bi bi-exclamation-triangle"></i> 
                ئاگاداری: ئەم مامەڵەیە سڕاوەتەوە و لە سیستەمدا کاریگەری نییە.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 