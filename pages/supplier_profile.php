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

// Check if supplier ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ناسنامەی دابینکەر نادروستە';
    header('Location: suppliers.php');
    exit();
}

$supplier_id = intval($_GET['id']);
$conn = Database::getInstance();

// Get supplier details
try {
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();

    if (!$supplier) {
        $_SESSION['error_message'] = 'دابینکەرەکە نەدۆزرایەوە';
        header('Location: suppliers.php');
        exit();
    }

    // Get supplier transactions
    $stmt = $conn->prepare("
        SELECT t.*, u.full_name as creator_name 
        FROM transactions t 
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.supplier_id = ? 
        ORDER BY t.date DESC, t.created_at DESC
    ");
    $stmt->execute([$supplier_id]);
    $transactions = $stmt->fetchAll();

    // Calculate real-time statistics (FIFO)
    $we_owe = 0;
    $advance_payment = 0;
    $total_payment = 0;
    foreach ($transactions as $transaction) {
        $paid = isset($transaction['paid_amount']) ? floatval($transaction['paid_amount']) : 0;
        $remaining = floatval($transaction['amount']) - $paid;
        if ($transaction['type'] === 'credit' && $remaining > 0) {
            $we_owe += $remaining;
        } elseif ($transaction['type'] === 'advance' && $remaining > 0) {
            $advance_payment += $remaining;
        } elseif ($transaction['type'] === 'payment') {
            $total_payment += floatval($transaction['amount']);
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
    header('Location: suppliers.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی دابینکەر - <?php echo htmlspecialchars($supplier['name']); ?></title>
    
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
                        <span class="icon-circle icon-suppliers">
                            <i class="bi bi-person"></i>
                        </span>
                        پڕۆفایلی دابینکەر: <?php echo htmlspecialchars($supplier['name']); ?>
                    </h2>
                    <a href="suppliers.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right-short"></i> گەڕانەوە بۆ لیستی دابینکەرەکان
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Financial Summary Section -->
        <div class="profile-summary-section">
            <div class="row">
                <div class="col-12">
                    <div class="card profile-card supplier-summary-card">
                        <div class="card-header" style="background-color: #454e6c; color: white;">
                            <h5 class="mb-0">
                                <i class="bi bi-cash-stack"></i> کورتەی دارایی
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="summary-card owe-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-credit-card"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">ئێمە قەرزارین</span>
                                            <span class="summary-value"><?php echo number_format($we_owe, 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="summary-card advance-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-arrow-up-circle"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">پێشەکی ئێمە</span>
                                            <span class="summary-value"><?php echo number_format($advance_payment, 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="summary-card payment-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-cash"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">کۆی پارەدان</span>
                                            <span class="summary-value"><?php echo number_format($total_payment, 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Supplier Information Section -->
        <div class="profile-info-section">
            <div class="row">
                <div class="col-12">
                    <div class="card profile-card supplier-card">
                        <div class="card-header text-center pt-4 pb-2" style="background-color: #454e6c; color: white;">
                            <div class="mb-3">
                                <span class="icon-circle icon-suppliers" style="width:64px;height:64px;background-color:white;"><i class="bi bi-person" style="font-size:2.5rem;"></i></span>
                            </div>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($supplier['name']); ?></h4>
                        </div>
                        <div class="card-body pt-0">
                            <ul class="list-group list-group-flush profile-info-list">
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی یەکەم:</span> <span class="profile-info-value"><?php echo htmlspecialchars($supplier['phone1']); ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی دووەم:</span> <span class="profile-info-value"><?php echo !empty($supplier['phone2']) ? htmlspecialchars($supplier['phone2']) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">شار:</span> <span class="profile-info-value"><?php echo htmlspecialchars($supplier['city']); ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">شوێن:</span> <span class="profile-info-value"><?php echo $supplier['location'] === 'inside' ? 'ناو شار' : 'دەرەوەی شار'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">تێبینی:</span> <span class="profile-info-value"><?php echo !empty($supplier['notes']) ? nl2br(htmlspecialchars($supplier['notes'])) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">بەرواری زیادکردن:</span> <span class="profile-info-value"><?php echo date('Y-m-d', strtotime($supplier['created_at'])); ?></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSupplierModalLabel">دەستکاری زانیاری دابینکەر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSupplierForm" action="../includes/functions/supplier_functions.php" method="post">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">ناوی دابینکەر</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone1" class="form-label">ژمارەی مۆبایلی یەکەم</label>
                                <input type="text" class="form-control" id="phone1" name="phone1" value="<?php echo htmlspecialchars($supplier['phone1']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone2" class="form-label">ژمارەی مۆبایلی دووەم (ئیختیاری)</label>
                                <input type="text" class="form-control" id="phone2" name="phone2" value="<?php echo htmlspecialchars($supplier['phone2'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">ناوی شار</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($supplier['city']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">شوێن</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location" id="location_inside" value="inside" <?php echo $supplier['location'] === 'inside' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="location_inside">ناو شار</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location" id="location_outside" value="outside" <?php echo $supplier['location'] === 'outside' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="location_outside">دەرەوەی شار</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="notes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($supplier['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" form="editSupplierForm" class="btn btn-primary">نوێکردنەوە</button>
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
                        window.location.href = '../includes/functions/transaction_functions.php?action=delete&id=' + id + '&redirect=supplier_profile.php?id=<?php echo $supplier_id; ?>';
                    }
                });
            });
        });
    </script>
</body>
</html> 