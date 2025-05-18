<?php
/**
 * Function to restore a soft-deleted transaction
 * 
 * @param int $transaction_id The ID of the transaction to restore
 * @return bool True if restoration was successful
 * @throws Exception If an error occurs during restoration
 */
function restoreTransaction($transaction_id) {
    try {
        $conn = Database::getInstance();
        
        // Start transaction
        $conn->beginTransaction();
        
        // Get transaction details before restoration
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND is_deleted = 1");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            throw new Exception("مامەڵەی سڕاوە نەدۆزرایەوە");
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
        
        // Update account balances based on transaction type and account type
        $amount = floatval($transaction['amount']);
        
        if ($account_type === 'customer') {
            $customer_id = $transaction['customer_id'];
            
            // Update customer balance based on transaction type
            switch ($transaction['type']) {
                case 'credit': // Add debt back to customer
                    $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount + ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
                    
                case 'cash': // For cash sales, nothing to adjust
                    // No balance adjustment needed for cash transactions
                    break;
                    
                case 'collection': // Remove debt from customer
                    $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount - ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
                    
                case 'advance': // Add advance payment back
                    $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment + ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
                    
                case 'advance_refund': // Remove advance payment
                    $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment - ? WHERE id = ?");
                    $stmt->execute([$amount, $customer_id]);
                    break;
            }
        } 
        else if ($account_type === 'supplier') {
            $supplier_id = $transaction['supplier_id'];
            
            // Update supplier balance based on transaction type
            switch ($transaction['type']) {
                case 'credit': // Add debt back to what we owe
                    $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe + ? WHERE id = ?");
                    $stmt->execute([$amount, $supplier_id]);
                    break;
                    
                case 'cash': // For cash purchases, nothing to adjust
                    // No balance adjustment needed for cash transactions
                    break;
                    
                case 'payment': // Remove debt from what we owe
                    $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe - ? WHERE id = ?");
                    $stmt->execute([$amount, $supplier_id]);
                    break;
                    
                case 'advance': // Add advance payment back
                    $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment + ? WHERE id = ?");
                    $stmt->execute([$amount, $supplier_id]);
                    break;
                    
                case 'advance_collection': // Remove advance payment
                    $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment - ? WHERE id = ?");
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
                        // Add debt back to them
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe + ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    } else if ($direction === 'purchase') {
                        // Add debt back to us
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe + ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    }
                    break;
                    
                case 'cash':
                    // No balance adjustment needed for cash transactions
                    break;
                    
                case 'collection':
                    // Remove debt from them
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe - ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
                    
                case 'payment':
                    // Remove debt from us
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe - ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
                    
                case 'advance':
                    if ($direction === 'sale') {
                        // Add our advance to them
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance + ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    } else if ($direction === 'purchase') {
                        // Add their advance to us
                        $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance + ? WHERE id = ?");
                        $stmt->execute([$amount, $mixed_account_id]);
                    }
                    break;
                    
                case 'advance_refund':
                    // Remove our advance
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance - ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
                    
                case 'advance_collection':
                    // Remove their advance
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance - ? WHERE id = ?");
                    $stmt->execute([$amount, $mixed_account_id]);
                    break;
            }
        }
        
        // Restore receipt files if they were moved to backup
        if (!empty($transaction['receipt_files'])) {
            $receipts = json_decode($transaction['receipt_files'], true);
            $backup_dir = "../../uploads/receipts_backup";
            
            foreach ($receipts as $receipt) {
                $backup_path = $backup_dir . "/" . $receipt;
                $original_path = "../../uploads/receipts/" . $receipt;
                
                // Check if backup file exists
                if (file_exists($backup_path)) {
                    // Move from backup to original location
                    if (!rename($backup_path, $original_path)) {
                        error_log("Failed to move file: " . $backup_path . " to " . $original_path);
                        // Continue anyway, this is not a critical error
                    }
                }
            }
        }
        
        // Restore the transaction (remove is_deleted flag)
        $stmt = $conn->prepare("UPDATE transactions SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
        $stmt->execute([$transaction_id]);
        
        // Commit transaction
        $conn->commit();
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error in restoreTransaction: " . $e->getMessage());
        throw $e;
    }
} 