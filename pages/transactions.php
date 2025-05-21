<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has permission to view transactions
requirePermission('view_transactions');

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
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مامەڵەکان - سیستەمی پارە و کریت</title>
    
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
    <!-- File Uploader CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="../assets/css/select2-custom.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">

<div class="container-fluid py-4">
    <?php include '../includes/navbar.php'; ?>
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">
                <span class="icon-circle icon-transactions">
                    <i class="bi bi-receipt"></i>
                </span>
            <!-- مامەڵەکان -->
            </h2>
            
            <!-- Transaction Add Modal -->
            <div class="modal fade" id="transactionAddModal" tabindex="-1" aria-labelledby="transactionAddModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="transactionAddModalLabel">زیادکردنی مامەڵە</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="transactionAddForm" action="javascript:void(0);">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="account_type" class="form-label">جۆری هەژمار</label>
                                        <select class="form-control" id="account_type" name="account_type" required>
                                            <option value="" selected disabled>هەڵبژاردنی جۆری هەژمار</option>
                                            <option value="customer">کڕیار</option>
                                            <option value="supplier">دابینکەر</option>
                                            <option value="mixed">هەژماری تێکەڵ</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 customer-select-container" style="display: none;">
                                        <label for="customer_id" class="form-label">کڕیار</label>
                                        <select class="form-control" id="customer_id" name="customer_id">
                                            <option value="" selected disabled>هەڵبژاردنی کڕیار</option>
                                            <?php
                                            $conn = Database::getInstance();
                                            $stmt = $conn->prepare("SELECT id, name, phone1 FROM customers ORDER BY name");
                                            $stmt->execute();
                                            $customers = $stmt->fetchAll();
                                            
                                            foreach ($customers as $customer) {
                                                echo "<option value='" . $customer['id'] . "'>" . $customer['name'] . " (" . $customer['phone1'] . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 supplier-select-container" style="display: none;">
                                        <label for="supplier_id" class="form-label">دابینکەر</label>
                                        <select class="form-control" id="supplier_id" name="supplier_id">
                                            <option value="" selected disabled>هەڵبژاردنی دابینکەر</option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT id, name, phone1 FROM suppliers ORDER BY name");
                                            $stmt->execute();
                                            $suppliers = $stmt->fetchAll();
                                            
                                            foreach ($suppliers as $supplier) {
                                                echo "<option value='" . $supplier['id'] . "'>" . $supplier['name'] . " (" . $supplier['phone1'] . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 mixed-select-container" style="display: none;">
                                        <label for="mixed_account_id" class="form-label">هەژماری تێکەڵ</label>
                                        <select class="form-control" id="mixed_account_id" name="mixed_account_id">
                                            <option value="" selected disabled>هەڵبژاردنی هەژماری تێکەڵ</option>
                                            <?php
                                            $stmt = $conn->prepare("SELECT id, name, phone1 FROM mixed_accounts ORDER BY name");
                                            $stmt->execute();
                                            $mixed_accounts = $stmt->fetchAll();
                                            
                                            foreach ($mixed_accounts as $account) {
                                                echo "<option value='" . $account['id'] . "'>" . $account['name'] . " (" . $account['phone1'] . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3 direction-select-container" style="display: none;">
                                        <label for="direction" class="form-label">ئاڕاستەی مامەڵە</label>
                                        <select class="form-control" id="direction" name="direction">
                                            <option value="" selected disabled>هەڵبژاردنی ئاڕاستەی مامەڵە</option>
                                            <option value="sale">فرۆشتن بۆیان</option>
                                            <option value="purchase">کڕین لێیان</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="type" class="form-label">جۆری مامەڵە</label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="" selected disabled>هەڵبژاردنی جۆری مامەڵە</option>
                                            <option value="cash">نەقد</option>
                                            <option value="credit">قەرز</option>
                                            <option value="advance">پێشەکی</option>
                                            <option value="payment">قەرز دانەوە</option>
                                            <option value="collection">قەرز وەرگرتنەوە</option>
                                            <option value="advance_refund">گەڕاندنەوەی پێشەکی</option>
                                            <option value="advance_collection">پێشەکی وەرگرتنەوە</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">بڕی پارە</label>
                                        <input type="number" class="form-control" id="amount" name="amount" value="0" step="0.01" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date" class="form-label">بەرواری مامەڵە</label>
                                        <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3 due-date-container" style="display: none;">
                                        <label for="due_date" class="form-label">بەرواری گەڕاندنەوەی قەرز</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="notes" class="form-label">تێبینی</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">وێنەی پسووڵە</label>
                                        <div id="receipt-dropzone" class="dropzone">
                                            <div class="dz-message">
                                                <i class="bi bi-cloud-upload"></i>
                                                <h3>وێنەکان بۆ ئەپلۆدکردن بکێشە ئێرە</h3>
                                                <p>یان کلیک لێرە بکە بۆ هەڵبژاردنی فایل</p>
                                            </div>
                                        </div>
                                        <div class="receipt-images"></div>
                                        <input type="hidden" id="receipt_files" name="receipt_files" value="">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                            <button type="button" id="saveTransactionAddBtn" class="btn btn-primary">زیادکردن</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Edit Modal -->
            <div class="modal fade" id="transactionEditModal" tabindex="-1" aria-labelledby="transactionEditModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="transactionEditModalLabel">دەستکاری مامەڵە</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="transactionEditForm" action="javascript:void(0);">
                                <input type="hidden" id="edit_transaction_id" name="transaction_id" value="">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_type" class="form-label">جۆری مامەڵە</label>
                                        <select class="form-control" id="edit_type" name="type" required>
                                            <option value="" disabled>هەڵبژاردنی جۆری مامەڵە</option>
                                            <option value="cash">نەقد</option>
                                            <option value="credit">قەرز</option>
                                            <option value="advance">پێشەکی</option>
                                            <option value="payment">قەرز دانەوە</option>
                                            <option value="collection">قەرز وەرگرتنەوە</option>
                                            <option value="advance_refund">گەڕاندنەوەی پێشەکی</option>
                                            <option value="advance_collection">پێشەکی وەرگرتنەوە</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_amount" class="form-label">بڕی پارە</label>
                                        <input type="number" class="form-control" id="edit_amount" name="amount" value="0" step="0.01" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_date" class="form-label">بەرواری مامەڵە</label>
                                        <input type="date" class="form-control" id="edit_date" name="date" required>
                                    </div>
                                    <div class="col-md-6 mb-3 edit-due-date-container" style="display: none;">
                                        <label for="edit_due_date" class="form-label">بەرواری گەڕاندنەوەی قەرز</label>
                                        <input type="date" class="form-control" id="edit_due_date" name="due_date">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_account_type" class="form-label">جۆری هەژمار</label>
                                        <select class="form-control" id="edit_account_type" name="account_type" required>
                                            <option value="" disabled>هەڵبژاردنی جۆری هەژمار</option>
                                            <option value="customer">کڕیار</option>
                                            <option value="supplier">دابینکەر</option>
                                            <option value="mixed">هەژماری تێکەڵ</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3 edit-customer-select-container" style="display: none;">
                                        <label for="edit_customer_id" class="form-label">کڕیار</label>
                                        <select class="form-control" id="edit_customer_id" name="customer_id">
                                            <option value="" disabled>هەڵبژاردنی کڕیار</option>
                                            <?php
                                            foreach ($customers as $customer) {
                                                echo "<option value='" . $customer['id'] . "'>" . $customer['name'] . " (" . $customer['phone1'] . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 edit-supplier-select-container" style="display: none;">
                                        <label for="edit_supplier_id" class="form-label">دابینکەر</label>
                                        <select class="form-control" id="edit_supplier_id" name="supplier_id">
                                            <option value="" disabled>هەڵبژاردنی دابینکەر</option>
                                            <?php
                                            foreach ($suppliers as $supplier) {
                                                echo "<option value='" . $supplier['id'] . "'>" . $supplier['name'] . " (" . $supplier['phone1'] . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 edit-mixed-select-container" style="display: none;">
                                        <label for="edit_mixed_account_id" class="form-label">هەژماری تێکەڵ</label>
                                        <select class="form-control" id="edit_mixed_account_id" name="mixed_account_id">
                                            <option value="" disabled>هەڵبژاردنی هەژماری تێکەڵ</option>
                                            <?php
                                            foreach ($mixed_accounts as $account) {
                                                echo "<option value='" . $account['id'] . "'>" . $account['name'] . " (" . $account['phone1'] . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 edit-direction-select-container" style="display: none;">
                                        <label for="edit_direction" class="form-label">ئاڕاستەی مامەڵە</label>
                                        <select class="form-control" id="edit_direction" name="direction">
                                            <option value="" disabled>هەڵبژاردنی ئاڕاستەی مامەڵە</option>
                                            <option value="sale">فرۆشتن بۆیان</option>
                                            <option value="purchase">کڕین لێیان</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="edit_notes" class="form-label">تێبینی</label>
                                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">وێنەی پسووڵە</label>
                                        <div id="edit-receipt-dropzone" class="dropzone">
                                            <div class="dz-message">
                                                <i class="bi bi-cloud-upload"></i>
                                                <h3>وێنەکان بۆ ئەپلۆدکردن بکێشە ئێرە</h3>
                                                <p>یان کلیک لێرە بکە بۆ هەڵبژاردنی فایل</p>
                                            </div>
                                        </div>
                                        <div class="edit-receipt-images"></div>
                                        <input type="hidden" id="edit_receipt_files" name="receipt_files" value="">
                                        <input type="hidden" id="existing_receipt_files" name="existing_receipt_files" value="">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                            <button type="button" id="saveTransactionEditBtn" class="btn btn-primary">نوێکردنەوە</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">لیستی مامەڵەکان</h5>
                    <?php if (hasPermission('add_transaction')): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionAddModal">
                        <i class="bi bi-plus-lg"></i> زیادکردنی مامەڵە
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <!-- SELECT2 Filters -->
                    <div class="select2-filters-wrapper">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="filter_account_name" class="form-label">فلتەر بە ناوی کەس/کۆمپانیا:</label>
                                <select id="filter_account_name" class="form-control select2-filter" data-column="4">
                                    <option value="">هەموو ناوەکان</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="filter_transaction_type" class="form-label">فلتەر بە جۆری مامەڵە:</label>
                                <select id="filter_transaction_type" class="form-control select2-filter" data-column="1">
                                    <option value="">هەموو جۆرەکان</option>
                                    <option value="نەقد">نەقد</option>
                                    <option value="قەرز">قەرز</option>
                                    <option value="پێشەکی">پێشەکی</option>
                                    <option value="قەرز دانەوە">قەرز دانەوە</option>
                                    <option value="قەرز وەرگرتنەوە">قەرز وەرگرتنەوە</option>
                                    <option value="گەڕاندنەوەی پێشەکی">گەڕاندنەوەی پێشەکی</option>
                                    <option value="پێشەکی وەرگرتنەوە">پێشەکی وەرگرتنەوە</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="filter_account_type_select2" class="form-label">فلتەر بە جۆری هەژمار:</label>
                                <select id="filter_account_type_select2" class="form-control select2-filter" data-column="5">
                                    <option value="">هەموو جۆرەکان</option>
                                    <option value="کڕیار">کڕیار</option>
                                    <option value="دابینکەر">دابینکەر</option>
                                    <option value="هەژماری تێکەڵ">هەژماری تێکەڵ</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="reset_filters" class="form-label d-block">&nbsp;</label>
                                <button type="button" class="btn btn-secondary w-100" id="reset_filters">
                                    <i class="bi bi-x-circle"></i> پاککردنەوەی فلتەر
                                </button>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-3 mb-2">
                                <label for="filter_date_from" class="form-label">لە بەرواری:</label>
                                <input type="date" class="form-control" id="filter_date_from">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="filter_date_to" class="form-label">بۆ بەرواری:</label>
                                <input type="date" class="form-control" id="filter_date_to">
                            </div>
                            <div class="col-md-6 mb-2" style="display:none;">
                                <!-- Hidden fields to maintain compatibility with original filters -->
                                <select class="form-control d-none" id="filter_type">
                                    <option value="" selected>هەموو</option>
                                    <option value="cash">نەقد</option>
                                    <option value="credit">قەرز</option>
                                    <option value="advance">پێشەکی</option>
                                    <option value="payment">قەرز دانەوە</option>
                                    <option value="collection">قەرز وەرگرتنەوە</option>
                                    <option value="advance_refund">گەڕاندنەوەی پێشەکی</option>
                                    <option value="advance_collection">پێشەکی وەرگرتنەوە</option>
                                </select>
                                <select class="form-control d-none" id="filter_account_type">
                                    <option value="" selected>هەموو</option>
                                    <option value="customer">کڕیار</option>
                                    <option value="supplier">دابینکەر</option>
                                    <option value="mixed">هەژماری تێکەڵ</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- End SELECT2 Filters -->
                    
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
                                            <div class="header-text">پسووڵە</div>
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
                    <div class="table-pagination">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center" id="pagination">
                                <!-- Pagination links will be added here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Receipt View Modal -->
            <div class="modal fade" id="receiptViewModal" tabindex="-1" aria-labelledby="receiptViewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="receiptViewModalLabel">وێنەی پسووڵە</h5>
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

<script src="../assets/js/navbar/navbar.js"></script>

<!-- Dropzone JS -->
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Select2 Custom JS -->
<script src="../assets/js/select2/select2.js"></script>
<!-- Permissions JS -->
<script src="../assets/js/permissions/permissions.js"></script>

<!-- SELECT2 Initialization -->
<script>
    $(document).ready(function() {
        // Set up filter configuration for the transactions page
        const filterConfig = {
            '#filter_account_name': 4,  // Account name column
            '#filter_transaction_type': 1, // Transaction type column
            '#filter_account_type_select2': 5 // Account type column
        };
        
        // Initialize the table observer to load filter values
        setupTableObserver(filterConfig);
    });
</script>

<script>
    // Pass PHP messages to JavaScript
    <?php if (!empty($success_message)): ?>
        var successMessage = <?php echo json_encode($success_message); ?>;
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        var errorMessage = <?php echo json_encode($error_message); ?>;
    <?php endif; ?>
</script>

<!-- Main JS -->
<script src="../assets/js/transactions/transactions.js"></script>
<script src="../assets/js/navbar/navbar.js"></script>
</body>
</html> 