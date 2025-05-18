<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has permission to view roles
requirePermission('view_roles');

// Get all roles with permissions count
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT r.*, COUNT(rp.permission_id) as permission_count 
    FROM roles r 
    LEFT JOIN role_permissions rp ON r.id = rp.role_id 
    GROUP BY r.id 
    ORDER BY r.id
");
$stmt->execute();
$roles = $stmt->fetchAll();

// Get all permissions
$stmt = $db->prepare("SELECT * FROM permissions ORDER BY id");
$stmt->execute();
$permissions = $stmt->fetchAll();

// Check for messages
$success_message = $_SESSION['role_success'] ?? '';
$error_message = $_SESSION['role_error'] ?? '';
$warning_message = $_SESSION['role_warning'] ?? '';

// Clear messages
unset($_SESSION['role_success']);
unset($_SESSION['role_error']);
unset($_SESSION['role_warning']);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی ڕۆڵەکان - سیستەمی پارە و کریت</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tables.css">
</head>
<body class="bg-body-tertiary">
    <div class="container-fluid py-4">
        <?php include '../includes/navbar.php'; ?>
        
        <div class="container-fluid">
            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($warning_message)): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?php echo $warning_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            

            
            <!-- Roles Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بەڕێوەبردنی ڕۆڵەکان</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="bi bi-plus-lg"></i> زیادکردنی ڕۆڵ
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-controls">
                        <div class="records-per-page">
                            <select id="per_page_roles" class="form-select">
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
                                            <div class="header-text">ناوی ڕۆڵ</div>
                                            <div class="column-search">
                                                <input type="text" class="form-control" placeholder="گەڕان بە ناو..." onkeyup="filterRolesTable(this, 1)">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">وەسف</div>
                                            <div class="column-search">
                                                <input type="text" class="form-control" placeholder="گەڕان بە وەسف..." onkeyup="filterRolesTable(this, 2)">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">ژمارەی مۆڵەتەکان</th>
                                    <th class="border">بەرواری دروستکردن</th>
                                    <th class="border">کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody id="rolesTableBody">
                                <?php foreach ($roles as $index => $role): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($role['name']); ?></td>
                                    <td><?php echo htmlspecialchars($role['description'] ?? 'بێ وەسف'); ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-info">
                                            <?php echo $role['permission_count']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y/m/d', strtotime($role['created_at'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a 
                                                href="javascript:void(0);"
                                                class="action-btn edit" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editRoleModal"
                                                data-id="<?php echo $role['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($role['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($role['description'] ?? ''); ?>"
                                                title="دەستکاری"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a 
                                                href="javascript:void(0);"
                                                class="action-btn view" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#managePermissionsModal"
                                                data-id="<?php echo $role['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($role['name']); ?>"
                                                title="مۆڵەتەکان"
                                            >
                                                <i class="bi bi-key"></i>
                                            </a>
                                            <?php if ($role['id'] > 1): // Cannot delete Super Admin role ?>
                                            <a 
                                                href="javascript:void(0);"
                                                class="action-btn delete" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteRoleModal"
                                                data-id="<?php echo $role['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($role['name']); ?>"
                                                title="سڕینەوە"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <div id="roles_pagination" class="pagination"></div>
                    </div>
                </div>
            </div>
            
            <!-- Permissions Table -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بەڕێوەبردنی مۆڵەتەکان</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
                        <i class="bi bi-plus-lg"></i> زیادکردنی مۆڵەت
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-controls">
                        <div class="records-per-page">
                            <select id="per_page_permissions" class="form-select">
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
                                            <div class="header-text">ناوی مۆڵەت</div>
                                            <div class="column-search">
                                                <input type="text" class="form-control" placeholder="گەڕان بە ناو..." onkeyup="filterPermissionsTable(this, 1)">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">وەسف</div>
                                            <div class="column-search">
                                                <input type="text" class="form-control" placeholder="گەڕان بە وەسف..." onkeyup="filterPermissionsTable(this, 2)">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">بەرواری دروستکردن</th>
                                    <th class="border">کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody id="permissionsTableBody">
                                <?php foreach ($permissions as $index => $permission): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($permission['name']); ?></td>
                                    <td><?php echo htmlspecialchars($permission['description'] ?? 'بێ وەسف'); ?></td>
                                    <td><?php echo date('Y/m/d', strtotime($permission['created_at'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a 
                                                href="javascript:void(0);"
                                                class="action-btn edit" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editPermissionModal"
                                                data-id="<?php echo $permission['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($permission['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($permission['description'] ?? ''); ?>"
                                                title="دەستکاری"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a 
                                                href="javascript:void(0);"
                                                class="action-btn delete" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deletePermissionModal"
                                                data-id="<?php echo $permission['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($permission['name']); ?>"
                                                title="سڕینەوە"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <div id="permissions_pagination" class="pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">زیادکردنی ڕۆڵ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addRoleForm" action="../process/roles/create.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="roleName" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="roleName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="roleDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="roleDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">زیادکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">دەستکاریکردنی ڕۆڵ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editRoleForm" action="../process/roles/update.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="editRoleId" name="id">
                        <div class="mb-3">
                            <label for="editRoleName" class="form-label">ناوی ڕۆڵ</label>
                            <input type="text" class="form-control" id="editRoleName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRoleDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="editRoleDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">پاشەکەوتکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manage Permissions Modal -->
    <div class="modal fade" id="managePermissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">بەڕێوەبردنی مۆڵەتەکان بۆ ڕۆڵی <span id="permissionRoleName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="managePermissionsForm" action="../process/roles/update_permissions.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="permissionRoleId" name="role_id">
                        <div class="mb-3">
                            <div class="row g-3" id="permissionsCheckboxes">
                                <!-- This will be filled dynamically with JS -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">پاشەکەوتکردنی مۆڵەتەکان</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Permission Modal -->
    <div class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">زیادکردنی مۆڵەت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addPermissionForm" action="../process/permissions/create.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="permissionName" class="form-label">ناوی مۆڵەت</label>
                            <input type="text" class="form-control" id="permissionName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="permissionDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="permissionDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">زیادکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Permission Modal -->
    <div class="modal fade" id="editPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">دەستکاریکردنی مۆڵەت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editPermissionForm" action="../process/permissions/update.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="editPermissionId" name="id">
                        <div class="mb-3">
                            <label for="editPermissionName" class="form-label">ناوی مۆڵەت</label>
                            <input type="text" class="form-control" id="editPermissionName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPermissionDescription" class="form-label">وەسف</label>
                            <textarea class="form-control" id="editPermissionDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary">پاشەکەوتکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/roles/roles.js"></script>
</body>
</html> 