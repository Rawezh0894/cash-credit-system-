<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions/permissions.php';
require_once '../process/transactions/permanent_delete_transaction.php';
require_once '../process/transactions/restore_transaction.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check permission to view deleted transactions
requirePermission('view_deleted_transactions');

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

// Process AJAX requests for restore/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Set JSON header first to prevent any HTML output
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'restore') {
        try {
            // Check permission to restore transactions
            if (!hasPermission('restore_transaction')) {
                throw new Exception("ڕێگەپێدانی ناتەواو. تۆ ناتوانیت مامەڵە گەڕێنیتەوە.");
            }

            if (!isset($_POST['transaction_id'])) {
                throw new Exception("نەخشەی مامەڵەکە دیاری نەکراوە");
            }
            
            $transaction_id = $_POST['transaction_id'];
            
            // Disable error reporting to prevent HTML output
            error_reporting(0);
            
            // Attempt to restore the transaction
            restoreTransaction($transaction_id);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => "مامەڵەکە بە سەرکەوتوویی گەڕێنرایەوە"
            ]);
            exit();
            
        } catch (Exception $e) {
            // Return error response
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => "هەڵەیەک ڕوویدا: " . $e->getMessage()
            ]);
            exit();
        }
    }
    else if ($_POST['action'] === 'delete') {
        try {
            if (!isset($_POST['transaction_id'])) {
                throw new Exception("نەخشەی مامەڵەکە دیاری نەکراوە");
            }
            
            $transaction_id = $_POST['transaction_id'];
            
            // Check permission to delete transactions
            if (!hasPermission('delete_transactions')) {
                throw new Exception("ڕێگەپێدانی ناتەواو. تۆ ناتوانیت مامەڵە بسڕیتەوە بەتەواوی.");
            }
            
            // Use the permanent delete function
            error_log("Attempting to permanently delete transaction ID: " . $transaction_id);
            permanentDeleteTransaction($transaction_id);
            error_log("Transaction ID: " . $transaction_id . " permanently deleted successfully");
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => "مامەڵەکە بە سەرکەوتوویی سڕایەوە بەتەواوی"
            ]);
            exit();
            
        } catch (Exception $e) {
            // Return error response
            error_log("Exception during permanent deletion: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => "هەڵەیەک ڕوویدا: " . $e->getMessage()
            ]);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مامەڵە سڕاوەکان - سیستەمی پارە و کریت</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tables.css">
    <link rel="stylesheet" href="../assets/css/transactions.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        .deleted-date {
            color: var(--bs-danger);
            font-style: italic;
        }
    </style>
</head>
<body class="bg-body-tertiary">

<div class="container-fluid py-4">
    <?php include '../includes/navbar.php'; ?>
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">
                <span class="icon-circle icon-transactions" style="background-color: var(--bs-danger-bg-subtle);">
                    <i class="bi bi-trash text-danger"></i>
                </span>
                مامەڵە سڕاوەکان
            </h2>

            <!-- Transaction List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">لیستی مامەڵە سڕاوەکان</h5>
                </div>
                <div class="card-body">
                    <!-- Filters Section -->
                    <div class="filters-section mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="filter_type" class="form-label">جۆری مامەڵە</label>
                                <select class="form-select" id="filter_type">
                                    <option value="">هەموو</option>
                                    <option value="cash">نەقد</option>
                                    <option value="credit">قەرز</option>
                                    <option value="advance">پێشەکی</option>
                                    <option value="payment">قەرز دانەوە</option>
                                    <option value="collection">قەرز وەرگرتنەوە</option>
                                    <option value="advance_refund">گەڕاندنەوەی پێشەکی</option>
                                    <option value="advance_collection">پێشەکی وەرگرتنەوە</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_account_type" class="form-label">جۆری هەژمار</label>
                                <select class="form-select" id="filter_account_type">
                                    <option value="">هەموو</option>
                                    <option value="customer">کڕیار</option>
                                    <option value="supplier">دابینکەر</option>
                                    <option value="mixed">هەژماری تێکەڵ</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_date_from" class="form-label">لە بەرواری</label>
                                <input type="date" class="form-control" id="filter_date_from">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_date_to" class="form-label">بۆ بەرواری</label>
                                <input type="date" class="form-control" id="filter_date_to">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-secondary" id="reset_filters">
                                    <i class="bi bi-x-circle"></i> پاککردنەوەی فلتەر
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-controls">
                        <div class="records-per-page">
                            <select id="per_page" class="form-select">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th class="border">#</th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">جۆری مامەڵە</div>
                                            <div class="column-search">
                                                <input type="text" id="search_type" class="form-control search-field" placeholder="گەڕان...">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">بڕی پارە</div>
                                            <div class="column-search">
                                                <input type="text" id="search_amount" class="form-control search-field" placeholder="گەڕان...">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">بەروار</div>
                                            <div class="column-search">
                                                <input type="text" id="search_date" class="form-control search-field" placeholder="گەڕان...">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">کەس/کۆمپانیا</div>
                                            <div class="column-search">
                                                <input type="text" id="search_account" class="form-control search-field" placeholder="گەڕان...">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">جۆری هەژمار</div>
                                            <div class="column-search">
                                                <input type="text" id="search_account_type" class="form-control search-field" placeholder="گەڕان...">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">تێبینی</div>
                                            <div class="column-search">
                                                <input type="text" id="search_notes" class="form-control search-field" placeholder="گەڕان...">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">بەرواری سڕینەوە</div>
                                            <div class="column-search">
                                                <input type="text" id="search_deleted_at" class="form-control search-field" placeholder="گەڕان...">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody id="transactions_table">
                                <!-- Data will be loaded here dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination" class="mt-3">
                        <!-- Pagination will be added here by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Receipt View Modal -->
            <div class="modal fade" id="receiptViewModal" tabindex="-1" aria-labelledby="receiptViewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="receiptViewModalLabel">وێنەی پسووڵە (سڕاوە)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="receipt-carousel" class="carousel slide">
                                <div class="carousel-inner">
                                    <!-- Images will be loaded here -->
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#receipt-carousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">پێشوو</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#receipt-carousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">دواتر</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Pass PHP messages to JavaScript
    <?php if (!empty($success_message)): ?>
        var successMessage = <?php echo json_encode($success_message); ?>;
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        var errorMessage = <?php echo json_encode($error_message); ?>;
    <?php endif; ?>
</script>
<script src="../assets/js/transactions/deleted_transactions.js"></script>
<script src="../assets/js/navbar/navbar.js"></script>
</body>
</html> 