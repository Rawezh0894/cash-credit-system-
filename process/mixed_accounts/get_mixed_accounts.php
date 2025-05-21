<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'تکایە سەرەتا بچۆرەوە']);
    exit();
}

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $offset = ($page - 1) * $perPage;
    
    // Build query
    $query = "SELECT ma.*, mt.name as mixed_type_name 
              FROM mixed_accounts ma 
              LEFT JOIN mixed_types mt ON ma.mixed_type_id = mt.id 
              WHERE ma.deleted_at IS NULL";
    
    // Add filters if provided
    if (isset($_GET['mixed_type_name']) && !empty($_GET['mixed_type_name'])) {
        $query .= " AND mt.name = :mixed_type_name";
    }
    
    // Get total count for pagination
    $countQuery = str_replace("ma.*, mt.name as mixed_type_name", "COUNT(*) as total", $query);
    $stmt = $conn->prepare($countQuery);
    if (isset($_GET['mixed_type_name']) && !empty($_GET['mixed_type_name'])) {
        $stmt->bindParam(':mixed_type_name', $_GET['mixed_type_name']);
    }
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Add pagination to main query
    $query .= " ORDER BY ma.id DESC LIMIT :offset, :per_page";
    
    // Execute main query
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
    if (isset($_GET['mixed_type_name']) && !empty($_GET['mixed_type_name'])) {
        $stmt->bindParam(':mixed_type_name', $_GET['mixed_type_name']);
    }
    $stmt->execute();
    $mixed_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total pages
    $totalPages = ceil($total / $perPage);
    
    // Return response
    echo json_encode([
        'success' => true,
        'mixed_accounts' => $mixed_accounts,
        'total' => $total,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $perPage
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی داتاکان: ' . $e->getMessage()
    ]);
} 