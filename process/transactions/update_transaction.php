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

// Check permission to edit transaction
if (!hasPermission('edit_transaction')) {
    $response = [
        'success' => false,
        'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت دەستکاری مامەڵە بکەیت.'
    ];
    echo json_encode($response);
    exit();
}

// Get post data
$transaction_id = $_POST['transaction_id'] ?? 0;
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
$existing_receipt_files = $_POST['existing_receipt_files'] ?? '[]';

// Validate required fields
if (empty($transaction_id) || empty($type) || empty($amount) || empty($account_type)) {
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
    
    // Get the original transaction data to revert the balances
    $stmt = $conn->prepare("
        SELECT type, amount, customer_id, supplier_id, mixed_account_id, direction 
        FROM transactions 
        WHERE id = :transaction_id
    ");
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    $originalTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$originalTransaction) {
        $conn->rollBack();
        $response = [
            'success' => false,
            'message' => 'مامەڵە نەدۆزرایەوە.'
        ];
        echo json_encode($response);
        exit();
    }
    
    // Revert the original transaction's effect on balances
    if ($originalTransaction['customer_id']) {
        if ($originalTransaction['type'] === 'credit') {
            // Decrease customer's owed amount to revert the credit
            $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount - :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':customer_id', $originalTransaction['customer_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'advance') {
            // Decrease customer's advance payment to revert
            $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment - :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':customer_id', $originalTransaction['customer_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'advance_refund') {
            // Revert advance_refund - add back the customer's advance payment
            $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment + :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':customer_id', $originalTransaction['customer_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'payment') {
            // Revert payment (قەرز دانەوە) - add back the customer's owed amount
            $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount + :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':customer_id', $originalTransaction['customer_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'collection') {
            // Revert collection (قەرز وەرگرتنەوە) - add back the customer's advance payment
            $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment + :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':customer_id', $originalTransaction['customer_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    } elseif ($originalTransaction['supplier_id']) {
        if ($originalTransaction['type'] === 'credit') {
            // Decrease supplier's owed amount to revert the credit
            $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe - :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':supplier_id', $originalTransaction['supplier_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'advance') {
            // Decrease supplier's advance payment to revert
            $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment - :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':supplier_id', $originalTransaction['supplier_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'advance_collection') {
            // Revert advance_collection - add back the supplier's advance payment
            $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment + :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':supplier_id', $originalTransaction['supplier_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'payment') {
            // Revert payment (قەرز دانەوە) - add back the supplier's owed amount
            $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe + :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':supplier_id', $originalTransaction['supplier_id'], PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($originalTransaction['type'] === 'collection') {
            // Revert collection (قەرز وەرگرتنەوە) - add back the supplier's advance payment
            $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment + :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $originalTransaction['amount']);
            $stmt->bindParam(':supplier_id', $originalTransaction['supplier_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    } elseif ($originalTransaction['mixed_account_id']) {
        if ($originalTransaction['direction'] === 'sale') {
            if ($originalTransaction['type'] === 'credit') {
                // Decrease they_owe to revert credit sales
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'advance') {
                // Decrease they_advance to revert
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'advance_refund') {
                // Revert advance_refund - add back we_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'payment') {
                // Revert payment (قەرز دانەوە) - add back they_owe
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'collection') {
                // Revert collection (قەرز وەرگرتنەوە) - add back they_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        } elseif ($originalTransaction['direction'] === 'purchase') {
            if ($originalTransaction['type'] === 'credit') {
                // Decrease we_owe to revert credit purchases
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'advance') {
                // Decrease we_advance to revert
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'advance_collection') {
                // Revert advance_collection - add back they_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'payment') {
                // Revert payment (قەرز دانەوە) - add back we_owe
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($originalTransaction['type'] === 'collection') {
                // Revert collection (قەرز وەرگرتنەوە) - add back we_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance + :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $originalTransaction['amount']);
                $stmt->bindParam(':mixed_account_id', $originalTransaction['mixed_account_id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    // Update the transaction
    $stmt = $conn->prepare("UPDATE transactions SET 
                            type = :type,
                            amount = :amount,
                            date = :date,
                            due_date = :due_date,
                            customer_id = :customer_id,
                            supplier_id = :supplier_id,
                            mixed_account_id = :mixed_account_id,
                            direction = :direction,
                            notes = :notes
                        WHERE id = :transaction_id");
    
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':due_date', $due_date, PDO::PARAM_STR | PDO::PARAM_NULL);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
    $stmt->bindParam(':direction', $direction);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    
    $stmt->execute();
    
    // Update account balances based on the new transaction data
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
                
                // Only add to owed_amount if there's remaining amount after deduction
                if ($remaining_amount > 0) {
                    $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount + :remaining_amount WHERE id = :customer_id");
                    $stmt->bindParam(':remaining_amount', $remaining_amount);
                    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            } else {
                // No advance payment, add full amount to owed_amount
                $stmt = $conn->prepare("UPDATE customers SET owed_amount = owed_amount + :amount WHERE id = :customer_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        } elseif ($type === 'advance') {
            // Increase customer's advance payment
            $stmt = $conn->prepare("UPDATE customers SET advance_payment = advance_payment + :amount WHERE id = :customer_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'advance_refund') {
            // گەڕاندنەوەی پێشەکی - کەمکردنەوەی پێشەکی کڕیار
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
            // قەرز وەرگرتنەوە - کەمکردنەوەی پێشەکی
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
        } elseif ($type === 'advance') {
            // Increase supplier's advance payment
            $stmt = $conn->prepare("UPDATE suppliers SET advance_payment = advance_payment + :amount WHERE id = :supplier_id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($type === 'advance_collection') {
            // پێشەکی وەرگرتنەوە - کەمکردنەوەی پێشەکی دابینکەر
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
            
            if ($supplier['we_owe'] >= $amount) {
                // Decrease we_owe
                $stmt = $conn->prepare("UPDATE suppliers SET we_owe = we_owe - :amount WHERE id = :supplier_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Decrease all we_owe and add remaining to advance_payment
                $remaining = $amount - $supplier['we_owe'];
                
                $stmt = $conn->prepare("UPDATE suppliers SET we_owe = 0, advance_payment = advance_payment + :remaining WHERE id = :supplier_id");
                $stmt->bindParam(':remaining', $remaining);
                $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
            }
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
        if ($direction === 'sale') {
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
            } elseif ($type === 'advance_refund') {
                // گەڕاندنەوەی پێشەکی - کەمکردنەوەی پێشەکی ئێمە
                $stmt = $conn->prepare("SELECT we_advance FROM mixed_accounts WHERE id = :mixed_account_id");
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
                $mixed_account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mixed_account['we_advance'] < $amount) {
                    // ناتوانرێت بڕی گەڕاندنەوەی پێشەکی زیاتر بێت لە پێشەکی
                    $conn->rollBack();
                    $response = [
                        'success' => false,
                        'message' => 'ناتوانرێت بڕی گەڕاندنەوەی پێشەکی زیاتر بێت لە پێشەکی ئێمە. پێشەکی ئێستا: ' . number_format($mixed_account['we_advance'], 0) . ' د.ع'
                    ];
                    echo json_encode($response);
                    exit();
                }
                
                // Decrease we_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($type === 'payment') {
                // قەرز دانەوە - کەمکردنەوەی قەرزی ئەوان
                $stmt = $conn->prepare("SELECT they_owe FROM mixed_accounts WHERE id = :mixed_account_id");
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
                $mixed_account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mixed_account['they_owe'] >= $amount) {
                    // Decrease they_owe
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = they_owe - :amount WHERE id = :mixed_account_id");
                    $stmt->bindParam(':amount', $amount);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    // Decrease all they_owe and add remaining to they_advance
                    $remaining = $amount - $mixed_account['they_owe'];
                    
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_owe = 0, they_advance = they_advance + :remaining WHERE id = :mixed_account_id");
                    $stmt->bindParam(':remaining', $remaining);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            } elseif ($type === 'collection') {
                // قەرز وەرگرتنەوە - کەمکردنەوەی پێشەکی ئەوان
                $stmt = $conn->prepare("SELECT they_advance FROM mixed_accounts WHERE id = :mixed_account_id");
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
                $mixed_account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mixed_account['they_advance'] >= $amount) {
                    // Decrease they_advance
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance - :amount WHERE id = :mixed_account_id");
                    $stmt->bindParam(':amount', $amount);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    // Decrease all they_advance and add remaining to they_owe
                    $remaining = $amount - $mixed_account['they_advance'];
                    
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = 0, they_owe = they_owe + :remaining WHERE id = :mixed_account_id");
                    $stmt->bindParam(':remaining', $remaining);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
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
            } elseif ($type === 'advance_collection') {
                // پێشەکی وەرگرتنەوە - کەمکردنەوەی پێشەکی ئەوان
                $stmt = $conn->prepare("SELECT they_advance FROM mixed_accounts WHERE id = :mixed_account_id");
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
                $mixed_account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mixed_account['they_advance'] < $amount) {
                    // ناتوانرێت بڕی وەرگرتنەوەی پێشەکی زیاتر بێت لە پێشەکی
                    $conn->rollBack();
                    $response = [
                        'success' => false,
                        'message' => 'ناتوانرێت بڕی وەرگرتنەوەی پێشەکی زیاتر بێت لە پێشەکی ئەوان. پێشەکی ئێستا: ' . number_format($mixed_account['they_advance'], 0) . ' د.ع'
                    ];
                    echo json_encode($response);
                    exit();
                }
                
                // Decrease they_advance
                $stmt = $conn->prepare("UPDATE mixed_accounts SET they_advance = they_advance - :amount WHERE id = :mixed_account_id");
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($type === 'payment') {
                // قەرز دانەوە - کەمکردنەوەی قەرزی ئێمە
                $stmt = $conn->prepare("SELECT we_owe FROM mixed_accounts WHERE id = :mixed_account_id");
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
                $mixed_account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mixed_account['we_owe'] >= $amount) {
                    // Decrease we_owe
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = we_owe - :amount WHERE id = :mixed_account_id");
                    $stmt->bindParam(':amount', $amount);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    // Decrease all we_owe and add remaining to we_advance
                    $remaining = $amount - $mixed_account['we_owe'];
                    
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_owe = 0, we_advance = we_advance + :remaining WHERE id = :mixed_account_id");
                    $stmt->bindParam(':remaining', $remaining);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            } elseif ($type === 'collection') {
                // قەرز وەرگرتنەوە - کەمکردنەوەی پێشەکی ئێمە
                $stmt = $conn->prepare("SELECT we_advance FROM mixed_accounts WHERE id = :mixed_account_id");
                $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                $stmt->execute();
                $mixed_account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mixed_account['we_advance'] >= $amount) {
                    // Decrease we_advance
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = we_advance - :amount WHERE id = :mixed_account_id");
                    $stmt->bindParam(':amount', $amount);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    // Decrease all we_advance and add remaining to we_owe
                    $remaining = $amount - $mixed_account['we_advance'];
                    
                    $stmt = $conn->prepare("UPDATE mixed_accounts SET we_advance = 0, we_owe = we_owe + :remaining WHERE id = :mixed_account_id");
                    $stmt->bindParam(':remaining', $remaining);
                    $stmt->bindParam(':mixed_account_id', $mixed_account_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
    }
    
    // Update receipt files
    $existingFiles = json_decode($existing_receipt_files, true);
    $newFiles = json_decode($receipt_files, true);
    
    // Safety: ensure both are arrays
    if (!is_array($existingFiles)) $existingFiles = [];
    if (!is_array($newFiles)) $newFiles = [];
    
    $allFiles = array_merge($existingFiles, $newFiles);
    
    // Get current files from database to compare with submitted files
    $stmt = $conn->prepare("SELECT file_path FROM transaction_files WHERE transaction_id = :transaction_id");
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    $currentFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Find files that were removed (in current DB but not in submitted files)
    $removedFiles = array_diff($currentFiles, $allFiles);
    
    // Delete physical files that are no longer used
    foreach ($removedFiles as $file_path) {
        $full_path = '../../' . $file_path;
        if (file_exists($full_path)) {
            @unlink($full_path); // Use @ to suppress errors if file can't be deleted
        }
    }
    
    // Delete removed files from database
    $stmt = $conn->prepare("DELETE FROM transaction_files WHERE transaction_id = :transaction_id");
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Insert the existing and new files
    if (!empty($allFiles)) {
        foreach ($allFiles as $file_path) {
            $stmt = $conn->prepare("INSERT INTO transaction_files (transaction_id, file_path) VALUES (:transaction_id, :file_path)");
            $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $stmt->bindParam(':file_path', $file_path);
            $stmt->execute();
        }
    }
    
    // After all customer transaction updates, recalculate owed_amount to ensure it is never null
    if ($account_type === 'customer') {
        // Check if this was the only transaction for this customer
        $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE customer_id = :customer_id AND type = 'credit' AND id != :transaction_id");
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();
        $other_transactions = $stmt->fetchColumn();

        // Get the initial customer data to see if they had an initial debt
        $stmt = $conn->prepare("SELECT created_at FROM customers WHERE id = :customer_id");
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $customer_created_at = $stmt->fetchColumn();
        
        // Get the earliest transaction date for this customer
        $stmt = $conn->prepare("SELECT MIN(created_at) FROM transactions WHERE customer_id = :customer_id AND type = 'credit'");
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $first_transaction_date = $stmt->fetchColumn();
        
        // Calculate a 5-minute window to determine if the first transaction was likely during customer creation
        $time_diff = strtotime($first_transaction_date) - strtotime($customer_created_at);
        $is_initial_transaction = ($time_diff < 300); // 5 minutes
        
        if ($other_transactions == 0 && $is_initial_transaction) {
            // This was the initial transaction, treat it as the initial debt
            // Don't recalculate, leave the current amount as is
        } else {
            // For normal cases, recalculate based on all credit transactions
            $stmt = $conn->prepare("SELECT SUM(amount - IFNULL(paid_amount, 0)) FROM transactions WHERE customer_id = :customer_id AND type = 'credit' AND is_deleted = 0");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $new_owed = $stmt->fetchColumn();
            if ($new_owed === null) $new_owed = 0;
            $stmt = $conn->prepare("UPDATE customers SET owed_amount = :owed WHERE id = :customer_id");
            $stmt->bindParam(':owed', $new_owed);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Apply payment to individual credits for payment transactions only (not collection)
    if ($account_type === 'customer' && $type === 'payment') {
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
    
    $conn->commit();
    
    $response = [
        'success' => true,
        'message' => 'مامەڵە بە سەرکەوتوویی نوێکرایەوە.'
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