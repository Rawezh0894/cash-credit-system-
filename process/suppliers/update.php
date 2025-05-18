<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

// Check permission to edit supplier
if (!hasPermission('edit_supplier')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەپێدانی ناتەواو. تۆ ناتوانیت دەستکاری دابینکەر بکەیت.']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها پۆستی ڕێپێدراوە']);
    exit();
}

// Check if supplier_id is provided
if (!isset($_POST['supplier_id']) || empty($_POST['supplier_id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی دابینکەر پێویستە']);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Prepare data
    $data = [
        'id' => $_POST['supplier_id'],
        'name' => trim($_POST['name']),
        'phone1' => trim($_POST['phone1']),
        'phone2' => !empty($_POST['phone2']) ? trim($_POST['phone2']) : null,
        'we_owe' => !empty($_POST['we_owe']) ? $_POST['we_owe'] : 0,
        'advance_payment' => !empty($_POST['advance_payment']) ? $_POST['advance_payment'] : 0,
        'city' => trim($_POST['city']),
        'location' => $_POST['location'],
        'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null
    ];
    
    // Update supplier
    $sql = "UPDATE suppliers SET 
            name = :name, 
            phone1 = :phone1, 
            phone2 = :phone2, 
            we_owe = :we_owe, 
            advance_payment = :advance_payment, 
            city = :city, 
            location = :location, 
            notes = :notes 
            WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $data['id']);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':phone1', $data['phone1']);
    $stmt->bindParam(':phone2', $data['phone2']);
    $stmt->bindParam(':we_owe', $data['we_owe']);
    $stmt->bindParam(':advance_payment', $data['advance_payment']);
    $stmt->bindParam(':city', $data['city']);
    $stmt->bindParam(':location', $data['location']);
    $stmt->bindParam(':notes', $data['notes']);
    
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'دابینکەر بە سەرکەوتوویی نوێ کرایەوە']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
}
