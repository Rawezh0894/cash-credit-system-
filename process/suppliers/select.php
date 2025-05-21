<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = Database::getInstance();

// Check if specific supplier ID is requested
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = :id");
    $stmt->bindValue(':id', (int)$_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($supplier) {
        echo json_encode(['success' => true, 'data' => [$supplier]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'دابینکەر نەدۆزرایەوە']);
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

// Filter by name
if (!empty($_GET['name'])) {
    $where[] = 'name LIKE ?';
    $params[] = '%' . $_GET['name'] . '%';
}

// Filter by city
if (!empty($_GET['city'])) {
    $where[] = 'city = ?';
    $params[] = $_GET['city'];
}

// Filter by location
if (!empty($_GET['location'])) {
    $location = $_GET['location'] === 'ناو شار' ? 'inside' : 'outside';
    $where[] = 'location = ?';
    $params[] = $location;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get total records count (with filters)
$countSql = "SELECT COUNT(*) FROM suppliers $whereSql";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get paginated records (with filters)
$sql = "SELECT * FROM suppliers $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);

// Bind filter parameters
foreach ($params as $idx => $val) {
    $stmt->bindValue($idx + 1, $val);
}

$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $suppliers, 'totalPages' => $totalPages]);
exit();
