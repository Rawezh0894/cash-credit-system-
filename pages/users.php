<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has permission to view users
requirePermission('view_users');

// Get all users
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT u.*, r.name as role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    ORDER BY u.id DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all roles for dropdown
$stmt = $db->prepare("SELECT * FROM roles ORDER BY id");
$stmt->execute();
$roles = $stmt->fetchAll();

// Check for messages
$success_message = $_SESSION['user_success'] ?? '';
$error_message = $_SESSION['user_error'] ?? '';
$warning_message = $_SESSION['user_warning'] ?? '';

// Clear messages
unset($_SESSION['user_success']);
unset($_SESSION['user_error']);
unset($_SESSION['user_warning']);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی بەکارهێنەران - سیستەمی پارە و کریت</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            
            <!-- Main Content -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بەڕێوەبردنی بەکارهێنەران</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-lg"></i> زیادکردنی بەکارهێنەر
                    </button>
                </div>
                <div class="card-body">
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
                                            <div class="header-text">ناوی تەواو</div>
                                            <div class="column-search">
                                                <input type="text" class="form-control" placeholder="گەڕان بە ناو..." onkeyup="filterTable(this, 1)">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">
                                        <div class="table-header-with-search">
                                            <div class="header-text">ناوی بەکارهێنەر</div>
                                            <div class="column-search">
                                                <input type="text" class="form-control" placeholder="گەڕان بە ناو..." onkeyup="filterTable(this, 2)">
                                            </div>
                                        </div>
                                    </th>
                                    <th class="border">ڕۆڵ</th>
                                    <th class="border">دوایین چوونەژوورەوە</th>
                                    <th class="border">باری چالاکی</th>
                                    <th class="border">کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role_id'] == 1 ? 'danger' : ($user['role_id'] == 2 ? 'primary' : ($user['role_id'] == 3 ? 'success' : 'secondary')); ?>">
                                            <?php echo htmlspecialchars($user['role_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('Y/m/d H:i', strtotime($user['last_login'])) : 'هیچ کات'; ?></td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">چالاکە</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ناچالاکە</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a 
                                                href="javascript:void(0);"
                                                class="action-btn edit"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-role="<?php echo $user['role_id']; ?>"
                                                data-active="<?php echo $user['is_active']; ?>"
                                                title="دەستکاری"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): // Cannot delete own account ?>
                                            <a 
                                                href="javascript:void(0);"
                                                class="action-btn delete"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteUserModal"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
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
                        <div id="pagination" class="pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">زیادکردنی بەکارهێنەر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addUserForm" action="../process/users/create.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">ناوی تەواو</label>
                            <input type="text" class="form-control" id="fullName" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">وشەی نهێنی</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">ڕۆڵ</label>
                            <select class="form-select" id="role" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>">
                                    <?php echo htmlspecialchars($role['name']); ?> - 
                                    <?php echo htmlspecialchars($role['description']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" checked>
                            <label class="form-check-label" for="isActive">بەکارهێنەر چالاکە</label>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">دەستکاریکردنی بەکارهێنەر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm" action="../process/users/update.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="id">
                        <div class="mb-3">
                            <label for="editFullName" class="form-label">ناوی تەواو</label>
                            <input type="text" class="form-control" id="editFullName" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">ناوی بەکارهێنەر</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">وشەی نهێنی نوێ <small class="text-muted">(ئەگەر ناتەوێت بیگۆڕیت، بەتاڵی بەجێی بهێڵە)</small></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="editPassword" name="password">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">ڕۆڵ</label>
                            <select class="form-select" id="editRole" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>">
                                    <?php echo htmlspecialchars($role['name']); ?> - 
                                    <?php echo htmlspecialchars($role['description']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="editIsActive" name="is_active" value="1">
                            <label class="form-check-label" for="editIsActive">بەکارهێنەر چالاکە</label>
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

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">سڕینەوەی بەکارهێنەر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>ئایا دڵنیایت کە دەتەوێت ئەم بەکارهێنەرە بسڕیتەوە: <strong id="deleteUserName"></strong>؟</p>
                    <p class="text-danger">ئەم کردارە ناگەڕێتەوە!</p>
                </div>
                <form id="deleteUserForm" action="../process/users/delete.php" method="post">
                    <input type="hidden" id="deleteUserId" name="id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-danger">سڕینەوە</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/users/users.js"></script>
</body>
</html> 