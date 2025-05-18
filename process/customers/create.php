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
        
        $sql = "INSERT INTO customers (name, phone1, phone2, guarantor_name, guarantor_phone, owed_amount, advance_payment, city, location, notes, created_by) 
                VALUES (:name, :phone1, :phone2, :guarantor_name, :guarantor_phone, :owed_amount, :advance_payment, :city, :location, :notes, :created_by)";
        
        $stmt = $db->prepare($sql);
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
        echo json_encode(['success' => true, 'message' => "کڕیار بە سەرکەوتوویی زیاد کرا."]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "هەڵە: " . $e->getMessage()]);
    }
    exit();
}
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit(); 