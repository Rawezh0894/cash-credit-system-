<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user has permission to manage permissions
if (!hasPermission('manage_permissions')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو']);
    exit();
}

// Check if role_id is provided
if (!isset($_GET['role_id']) || empty($_GET['role_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'پێویستە ناسنامەی ڕۆڵ دیاری بکرێت']);
    exit();
}

$role_id = (int)$_GET['role_id'];

try {
    $db = Database::getInstance();
    
    // Get all permissions and check if they are assigned to the role
    $stmt = $db->prepare("
        SELECT p.*, 
               CASE WHEN rp.role_id IS NOT NULL THEN 1 ELSE 0 END AS assigned
        FROM permissions p
        LEFT JOIN role_permissions rp ON p.id = rp.permission_id AND rp.role_id = ?
        ORDER BY p.id
    ");
    $stmt->execute([$role_id]);
    $permissions = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'permissions' => $permissions]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
} 