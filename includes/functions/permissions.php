<?php
/**
 * Check if user has specific permission
 * 
 * @param string $permission_name The name of the permission to check
 * @param int|null $user_id The user ID to check (default: current logged-in user)
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($permission_name, $user_id = null) {
    // If no user ID provided, use current user
    if ($user_id === null) {
        if (!isset($_SESSION['user_id'])) {
            return false; // Not logged in
        }
        $user_id = $_SESSION['user_id'];
    }
    
    // Super admin (role_id = 1) has all permissions
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        return true;
    }
    
    try {
        $db = Database::getInstance();
        
        // Check if user has the specific permission
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = ? AND p.name = ? AND u.is_active = 1
        ");
        $stmt->execute([$user_id, $permission_name]);
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has at least one of the specified permissions
 * 
 * @param array $permission_names Array of permission names to check
 * @param int|null $user_id The user ID to check (default: current logged-in user)
 * @return bool True if user has at least one permission, false otherwise
 */
function hasAnyPermission($permission_names, $user_id = null) {
    if (empty($permission_names)) {
        return false;
    }
    
    // If no user ID provided, use current user
    if ($user_id === null) {
        if (!isset($_SESSION['user_id'])) {
            return false; // Not logged in
        }
        $user_id = $_SESSION['user_id'];
    }
    
    // Super admin (role_id = 1) has all permissions
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        return true;
    }
    
    try {
        $db = Database::getInstance();
        
        // Prepare placeholders for the query
        $placeholders = rtrim(str_repeat('?,', count($permission_names)), ',');
        
        // Check if user has any of the specified permissions
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = ? AND p.name IN ($placeholders) AND u.is_active = 1
        ");
        
        // Prepare parameters
        $params = array_merge([$user_id], $permission_names);
        
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Require a specific permission or redirect to dashboard
 * 
 * @param string $permission_name The name of the permission to require
 * @return bool True if user has permission
 */
function requirePermission($permission_name) {
    if (!hasPermission($permission_name)) {
        // Set the required permission for the access denied page
        $required_permission = $permission_name;
        
        // Check if it's an AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // For AJAX requests, return a JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت ئەم کردارە ئەنجام بدەیت'
            ]);
            exit;
        } else {
            // For regular page requests, show the access denied page
            // Set variables for the access denied page
            $page_title = getPageTitleFromPermission($permission_name);
            
            // Include header
            include __DIR__ . '/../header.php';
            
            // Include access denied page
            include __DIR__ . '/../access_denied.php';
            
            // Include footer
            include __DIR__ . '/../footer.php';
            exit;
        }
    }

    return true;
}

// Helper function to get a user-friendly page title from a permission name
function getPageTitleFromPermission($permission_name) {
    $titles = [
        'view_dashboard' => 'داشبۆرد',
        'view_customers' => 'کڕیارەکان',
        'view_suppliers' => 'دابینکەرەکان',
        'view_mixed_accounts' => 'هەژمارە تێکەڵەکان',
        'view_transactions' => 'مامەڵەکان',
        'view_deleted_transactions' => 'مامەڵە سڕاوەکان',
        'view_reports' => 'ڕاپۆرتەکان',
        'view_users' => 'بەکارهێنەران',
        'view_roles' => 'ڕۆڵەکان',
        'default' => 'پەیج'
    ];
    
    return $titles[$permission_name] ?? $titles['default'];
} 