<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

// Check permission to add mixed account
if (!hasPermission('add_mixed_account')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت حسابی تێکەڵاو زیاد بکەیت.']);
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
        'guarantor_name' => !empty($_POST['guarantor_name']) ? trim($_POST['guarantor_name']) : null,
        'guarantor_phone' => !empty($_POST['guarantor_phone']) ? trim($_POST['guarantor_phone']) : null,
        'they_owe' => !empty($_POST['they_owe']) ? $_POST['they_owe'] : 0,
        'we_owe' => !empty($_POST['we_owe']) ? $_POST['we_owe'] : 0,
        'they_advance' => !empty($_POST['they_advance']) ? $_POST['they_advance'] : 0,
        'we_advance' => !empty($_POST['we_advance']) ? $_POST['we_advance'] : 0,
        'city' => trim($_POST['city']),
        'location' => $_POST['location'],
        'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null,
        'created_by' => $_SESSION['user_id']
    ];
    
    // Check for duplicate phone1
    $checkSql = "SELECT COUNT(*) FROM mixed_accounts WHERE phone1 = :phone1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':phone1', $data['phone1']);
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'ئەم ژمارەی مۆبایلە پێشتر تۆمارکراوە.']);
        exit();
    }
    
    // Add new mixed account
    $sql = "INSERT INTO mixed_accounts (name, phone1, phone2, guarantor_name, guarantor_phone, they_owe, we_owe, they_advance, we_advance, city, location, notes, created_by) 
            VALUES (:name, :phone1, :phone2, :guarantor_name, :guarantor_phone, :they_owe, :we_owe, :they_advance, :we_advance, :city, :location, :notes, :created_by)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':phone1', $data['phone1']);
    $stmt->bindParam(':phone2', $data['phone2']);
    $stmt->bindParam(':guarantor_name', $data['guarantor_name']);
    $stmt->bindParam(':guarantor_phone', $data['guarantor_phone']);
    $stmt->bindParam(':they_owe', $data['they_owe']);
    $stmt->bindParam(':we_owe', $data['we_owe']);
    $stmt->bindParam(':they_advance', $data['they_advance']);
    $stmt->bindParam(':we_advance', $data['we_advance']);
    $stmt->bindParam(':city', $data['city']);
    $stmt->bindParam(':location', $data['location']);
    $stmt->bindParam(':notes', $data['notes']);
    $stmt->bindParam(':created_by', $data['created_by']);
    
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'حساب بە سەرکەوتوویی زیاد کرا']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
} 