<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    // Get unique names
    $namesStmt = $db->query("SELECT DISTINCT name FROM mixed_accounts ORDER BY name ASC");
    $names = $namesStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get unique cities
    $citiesStmt = $db->query("SELECT DISTINCT city FROM mixed_accounts WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
    $cities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'names' => $names,
        'cities' => $cities
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 