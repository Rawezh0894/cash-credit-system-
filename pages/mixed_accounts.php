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

// Check permission to view mixed accounts
requirePermission('view_mixed_accounts');

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
    <title>حسابە تێکەڵەکان - سیستەمی پارە و کریت</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tables.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SELECT2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets/css/select2-custom.css" rel="stylesheet" />
</head>
<body class="bg-body-tertiary">
    <div class="container-fluid py-4">
    <?php include '../includes/navbar.php'; ?>
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">
                    <span class="icon-circle icon-mixed">
                        <i class="bi bi-people"></i>
                    </span>
                    حسابە تێکەڵەکان
                </h2>
                
                <!-- Mixed Account Add Modal -->
                <div class="modal fade" id="mixedAccountAddModal" tabindex="-1" aria-labelledby="mixedAccountAddModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="mixedAccountAddModalLabel">زیادکردنی حساب</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="mixedAccountAddForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">ناو</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone1" class="form-label">ژمارەی مۆبایل</label>
                                            <input type="text" class="form-control" id="phone1" name="phone1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                                            <input type="text" class="form-control" id="phone2" name="phone2">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="guarantor_name" class="form-label">ناوی کەفیل (ئیختیاری)</label>
                                            <input type="text" class="form-control" id="guarantor_name" name="guarantor_name">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="guarantor_phone" class="form-label">ژمارەی مۆبایلی کەفیل (ئیختیاری)</label>
                                            <input type="text" class="form-control" id="guarantor_phone" name="guarantor_phone">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="they_owe" class="form-label">بڕی ئەوەی ئەوان قەرزارن</label>
                                            <input type="number" class="form-control" id="they_owe" name="they_owe" value="0" step="0.01" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="we_owe" class="form-label">بڕی ئەوەی ئێمە قەرزارین</label>
                                            <input type="number" class="form-control" id="we_owe" name="we_owe" value="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="they_advance" class="form-label">بڕی پێشەکی ئەوان</label>
                                            <input type="number" class="form-control" id="they_advance" name="they_advance" value="0" step="0.01" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="we_advance" class="form-label">بڕی پێشەکی ئێمە</label>
                                            <input type="number" class="form-control" id="we_advance" name="we_advance" value="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">شار</label>
                                            <input type="text" class="form-control" id="city" name="city">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">شوێن</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="location" id="location_inside" value="inside" checked>
                                                <label class="form-check-label" for="location_inside">ناو شار</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="location" id="location_outside" value="outside">
                                                <label class="form-check-label" for="location_outside">دەرەوەی شار</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="notes" class="form-label">تێبینی</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                                <button type="button" class="btn btn-primary" id="saveAccountAddBtn">زیادکردن</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mixed Account Edit Modal -->
                <div class="modal fade" id="mixedAccountEditModal" tabindex="-1" aria-labelledby="mixedAccountEditModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="mixedAccountEditModalLabel">دەستکاری حساب</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="mixedAccountEditForm">
                                    <input type="hidden" name="account_id" value="">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_name" class="form-label">ناو</label>
                                            <input type="text" class="form-control" id="edit_name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_phone1" class="form-label">ژمارەی مۆبایل</label>
                                            <input type="text" class="form-control" id="edit_phone1" name="phone1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_phone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                                            <input type="text" class="form-control" id="edit_phone2" name="phone2">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_guarantor_name" class="form-label">ناوی کەفیل (ئیختیاری)</label>
                                            <input type="text" class="form-control" id="edit_guarantor_name" name="guarantor_name">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_guarantor_phone" class="form-label">ژمارەی مۆبایلی کەفیل (ئیختیاری)</label>
                                            <input type="text" class="form-control" id="edit_guarantor_phone" name="guarantor_phone">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_they_owe" class="form-label">بڕی ئەوەی ئەوان قەرزارن</label>
                                            <input type="number" class="form-control" id="edit_they_owe" name="they_owe" value="0" step="0.01" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_we_owe" class="form-label">بڕی ئەوەی ئێمە قەرزارین</label>
                                            <input type="number" class="form-control" id="edit_we_owe" name="we_owe" value="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_they_advance" class="form-label">بڕی پێشەکی ئەوان</label>
                                            <input type="number" class="form-control" id="edit_they_advance" name="they_advance" value="0" step="0.01" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_we_advance" class="form-label">بڕی پێشەکی ئێمە</label>
                                            <input type="number" class="form-control" id="edit_we_advance" name="we_advance" value="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_city" class="form-label">شار</label>
                                            <input type="text" class="form-control" id="edit_city" name="city">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">شوێن</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="location" id="edit_location_inside" value="inside" checked>
                                                <label class="form-check-label" for="edit_location_inside">ناو شار</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="location" id="edit_location_outside" value="outside">
                                                <label class="form-check-label" for="edit_location_outside">دەرەوەی شار</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_notes" class="form-label">تێبینی</label>
                                            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                                <button type="button" class="btn btn-primary" id="saveAccountEditBtn">نوێکردنەوە</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mixed Accounts List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">لیستی حسابەکان</h5>
                        <?php if (hasPermission('add_mixed_account')): ?>
                        <button type="button" class="btn btn-primary add-mixed-account-btn" data-bs-toggle="modal" data-bs-target="#mixedAccountAddModal">
                            <i class="bi bi-plus-lg"></i> زیادکردنی حساب
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- SELECT2 Filters -->
                        <div class="select2-filters-wrapper">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label for="filter_name" class="form-label">فلتەر بە ناو:</label>
                                    <select id="filter_name" class="form-control select2-filter" data-column="1">
                                        <option value="">هەموو ناوەکان</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="filter_city" class="form-label">فلتەر بە شار:</label>
                                    <select id="filter_city" class="form-control select2-filter" data-column="5">
                                        <option value="">هەموو شارەکان</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="filter_location" class="form-label">فلتەر بە شوێن:</label>
                                    <select id="filter_location" class="form-control" data-column="6">
                                        <option value="">هەموو شوێنەکان</option>
                                        <option value="ناو شار">ناو شار</option>
                                        <option value="دەرەوەی شار">دەرەوەی شار</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- End SELECT2 Filters -->
                        <div class="table-controls">
                            <div class="records-per-page">
                                <select id="per_page" class="form-select">
                                    <option value="10">10</option>
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
                                                <div class="header-text">ناو</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە ناو..." onkeyup="filterTable(this, 1)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">
                                            <div class="table-header-with-search">
                                                <div class="header-text">ژمارەی مۆبایل</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە ژمارە..." onkeyup="filterTable(this, 2)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">
                                            <div class="table-header-with-search">
                                                <div class="header-text">ئەوان قەرزارن</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە قەرز..." onkeyup="filterTable(this, 3)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">
                                            <div class="table-header-with-search">
                                                <div class="header-text">ئێمە قەرزارین</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە قەرز..." onkeyup="filterTable(this, 4)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">
                                            <div class="table-header-with-search">
                                                <div class="header-text">شار</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە شار..." onkeyup="filterTable(this, 5)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">
                                            <div class="table-header-with-search">
                                                <div class="header-text">شوێن</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە شوێن..." onkeyup="filterTable(this, 6)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody id="mixedAccountsTableBody">
                                    <!-- Data will be loaded here via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        <div id="pagination">
                            <!-- Pagination will be loaded here via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SELECT2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Select2 Custom JS -->
    <script src="../assets/js/select2/select2.js"></script>
    <!-- Permissions JS -->
    <script src="../assets/js/permissions/permissions.js"></script>
    <!-- Script for navbar -->
   
    <!-- Custom scripts -->
    <script src="../assets/js/mixed_accounts/table-controls.js"></script>
    <script src="../assets/js/mixed_accounts/pagination.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/swalAlert2/swalAlert2.js"></script>
    <script src="../assets/js/mixed_accounts/delete.js"></script>
    <script src="../assets/js/mixed_accounts/mixed_accounts.js"></script>
    <script src="../assets/js/filtters/location-filter.js"></script>
    
    <!-- SELECT2 Initialization -->
    <script>
        $(document).ready(function() {
            // Set up filter configuration for the mixed accounts page
            const filterConfig = {
                '#filter_name': 1,   // Name column
                '#filter_city': 5    // City column
            };
            
            // Initialize the table observer to load filter values
            setupTableObserver(filterConfig);
            
            // Initialize location filter with select2 (without search)
            $('#filter_location').select2({
                width: '100%',
                minimumResultsForSearch: -1, // Disable search
                placeholder: 'هەموو شوێنەکان',
                allowClear: true,
                dir: 'rtl'
            }).on('change', function() {
                // Call the applyLocationFilter function when the selection changes
                applyLocationFilter();
            });
        });
    </script>

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
</body>
</html> 