<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'نابێت بچیتە ژوورەوە.'
    ]);
    exit();
}

// Parse request parameters
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

// Validate parameters
if ($current_page < 1) $current_page = 1;
if ($per_page < 1) $per_page = 10;
if ($per_page > 100) $per_page = 100; // Limit to reasonable size

// Calculate offset
$offset = ($current_page - 1) * $per_page;

try {
    $conn = Database::getInstance();
    
    // Start building query
    $sql = "SELECT t.*, 
            c.name AS customer_name, 
            s.name AS supplier_name, 
            m.name AS mixed_account_name 
            FROM transactions t 
            LEFT JOIN customers c ON t.customer_id = c.id 
            LEFT JOIN suppliers s ON t.supplier_id = s.id 
            LEFT JOIN mixed_accounts m ON t.mixed_account_id = m.id 
            WHERE t.is_deleted = 1";
    
    $count_sql = "SELECT COUNT(*) AS total FROM transactions WHERE is_deleted = 1";
    
    $params = [];
    $count_params = [];
    
    // Apply filters
    
    // Type filter
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $sql .= " AND t.type = ?";
        $count_sql .= " AND type = ?";
        $params[] = $_GET['type'];
        $count_params[] = $_GET['type'];
    }
    
    // Account type filter
    if (isset($_GET['account_type']) && !empty($_GET['account_type'])) {
        switch ($_GET['account_type']) {
            case 'customer':
                $sql .= " AND t.customer_id IS NOT NULL";
                $count_sql .= " AND customer_id IS NOT NULL";
                break;
            case 'supplier':
                $sql .= " AND t.supplier_id IS NOT NULL";
                $count_sql .= " AND supplier_id IS NOT NULL";
                break;
            case 'mixed':
                $sql .= " AND t.mixed_account_id IS NOT NULL";
                $count_sql .= " AND mixed_account_id IS NOT NULL";
                break;
        }
    }
    
    // Date range filter
    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
        $sql .= " AND t.date >= ?";
        $count_sql .= " AND date >= ?";
        $params[] = $_GET['date_from'];
        $count_params[] = $_GET['date_from'];
    }
    
    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
        $sql .= " AND t.date <= ?";
        $count_sql .= " AND date <= ?";
        $params[] = $_GET['date_to'];
        $count_params[] = $_GET['date_to'];
    }
    
    // Search filters
    
    // Type search
    if (isset($_GET['search_type']) && !empty($_GET['search_type'])) {
        $sql .= " AND t.type LIKE ?";
        $count_sql .= " AND type LIKE ?";
        $params[] = '%' . $_GET['search_type'] . '%';
        $count_params[] = '%' . $_GET['search_type'] . '%';
    }
    
    // Amount search
    if (isset($_GET['search_amount']) && !empty($_GET['search_amount'])) {
        $sql .= " AND t.amount LIKE ?";
        $count_sql .= " AND amount LIKE ?";
        $params[] = '%' . $_GET['search_amount'] . '%';
        $count_params[] = '%' . $_GET['search_amount'] . '%';
    }
    
    // Date search
    if (isset($_GET['search_date']) && !empty($_GET['search_date'])) {
        $sql .= " AND t.date LIKE ?";
        $count_sql .= " AND date LIKE ?";
        $params[] = '%' . $_GET['search_date'] . '%';
        $count_params[] = '%' . $_GET['search_date'] . '%';
    }
    
    // Account search (searches across all account types)
    if (isset($_GET['search_account']) && !empty($_GET['search_account'])) {
        $search_term = '%' . $_GET['search_account'] . '%';
        $sql .= " AND (c.name LIKE ? OR s.name LIKE ? OR m.name LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        
        // For count query, we need a simpler approach since we don't have joins
        $count_sql .= " AND (
            (customer_id IS NOT NULL AND customer_id IN (SELECT id FROM customers WHERE name LIKE ?)) OR 
            (supplier_id IS NOT NULL AND supplier_id IN (SELECT id FROM suppliers WHERE name LIKE ?)) OR 
            (mixed_account_id IS NOT NULL AND mixed_account_id IN (SELECT id FROM mixed_accounts WHERE name LIKE ?))
        )";
        $count_params[] = $search_term;
        $count_params[] = $search_term;
        $count_params[] = $search_term;
    }
    
    // Account type search
    if (isset($_GET['search_account_type']) && !empty($_GET['search_account_type'])) {
        $search_term = strtolower($_GET['search_account_type']);
        if (strpos($search_term, 'كڕيار') !== false || strpos($search_term, 'كريار') !== false || 
            strpos($search_term, 'کڕیار') !== false || strpos($search_term, 'customer') !== false) {
            $sql .= " AND t.customer_id IS NOT NULL";
            $count_sql .= " AND customer_id IS NOT NULL";
        } else if (strpos($search_term, 'دابينكەر') !== false || strpos($search_term, 'دابینکەر') !== false ||
                  strpos($search_term, 'supplier') !== false) {
            $sql .= " AND t.supplier_id IS NOT NULL";
            $count_sql .= " AND supplier_id IS NOT NULL";
        } else if (strpos($search_term, 'تێکەڵ') !== false || strpos($search_term, 'تيكەل') !== false ||
                  strpos($search_term, 'mixed') !== false) {
            $sql .= " AND t.mixed_account_id IS NOT NULL";
            $count_sql .= " AND mixed_account_id IS NOT NULL";
        }
    }
    
    // Notes search
    if (isset($_GET['search_notes']) && !empty($_GET['search_notes'])) {
        $sql .= " AND t.notes LIKE ?";
        $count_sql .= " AND notes LIKE ?";
        $params[] = '%' . $_GET['search_notes'] . '%';
        $count_params[] = '%' . $_GET['search_notes'] . '%';
    }
    
    // Deleted date search
    if (isset($_GET['search_deleted_at']) && !empty($_GET['search_deleted_at'])) {
        $sql .= " AND t.deleted_at LIKE ?";
        $count_sql .= " AND deleted_at LIKE ?";
        $params[] = '%' . $_GET['search_deleted_at'] . '%';
        $count_params[] = '%' . $_GET['search_deleted_at'] . '%';
    }
    
    // Order by and limit
    $sql .= " ORDER BY t.deleted_at DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $per_page;
    
    // Prepare and execute the queries
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $total_pages = ceil($total_records / $per_page);
    
    // Format response
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_deleted_transactions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 