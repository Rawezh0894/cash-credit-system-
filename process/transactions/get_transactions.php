<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response = [
        'success' => false,
        'message' => 'دەبێت خۆت تۆمار بکەیت بۆ ئەنجامدانی ئەم کردارە.'
    ];
    echo json_encode($response);
    exit();
}

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$offset = ($page - 1) * $per_page;

// Build the query
$query = "
    SELECT 
        t.id, 
        t.type, 
        t.amount, 
        t.date, 
        t.due_date,
        t.customer_id, 
        t.supplier_id, 
        t.mixed_account_id, 
        t.direction, 
        t.notes, 
        t.created_at,
        t.is_deleted,
        t.deleted_at,
        c.name AS customer_name,
        s.name AS supplier_name,
        m.name AS mixed_account_name
    FROM 
        transactions t
    LEFT JOIN 
        customers c ON t.customer_id = c.id
    LEFT JOIN 
        suppliers s ON t.supplier_id = s.id
    LEFT JOIN 
        mixed_accounts m ON t.mixed_account_id = m.id
    WHERE 1=1
    AND (t.is_deleted = 0 OR t.is_deleted IS NULL)
";

$countQuery = "SELECT COUNT(*) as total FROM transactions t 
    LEFT JOIN 
        customers c ON t.customer_id = c.id
    LEFT JOIN 
        suppliers s ON t.supplier_id = s.id
    LEFT JOIN 
        mixed_accounts m ON t.mixed_account_id = m.id
    WHERE 1=1
    AND (t.is_deleted = 0 OR t.is_deleted IS NULL)";

$queryParams = [];
$countParams = [];

// Debug: Log all GET parameters
error_log("GET Parameters: " . print_r($_GET, true));

// Apply search filters if provided
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $query .= " AND t.type LIKE :type";
    $countQuery .= " AND t.type LIKE :type";
    $queryParams[':type'] = '%' . $_GET['type'] . '%';
    $countParams[':type'] = '%' . $_GET['type'] . '%';
}

if (isset($_GET['amount']) && !empty($_GET['amount'])) {
    $query .= " AND t.amount LIKE :amount";
    $countQuery .= " AND t.amount LIKE :amount";
    $queryParams[':amount'] = '%' . $_GET['amount'] . '%';
    $countParams[':amount'] = '%' . $_GET['amount'] . '%';
}

if (isset($_GET['date']) && !empty($_GET['date'])) {
    $query .= " AND t.date LIKE :date";
    $countQuery .= " AND t.date LIKE :date";
    $queryParams[':date'] = '%' . $_GET['date'] . '%';
    $countParams[':date'] = '%' . $_GET['date'] . '%';
}

// Add date range filter
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $query .= " AND t.date >= :date_from";
    $countQuery .= " AND t.date >= :date_from";
    $queryParams[':date_from'] = $_GET['date_from'];
    $countParams[':date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $query .= " AND t.date <= :date_to";
    $countQuery .= " AND t.date <= :date_to";
    $queryParams[':date_to'] = $_GET['date_to'];
    $countParams[':date_to'] = $_GET['date_to'];
}

if (isset($_GET['account']) && !empty($_GET['account'])) {
    $accountSearch = '%' . $_GET['account'] . '%';
    $query .= " AND (c.name LIKE :customer_account OR s.name LIKE :supplier_account OR m.name LIKE :mixed_account)";
    $countQuery .= " AND (c.name LIKE :customer_account OR s.name LIKE :supplier_account OR m.name LIKE :mixed_account)";
    $queryParams[':customer_account'] = $accountSearch;
    $queryParams[':supplier_account'] = $accountSearch;
    $queryParams[':mixed_account'] = $accountSearch;
    $countParams[':customer_account'] = $accountSearch;
    $countParams[':supplier_account'] = $accountSearch;
    $countParams[':mixed_account'] = $accountSearch;
}

if (isset($_GET['account_type']) && !empty($_GET['account_type'])) {
    $accountType = $_GET['account_type'];
    if ($accountType === 'customer') {
        $query .= " AND t.customer_id IS NOT NULL";
        $countQuery .= " AND t.customer_id IS NOT NULL";
    } elseif ($accountType === 'supplier') {
        $query .= " AND t.supplier_id IS NOT NULL";
        $countQuery .= " AND t.supplier_id IS NOT NULL";
    } elseif ($accountType === 'mixed') {
        $query .= " AND t.mixed_account_id IS NOT NULL";
        $countQuery .= " AND t.mixed_account_id IS NOT NULL";
    }
}

if (isset($_GET['notes']) && !empty($_GET['notes'])) {
    $query .= " AND t.notes LIKE :notes";
    $countQuery .= " AND t.notes LIKE :notes";
    $queryParams[':notes'] = '%' . $_GET['notes'] . '%';
    $countParams[':notes'] = '%' . $_GET['notes'] . '%';
}

// Add ORDER BY clause
$query .= " ORDER BY t.date DESC, t.id DESC";

// Add LIMIT clause
$query .= " LIMIT :offset, :per_page";
$queryParams[':offset'] = $offset;
$queryParams[':per_page'] = $per_page;

// Debug: Log the final queries and parameters
error_log("Main Query: " . $query);
error_log("Count Query: " . $countQuery);
error_log("Query Parameters: " . print_r($queryParams, true));
error_log("Count Parameters: " . print_r($countParams, true));

try {
    $conn = Database::getInstance();
    
    // Get total count
    $countStmt = $conn->prepare($countQuery);
    
    // Debug: Log the count statement
    error_log("Count Statement: " . print_r($countStmt, true));
    
    // Bind params for count query
    foreach ($countParams as $key => $value) {
        error_log("Binding count param: $key = $value");
        $countStmt->bindValue($key, $value);
    }
    
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get transactions
    $stmt = $conn->prepare($query);
    
    // Debug: Log the main statement
    error_log("Main Statement: " . print_r($stmt, true));
    
    // Bind all params
    foreach ($queryParams as $key => $value) {
        error_log("Binding main param: $key = $value");
        if ($key === ':offset' || $key === ':per_page') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get receipt files for each transaction
    foreach ($transactions as &$transaction) {
        $fileStmt = $conn->prepare("
            SELECT file_path 
            FROM transaction_files 
            WHERE transaction_id = :transaction_id
        ");
        $fileStmt->bindParam(':transaction_id', $transaction['id'], PDO::PARAM_INT);
        $fileStmt->execute();
        $files = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
        $transaction['receipt_files'] = $files;
    }
    
    $response = [
        'success' => true,
        'transactions' => $transactions,
        'total_count' => $totalCount,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($totalCount / $per_page)
    ];
    
} catch (Exception $e) {
    error_log("Error in get_transactions.php: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    $response = [
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ];
}

echo json_encode($response);
exit(); 