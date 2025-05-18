<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

// Check permission to add supplier
if (!hasPermission('add_supplier')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت دابینکەر زیاد بکەیت.']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها پۆستی ڕێپێدراوە']);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Prepare data
    $data = [
        'name' => trim($_POST['name']),
        'phone1' => trim($_POST['phone1']),
        'phone2' => !empty($_POST['phone2']) ? trim($_POST['phone2']) : null,
        'we_owe' => !empty($_POST['we_owe']) ? $_POST['we_owe'] : 0,
        'advance_payment' => !empty($_POST['advance_payment']) ? $_POST['advance_payment'] : 0,
        'city' => trim($_POST['city']),
        'location' => $_POST['location'],
        'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null,
        'created_by' => $_SESSION['user_id']
    ];
    
    // Check for duplicate phone1
    $checkSql = "SELECT COUNT(*) FROM suppliers WHERE phone1 = :phone1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':phone1', $data['phone1']);
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایلە پێشتر تۆمارکراوە.']);
        exit();
    }
    
    // Add new supplier
    $sql = "INSERT INTO suppliers (name, phone1, phone2, we_owe, advance_payment, city, location, notes, created_by) 
            VALUES (:name, :phone1, :phone2, :we_owe, :advance_payment, :city, :location, :notes, :created_by)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':phone1', $data['phone1']);
    $stmt->bindParam(':phone2', $data['phone2']);
    $stmt->bindParam(':we_owe', $data['we_owe']);
    $stmt->bindParam(':advance_payment', $data['advance_payment']);
    $stmt->bindParam(':city', $data['city']);
    $stmt->bindParam(':location', $data['location']);
    $stmt->bindParam(':notes', $data['notes']);
    $stmt->bindParam(':created_by', $data['created_by']);
    
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'دابینکەر بە سەرکەوتوویی زیاد کرا']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
} 