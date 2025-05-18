<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response = [
        'success' => false,
        'message' => 'دەبێت خۆت تۆمار بکەیت بۆ ئەنجامدانی ئەم کردارە.'
    ];
    echo json_encode($response);
    exit();
}

// Check permission to edit transactions (since receipt deletion is part of editing)
if (!hasPermission('edit_transaction')) {
    $response = [
        'success' => false,
        'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت پسووڵەی مامەڵە بسڕیتەوە.'
    ];
    echo json_encode($response);
    exit();
}

// Get file path
$file_path = $_POST['file_path'] ?? '';

if (empty($file_path)) {
    $response = [
        'success' => false,
        'message' => 'ڕێڕەوی فایل پێویستە.'
    ];
    echo json_encode($response);
    exit();
}

try {
    $conn = Database::getInstance();
    $conn->beginTransaction();
    
    // Check if this file is used by any transaction
    $stmt = $conn->prepare("SELECT COUNT(*) FROM transaction_files WHERE file_path = :file_path");
    $stmt->bindParam(':file_path', $file_path);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    // Remove record from database if it exists
    if ($count > 0) {
        $stmt = $conn->prepare("DELETE FROM transaction_files WHERE file_path = :file_path");
        $stmt->bindParam(':file_path', $file_path);
        $stmt->execute();
    }
    
    // Check if the file exists
    $full_path = '../../' . $file_path;
    if (file_exists($full_path)) {
        // Delete the file
        if (unlink($full_path)) {
            $response = [
                'success' => true,
                'message' => 'فایل بە سەرکەوتوویی سڕایەوە.'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی فایل.'
            ];
            $conn->rollBack();
            echo json_encode($response);
            exit();
        }
    } else {
        // File doesn't exist, but we'll still return success
        $response = [
            'success' => true,
            'message' => 'فایل نەدۆزرایەوە یان پێشتر سڕاوەتەوە.'
        ];
    }
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    $response = [
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ];
}

echo json_encode($response);
exit(); 