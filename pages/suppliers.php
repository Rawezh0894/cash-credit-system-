<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check permission to view suppliers
requirePermission('view_suppliers');

// Get messages from session
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دابینکەرەکان - سیستەمی پارە و کریت</title>
    
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
                    <span class="icon-circle icon-suppliers">
                        <i class="bi bi-truck"></i>
                    </span>
                    دابینکەرەکان
                </h2>
                
                <!-- Supplier Add Modal -->
                <div class="modal fade" id="supplierAddModal" tabindex="-1" aria-labelledby="supplierAddModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="supplierAddModalLabel">زیادکردنی دابینکەر</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="supplierAddForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">ناوی دابینکەر</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone1" class="form-label">ژمارەی مۆبایلی یەکەم</label>
                                            <input type="text" class="form-control" id="phone1" name="phone1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone2" class="form-label">ژمارەی مۆبایلی دووەم (ئیختیاری)</label>
                                            <input type="text" class="form-control" id="phone2" name="phone2">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="we_owe" class="form-label">بڕی ئەوەی ئێمە قەرزارین</label>
                                            <input type="number" class="form-control" id="we_owe" name="we_owe" value="0" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="advance_payment" class="form-label">بڕی پێشەکی (ئەوەی ئێمە داومانە)</label>
                                            <input type="number" class="form-control" id="advance_payment" name="advance_payment" value="0" step="0.01">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">ناوی شار</label>
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
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                                <button type="button" class="btn btn-primary" id="saveSupplierAddBtn">زیادکردن</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supplier Edit Modal -->
                <div class="modal fade" id="supplierEditModal" tabindex="-1" aria-labelledby="supplierEditModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="supplierEditModalLabel">دەستکاری دابینکەر</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="supplierEditForm">
                                    <input type="hidden" name="supplier_id" value="">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_name" class="form-label">ناوی دابینکەر</label>
                                            <input type="text" class="form-control" id="edit_name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_phone1" class="form-label">ژمارەی مۆبایلی یەکەم</label>
                                            <input type="text" class="form-control" id="edit_phone1" name="phone1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_phone2" class="form-label">ژمارەی مۆبایلی دووەم (ئیختیاری)</label>
                                            <input type="text" class="form-control" id="edit_phone2" name="phone2">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_we_owe" class="form-label">بڕی ئەوەی ئێمە قەرزارین</label>
                                            <input type="number" class="form-control" id="edit_we_owe" name="we_owe" value="0" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_advance_payment" class="form-label">بڕی پێشەکی (ئەوەی ئێمە داومانە)</label>
                                            <input type="number" class="form-control" id="edit_advance_payment" name="advance_payment" value="0" step="0.01">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="edit_city" class="form-label">ناوی شار</label>
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
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                                <button type="button" class="btn btn-primary" id="saveSupplierEditBtn">نوێکردنەوە</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Supplier List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">لیستی دابینکەرەکان</h5>
                        <?php if (hasPermission('add_supplier')): ?>
                        <button type="button" class="btn btn-primary add-supplier-btn" data-bs-toggle="modal" data-bs-target="#supplierAddModal">
                            <i class="bi bi-plus-lg"></i> زیادکردنی دابینکەر
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
                                    <label id="filter_location_label" class="form-label">فلتەر بە شوێن:</label>
                                    <select id="filter_location" class="form-control" data-column="6" aria-labelledby="filter_location_label">
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
                                                <div class="header-text">بڕی قەرزمان</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە بڕ..." onkeyup="filterTable(this, 3)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">
                                            <div class="table-header-with-search">
                                                <div class="header-text">بڕی پێشەکی</div>
                                                <div class="column-search">
                                                    <input type="text" class="form-control" placeholder="گەڕان بە بڕ..." onkeyup="filterTable(this, 4)">
                                                </div>
                                            </div>
                                        </th>
                                        <th class="border">
                                            <div class="table-header-with-search">
                                                <div class="header-text">ناوی شار</div>
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
                                <tbody id="suppliersTableBody">
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
    <!-- Custom scripts -->
    <script src="../assets/js/suppliers/table-controls.js"></script>
    <script src="../assets/js/suppliers/suppliers.js"></script>
    <script src="../assets/js/suppliers/pagination.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/swalAlert2/swalAlert2.js"></script>
    <script src="../assets/js/filtters/location-filter.js"></script>
    <!-- Pagination Fix -->
    <script src="../assets/js/pagination-fix.js"></script>
    
    <!-- SELECT2 Initialization -->
    <script>
        $(document).ready(function() {
            // Set up filter configuration for the suppliers page
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
        showSwalAlert2('success', 'سەرکەوتوو!', <?php echo json_encode($success_message); ?>);
    </script>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <script>
        showSwalAlert2('error', 'هەڵە!', <?php echo json_encode($error_message); ?>);
    </script>
    <?php endif; ?>
</body>
</html> 