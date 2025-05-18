<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and permission functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions/permissions.php';

// Set JSON content type for API response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'تکایە دووبارە بچۆرەوە ژوورەوە',
        'has_permission' => false
    ]);
    exit;
}

// Check if permission parameter is provided
if (!isset($_GET['check']) || empty($_GET['check'])) {
    echo json_encode([
        'success' => false,
        'message' => 'هیچ ڕێگەپێدانێک دیاری نەکراوە',
        'has_permission' => false
    ]);
    exit;
}

$permission = $_GET['check'];

// Check if user has the requested permission
$has_permission = hasPermission($permission);

// Return the result
echo json_encode([
    'success' => true,
    'has_permission' => $has_permission,
    'permission' => $permission
]);
exit; 