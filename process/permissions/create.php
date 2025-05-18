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
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'ناوی ڕێگەپێدان پێویستە';
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if permission name already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM permissions WHERE name = ?");
            $stmt->execute([$name]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                echo json_encode(['success' => false, 'message' => 'ئەم ناوی ڕێگەپێدانە پێشتر بەکار هاتووە']);
                exit();
            }
            
            // Insert new permission
            $stmt = $db->prepare("
                INSERT INTO permissions (name, description, created_at)
                VALUES (?, ?, NOW())
            ");
            
            $stmt->execute([$name, $description]);
            
            // Check if insert was successful
            if ($stmt->rowCount() > 0) {
                $newId = $db->lastInsertId();
                echo json_encode([
                    'success' => true, 
                    'message' => 'ڕێگەپێدان بە سەرکەوتوویی زیادکرا',
                    'id' => $newId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی ڕێگەپێدان']);
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