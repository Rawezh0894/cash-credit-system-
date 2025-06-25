<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = Database::getInstance();

// Check if a specific customer is requested
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT c.*, t.type_name as customer_type_name FROM customers c LEFT JOIN customer_types t ON c.customer_type_id = t.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo json_encode(['success' => true, 'data' => [$customer]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
    }
    exit();
}

// --- Server-side search by column and value ---
if (!empty($_GET['search_column']) && !empty($_GET['search_value'])) {
    $allowedColumns = ['name', 'phone1', 'owed_amount', 'advance_payment', 'city', 'location'];
    $searchColumn = $_GET['search_column'];
    $searchValue = $_GET['search_value'];
    
    if (!in_array($searchColumn, $allowedColumns)) {
        echo json_encode(['success' => false, 'message' => 'Invalid search column']);
        exit();
    }
    
    // Simple search without duplicate filtering - returns all matching records
    $sql = "SELECT c.*, t.type_name as customer_type_name 
            FROM customers c 
            LEFT JOIN customer_types t ON c.customer_type_id = t.id 
            WHERE c." . $searchColumn . " LIKE ? 
            ORDER BY c.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['%' . $searchValue . '%']);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $customers, 'totalPages' => 1]);
    exit();
}

// Pagination settings
$recordsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Filtering by customer_type_name
$where = [];
$params = [];
if (!empty($_GET['customer_type_name'])) {
    $where[] = 't.type_name = ?';
    $params[] = $_GET['customer_type_name'];
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get total records count (with filter)
$countSql = "SELECT COUNT(*) FROM customers c LEFT JOIN customer_types t ON c.customer_type_id = t.id $whereSql";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get paginated records (with filter)
$sql = "SELECT c.*, t.type_name as customer_type_name FROM customers c LEFT JOIN customer_types t ON c.customer_type_id = t.id $whereSql ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $idx => $val) {
    $stmt->bindValue($idx + 1, $val);
}
$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $customers, 'totalPages' => $totalPages]);
exit();
?>