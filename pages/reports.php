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

// Check permission to view reports
requirePermission('view_reports');

// Include the reports processing file
require_once '../process/reports/reports.php';
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتەکان - سیستەمی پارە و کریت</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/reports.css">

</head>
<body class="bg-body-tertiary">

<div class="container-fluid py-4">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4">
                <span class="icon-circle" style="background-color: var(--bs-primary)">
                    <i class="bi bi-bar-chart-fill"></i>
                </span>
                ڕاپۆرتەکان
            </h2>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <form id="report-filter-form" action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="report_range" class="form-label">جۆری ڕاپۆرت</label>
                        <select class="form-select" id="report_range" name="report_range">
                            <option value="daily">ڕۆژانە</option>
                            <option value="weekly">هەفتانە</option>
                            <option value="monthly">مانگانە</option>
                            <option value="yearly">ساڵانە</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">بەرواری دەستپێک</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">بەرواری کۆتایی</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                </form>
                <div class="col-md-12 d-flex justify-content-end mt-2">
                    <button type="button" id="reset-filters-btn" class="btn btn-outline-secondary">پاککردنەوەی فلتەرەکان</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="stat-content">
                    <h6>بڕی قەرزی ئەوان</h6>
                    <h3><?php echo number_format($total_they_owe, 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="stat-content">
                    <h6>بڕی قەرزی ئێمە</h6>
                    <h3><?php echo number_format($total_we_owe, 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-arrow-left-circle"></i>
                </div>
                <div class="stat-content">
                    <h6>بڕی پێشەکی ئەوان</h6>
                    <h3><?php echo number_format($total_they_advance, 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-arrow-right-circle"></i>
                </div>
                <div class="stat-content">
                    <h6>بڕی پێشەکی ئێمە</h6>
                    <h3><?php echo number_format($total_we_advance, 0); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="chart-container">
                <h5 class="text-center mb-3">مامەڵەکان بەپێی جۆر</h5>
                <canvas id="transactionTypesChart"></canvas>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="chart-container">
                <h5 class="text-center mb-3">شیکردنەوەی ژمارەی مامەڵەکان</h5>
                <canvas id="transactionCountsChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Daily Transactions Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-container" style="height: 400px;">
                <h5 class="text-center mb-3">مامەڵەکان بەپێی ڕۆژ</h5>
                <canvas id="dailyTransactionsChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Account Summaries -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <h5 class="text-center">پوختەی کڕیارەکان</h5>
                <table class="summary-table">
                    <tr>
                        <td>کۆی بڕی قەرز</td>
                        <td><?php echo number_format($account_balances['customer']['total_customer_owed'] ?? 0, 0); ?></td>
                    </tr>
                    <tr>
                        <td>کۆی بڕی پێشەکی</td>
                        <td><?php echo number_format($account_balances['customer']['total_customer_advance'] ?? 0, 0); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <h5 class="text-center">پوختەی دابینکەرەکان</h5>
                <table class="summary-table">
                    <tr>
                        <td>کۆی بڕی قەرزمان</td>
                        <td><?php echo number_format($account_balances['supplier']['total_supplier_owed'] ?? 0, 0); ?></td>
                    </tr>
                    <tr>
                        <td>کۆی بڕی پێشەکی</td>
                        <td><?php echo number_format($account_balances['supplier']['total_supplier_advance'] ?? 0, 0); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <h5 class="text-center">پوختەی هەژمارە تێکەڵەکان</h5>
                <table class="summary-table">
                    <tr>
                        <td>کۆی بڕی قەرزی ئەوان</td>
                        <td><?php echo number_format($account_balances['mixed']['total_mixed_they_owe'] ?? 0, 0); ?></td>
                    </tr>
                    <tr>
                        <td>کۆی بڕی قەرزی ئێمە</td>
                        <td><?php echo number_format($account_balances['mixed']['total_mixed_we_owe'] ?? 0, 0); ?></td>
                    </tr>
                    <tr>
                        <td>کۆی بڕی پێشەکی ئەوان</td>
                        <td><?php echo number_format($account_balances['mixed']['total_mixed_they_advance'] ?? 0, 0); ?></td>
                    </tr>
                    <tr>
                        <td>کۆی بڕی پێشەکی ئێمە</td>
                        <td><?php echo number_format($account_balances['mixed']['total_mixed_we_advance'] ?? 0, 0); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Credit Due Dates Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card report-card shadow">
                <div class="card-header primary-header d-flex justify-content-between align-items-center" style="color: white;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar2-week-fill me-2 fs-5"></i>
                        <span class="fs-5 fw-semibold">ڕاپۆرتی دانەوەی قەرز</span>
                    </div>
                    
                    <!-- Pagination controls -->
                    <div class="d-flex align-items-center">
                        <label for="per_page" class="text-white me-2 mb-0">ژمارەی ڕیزەکان:</label>
                        <select id="per_page" class="form-select form-select-sm" style="width: 70px;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs mb-4" id="debtTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue" type="button" role="tab" aria-controls="overdue" aria-selected="true">
                                <i class="bi bi-exclamation-triangle-fill me-1" style="color: var(--tertiary);"></i> قەرزەکانی دوا کەوتوون
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="false">
                                <i class="bi bi-calendar-check-fill me-1" style="color: var(--secondary);"></i> قەرزەکانی داهاتوو (7 ڕۆژی داهاتوو)
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content">
                        <!-- Overdue Debts Tab -->
                        <div class="tab-pane fade show active" id="overdue" role="tabpanel" aria-labelledby="overdue-tab">
                            <div class="row">
                                <!-- Overdue debts they owe us -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 debt-card">
                                        <div class="card-header overdue-header text-white d-flex align-items-center">
                                            <i class="bi bi-arrow-left-circle-fill me-2"></i>
                                            <span class="fw-semibold">قەرزی دواکەوتووی ئەوان (وەرگرتنەوە)</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <?php if (!empty($overdue_their_debts)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>کەس/کۆمپانیا</th>
                                                            <th>جۆری هەژمار</th>
                                                            <th>بەرواری دانەوە</th>
                                                            <th>بڕ</th>
                                                            <th>کردار</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="their-debts-overdue-body">
                                                        <?php foreach ($overdue_their_debts as $credit): ?>
                                                        <tr>
                                                            <td class="fw-medium"><?php echo $credit['account_name']; ?></td>
                                                            <td><span class="badge account-type-badge"><?php echo $credit['account_type']; ?></span></td>
                                                            <td><span class="text-danger fw-bold"><?php echo $credit['due_date']; ?></span></td>
                                                            <td class="fw-bold"><?php echo number_format($credit['amount']); ?> د.ع</td>
                                                            <td>
                                                                <a href="transactions.php?id=<?php echo $credit['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="بینین">
                                                                    <i class="bi bi-eye-fill"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div id="their-debts-overdue-pagination" class="p-3 border-top">
                                                <!-- Pagination will be rendered here by JS -->
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-light m-3">
                                                <i class="bi bi-info-circle me-2"></i>
                                                هیچ قەرزێکی دواکەوتووی ئەوان نییە.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Overdue debts we owe them -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 debt-card">
                                        <div class="card-header overdue-header text-white d-flex align-items-center">
                                            <i class="bi bi-arrow-right-circle-fill me-2"></i>
                                            <span class="fw-semibold">قەرزی دواکەوتووی ئێمە (دانەوە)</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <?php if (!empty($overdue_our_debts)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>کەس/کۆمپانیا</th>
                                                            <th>جۆری هەژمار</th>
                                                            <th>بەرواری دانەوە</th>
                                                            <th>بڕ</th>
                                                            <th>کردار</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="our-debts-overdue-body">
                                                        <?php foreach ($overdue_our_debts as $credit): ?>
                                                        <tr>
                                                            <td class="fw-medium"><?php echo $credit['account_name']; ?></td>
                                                            <td><span class="badge account-type-badge"><?php echo $credit['account_type']; ?></span></td>
                                                            <td><span class="text-danger fw-bold"><?php echo $credit['due_date']; ?></span></td>
                                                            <td class="fw-bold"><?php echo number_format($credit['amount']); ?> د.ع</td>
                                                            <td>
                                                                <a href="transactions.php?id=<?php echo $credit['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="بینین">
                                                                    <i class="bi bi-eye-fill"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div id="our-debts-overdue-pagination" class="p-3 border-top">
                                                <!-- Pagination will be rendered here by JS -->
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-light m-3">
                                                <i class="bi bi-info-circle me-2"></i>
                                                هیچ قەرزێکی دواکەوتووی ئێمە نییە.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upcoming Debts Tab -->
                        <div class="tab-pane fade" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                            <div class="row">
                                <!-- Upcoming debts they owe us -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 debt-card">
                                        <div class="card-header upcoming-header text-white d-flex align-items-center">
                                            <i class="bi bi-arrow-left-circle-fill me-2"></i>
                                            <span class="fw-semibold">قەرزی داهاتووی ئەوان (وەرگرتنەوە)</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <?php if (!empty($upcoming_their_debts)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>کەس/کۆمپانیا</th>
                                                            <th>جۆری هەژمار</th>
                                                            <th>بەرواری دانەوە</th>
                                                            <th>بڕ</th>
                                                            <th>کردار</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="their-debts-upcoming-body">
                                                        <?php foreach ($upcoming_their_debts as $credit): ?>
                                                        <tr>
                                                            <td class="fw-medium"><?php echo $credit['account_name']; ?></td>
                                                            <td><span class="badge account-type-badge"><?php echo $credit['account_type']; ?></span></td>
                                                            <td><span class="fw-medium"><?php echo $credit['due_date']; ?></span></td>
                                                            <td class="fw-bold"><?php echo number_format($credit['amount']); ?> د.ع</td>
                                                            <td>
                                                                <a href="transactions.php?id=<?php echo $credit['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="بینین">
                                                                    <i class="bi bi-eye-fill"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div id="their-debts-upcoming-pagination" class="p-3 border-top">
                                                <!-- Pagination will be rendered here by JS -->
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-light m-3">
                                                <i class="bi bi-info-circle me-2"></i>
                                                هیچ قەرزێکی نزیکی ئەوان نییە بۆ ماوەی 7 ڕۆژی داهاتوو.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Upcoming debts we owe them -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 debt-card">
                                        <div class="card-header upcoming-header text-white d-flex align-items-center">
                                            <i class="bi bi-arrow-right-circle-fill me-2"></i>
                                            <span class="fw-semibold">قەرزی داهاتووی ئێمە (دانەوە)</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <?php if (!empty($upcoming_our_debts)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>کەس/کۆمپانیا</th>
                                                            <th>جۆری هەژمار</th>
                                                            <th>بەرواری دانەوە</th>
                                                            <th>بڕ</th>
                                                            <th>کردار</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="our-debts-upcoming-body">
                                                        <?php foreach ($upcoming_our_debts as $credit): ?>
                                                        <tr>
                                                            <td class="fw-medium"><?php echo $credit['account_name']; ?></td>
                                                            <td><span class="badge account-type-badge"><?php echo $credit['account_type']; ?></span></td>
                                                            <td><span class="fw-medium"><?php echo $credit['due_date']; ?></span></td>
                                                            <td class="fw-bold"><?php echo number_format($credit['amount']); ?> د.ع</td>
                                                            <td>
                                                                <a href="transactions.php?id=<?php echo $credit['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="بینین">
                                                                    <i class="bi bi-eye-fill"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div id="our-debts-upcoming-pagination" class="p-3 border-top">
                                                <!-- Pagination will be rendered here by JS -->
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-light m-3">
                                                <i class="bi bi-info-circle me-2"></i>
                                                هیچ قەرزێکی نزیکی ئێمە نییە بۆ ماوەی 7 ڕۆژی داهاتوو.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
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
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Export PHP data to JS -->
<script>
    window.transaction_types = <?php echo json_encode($transaction_types); ?>;
    window.transaction_amounts = <?php echo json_encode($transaction_amounts); ?>;
    window.transaction_counts = <?php echo json_encode($transaction_counts); ?>;
    window.dates = <?php echo json_encode($dates); ?>;
    window.cash_amounts = <?php echo json_encode($cash_amounts); ?>;
    window.credit_amounts = <?php echo json_encode($credit_amounts); ?>;
    window.advance_amounts = <?php echo json_encode($advance_amounts); ?>;
    window.payment_amounts = <?php echo json_encode($payment_amounts); ?>;
    window.collection_amounts = <?php echo json_encode($collection_amounts); ?>;
</script>
<!-- Reports JS -->
<script src="../assets/js/reports/reports.js"></script>

<!-- Add pagination JavaScript for the credit due dates tables -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply pagination to all four tables
    initTablePagination('.their-debts-overdue-body', '#their-debts-overdue-pagination');
    initTablePagination('.our-debts-overdue-body', '#our-debts-overdue-pagination');
    initTablePagination('.their-debts-upcoming-body', '#their-debts-upcoming-pagination');
    initTablePagination('.our-debts-upcoming-body', '#our-debts-upcoming-pagination');
    
    // Listen for changes to the rows per page dropdown
    document.getElementById('per_page').addEventListener('change', function() {
        const rowsPerPage = parseInt(this.value);
        
        // Update all tables with the new rows per page value
        updateTablePagination('.their-debts-overdue-body', '#their-debts-overdue-pagination', rowsPerPage);
        updateTablePagination('.our-debts-overdue-body', '#our-debts-overdue-pagination', rowsPerPage);
        updateTablePagination('.their-debts-upcoming-body', '#their-debts-upcoming-pagination', rowsPerPage);
        updateTablePagination('.our-debts-upcoming-body', '#our-debts-upcoming-pagination', rowsPerPage);
    });
    
    /**
     * Initialize pagination for a table
     * @param {string} tableBodySelector - Selector for the table body
     * @param {string} paginationContainerSelector - Selector for the pagination container
     */
    function initTablePagination(tableBodySelector, paginationContainerSelector) {
        const tableBody = document.querySelector(tableBodySelector);
        const paginationContainer = document.querySelector(paginationContainerSelector);
        
        if (!tableBody || !paginationContainer) return;
        
        const rows = tableBody.querySelectorAll('tr');
        if (rows.length === 0) return;
        
        const rowsPerPage = parseInt(document.getElementById('per_page').value) || 10;
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        
        // Initialize pagination
        paginateTable(tableBody, 1, rowsPerPage);
        
        // Create pagination UI
        createPaginationUI(paginationContainer, tableBodySelector, 1, totalPages);
    }
    
    /**
     * Update pagination for a table when rows per page changes
     * @param {string} tableBodySelector - Selector for the table body
     * @param {string} paginationContainerSelector - Selector for the pagination container
     * @param {number} rowsPerPage - Number of rows per page
     */
    function updateTablePagination(tableBodySelector, paginationContainerSelector, rowsPerPage) {
        const tableBody = document.querySelector(tableBodySelector);
        const paginationContainer = document.querySelector(paginationContainerSelector);
        
        if (!tableBody || !paginationContainer) return;
        
        const rows = tableBody.querySelectorAll('tr');
        if (rows.length === 0) return;
        
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        
        // Reset to first page with new rows per page setting
        paginateTable(tableBody, 1, rowsPerPage);
        
        // Recreate pagination UI
        createPaginationUI(paginationContainer, tableBodySelector, 1, totalPages);
    }
    
    /**
     * Paginate a table by showing/hiding rows
     * @param {HTMLElement} tableBody - The table body element
     * @param {number} currentPage - Current page number
     * @param {number} rowsPerPage - Number of rows per page
     */
    function paginateTable(tableBody, currentPage, rowsPerPage) {
        const rows = tableBody.querySelectorAll('tr');
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        
        // Hide all rows
        rows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    /**
     * Create pagination UI for a table
     * @param {HTMLElement} container - The container for pagination
     * @param {string} tableBodySelector - Selector for the table body
     * @param {number} currentPage - Current page number
     * @param {number} totalPages - Total number of pages
     */
    function createPaginationUI(container, tableBodySelector, currentPage, totalPages) {
        if (!container) return;
        
        // Get rows per page
        const rowsPerPage = parseInt(document.getElementById('per_page').value) || 10;
        
        // Clear existing pagination
        container.innerHTML = '';
        
        // If there's only one page or no pages, don't show pagination
        if (totalPages <= 1) return;
        
        // Create the navigation
        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'Page navigation');
        
        // Create pagination list
        const ul = document.createElement('ul');
        ul.className = 'pagination';
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.setAttribute('aria-label', 'Previous');
        prevLink.innerHTML = '<span aria-hidden="true">&laquo;</span>';
        if (currentPage > 1) {
            prevLink.setAttribute('data-page', currentPage - 1);
            prevLink.addEventListener('click', function(e) {
                e.preventDefault();
                goToPage(tableBodySelector, container, currentPage - 1);
            });
        }
        prevLi.appendChild(prevLink);
        ul.appendChild(prevLi);
        
        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
        
        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            const pageLink = document.createElement('a');
            pageLink.className = 'page-link';
            pageLink.href = '#';
            pageLink.textContent = i;
            pageLink.setAttribute('data-page', i);
            
            pageLink.addEventListener('click', function(e) {
                e.preventDefault();
                goToPage(tableBodySelector, container, i);
            });
            
            pageLi.appendChild(pageLink);
            ul.appendChild(pageLi);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.setAttribute('aria-label', 'Next');
        nextLink.innerHTML = '<span aria-hidden="true">&raquo;</span>';
        if (currentPage < totalPages) {
            nextLink.setAttribute('data-page', currentPage + 1);
            nextLink.addEventListener('click', function(e) {
                e.preventDefault();
                goToPage(tableBodySelector, container, currentPage + 1);
            });
        }
        nextLi.appendChild(nextLink);
        ul.appendChild(nextLi);
        
        // Add pagination to the container
        nav.appendChild(ul);
        container.appendChild(nav);
        
        // Add pagination info
        const paginationInfo = document.createElement('div');
        paginationInfo.className = 'pagination-info';
        const tableBody = document.querySelector(tableBodySelector);
        const rows = tableBody.querySelectorAll('tr');
        const startIndex = (currentPage - 1) * rowsPerPage + 1;
        const endIndex = Math.min(currentPage * rowsPerPage, rows.length);
        paginationInfo.textContent = `پیشاندانی ${startIndex} بۆ ${endIndex} لە کۆی ${rows.length}`;
        
        // Create a wrapper div for the pagination and info
        const paginationWrapper = document.createElement('div');
        paginationWrapper.className = 'd-flex justify-content-between align-items-center';
        paginationWrapper.appendChild(paginationInfo);
        paginationWrapper.appendChild(nav);
        
        container.appendChild(paginationWrapper);
    }
    
    /**
     * Navigate to a specific page
     * @param {string} tableBodySelector - Selector for the table body
     * @param {HTMLElement} paginationContainer - Container for pagination
     * @param {number} page - Page number to navigate to
     */
    function goToPage(tableBodySelector, paginationContainer, page) {
        const tableBody = document.querySelector(tableBodySelector);
        if (!tableBody) return;
        
        const rows = tableBody.querySelectorAll('tr');
        const rowsPerPage = parseInt(document.getElementById('per_page').value) || 10;
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        
        // Update pagination
        paginateTable(tableBody, page, rowsPerPage);
        
        // Recreate pagination UI
        createPaginationUI(paginationContainer, tableBodySelector, page, totalPages);
    }
});
</script>

</body>
</html> 