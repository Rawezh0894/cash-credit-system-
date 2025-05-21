<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    // Get unique names
    $namesStmt = $db->query("SELECT DISTINCT name FROM customers ORDER BY name ASC");
    $names = $namesStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get unique cities
    $citiesStmt = $db->query("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
    $cities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get unique types (join with customer_types)
    $typesStmt = $db->query("SELECT DISTINCT ct.type_name FROM customers c JOIN customer_types ct ON c.customer_type_id = ct.id ORDER BY ct.type_name ASC");
    $types = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'names' => $names,
        'cities' => $cities,
        'types' => $types
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 