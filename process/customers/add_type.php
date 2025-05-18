<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_name = isset($_POST['type_name']) ? trim($_POST['type_name']) : '';
    if ($type_name === '') {
        echo json_encode(['success' => false, 'message' => 'ناوی جۆر پێویستە']);
        exit();
    }
    try {
        $db = Database::getInstance();
        // Check for duplicate
        $stmt = $db->prepare('SELECT COUNT(*) FROM customer_types WHERE type_name = ?');
        $stmt->execute([$type_name]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'ئەم جۆرە پێشتر تۆمارکراوە']);
            exit();
        }
        $stmt = $db->prepare('INSERT INTO customer_types (type_name) VALUES (?)');
        $stmt->execute([$type_name]);
        $new_id = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'جۆر زیادکرا', 'new_id' => $new_id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'هەڵە: ' . $e->getMessage()]);
    }
    exit();
}
echo json_encode(['success' => false, 'message' => 'Invalid request method']); 