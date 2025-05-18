<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

// Check permission to delete supplier
if (!hasPermission('delete_supplier')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت دابینکەر بسڕیتەوە.']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها پۆستی ڕێپێدراوە']);
    exit();
}

// Check if id is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی دابینکەر پێویستە']);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Check if supplier has associated transactions
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE supplier_id = :id AND (is_deleted = 0 OR is_deleted IS NULL)");
    $checkStmt->bindParam(':id', $_POST['id']);
    $checkStmt->execute();
    $transactionCount = $checkStmt->fetchColumn();
    
    if ($transactionCount > 0) {
        echo json_encode(['success' => false, 'message' => "ناتوانیت ئەم دابینکەرە بسڕیتەوە چونکە مامەڵەی بۆ تۆمار کراوە. دەبێت سەرەتا هەموو مامەڵەکان بسڕیتەوە."]);
        exit();
    }
    
    // Delete supplier
    $sql = "DELETE FROM suppliers WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'دابینکەر بە سەرکەوتوویی سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'دابینکەر نەدۆزرایەوە']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
} 