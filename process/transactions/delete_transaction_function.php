<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if user has permission to delete transactions
requirePermission('delete_transaction');

/**
 * Deletes a transaction and adjusts account balances accordingly
 * 
 * @param int $transaction_id The ID of the transaction to delete
 * @return bool True if deletion was successful
 * @throws Exception If an error occurs during deletion
 */
function deleteTransaction($transaction_id) {
    try {
        $conn = Database::getInstance();
        
        // Start transaction
        $conn->beginTransaction();
        
        // Get transaction details before deletion
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            throw new Exception("مامەڵەکە نەدۆزرایەوە");
        }
        
        // Determine account type based on the transaction data
        $account_type = '';
        if ($transaction['customer_id']) {
            $account_type = 'customer';
        } else if ($transaction['supplier_id']) {
            $account_type = 'supplier';
        } else if ($transaction['mixed_account_id']) {
            $account_type = 'mixed';
        }
        
        // Create backup of transaction with original_id
        $stmt = $conn->prepare("INSERT INTO transactions_backup (original_id, type, amount, date, account_type, customer_id, supplier_id, mixed_account_id, direction, notes, receipt_files, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $transaction['id'],
            $transaction['type'],
            $transaction['amount'],
            $transaction['date'],
            $account_type,
            $transaction['customer_id'],
            $transaction['supplier_id'],
            $transaction['mixed_account_id'],
            $transaction['direction'],
            $transaction['notes'],
            $transaction['receipt_files'],
            $transaction['created_at'],
            $transaction['updated_at']
        ]);
        
        // Update account balances based on transaction type and account type
        $amount = floatval($transaction['amount']);
        
        if ($account_type === 'customer') {
            $customer_id = $transaction['customer_id'];
            
            // Update customer balance based on transaction type
            switch ($transaction['type']) {
                case 'credit': // Remove debt from customer
                    $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount - ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
                    
                case 'cash': // For cash sales, nothing to adjust
                    // No balance adjustment needed for cash transactions
                    break;
                    
                case 'collection': // Add debt back to customer
                    $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount + ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
                    
                case 'advance': // Remove advance payment
                    $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment - ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
                    
                case 'advance_refund': // Add advance payment back
                    $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment + ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
            }
        } 
        else if ($account_type === 'supplier') {
            $supplier_id = $transaction['supplier_id'];
            
            // Update supplier balance based on transaction type
            switch ($transaction['type']) {
                case 'credit': // Remove debt to supplier
                    $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe - ? WHERE id = ?");
                    $stmt->execute([$amount, $supplier_id]);
                    break;
                    
                case 'cash': // For cash purchases, nothing to adjust
                    // No balance adjustment needed for cash transactions
                    break;
                    
                case 'payment': // Add debt back to what we owe
                    $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe + ? WHERE id = ?");
                    $stmt->execute([$amount, $supplier_id]);
                    break;
                    
                case 'advance': // Remove advance payment
                    $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment - ? WHERE id = ?");
                    $stmt->execute([$amount, $supplier_id]);
                    break;
                    
                case 'advance_collection': // Add advance payment back
                    $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment + ? WHERE id = ?");
                    $stmt->execute([$amount, $supplier_id]);
                    break;
            }
        }
        else if ($account_type === 'mixed') {
            $mixed_account_id = $transaction['mixed_account_id'];
            $direction = $transaction['direction']; // 'sale' or 'purchase'
            
            // Update mixed account balance based on transaction type and direction
            switch ($transaction['type']) {
                case 'credit':
                    if ($direction === 'sale') {
                        // Remove debt from them
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe - ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    } else if ($direction === 'purchase') {
                        // Remove debt from us
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe - ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    }
                    break;
                    
                case 'cash':
                    // No balance adjustment needed for cash transactions
                    break;
                    
                case 'collection':
                    // Add debt back to them
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe + ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
                    
                case 'payment':
                    // Add debt back to us
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe + ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
                    
                case 'advance':
                    if ($direction === 'sale') {
                        // Remove our advance to them
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance - ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    } else if ($direction === 'purchase') {
                        // Remove their advance to us
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance - ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    }
                    break;
                    
                case 'advance_refund':
                    // Add back our advance
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance + ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
                    
                case 'advance_collection':
                    // Add back their advance
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance + ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
            }
        }
        
        // Soft delete the transaction
        $stmt = $conn->prepare("UPDATE transactions SET is_deleted = 1, deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$transaction_id]);
        
        // If transaction has receipts, move them to backup
        if (!empty($transaction['receipt_files'])) {
            $receipts = json_decode($transaction['receipt_files'], true);
            $backup_dir = "../../uploads/receipts_backup";
            
            // Create backup directory if it doesn't exist
            if (!file_exists($backup_dir)) {
                if (!mkdir($backup_dir, 0777, true)) {
                    throw new Exception("نەتوانرا فۆڵدەری backup دروست بکرێت: " . $backup_dir);
                }
            }
            
            foreach ($receipts as $receipt) {
                $old_path = "../../uploads/receipts/" . $receipt;
                $new_path = $backup_dir . "/" . $receipt;
                
                // Log file paths for debugging
                error_log("Moving receipt from: " . $old_path . " to: " . $new_path);
                
                // Check if source file exists
                if (!file_exists($old_path)) {
                    error_log("Source file does not exist: " . $old_path);
                    continue;
                }
                
                // Check if destination directory exists
                if (!is_dir($backup_dir)) {
                    error_log("Backup directory does not exist: " . $backup_dir);
                    continue;
                }
                
                // Check if destination directory is writable
                if (!is_writable($backup_dir)) {
                    error_log("Backup directory is not writable: " . $backup_dir);
                    continue;
                }
                
                // Move file to backup
                if (!rename($old_path, $new_path)) {
                    error_log("Failed to move file: " . $old_path . " to " . $new_path);
                    throw new Exception("نەتوانرا فایلی پسووڵە بگوازرێتەوە: " . $receipt);
                }
                
                error_log("Successfully moved receipt: " . $receipt);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error in deleteTransaction: " . $e->getMessage());
        throw $e;
    }
}

// Handle AJAX requests to delete transactions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set JSON header first to prevent any HTML output
    header('Content-Type: application/json');
    
    try {
        if (!isset($_POST['transaction_id'])) {
            throw new Exception("نەخشەی مامەڵەکە دیاری نەکراوە");
        }
        
        $transaction_id = $_POST['transaction_id'];
        
        // Disable error reporting to prevent HTML output
        error_reporting(0);
        
        // Attempt to delete the transaction
        deleteTransaction($transaction_id);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => "مامەڵەکە بە سەرکەوتوویی سڕایەوە"
        ]);
        exit();
        
    } catch (Exception $e) {
        // Return error response
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "هەڵەیەک ڕوویدا: " . $e->getMessage()
        ]);
        exit();
    }
}
?> 