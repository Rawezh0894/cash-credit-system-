<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission to delete customer
if (!hasPermission('delete_customer')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت کڕیار بسڕیتەوە.']);
    exit();
}

// Get ID from either GET or POST
$id = 0;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
}

if ($id > 0) {
    $db = Database::getInstance();
    
    // Check if customer has associated transactions
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE customer_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
    $checkStmt->execute([$id]);
    $transactionCount = $checkStmt->fetchColumn();
    
    if ($transactionCount > 0) {
        echo json_encode(['success' => false, 'message' => "ناتوانیت ئەم کڕیارە بسڕیتەوە چونکە مامەڵەی بۆ تۆمار کراوە. دەبێت سەرەتا هەموو مامەڵەکان بسڕیتەوە."]);
        exit();
    }
    
    $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => "کڕیار بە سەرکەوتوویی سڕایەوە."]);
    } else {
        echo json_encode(['success' => false, 'message' => "هەڵە لە سڕینەوەی کڕیار."]);
    }
    exit();
}
echo json_encode(['success' => false, 'message' => 'ID not provided']);
exit(); 