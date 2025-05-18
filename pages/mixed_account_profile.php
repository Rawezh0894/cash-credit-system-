<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Handle success and error messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Check if account ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ناسنامەی حساب نادروستە';
    header('Location: mixed_accounts.php');
    exit();
}

$account_id = intval($_GET['id']);
$conn = Database::getInstance();

// Get account details
try {
    $stmt = $conn->prepare("SELECT * FROM mixed_accounts WHERE id = ?");
    $stmt->execute([$account_id]);
    $account = $stmt->fetch();

    if (!$account) {
        $_SESSION['error_message'] = 'حسابەکە نەدۆزرایەوە';
        header('Location: mixed_accounts.php');
        exit();
    }

    // Get account transactions
    $stmt = $conn->prepare("
        SELECT t.*, u.full_name as creator_name 
        FROM transactions t 
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.mixed_account_id = ? 
        ORDER BY t.date DESC, t.created_at DESC
    ");
    $stmt->execute([$account_id]);
    $transactions = $stmt->fetchAll();

    // Calculate statistics
    $total_credit_sale = 0;
    $total_credit_purchase = 0;
    $total_cash_sale = 0;
    $total_cash_purchase = 0;
    $total_advance_sale = 0;
    $total_advance_purchase = 0;
    $total_payment_sale = 0;
    $total_payment_purchase = 0;

    foreach ($transactions as $transaction) {
        if ($transaction['type'] === 'credit') {
            if ($transaction['direction'] === 'sale') {
                $total_credit_sale += floatval($transaction['amount']);
            } else {
                $total_credit_purchase += floatval($transaction['amount']);
            }
        } elseif ($transaction['type'] === 'cash') {
            if ($transaction['direction'] === 'sale') {
                $total_cash_sale += floatval($transaction['amount']);
            } else {
                $total_cash_purchase += floatval($transaction['amount']);
            }
        } elseif ($transaction['type'] === 'advance') {
            if ($transaction['direction'] === 'sale') {
                $total_advance_sale += floatval($transaction['amount']);
            } else {
                $total_advance_purchase += floatval($transaction['amount']);
            }
        } elseif ($transaction['type'] === 'payment') {
            if ($transaction['direction'] === 'sale') {
                $total_payment_sale += floatval($transaction['amount']);
            } else {
                $total_payment_purchase += floatval($transaction['amount']);
            }
        }
    }

    // Get transaction files/receipts
    $transaction_ids = array_column($transactions, 'id');
    $transaction_files = [];
    
    if (!empty($transaction_ids)) {
        $placeholders = str_repeat('?,', count($transaction_ids) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT * FROM transaction_files 
            WHERE transaction_id IN ($placeholders)
        ");
        $stmt->execute($transaction_ids);
        
        $all_files = $stmt->fetchAll();
        foreach ($all_files as $file) {
            $transaction_files[$file['transaction_id']][] = $file;
        }
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'هەڵە: ' . $e->getMessage();
    header('Location: mixed_accounts.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی حساب - <?php echo htmlspecialchars($account['name']); ?></title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tables.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="bg-body-tertiary">
    <div class="container-fluid py-4">
        <?php include '../includes/navbar.php'; ?>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row d-flex justify-content-between align-items-center">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h2>
                        <span class="icon-circle icon-mixed">
                            <i class="bi bi-person"></i>
                        </span>
                        پڕۆفایلی حساب: <?php echo htmlspecialchars($account['name']); ?>
                    </h2>
                    <a href="mixed_accounts.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right-short"></i> گەڕانەوە بۆ لیستی حسابەکان
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Financial Summary Section -->
        <div class="profile-summary-section">
            <div class="row">
                <div class="col-12">
                    <div class="card profile-card mixed-summary-card">
                        <div class="card-header" style="background-color: #454e6c; color: white;">
                            <h5 class="mb-0">
                                <i class="bi bi-cash-stack"></i> کورتەی دارایی
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="summary-card they-owe-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-credit-card"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">ئەوان قەرزارن</span>
                                            <span class="summary-value"><?php echo number_format($account['they_owe'], 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="summary-card we-owe-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-credit-card-2-back"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">ئێمە قەرزارین</span>
                                            <span class="summary-value"><?php echo number_format($account['we_owe'], 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="summary-card they-advance-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-arrow-up-circle"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">پێشەکی ئەوان</span>
                                            <span class="summary-value"><?php echo number_format($account['they_advance'], 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="summary-card we-advance-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-arrow-down-circle"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">پێشەکی ئێمە</span>
                                            <span class="summary-value"><?php echo number_format($account['we_advance'], 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Information Section -->
        <div class="profile-info-section">
            <div class="row">
                <div class="col-12">
                    <div class="card profile-card mixed-card">
                        <div class="card-header text-center pt-4 pb-2" style="background-color: #454e6c; color: white;">
                            <div class="mb-3">
                                <span class="icon-circle icon-mixed" style="width:64px;height:64px;background-color:white;"><i class="bi bi-person" style="font-size:2.5rem;"></i></span>
                            </div>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($account['name']); ?></h4>
                        </div>
                        <div class="card-body pt-0">
                            <ul class="list-group list-group-flush profile-info-list">
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی یەکەم:</span> <span class="profile-info-value"><?php echo htmlspecialchars($account['phone1']); ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی دووەم:</span> <span class="profile-info-value"><?php echo !empty($account['phone2']) ? htmlspecialchars($account['phone2']) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">ناوی کەفیل:</span> <span class="profile-info-value"><?php echo !empty($account['guarantor_name']) ? htmlspecialchars($account['guarantor_name']) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی کەفیل:</span> <span class="profile-info-value"><?php echo !empty($account['guarantor_phone']) ? htmlspecialchars($account['guarantor_phone']) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">شار:</span> <span class="profile-info-value"><?php echo htmlspecialchars($account['city']); ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">شوێن:</span> <span class="profile-info-value"><?php echo $account['location'] === 'inside' ? 'ناو شار' : 'دەرەوەی شار'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">تێبینی:</span> <span class="profile-info-value"><?php echo !empty($account['notes']) ? nl2br(htmlspecialchars($account['notes'])) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">بەرواری زیادکردن:</span> <span class="profile-info-value"><?php echo date('Y-m-d', strtotime($account['created_at'])); ?></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Mixed Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAccountModalLabel">دەستکاری زانیاری حساب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAccountForm" action="../includes/functions/mixed_account_functions.php" method="post">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="account_id" value="<?php echo $account_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">ناو</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($account['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone1" class="form-label">ژمارەی مۆبایل</label>
                                <input type="text" class="form-control" id="phone1" name="phone1" value="<?php echo htmlspecialchars($account['phone1']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                                <input type="text" class="form-control" id="phone2" name="phone2" value="<?php echo htmlspecialchars($account['phone2'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guarantor_name" class="form-label">ناوی کەفیل (ئیختیاری)</label>
                                <input type="text" class="form-control" id="guarantor_name" name="guarantor_name" value="<?php echo htmlspecialchars($account['guarantor_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="guarantor_phone" class="form-label">ژمارەی مۆبایلی کەفیل (ئیختیاری)</label>
                                <input type="text" class="form-control" id="guarantor_phone" name="guarantor_phone" value="<?php echo htmlspecialchars($account['guarantor_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">شار</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($account['city']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">شوێن</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location" id="location_inside" value="inside" <?php echo $account['location'] === 'inside' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="location_inside">ناو شار</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location" id="location_outside" value="outside" <?php echo $account['location'] === 'outside' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="location_outside">دەرەوەی شار</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="notes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($account['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" form="editAccountForm" class="btn btn-primary">نوێکردنەوە</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/swalAlert2/swalAlert2.js"></script>
    
    <?php if ($success_message): ?>
    <script>
        showSwalAlert2('success', 'سەرکەوتوو!', '<?php echo $success_message; ?>');
    </script>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <script>
        showSwalAlert2('error', 'هەڵە!', '<?php echo $error_message; ?>');
    </script>
    <?php endif; ?>
    
    <script>
        // Delete transaction confirmation
        document.querySelectorAll('.delete-transaction').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                
                Swal.fire({
                    title: 'دڵنیای؟',
                    text: 'ئەم مامەڵەیە بە تەواوی دەسڕدرێتەوە!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'بەڵێ، بیسڕەوە!',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../includes/functions/transaction_functions.php?action=delete&id=' + id + '&redirect=mixed_account_profile.php?id=<?php echo $account_id; ?>';
                    }
                });
            });
        });
    </script>
</body>
</html> 