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

// Get total records count
$stmt = $db->prepare("SELECT COUNT(*) FROM suppliers");
$stmt->execute();
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get paginated records
$stmt = $db->prepare("SELECT * FROM suppliers ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $suppliers, 'totalPages' => $totalPages]);
exit();
