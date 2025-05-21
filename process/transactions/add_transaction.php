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

// Check permission to add transaction
if (!hasPermission('add_transaction')) {
    $response = [
        'success' => false,
        'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت مامەڵە زیاد بکەیت.'
    ];
    echo json_encode($response);
    exit();
}

// Get post data
$type = $_POST['type'] ?? '';
$amount = $_POST['amount'] ?? 0;
$date = $_POST['date'] ?? date('Y-m-d');
$due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$account_type = $_POST['account_type'] ?? '';
$customer_id = $_POST['customer_id'] ?? null;
$supplier_id = $_POST['supplier_id'] ?? null;
$mixed_account_id = $_POST['mixed_account_id'] ?? null;
$direction = $_POST['direction'] ?? null;
$notes = $_POST['notes'] ?? '';
$receipt_files = $_POST['receipt_files'] ?? '[]';

// Validate required fields
if (empty($type) || empty($amount) || empty($account_type)) {
    $response = [
        'success' => false,
        'message' => 'تکایە هەموو خانە پێویستەکان پڕبکەوە.'
    ];
    echo json_encode($response);
    exit();
}

// Convert amount to float
$amount = floatval($amount);

// Validate account based on account type
if ($account_type === 'customer' && empty($customer_id)) {
    $response = [
        'success' => false,
        'message' => 'تکایە کڕیارێک هەڵبژێرە.'
    ];
    echo json_encode($response);
    exit();
} elseif ($account_type === 'supplier' && empty($supplier_id)) {
    $response = [
        'success' => false,
        'message' => 'تکایە دابینکەرێک هەڵبژێرە.'
    ];
    echo json_encode($response);
    exit();
 } elseif ($account_type === 'mixed' && (empty($mixed_account_id) || (in_array($type, ['cash','credit']) && empty($direction)))) {
    $response = [
        'success' => false,
        'message' => 'تکایە هەژماری تێکەڵ و ئاڕاستەی مامەڵە هەڵبژێرە.'
    ];
    echo json_encode($response);
    exit();
}

