<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

// Check permission to delete transactions 
// We need a higher permission level for permanent deletion
if (!hasPermission('delete_transactions')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت مامەڵە بسڕیتەوە بەتەواوی.']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها پۆستی ڕێپێدراوە']);
    exit();
}

// Check if transaction ID is provided
if (!isset($_POST['transaction_id']) || empty($_POST['transaction_id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی مامەڵە پێویستە']);
    exit();
}

$transaction_id = intval($_POST['transaction_id']);

try {
    $db = Database::getInstance();
    $db->beginTransaction();
    
    // First check if transaction exists and is already soft-deleted
    $checkStmt = $db->prepare("SELECT * FROM transactions WHERE id = :id AND is_deleted = 1");
    $checkStmt->bindParam(':id', $transaction_id);
    $checkStmt->execute();
    
    $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'مامەڵەکە نەدۆزرایەوە یان پێشتر سڕاوەتەوە']);
        exit();
    }
    
    // Get receipt files before deleting
    $fileStmt = $db->prepare("SELECT file_path FROM transaction_files WHERE transaction_id = :transaction_id");
    $fileStmt->bindParam(':transaction_id', $transaction_id);
    $fileStmt->execute();
    $files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Delete transaction files from database
    $deleteFilesStmt = $db->prepare("DELETE FROM transaction_files WHERE transaction_id = :transaction_id");
    $deleteFilesStmt->bindParam(':transaction_id', $transaction_id);
    $deleteFilesStmt->execute();
    
    // Delete transaction from database
    $deleteTransactionStmt = $db->prepare("DELETE FROM transactions WHERE id = :id");
    $deleteTransactionStmt->bindParam(':id', $transaction_id);
    $deleteTransactionStmt->execute();
    
    // Delete physical files from backup directory
    if (!empty($files)) {
        foreach ($files as $file_path) {
            $backup_path = '../../uploads/receipts_backup/' . basename($file_path);
            if (file_exists($backup_path)) {
                @unlink($backup_path);
            }
        }
    }
    
    // If there's a backup in transactions_backup, keep it (for history)
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'مامەڵەکە بەتەواوی سڕایەوە']);
    
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
} 