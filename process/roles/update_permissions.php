<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('manage_permissions')) {
    header('Location: ../../pages/dashboard.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $role_id = (int)($_POST['role_id'] ?? 0);
    $permissions = $_POST['permissions'] ?? [];
    
    // Convert permissions to integers
    $permissions = array_map('intval', $permissions);
    
    // Validate input
    if ($role_id <= 0) {
        $_SESSION['role_error'] = 'ناسنامەی ڕۆڵ دروست نییە';
        header('Location: ../../pages/roles.php');
        exit();
    }
    
    try {
        $db = Database::getInstance();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Delete all existing permissions for this role
        $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$role_id]);
        
        // Insert new permissions
        if (!empty($permissions)) {
            $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES ";
            $values = [];
            $params = [];
            
            foreach ($permissions as $permission_id) {
                $values[] = "(?, ?)";
                $params[] = $role_id;
                $params[] = $permission_id;
            }
            
            $sql .= implode(',', $values);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['role_success'] = 'مۆڵەتەکانی ڕۆڵ بە سەرکەوتوویی نوێ کرانەوە';
    } catch (PDOException $e) {
        // Rollback transaction
        $db->rollBack();
        $_SESSION['role_error'] = 'هەڵەیەک ڕوویدا: ' . $e->getMessage();
    }
    
    // Redirect back to roles page
    header('Location: ../../pages/roles.php');
    exit();
} else {
    // Not a POST request, redirect to roles page
    header('Location: ../../pages/roles.php');
    exit();
} 