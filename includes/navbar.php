<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get the user's full name from session
$user_full_name = $_SESSION['full_name'] ?? 'بەکارهێنەر';

// Get user role for conditional menu items
$user_role = $_SESSION['role_id'] ?? 0;

// Include permission functions
require_once __DIR__ . '/functions/permissions.php';

// Base path - check if we're in the pages directory or root
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $base_path = './'; // We're already in pages directory
} else {
    $base_path = 'pages/'; // We're in the root
}
?>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">

        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_dashboard') ? 'disabled' : ''; ?>" 
                       href="<?php echo $base_path; ?>dashboard.php">
                        <?php if (!hasPermission('view_dashboard')): ?>
                        <i class="bi bi-lock me-1"></i>
                        <?php else: ?>
                        <i class="bi bi-speedometer2 me-1"></i>
                        <?php endif; ?>
                        داشبۆرد
                    </a>
                </li>
                
                <!-- هەژمارەکان Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['customers.php', 'suppliers.php', 'mixed_accounts.php']) ? 'active' : ''; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-journal-text me-1"></i>
                        هەژمارەکان
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item <?php echo $current_page == 'customers.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_customers') ? 'disabled' : ''; ?>" 
                               href="<?php echo $base_path; ?>customers.php">
                                <?php if (!hasPermission('view_customers')): ?>
                                <i class="bi bi-lock me-2"></i>
                                <?php else: ?>
                                <i class="bi bi-people me-2"></i>
                                <?php endif; ?>
                                کڕیارەکان
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item <?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_suppliers') ? 'disabled' : ''; ?>" 
                               href="<?php echo $base_path; ?>suppliers.php">
                                <?php if (!hasPermission('view_suppliers')): ?>
                                <i class="bi bi-lock me-2"></i>
                                <?php else: ?>
                                <i class="bi bi-truck me-2"></i>
                                <?php endif; ?>
                                دابینکەرەکان
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item <?php echo $current_page == 'mixed_accounts.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_mixed_accounts') ? 'disabled' : ''; ?>" 
                               href="<?php echo $base_path; ?>mixed_accounts.php">
                                <?php if (!hasPermission('view_mixed_accounts')): ?>
                                <i class="bi bi-lock me-2"></i>
                                <?php else: ?>
                                <i class="bi bi-arrow-left-right me-2"></i>
                                <?php endif; ?>
                                هەژمارە تێکەڵاوەکان
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- بەڕێوەبردن Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['users.php', 'roles.php']) ? 'active' : ''; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-shield-lock me-1"></i>
                        بەڕێوەبردن
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_users') ? 'disabled' : ''; ?>" 
                               href="<?php echo $base_path; ?>users.php">
                                <?php if (!hasPermission('view_users')): ?>
                                <i class="bi bi-lock me-2"></i>
                                <?php else: ?>
                                <i class="bi bi-people me-2"></i>
                                <?php endif; ?>
                                بەکارهێنەران
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item <?php echo $current_page == 'roles.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_roles') ? 'disabled' : ''; ?>" 
                               href="<?php echo $base_path; ?>roles.php">
                                <?php if (!hasPermission('view_roles')): ?>
                                <i class="bi bi-lock me-2"></i>
                                <?php else: ?>
                                <i class="bi bi-key me-2"></i>
                                <?php endif; ?>
                                ڕۆڵەکان و مۆڵەتەکان
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['transactions.php', 'deleted_transactions.php']) ? 'active' : ''; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-receipt me-1"></i>
                        مامەڵەکان
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_transactions') ? 'disabled' : ''; ?>" 
                               href="<?php echo $base_path; ?>transactions.php">
                                <?php if (!hasPermission('view_transactions')): ?>
                                <i class="bi bi-lock me-2"></i>
                                <?php else: ?>
                                <i class="bi bi-receipt-cutoff me-2"></i>
                                <?php endif; ?>
                                مامەڵە چالاکەکان
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item <?php echo $current_page == 'deleted_transactions.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_deleted_transactions') ? 'disabled' : ''; ?>" 
                               href="<?php echo $base_path; ?>deleted_transactions.php">
                                <?php if (!hasPermission('view_deleted_transactions')): ?>
                                <i class="bi bi-lock me-2"></i>
                                <?php else: ?>
                                <i class="bi bi-trash me-2"></i>
                                <?php endif; ?>
                                مامەڵە سڕاوەکان
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?> <?php echo !hasPermission('view_reports') ? 'disabled' : ''; ?>" 
                       href="<?php echo $base_path; ?>reports.php">
                        <?php if (!hasPermission('view_reports')): ?>
                        <i class="bi bi-lock me-1"></i>
                        <?php else: ?>
                        <i class="bi bi-file-earmark-text me-1"></i>
                        <?php endif; ?>
                        ڕاپۆرتەکان
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center">
                <!-- Theme Switcher -->
                <div class="theme-switcher">
                    <button class="btn" id="theme-toggle">
                        <i class="bi bi-sun-fill d-none"></i>
                        <i class="bi bi-moon-fill d-none"></i>
                    </button>
                </div>

                <!-- User Dropdown -->
                <div class="dropdown user-dropdown">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span><?php echo htmlspecialchars($user_full_name); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                  
                        <li>
                            <a class="dropdown-item text-danger" id="logout-link" href="#">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                دەرچوون
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Theme toggle and icon logic moved to external JS file -->
<script src="../assets/js/navbar/navbar.js"></script>

<!-- Script to fix logout link -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the current host and protocol
    const baseUrl = window.location.protocol + '//' + window.location.host;
    // Set the logout link to the absolute path
    document.getElementById('logout-link').href = baseUrl + '/cash-credit-system/core/logout.php';
});
</script> 