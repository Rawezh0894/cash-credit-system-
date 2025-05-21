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

// Pagination settings
$recordsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Build WHERE clause with filters
$where = [];
$params = [];

// Filter by customer type
if (!empty($_GET['customer_type_name'])) {
    $where[] = 't.type_name = ?';
    $params[] = $_GET['customer_type_name'];
}

// Filter by name
if (!empty($_GET['name'])) {
    $where[] = 'c.name LIKE ?';
    $params[] = '%' . $_GET['name'] . '%';
}

// Filter by city
if (!empty($_GET['city'])) {
    $where[] = 'c.city = ?';
    $params[] = $_GET['city'];
}

// Filter by location
if (!empty($_GET['location'])) {
    $location = $_GET['location'] === 'ناو شار' ? 'inside' : 'outside';
    $where[] = 'c.location = ?';
    $params[] = $location;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get total records count (with filters)
$countSql = "SELECT COUNT(*) FROM customers c LEFT JOIN customer_types t ON c.customer_type_id = t.id $whereSql";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get paginated records (with filters)
$sql = "SELECT c.*, t.type_name as customer_type_name FROM customers c LEFT JOIN customer_types t ON c.customer_type_id = t.id $whereSql ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);

// Bind filter parameters
foreach ($params as $idx => $val) {
    $stmt->bindValue($idx + 1, $val);
}

$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $customers, 'totalPages' => $totalPages]);
exit();
