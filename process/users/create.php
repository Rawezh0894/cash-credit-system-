<?php
// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('add_user')) {
    header('Location: ../../pages/dashboard.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'ناوی تەواو پێویستە';
    }
    
    if (empty($username)) {
        $errors[] = 'ناوی بەکارهێنەر پێویستە';
    }
    
    if (empty($password)) {
        $errors[] = 'وشەی نهێنی پێویستە';
    }
    
    if ($role_id <= 0) {
        $errors[] = 'ڕۆڵی بەکارهێنەر پێویستە';
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if username already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['user_error'] = 'ئەم ناوی بەکارهێنەرە پێشتر بەکار هاتووە';
                header('Location: ../../pages/users.php');
                exit();
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $db->prepare("
                INSERT INTO users (username, password, full_name, role_id, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $username,
                $password_hash,
                $full_name,
                $role_id,
                $is_active,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['user_success'] = 'بەکارهێنەر بە سەرکەوتوویی زیاد کرا';
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