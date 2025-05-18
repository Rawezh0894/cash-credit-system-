<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = Database::getInstance();
    $stmt = $db->prepare('SELECT id, type_name FROM customer_types ORDER BY id ASC');
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $types]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵە لە گەڕاندنەوەی جۆرەکان: ' . $e->getMessage()]);
} 