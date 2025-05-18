<?php
/**
 * Function to permanently delete a transaction that is already soft-deleted
 * 
 * @param int $transaction_id The ID of the transaction to permanently delete
 * @return bool True if deletion was successful
 * @throws Exception If an error occurs during deletion
 */
function permanentDeleteTransaction($transaction_id) {
    try {
        $db = Database::getInstance();
        $db->beginTransaction();
        
        // First check if transaction exists and is already soft-deleted
        $checkStmt = $db->prepare("SELECT * FROM transactions WHERE id = :id AND is_deleted = 1");
        $checkStmt->bindParam(':id', $transaction_id);
        $checkStmt->execute();
        
        $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception('مامەڵەکە نەدۆزرایەوە یان پێشتر سڕاوەتەوە');
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
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error permanently deleting transaction: " . $e->getMessage());
        throw new Exception($e->getMessage());
    }
} 