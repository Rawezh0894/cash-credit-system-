<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('manage_roles')) {
    header('Location: ../../pages/dashboard.php');
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
        $errors[] = 'ناسنامەی ڕۆڵ دروست نییە';
    }
    
    if (empty($name)) {
        $errors[] = 'ناوی ڕۆڵ پێویستە';
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if role exists and is not Super Admin (id = 1)
            $stmt = $db->prepare("SELECT id FROM roles WHERE id = ? AND id != 1");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $_SESSION['role_error'] = 'ڕۆڵ نەدۆزرایەوە یان ناتوانرێت دەستکاری بکرێت';
                header('Location: ../../pages/roles.php');
                exit();
            }
            
            // Check if role name already exists for another role
            $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['role_error'] = 'ئەم ناوی ڕۆڵە پێشتر بەکار هاتووە';
                header('Location: ../../pages/roles.php');
                exit();
            }
            
            // Update role
            $stmt = $db->prepare("
                UPDATE roles 
                SET name = ?, description = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$name, $description, $id]);
            
            // Check if update was successful
            if ($stmt->rowCount() > 0) {
                $_SESSION['role_success'] = 'زانیارییەکانی ڕۆڵ بە سەرکەوتوویی نوێ کرانەوە';
            } else {
                $_SESSION['role_warning'] = 'هیچ گۆڕانکارییەک نەکرا';
            }
        } catch (PDOException $e) {
            $_SESSION['role_error'] = 'هەڵەیەک ڕوویدا: ' . $e->getMessage();
        }
    } else {
        $_SESSION['role_error'] = implode('<br>', $errors);
    }
    
    // Redirect back to roles page
    header('Location: ../../pages/roles.php');
    exit();
} else {
    // Not a POST request, redirect to roles page
    header('Location: ../../pages/roles.php');
    exit();
} 