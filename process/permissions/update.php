<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('manage_permissions')) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate input
    $errors = [];
    
    if ($id <= 0) {
        $errors[] = 'ناسنامەی ڕێگەپێدان دروست نییە';
    }
    
    if (empty($name)) {
        $errors[] = 'ناوی ڕێگەپێدان پێویستە';
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if permission exists
            $stmt = $db->prepare("SELECT id FROM permissions WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'مۆڵەت نەدۆزرایەوە']);
                exit();
            }
            
            // Check if permission name already exists for another permission
            $stmt = $db->prepare("SELECT COUNT(*) FROM permissions WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                echo json_encode(['success' => false, 'message' => 'ئەم ناوی مۆڵەتە پێشتر بەکار هاتووە']);
                exit();
            }
            
            // Update permission
            $stmt = $db->prepare("
                UPDATE permissions 
                SET name = ?, description = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$name, $description, $id]);
            
            // Check if update was successful
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'زانیارییەکانی مۆڵەت بە سەرکەوتوویی نوێ کرانەوە']);
            } else {
                echo json_encode(['success' => false, 'message' => 'هیچ گۆڕانکارییەک نەکرا']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    }
    exit();
} else {
    // Not a POST request
    echo json_encode(['success' => false, 'message' => 'تەنها پۆستی ڕێپێدراوە']);
    exit();
} 