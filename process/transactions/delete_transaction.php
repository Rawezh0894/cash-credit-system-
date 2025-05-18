<?php
// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// Check permission to delete transaction
if (!hasPermission('delete_transaction')) {
    $response = [
        'success' => false,
        'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت مامەڵە بسڕیتەوە.'
    ];
    echo json_encode($response);
    exit();
}

// Get transaction ID
$transaction_id = $_POST['transaction_id'] ?? 0;

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
    $conn->beginTransaction();
    
    // Get the transaction details before deleting
    $stmt = $conn->prepare("
        SELECT type, amount, customer_id, supplier_id, mixed_account_id, direction 
        FROM transactions 
        WHERE id = :transaction_id
    ");
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        $conn->rollBack();
        $response = [
            'success' => false,
            'message' => 'مامەڵە نەدۆزرایەوە.'
        ];
        echo json_encode($response);
        exit();
    }
    
    // Update account balances based on the transaction type
    if ($transaction['customer_id']) {
        if ($transaction['type'] === 'credit') {
            // Decrease customer's owed amount
            $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount - :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $transaction['amount']);
            $stmt->bindParam(':customer_id', $transaction['customer_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($transaction['type'] === 'advance') {
            // Decrease customer's advance payment
            $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment - :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $transaction['amount']);
            $stmt->bindParam(':customer_id', $transaction['customer_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    } elseif ($transaction['supplier_id']) {
        if ($transaction['type'] === 'credit') {
            // Decrease supplier's owed amount
            $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe - :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $transaction['amount']);
            $stmt->bindParam(':supplier_id', $transaction['supplier_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($transaction['type'] === 'advance') {
            // Decrease supplier's advance payment
            $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment - :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $transaction['amount']);
            $stmt->bindParam(':supplier_id', $transaction['supplier_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    } elseif ($transaction['mixed_account_id']) {
        if ($transaction['direction'] === 'sale') {
            if ($transaction['type'] === 'credit') {
                // Decrease they_owe
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $transaction['amount']);
                $stmt->bindParam(':mixed_account_id', $transaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($transaction['type'] === 'advance') {
                // Decrease they_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $transaction['amount']);
                $stmt->bindParam(':mixed_account_id', $transaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        } elseif ($transaction['direction'] === 'purchase') {
            if ($transaction['type'] === 'credit') {
                // Decrease we_owe
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $transaction['amount']);
                $stmt->bindParam(':mixed_account_id', $transaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($transaction['type'] === 'advance') {
                // Decrease we_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $transaction['amount']);
                $stmt->bindParam(':mixed_account_id', $transaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    // Get receipt files before deleting
    $fileStmt = $conn->prepare("SELECT file_path FROM transaction_files WHERE transaction_id = :transaction_id");
    $fileStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $fileStmt->execute();
    $files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Delete the transaction files from database
    $stmt = $conn->prepare("DELETE FROM transaction_files WHERE transaction_id = :transaction_id");
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Delete the transaction
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = :transaction_id");
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $conn->commit();
    
    // Delete physical files
    if (!empty($files)) {
        foreach ($files as $file_path) {
            $full_path = '../../' . $file_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'مامەڵە بە سەرکەوتوویی سڕایەوە.'
    ];
    
} catch (Exception $e) {
    $conn->rollBack();
    
    $response = [
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ];
}

echo json_encode($response);
exit(); 