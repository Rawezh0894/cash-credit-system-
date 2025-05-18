<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'cash_credit_db';
$username = 'root';
$password = '';

try {
    // Connect to database
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>بەڕێکخستنی ئەکاونتی ئەدمین</h1>";
    
    // Admin credentials
    $admin_username = 'ashkan@5678';
    $admin_password = 'Ashkan-koga5678';
    $admin_full_name = 'Super Admin';
    $admin_role_id = 1;
    
    // Hash the password using PASSWORD_DEFAULT algorithm
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    echo "ناوی بەکارهێنەر: $admin_username<br>";
    echo "وشەی نهێنی ئەسڵی: $admin_password<br>";
    echo "وشەی نهێنی هاشکراو: $hashed_password<br><br>";
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$admin_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update user
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
        $result = $stmt->execute([$hashed_password, $admin_username]);
        
        if ($result) {
            echo "پاسۆردی ئەدمین بە سەرکەوتوویی نوێکرایەوە (ID: " . $user['id'] . ")<br>";
        } else {
            echo "هەڵە لە نوێکردنەوەی پاسۆردی ئەدمین<br>";
        }
    } else {
        // Add new user
        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role_id, is_active) VALUES (?, ?, ?, ?, 1)");
        $result = $stmt->execute([$admin_username, $hashed_password, $admin_full_name, $admin_role_id]);
        
        if ($result) {
            echo "ئەکاونتی ئەدمین بە سەرکەوتوویی دروست کرا<br>";
        } else {
            echo "هەڵە لە دروستکردنی ئەکاونتی ئەدمین<br>";
        }
    }
    
    // Verify password
    $stmt = $db->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$admin_username]);
    $stored_hash = $stmt->fetchColumn();
    
    if ($stored_hash) {
        echo "<br>پاسۆردی خەزنکراو لە داتابەیس: $stored_hash<br>";
        
        $password_verify_result = password_verify($admin_password, $stored_hash);
        echo "دڵنیایی پاسۆرد: " . ($password_verify_result ? "سەرکەوتوو" : "ناسەرکەوتوو") . "<br>";
        
        if (!$password_verify_result) {
            echo "هۆکاری ناسەرکەوتوویی: پاسۆردەکە بە دروستی هاش نەکراوە<br>";
        }
    } else {
        echo "<br>هەڵە لە خوێندنەوەی پاسۆرد لە داتابەیس<br>";
    }
    
    echo "<br><a href='../test_login.php'>تاقیکردنەوەی چوونەژوورەوە</a>";
    
} catch (PDOException $e) {
    echo "هەڵەی داتابەیس: " . $e->getMessage();
} 