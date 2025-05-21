<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

try {
    $db = Database::getInstance();
    
    // If id is provided, get single mixed account
    if (isset($_GET['id'])) {
        $sql = "SELECT * FROM mixed_accounts WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        if ($account = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => true, 'data' => [$account]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'حساب نەدۆزرایەوە']);
        }
        exit();
    }
    
    // Get paginated mixed accounts
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $per_page;
    
    // Get total records
    $count_sql = "SELECT COUNT(*) FROM mixed_accounts";
    $total_records = $db->query($count_sql)->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    
    // Get mixed accounts
    $sql = "SELECT * FROM mixed_accounts ORDER BY created_at DESC LIMIT :offset, :per_page";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $accounts,
        'pagination' => [
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
}
