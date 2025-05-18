<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

// Check permission to delete mixed account
if (!hasPermission('delete_mixed_account')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت حسابی تێکەڵاو بسڕیتەوە.']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها پۆستی ڕێپێدراوە']);
    exit();
}

// Check if id is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی حساب پێویستە']);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Check if mixed account has associated transactions
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE mixed_account_id = :id AND (is_deleted = 0 OR is_deleted IS NULL)");
    $checkStmt->bindParam(':id', $_POST['id']);
    $checkStmt->execute();
    $transactionCount = $checkStmt->fetchColumn();
    
    if ($transactionCount > 0) {
        echo json_encode(['success' => false, 'message' => "ناتوانیت ئەم هەژمارە تێکەڵە بسڕیتەوە چونکە مامەڵەی بۆ تۆمار کراوە. دەبێت سەرەتا هەموو مامەڵەکان بسڕیتەوە."]);
        exit();
    }
    
    // Delete mixed account
    $sql = "DELETE FROM mixed_accounts WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'حساب بە سەرکەوتوویی سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حساب نەدۆزرایەوە']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
} 