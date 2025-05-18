<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('delete_user')) {
    header('Location: ../../pages/dashboard.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID
    $id = (int)($_POST['id'] ?? 0);
    
    // Validate input
    $errors = [];
    
    if ($id <= 0) {
        $errors[] = 'ناسنامەی بەکارهێنەر دروست نییە';
    }
    
    // Prevent deleting own account
    if ($id == $_SESSION['user_id']) {
        $errors[] = 'ناتوانیت هەژماری خۆت بسڕیتەوە';
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if user exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                $_SESSION['user_error'] = 'بەکارهێنەر نەدۆزرایەوە';
                header('Location: ../../pages/users.php');
                exit();
            }
            
            // Delete user
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            // Check if deletion was successful
            if ($stmt->rowCount() > 0) {
                $_SESSION['user_success'] = 'بەکارهێنەر بە سەرکەوتوویی سڕایەوە';
            } else {
                $_SESSION['user_warning'] = 'هیچ گۆڕانکارییەک نەکرا';
            }
        } catch (PDOException $e) {
            // Check if error is due to foreign key constraint
            if ($e->getCode() == '23000') {
                $_SESSION['user_error'] = 'ناتوانرێت ئەم بەکارهێنەرە بسڕدرێتەوە چونکە هێشتا پەیوەندی بە داتاکانی دیکەوە هەیە';
            } else {
                $_SESSION['user_error'] = 'هەڵەیەک ڕوویدا: ' . $e->getMessage();
            }
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