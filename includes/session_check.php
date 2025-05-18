<?php
/**
 * Session management helper
 * Include this file at the beginning of process files
 * to ensure consistent session handling
 */

// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function checkUserLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        // For AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'تکایە دووبارە بچۆرەوە ژوورەوە'
            ]);
            exit;
        }
        
        // For regular requests
        header('Location: ' . determineLoginRedirect());
        exit;
    }
    
    return true;
}

// Determine the correct login redirect path based on the current script location
function determineLoginRedirect() {
    $script_path = $_SERVER['SCRIPT_NAME'];
    
    // If we're in a process file, we need to go up two levels
    if (strpos($script_path, '/process/') !== false) {
        return '../../login.php';
    }
    
    // If we're in pages directory, go up one level
    if (strpos($script_path, '/pages/') !== false) {
        return '../login.php';
    }
    
    // Otherwise we're probably at the root
    return 'login.php';
} 