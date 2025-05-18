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
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'ناوی ڕۆڵ پێویستە';
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if role name already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE name = ?");
            $stmt->execute([$name]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['role_error'] = 'ئەم ناوی ڕۆڵە پێشتر بەکار هاتووە';
                header('Location: ../../pages/roles.php');
                exit();
            }
            
            // Insert new role
            $stmt = $db->prepare("
                INSERT INTO roles (name, description)
                VALUES (?, ?)
            ");
            
            $stmt->execute([$name, $description]);
            
            $_SESSION['role_success'] = 'ڕۆڵ بە سەرکەوتوویی زیاد کرا';
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