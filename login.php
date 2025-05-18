<?php
session_start();
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = '';
    
    if (empty($username) || empty($password)) {
        $error = 'تکایە هەموو خانەکان پڕ بکەرەوە';
    } else {
        try {
            $db = Database::getInstance();
            
            // Get user from database
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug information
            error_log("Login attempt for: " . $username);
            if ($user) {
                error_log("User found with ID: " . $user['id']);
                error_log("Password verification: " . (password_verify($password, $user['password']) ? "Successful" : "Failed"));
            } else {
                error_log("User not found");
            }
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id'] = $user['role_id'];
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect to dashboard
                header('Location: pages/dashboard.php');
                exit();
            } else {
                $error = 'ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە';
            }
        } catch (PDOException $e) {
            $error = 'هەڵەیەک ڕوویدا، تکایە دووبارە هەوڵ بدەرەوە';
            error_log("Login error: " . $e->getMessage());
        }
    }
    
    // If we got here, there was an error
    $_SESSION['login_error'] = $error;
    header('Location: index.php');
    exit();
} else {
    // Redirect to login page if not POST request
    header('Location: index.php');
    exit();
} 