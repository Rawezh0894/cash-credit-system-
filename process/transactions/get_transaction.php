<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response = [
        'success' => false,
        'message' => 'دەبێت خۆت تۆمار بکەیت بۆ ئەنجامدانی ئەم کردارە.'
    ];
    echo json_encode($response);
    exit();
}

// Get transaction ID
$transaction_id = $_GET['transaction_id'] ?? 0;

if (empty($transaction_id)) {
    $response = [
        'success' => false,
        'message' => 'ناسنامەی مامەڵە پێویستە.'
    ];
    echo json_encode($response);
    exit();
}

try {
    $conn = Database::getInstance();
    
    // Get transaction details
    $stmt = $conn->prepare("
        SELECT 
            t.id, 
            t.type, 
            t.amount, 
            t.date, 
            t.due_date,
            t.customer_id, 
            t.supplier_id, 
            t.mixed_account_id, 
            t.direction, 
            t.notes, 
            t.created_at,
            c.name AS customer_name,
            s.name AS supplier_name,
            m.name AS mixed_account_name
        FROM 
            transactions t
        LEFT JOIN 
            customers c ON t.customer_id = c.id
        LEFT JOIN 
            suppliers s ON t.supplier_id = s.id
        LEFT JOIN 
            mixed_accounts m ON t.mixed_account_id = m.id
        WHERE 
            t.id = :transaction_id
    ");
    
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        $response = [
            'success' => false,
            'message' => 'مامەڵە نەدۆزرایەوە.'
        ];
        echo json_encode($response);
        exit();
    }
    
    // Get receipt files
    $fileStmt = $conn->prepare("
        SELECT file_path 
        FROM transaction_files 
        WHERE transaction_id = :transaction_id
    ");
    $fileStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $fileStmt->execute();
    $files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $transaction['receipt_files'] = $files;
    
    $response = [
        'success' => true,
        'transaction' => $transaction
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ];
}

echo json_encode($response);
exit(); 