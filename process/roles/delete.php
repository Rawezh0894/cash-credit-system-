<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('manage_roles')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو']);
    exit();
}

// Check if user is a Super Admin (role_id = 1)
$db = Database::getInstance();
$stmt = $db->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetchColumn();

if ($user_role != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'تەنها بەڕێوەبەری سیستەم دەتوانێت ئەم کردارە ئەنجام بدات']);
    exit();
}

// Check if role ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ناسنامەی ڕۆڵ هەڵەبژێردراوە']);
    exit();
}

$role_id = $_POST['id'];

try {
    // Start transaction
    $db->beginTransaction();

    // Check if role exists and is not Super Admin (id = 1)
    $stmt = $db->prepare("SELECT id FROM roles WHERE id = ? AND id != 1");
    $stmt->execute([$role_id]);
    if (!$stmt->fetch()) {
        throw new Exception('ڕۆڵ نەدۆزرایەوە یان ناتوانرێت بسڕدرێتەوە');
    }

    // Check if role is assigned to any users
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
    $stmt->execute([$role_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ناتوانرێت ڕۆڵێک بسڕدرێتەوە کە بەکارهێنەری هەیە');
    }

    // Delete role permissions
    $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);

    // Delete role
    $stmt = $db->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);

    // Commit transaction
    $db->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'ڕۆڵ بە سەرکەوتوویی سڕایەوە']);

} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 