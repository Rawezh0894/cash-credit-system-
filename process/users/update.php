<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('edit_user')) {
    header('Location: ../../pages/dashboard.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = (int)($_POST['id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // Optional, can be empty if not changing
    $role_id = (int)($_POST['role_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if ($id <= 0) {
        $errors[] = 'ناسنامەی بەکارهێنەر دروست نییە';
    }
    
    if (empty($full_name)) {
        $errors[] = 'ناوی تەواو پێویستە';
    }
    
    if (empty($username)) {
        $errors[] = 'ناوی بەکارهێنەر پێویستە';
    }
    
    if ($role_id <= 0) {
        $errors[] = 'ڕۆڵی بەکارهێنەر پێویستە';
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if username already exists for another user
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['user_error'] = 'ئەم ناوی بەکارهێنەرە پێشتر بەکار هاتووە';
                header('Location: ../../pages/users.php');
                exit();
            }
            
            // Start with basic update query
            $updateSql = "
                UPDATE users 
                SET full_name = ?, username = ?, role_id = ?, is_active = ?
                WHERE id = ?
            ";
            $params = [$full_name, $username, $role_id, $is_active, $id];
            
            // If password is provided, hash it and add to update
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $updateSql = "
                    UPDATE users 
                    SET full_name = ?, username = ?, password = ?, role_id = ?, is_active = ?
                    WHERE id = ?
                ";
                $params = [$full_name, $username, $password_hash, $role_id, $is_active, $id];
            }
            
            // Execute update
            $stmt = $db->prepare($updateSql);
            $stmt->execute($params);
            
            // Check if update was successful (affected rows)
            if ($stmt->rowCount() > 0) {
                $_SESSION['user_success'] = 'زانیارییەکانی بەکارهێنەر بە سەرکەوتوویی نوێ کرانەوە';
                
                // If user updated their own account, update session data
                if ($id == $_SESSION['user_id']) {
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                }
            } else {
                $_SESSION['user_warning'] = 'هیچ گۆڕانکارییەک نەکرا';
            }
        } catch (PDOException $e) {
            $_SESSION['user_error'] = 'هەڵەیەک ڕوویدا: ' . $e->getMessage();
        }
    } else {
        $_SESSION['user_error'] = implode('<br>', $errors);
    }
    
    // Redirect back to users page
    header('Location: ../../pages/users.php');
    exit();
} else {
    // Not a POST request, redirect to users page
    header('Location: ../../pages/users.php');
    exit();
} 