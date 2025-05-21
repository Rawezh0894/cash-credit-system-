<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission to add customer
if (!hasPermission('add_customer')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت کڕیار زیاد بکەیت.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    try {
        $data = [
            'customer_type_id' => isset($_POST['customer_type_id']) && is_numeric($_POST['customer_type_id']) ? (int)$_POST['customer_type_id'] : null,
            'name' => trim($_POST['name']),
            'phone1' => trim($_POST['phone1']),
            'phone2' => !empty($_POST['phone2']) ? trim($_POST['phone2']) : null,
            'guarantor_name' => !empty($_POST['guarantor_name']) ? trim($_POST['guarantor_name']) : null,
            'guarantor_phone' => !empty($_POST['guarantor_phone']) ? trim($_POST['guarantor_phone']) : null,
            'owed_amount' => !empty($_POST['owed_amount']) ? (float)$_POST['owed_amount'] : 0,
            'advance_payment' => !empty($_POST['advance_payment']) ? (float)$_POST['advance_payment'] : 0,
            'city' => !empty($_POST['city']) ? trim($_POST['city']) : '',
            'location' => $_POST['location'],
            'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null,
            'created_by' => $_SESSION['user_id']
        ];
        
        // Check for duplicate phone1
        $checkSql = "SELECT COUNT(*) FROM customers WHERE phone1 = :phone1";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':phone1', $data['phone1']);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایلە پێشتر تۆمارکراوە.']);
            exit();
        }
        
        $sql = "INSERT INTO customers (customer_type_id, name, phone1, phone2, guarantor_name, guarantor_phone, owed_amount, advance_payment, city, location, notes, created_by) 
                VALUES (:customer_type_id, :name, :phone1, :phone2, :guarantor_name, :guarantor_phone, :owed_amount, :advance_payment, :city, :location, :notes, :created_by)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':customer_type_id', $data['customer_type_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':phone1', $data['phone1']);
        $stmt->bindParam(':phone2', $data['phone2']);
        $stmt->bindParam(':guarantor_name', $data['guarantor_name']);
        $stmt->bindParam(':guarantor_phone', $data['guarantor_phone']);
        $stmt->bindParam(':owed_amount', $data['owed_amount']);
        $stmt->bindParam(':advance_payment', $data['advance_payment']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        $stmt->execute();
        $customer_id = $db->lastInsertId();
        
        // If owed_amount > 0, insert an initial credit transaction for this customer
        if ($data['owed_amount'] > 0) {
            $transactionSql = "INSERT INTO transactions (type, amount, date, account_type, customer_id, notes, created_by) VALUES (:type, :amount, :date, :account_type, :customer_id, :notes, :created_by)";
            $transactionStmt = $db->prepare($transactionSql);
            $transactionType = 'credit';
            $transactionAmount = $data['owed_amount'];
            $transactionDate = date('Y-m-d');
            $transactionAccountType = 'customer';
            $transactionNotes = 'قەرزی سەرەتایی کاتێک زیادکرا';
            $transactionCreatedBy = $data['created_by'];
            $transactionStmt->bindParam(':type', $transactionType);
            $transactionStmt->bindParam(':amount', $transactionAmount);
            $transactionStmt->bindParam(':date', $transactionDate);
            $transactionStmt->bindParam(':account_type', $transactionAccountType);
            $transactionStmt->bindParam(':customer_id', $customer_id);
            $transactionStmt->bindParam(':notes', $transactionNotes);
            $transactionStmt->bindParam(':created_by', $transactionCreatedBy);
            $transactionStmt->execute();
        }
        echo json_encode(['success' => true, 'message' => "کڕیار بە سەرکەوتوویی زیاد کرا."]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "هەڵە: " . $e->getMessage()]);
    }
    exit();
}
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit(); 