try {
    $conn = Database::getInstance();
    $conn->beginTransaction();

    // Insert transaction
    $stmt = $conn->prepare("INSERT INTO transactions (type, amount, date, due_date, customer_id, supplier_id, mixed_account_id, direction, notes, created_by) 
                            VALUES (:type, :amount, :date, :due_date, :customer_id, :supplier_id, :mixed_account_id, :direction, :notes, :created_by)");
    
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':due_date', $due_date, PDO::PARAM_STR | PDO::PARAM_NULL);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
    $stmt->bindParam(':direction', $direction);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':created_by', $_SESSION['user_id'], PDO::PARAM_INT);
    
    $stmt->execute();
    $transaction_id = $conn->lastInsertId();

    // Update account balances based on transaction type and account type
    if ($account_type === 'customer') {
        if ($type === 'credit') {
            // Check for advance payment and deduct if available
            $stmt = $conn->prepare("SELECT advance_payment FROM customers WHERE id = :customer_id");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $remaining_amount = $amount;
            if ($customer['advance_payment'] > 0) {
                $deduction = min($customer['advance_payment'], $amount);
                $remaining_amount = $amount - $deduction;
                
                // Deduct from advance payment
                $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment - :deduction WHERE id = :customer_id");
                $stmt->bindParam(':deduction', $deduction);
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            // Always add remaining_amount to owed_amount (even if 0)
            if ($remaining_amount > 0) {
                $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount + :remaining_amount WHERE id = :customer_id");
                $stmt->bindParam(':remaining_amount', $remaining_amount);
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        } elseif ($type === 'cash') {
            // No change to balance for cash transaction
        } elseif ($type === 'advance') {
            // Increase customer's advance payment
            $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment + :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'advance_refund') {
            // گەڕاندنەوەی پێشەکی - تەنها we_advance کەم بکە
            $stmt = $conn->prepare("SELECT advance_payment FROM customers WHERE id = :customer_id");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer['advance_payment'] < $amount) {
                // ناتوانرێت بڕی گەڕاندنەوەی پێشەکی زیاتر بێت لە پێشەکی کڕیار
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی گەڕاندنەوەی پێشەکی زیاتر بێت لە پێشەکی کڕیار. پێشەکی ئێستا: ' . number_format($customer['advance_payment'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            
            // Decrease advance_payment
            $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment - :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'payment') {
            // قەرز دانەوە - کەمکردنەوەی قەرز
            $stmt = $conn->prepare("SELECT owed_amount FROM customers WHERE id = :customer_id");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer['owed_amount'] >= $amount) {
                // Decrease owed_amount
                $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount - :amount WHERE id = :customer_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Decrease all owed_amount and add remaining to advance_payment
                $remaining = $amount - $customer['owed_amount'];
                
                $stmt = $conn->prepare("UPDATE customers SET owed_amount = 0, advance_payment = advance_payment + :remaining WHERE id = :customer_id");
                $stmt->bindParam(':remaining', $remaining);
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        } elseif ($type === 'collection') {
            // قەرز وەرگرتنەوە - کەمکردنەوەی قەرز
            $stmt = $conn->prepare("SELECT owed_amount FROM customers WHERE id = :customer_id");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer['owed_amount'] < $amount) {
                // ناتوانرێت بڕی قەرز وەرگرتنەوە زیاتر بێت لە قەرز
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی قەرز وەرگرتنەوە زیاتر بێت لە قەرزی کڕیار. قەرزی ئێستا: ' . number_format($customer['owed_amount'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            // Decrease owed_amount
            $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount - :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        if ($account_type === 'customer' && $type === 'payment') {
            // Only apply credit payments for payment transactions (not collection)
            $remaining = $amount;
            // Get all unpaid credits for this customer, FIFO order
            $stmt = $conn->prepare("SELECT id, amount, IFNULL(paid_amount,0) as paid_amount FROM transactions WHERE customer_id = :customer_id AND type = 'credit' AND (amount - IFNULL(paid_amount,0)) > 0 ORDER BY due_date ASC, id ASC");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($credits as $credit) {
                $credit_remaining = $credit['amount'] - $credit['paid_amount'];
                if ($credit_remaining <= 0) continue;
                $pay = min($remaining, $credit_remaining);
                // Update paid_amount for this credit
                $stmt2 = $conn->prepare("UPDATE transactions SET paid_amount = paid_amount + :pay WHERE id = :id");
                $stmt2->bindParam(':pay', $pay);
                $stmt2->bindParam(':id', $credit['id']);
                $stmt2->execute();
                $remaining -= $pay;
                if ($remaining <= 0) break;
            }
            // If any amount remains, apply to advance_payment
            if ($remaining > 0) {
                $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment + :amount WHERE id = :customer_id");
                $stmt->bindParam(':amount', $remaining);
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        // Also update owed_amount accordingly for all customer transactions
        // (No recalculation! Only add/deduct as above for credit/collection/payment)
    } elseif ($account_type === 'supplier') {
        if ($type === 'credit') {
            // Check for advance payment and deduct if available
            $stmt = $conn->prepare("SELECT advance_payment FROM suppliers WHERE id = :supplier_id");
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $remaining_amount = $amount;
            if ($supplier['advance_payment'] > 0) {
                $deduction = min($supplier['advance_payment'], $amount);
                $remaining_amount = $amount - $deduction;
                
                // Deduct from advance payment
                $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment - :deduction WHERE id = :supplier_id");
                $stmt->bindParam(':deduction', $deduction);
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Only add to we_owe if there's remaining amount after deduction
                if ($remaining_amount > 0) {
                    $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe + :remaining_amount WHERE id = :supplier_id");
                    $stmt->bindParam(':remaining_amount', $remaining_amount);
                    $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            } else {
                // No advance payment, add full amount to we_owe
                $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe + :amount WHERE id = :supplier_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        } elseif ($type === 'cash') {
            // No change to balance for cash transaction
        } elseif ($type === 'advance') {
            // Increase supplier's advance payment
            $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment + :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'advance_collection') {
            // پێشەکی وەرگرتنەوە - تەنها they_advance کەم بکە
            $stmt = $conn->prepare("SELECT advance_payment FROM suppliers WHERE id = :supplier_id");
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($supplier['advance_payment'] < $amount) {
                // ناتوانرێت بڕی وەرگرتنەوەی پێشەکی زیاتر بێت لە پێشەکی دابینکەر
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی وەرگرتنەوەی پێشەکی زیاتر بێت لە پێشەکی دابینکەر. پێشەکی ئێستا: ' . number_format($supplier['advance_payment'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            
            // Decrease advance_payment
            $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment - :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'payment') {
            // قەرز دانەوە - کەمکردنەوەی قەرزی ئێمە
            $stmt = $conn->prepare("SELECT we_owe FROM suppliers WHERE id = :supplier_id");
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($supplier['we_owe'] < $amount) {
                // ناتوانرێت بڕی قەرز دانەوە زیاتر بێت لە قەرزمان
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی قەرز دانەوە زیاتر بێت لە قەرزمان. قەرزی ئێستا: ' . number_format($supplier['we_owe'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            
            // Decrease we_owe
            $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe - :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'collection') {
            // قەرز وەرگرتنەوە - کەمکردنەوەی پێشەکی ئێمە
            $stmt = $conn->prepare("SELECT advance_payment FROM suppliers WHERE id = :supplier_id");
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($supplier['advance_payment'] >= $amount) {
                // Decrease advance_payment
                $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment - :amount WHERE id = :supplier_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Decrease all advance_payment and add remaining to we_owe
                $remaining = $amount - $supplier['advance_payment'];
                
                $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = 0, we_owe = we_owe + :remaining WHERE id = :supplier_id");
                $stmt->bindParam(':remaining', $remaining);
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    } elseif ($account_type === 'mixed') {
        // Get current balances before update
        $stmt = $conn->prepare("SELECT they_owe, we_owe, they_advance, we_advance FROM mixed_accounts WHERE id = :mixed_account_id");
        $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
        $stmt->execute();
        $current_balance = $stmt->fetch(PDO::FETCH_ASSOC);

        // Handle transactions that don't require direction first
        if ($type === 'payment') {
            // For payment, check the balances to determine which one to decrease
            if ($current_balance['we_owe'] < $amount) {
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی قەرز دانەوە زیاتر بێت لە قەرزمان. قەرزی ئێستا: ' . number_format($current_balance['we_owe'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe - :amount WHERE id = :mixed_account_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'collection') {
            // For collection, check the balances to determine which one to decrease
            if ($current_balance['they_owe'] < $amount) {
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی قەرز وەرگرتنەوە زیاتر بێت لە قەرزی ئەوان. قەرزی ئێستا: ' . number_format($current_balance['they_owe'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe - :amount WHERE id = :mixed_account_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'advance_refund') {
            // گەڕاندنەوەی پێشەکی - تەنها we_advance کەم بکە
            if ($current_balance['we_advance'] < $amount) {
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی گەڕاندنەوەی پێشەکی زیاتر بێت لە پێشەکی ئێمە. پێشەکی ئێستا: ' . number_format($current_balance['we_advance'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance - :amount WHERE id = :mixed_account_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'advance_collection') {
            // پێشەکی وەرگرتنەوە - تەنها they_advance کەم بکە
            if ($current_balance['they_advance'] < $amount) {
                $conn->rollBack();
                $response = [
                    'success' => false,
                    'message' => 'ناتوانرێت بڕی وەرگرتنەوەی پێشەکی زیاتر بێت لە پێشەکی ئەوان. پێشەکی ئێستا: ' . number_format($current_balance['they_advance'], 0) . ' د.ع'
                ];
                echo json_encode($response);
                exit();
            }
            $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance - :amount WHERE id = :mixed_account_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($direction === 'sale') {
            if ($type === 'credit') {
                // Increase they_owe for credit sales
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($type === 'advance') {
                // Increase they_advance for advance payment
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($type === 'cash') {
                // No change to balance for cash transaction (sale direction)
                // But we still record it in the transactions table
            }
        } elseif ($direction === 'purchase') {
            if ($type === 'credit') {
                // Increase we_owe for credit purchases
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($type === 'advance') {
                // Increase we_advance for advance payment
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($type === 'cash') {
                // No change to balance for cash transaction (purchase direction)
                // But we still record it in the transactions table
            }
        } elseif ($direction === 'advance_give') {
            if ($type === 'advance') {
                // Increase we_advance for when we give advance to them
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        } elseif ($direction === 'advance_receive') {
            if ($type === 'advance') {
                // Increase they_advance for when they give advance to us
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    // Save receipt files if any
    $receipt_files_array = json_decode($receipt_files, true);
    if (!empty($receipt_files_array)) {
        foreach ($receipt_files_array as $file_path) {
            $stmt = $conn->prepare("INSERT INTO transaction_files (transaction_id, file_path) VALUES (:transaction_id, :file_path)");
            $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $stmt->bindParam(':file_path', $file_path);
            $stmt->execute();
        }
    }

    $conn->commit();
    
    $response = [
        'success' => true,
        'message' => 'مامەڵە بە سەرکەوتوویی زیاد کرا.',
        'transaction_id' => $transaction_id
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