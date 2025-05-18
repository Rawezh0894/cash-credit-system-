<?php
// Only start session if one doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Connect to database
$conn = Database::getInstance();

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Function to get transaction stats
function getTransactionStats($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            type,
            SUM(amount) as total_amount,
            COUNT(*) as transaction_count
        FROM 
            transactions
        WHERE 
            date BETWEEN :start_date AND :end_date
        GROUP BY 
            type
    ");
    
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get account balances
function getAccountBalances($conn) {
    // Customer balances
    $stmt = $conn->prepare("
        SELECT 
            SUM(owed_amount) as total_customer_owed,
            SUM(advance_payment) as total_customer_advance
        FROM 
            customers
    ");
    $stmt->execute();
    $customer_totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Supplier balances
    $stmt = $conn->prepare("
        SELECT 
            SUM(we_owe) as total_supplier_owed,
            SUM(advance_payment) as total_supplier_advance
        FROM 
            suppliers
    ");
    $stmt->execute();
    $supplier_totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Mixed account balances
    $stmt = $conn->prepare("
        SELECT 
            SUM(they_owe) as total_mixed_they_owe,
            SUM(we_owe) as total_mixed_we_owe,
            SUM(they_advance) as total_mixed_they_advance,
            SUM(we_advance) as total_mixed_we_advance
        FROM 
            mixed_accounts
    ");
    $stmt->execute();
    $mixed_totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'customer' => $customer_totals,
        'supplier' => $supplier_totals,
        'mixed' => $mixed_totals
    ];
}

// Function to get daily transaction amounts for the past 30 days
function getDailyTransactions($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            date,
            SUM(CASE WHEN type = 'cash' THEN amount ELSE 0 END) as cash_amount,
            SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credit_amount,
            SUM(CASE WHEN type = 'advance' THEN amount ELSE 0 END) as advance_amount,
            SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as payment_amount,
            SUM(CASE WHEN type = 'collection' THEN amount ELSE 0 END) as collection_amount
        FROM 
            transactions
        WHERE 
            date BETWEEN :start_date AND :end_date
        GROUP BY 
            date
        ORDER BY 
            date
    ");
    
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data for charts
$transaction_stats = getTransactionStats($conn, $start_date, $end_date);
$account_balances = getAccountBalances($conn);
$daily_transactions = getDailyTransactions($conn, $start_date, $end_date);

// Prepare data for charts
$transaction_types = [];
$transaction_amounts = [];
$transaction_counts = [];

foreach ($transaction_stats as $stat) {
    $type_label = '';
    switch ($stat['type']) {
        case 'cash': $type_label = 'نەقد'; break;
        case 'credit': $type_label = 'قەرز'; break;
        case 'advance': $type_label = 'پێشەکی'; break;
        case 'payment': $type_label = 'قەرز دانەوە'; break;
        case 'collection': $type_label = 'قەرز وەرگرتنەوە'; break;
        default: $type_label = $stat['type'];
    }
    
    $transaction_types[] = $type_label;
    $transaction_amounts[] = $stat['total_amount'];
    $transaction_counts[] = $stat['transaction_count'];
}

// Prepare data for daily chart
$dates = [];
$cash_amounts = [];
$credit_amounts = [];
$advance_amounts = [];
$payment_amounts = [];
$collection_amounts = [];

foreach ($daily_transactions as $day) {
    $dates[] = $day['date'];
    $cash_amounts[] = $day['cash_amount'];
    $credit_amounts[] = $day['credit_amount'];
    $advance_amounts[] = $day['advance_amount'];
    $payment_amounts[] = $day['payment_amount'];
    $collection_amounts[] = $day['collection_amount'];
}

// Calculate total balances
$total_we_owe = $account_balances['supplier']['total_supplier_owed'] + $account_balances['mixed']['total_mixed_we_owe'];
$total_they_owe = $account_balances['customer']['total_customer_owed'] + $account_balances['mixed']['total_mixed_they_owe'];
$total_we_advance = $account_balances['supplier']['total_supplier_advance'] + $account_balances['mixed']['total_mixed_we_advance'];
$total_they_advance = $account_balances['customer']['total_customer_advance'] + $account_balances['mixed']['total_mixed_they_advance'];

// Function to get upcoming and overdue credit transactions
function getCreditDueDates($conn) {
    $today = date('Y-m-d');
    $upcoming_date = date('Y-m-d', strtotime('+7 days'));
    
    // Query for overdue credit transactions - DEBTS THEY OWE US
    $overdue_their_debts_stmt = $conn->prepare("
        SELECT 
            t.id,
            t.type,
            t.amount,
            t.date,
            t.due_date,
            CASE
                WHEN t.customer_id IS NOT NULL THEN c.name
                WHEN t.supplier_id IS NOT NULL THEN s.name
                WHEN t.mixed_account_id IS NOT NULL THEN m.name
                ELSE 'Unknown'
            END as account_name,
            CASE
                WHEN t.customer_id IS NOT NULL THEN 'کڕیار'
                WHEN t.supplier_id IS NOT NULL THEN 'دابینکەر'
                WHEN t.mixed_account_id IS NOT NULL THEN 'هەژماری تێکەڵ'
                ELSE 'Unknown'
            END as account_type,
            t.customer_id,
            t.supplier_id,
            t.mixed_account_id,
            'they_owe' as debt_type
        FROM 
            transactions t
        LEFT JOIN
            customers c ON t.customer_id = c.id
        LEFT JOIN
            suppliers s ON t.supplier_id = s.id
        LEFT JOIN
            mixed_accounts m ON t.mixed_account_id = m.id
        WHERE 
            t.type = 'credit'
            AND t.due_date IS NOT NULL
            AND t.due_date < :today
            AND (t.is_deleted = 0 OR t.is_deleted IS NULL)
            AND (
                t.customer_id IS NOT NULL 
                OR (t.mixed_account_id IS NOT NULL AND t.direction = 'their_debt')
            )
        ORDER BY
            t.due_date ASC
    ");
    
    $overdue_their_debts_stmt->bindParam(':today', $today);
    $overdue_their_debts_stmt->execute();
    $overdue_their_debts = $overdue_their_debts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query for overdue credit transactions - DEBTS WE OWE THEM
    $overdue_our_debts_stmt = $conn->prepare("
        SELECT 
            t.id,
            t.type,
            t.amount,
            t.date,
            t.due_date,
            CASE
                WHEN t.customer_id IS NOT NULL THEN c.name
                WHEN t.supplier_id IS NOT NULL THEN s.name
                WHEN t.mixed_account_id IS NOT NULL THEN m.name
                ELSE 'Unknown'
            END as account_name,
            CASE
                WHEN t.customer_id IS NOT NULL THEN 'کڕیار'
                WHEN t.supplier_id IS NOT NULL THEN 'دابینکەر'
                WHEN t.mixed_account_id IS NOT NULL THEN 'هەژماری تێکەڵ'
                ELSE 'Unknown'
            END as account_type,
            t.customer_id,
            t.supplier_id,
            t.mixed_account_id,
            'we_owe' as debt_type
        FROM 
            transactions t
        LEFT JOIN
            customers c ON t.customer_id = c.id
        LEFT JOIN
            suppliers s ON t.supplier_id = s.id
        LEFT JOIN
            mixed_accounts m ON t.mixed_account_id = m.id
        WHERE 
            t.type = 'credit'
            AND t.due_date IS NOT NULL
            AND t.due_date < :today
            AND (t.is_deleted = 0 OR t.is_deleted IS NULL)
            AND (
                t.supplier_id IS NOT NULL 
                OR (t.mixed_account_id IS NOT NULL AND t.direction = 'our_debt')
            )
        ORDER BY
            t.due_date ASC
    ");
    
    $overdue_our_debts_stmt->bindParam(':today', $today);
    $overdue_our_debts_stmt->execute();
    $overdue_our_debts = $overdue_our_debts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query for upcoming credit transactions - DEBTS THEY OWE US
    $upcoming_their_debts_stmt = $conn->prepare("
        SELECT 
            t.id,
            t.type,
            t.amount,
            t.date,
            t.due_date,
            CASE
                WHEN t.customer_id IS NOT NULL THEN c.name
                WHEN t.supplier_id IS NOT NULL THEN s.name
                WHEN t.mixed_account_id IS NOT NULL THEN m.name
                ELSE 'Unknown'
            END as account_name,
            CASE
                WHEN t.customer_id IS NOT NULL THEN 'کڕیار'
                WHEN t.supplier_id IS NOT NULL THEN 'دابینکەر'
                WHEN t.mixed_account_id IS NOT NULL THEN 'هەژماری تێکەڵ'
                ELSE 'Unknown'
            END as account_type,
            t.customer_id,
            t.supplier_id,
            t.mixed_account_id,
            'they_owe' as debt_type
        FROM 
            transactions t
        LEFT JOIN
            customers c ON t.customer_id = c.id
        LEFT JOIN
            suppliers s ON t.supplier_id = s.id
        LEFT JOIN
            mixed_accounts m ON t.mixed_account_id = m.id
        WHERE 
            t.type = 'credit'
            AND t.due_date IS NOT NULL
            AND t.due_date >= :today
            AND t.due_date <= :upcoming_date
            AND (t.is_deleted = 0 OR t.is_deleted IS NULL)
            AND (
                t.customer_id IS NOT NULL 
                OR (t.mixed_account_id IS NOT NULL AND t.direction = 'their_debt')
            )
        ORDER BY
            t.due_date ASC
    ");
    
    $upcoming_their_debts_stmt->bindParam(':today', $today);
    $upcoming_their_debts_stmt->bindParam(':upcoming_date', $upcoming_date);
    $upcoming_their_debts_stmt->execute();
    $upcoming_their_debts = $upcoming_their_debts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query for upcoming credit transactions - DEBTS WE OWE THEM
    $upcoming_our_debts_stmt = $conn->prepare("
        SELECT 
            t.id,
            t.type,
            t.amount,
            t.date,
            t.due_date,
            CASE
                WHEN t.customer_id IS NOT NULL THEN c.name
                WHEN t.supplier_id IS NOT NULL THEN s.name
                WHEN t.mixed_account_id IS NOT NULL THEN m.name
                ELSE 'Unknown'
            END as account_name,
            CASE
                WHEN t.customer_id IS NOT NULL THEN 'کڕیار'
                WHEN t.supplier_id IS NOT NULL THEN 'دابینکەر'
                WHEN t.mixed_account_id IS NOT NULL THEN 'هەژماری تێکەڵ'
                ELSE 'Unknown'
            END as account_type,
            t.customer_id,
            t.supplier_id,
            t.mixed_account_id,
            'we_owe' as debt_type
        FROM 
            transactions t
        LEFT JOIN
            customers c ON t.customer_id = c.id
        LEFT JOIN
            suppliers s ON t.supplier_id = s.id
        LEFT JOIN
            mixed_accounts m ON t.mixed_account_id = m.id
        WHERE 
            t.type = 'credit'
            AND t.due_date IS NOT NULL
            AND t.due_date >= :today
            AND t.due_date <= :upcoming_date
            AND (t.is_deleted = 0 OR t.is_deleted IS NULL)
            AND (
                t.supplier_id IS NOT NULL 
                OR (t.mixed_account_id IS NOT NULL AND t.direction = 'our_debt')
            )
        ORDER BY
            t.due_date ASC
    ");
    
    $upcoming_our_debts_stmt->bindParam(':today', $today);
    $upcoming_our_debts_stmt->bindParam(':upcoming_date', $upcoming_date);
    $upcoming_our_debts_stmt->execute();
    $upcoming_our_debts = $upcoming_our_debts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'overdue_their_debts' => $overdue_their_debts,
        'overdue_our_debts' => $overdue_our_debts,
        'upcoming_their_debts' => $upcoming_their_debts,
        'upcoming_our_debts' => $upcoming_our_debts
    ];
}

// Get credit due dates
$credit_due_dates = getCreditDueDates($conn);
$overdue_their_debts = $credit_due_dates['overdue_their_debts'];
$overdue_our_debts = $credit_due_dates['overdue_our_debts'];
$upcoming_their_debts = $credit_due_dates['upcoming_their_debts'];
$upcoming_our_debts = $credit_due_dates['upcoming_our_debts'];

// Remove DEBUG: Test AJAX connection
// Restore real AJAX report filtering logic
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    $input = json_decode(file_get_contents('php://input'), true);
    $report_range = $input['report_range'] ?? 'daily';
    $start_date = $input['start_date'] ?? null;
    $end_date = $input['end_date'] ?? null;

    // Calculate date range based on report_range
    $today = date('Y-m-d');
    switch ($report_range) {
        case 'daily':
            $start_date = $start_date ?: $today;
            $end_date = $end_date ?: $today;
            break;
        case 'weekly':
            $start_date = $start_date ?: date('Y-m-d', strtotime('monday this week'));
            $end_date = $end_date ?: date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'monthly':
            $start_date = $start_date ?: date('Y-m-01');
            $end_date = $end_date ?: date('Y-m-t');
            break;
        case 'yearly':
            $start_date = $start_date ?: date('Y-01-01');
            $end_date = $end_date ?: date('Y-12-31');
            break;
        default:
            $start_date = $start_date ?: date('Y-m-d', strtotime('-30 days'));
            $end_date = $end_date ?: $today;
    }

    // Get data for charts and tables
    $transaction_stats = getTransactionStats($conn, $start_date, $end_date);
    $account_balances = getAccountBalances($conn);
    $daily_transactions = getDailyTransactions($conn, $start_date, $end_date);

    $transaction_types = [];
    $transaction_amounts = [];
    $transaction_counts = [];
    foreach ($transaction_stats as $stat) {
        $type_label = '';
        switch ($stat['type']) {
            case 'cash': $type_label = 'نەقد'; break;
            case 'credit': $type_label = 'قەرز'; break;
            case 'advance': $type_label = 'پێشەکی'; break;
            case 'payment': $type_label = 'قەرز دانەوە'; break;
            case 'collection': $type_label = 'قەرز وەرگرتنەوە'; break;
            default: $type_label = $stat['type'];
        }
        $transaction_types[] = $type_label;
        $transaction_amounts[] = $stat['total_amount'];
        $transaction_counts[] = $stat['transaction_count'];
    }

    $dates = [];
    $cash_amounts = [];
    $credit_amounts = [];
    $advance_amounts = [];
    $payment_amounts = [];
    $collection_amounts = [];
    foreach ($daily_transactions as $day) {
        $dates[] = $day['date'];
        $cash_amounts[] = $day['cash_amount'];
        $credit_amounts[] = $day['credit_amount'];
        $advance_amounts[] = $day['advance_amount'];
        $payment_amounts[] = $day['payment_amount'];
        $collection_amounts[] = $day['collection_amount'];
    }

    $total_we_owe = $account_balances['supplier']['total_supplier_owed'] + $account_balances['mixed']['total_mixed_we_owe'];
    $total_they_owe = $account_balances['customer']['total_customer_owed'] + $account_balances['mixed']['total_mixed_they_owe'];
    $total_we_advance = $account_balances['supplier']['total_supplier_advance'] + $account_balances['mixed']['total_mixed_we_advance'];
    $total_they_advance = $account_balances['customer']['total_customer_advance'] + $account_balances['mixed']['total_mixed_they_advance'];

    $credit_due_dates = getCreditDueDates($conn);
    // Add a class for overdue dates
    foreach ($credit_due_dates['overdue_their_debts'] as &$row) {
        $row['due_date_class'] = 'text-danger fw-bold';
    }
    foreach ($credit_due_dates['overdue_our_debts'] as &$row) {
        $row['due_date_class'] = 'text-danger fw-bold';
    }
    foreach ($credit_due_dates['upcoming_their_debts'] as &$row) {
        $row['due_date_class'] = 'fw-medium';
    }
    foreach ($credit_due_dates['upcoming_our_debts'] as &$row) {
        $row['due_date_class'] = 'fw-medium';
    }

    header('Content-Type: application/json');
    echo json_encode([
        'transaction_types' => $transaction_types,
        'transaction_amounts' => $transaction_amounts,
        'transaction_counts' => $transaction_counts,
        'dates' => $dates,
        'cash_amounts' => $cash_amounts,
        'credit_amounts' => $credit_amounts,
        'advance_amounts' => $advance_amounts,
        'payment_amounts' => $payment_amounts,
        'collection_amounts' => $collection_amounts,
        'account_balances' => $account_balances,
        'total_we_owe' => $total_we_owe,
        'total_they_owe' => $total_they_owe,
        'total_we_advance' => $total_we_advance,
        'total_they_advance' => $total_they_advance,
        'overdue_their_debts' => $credit_due_dates['overdue_their_debts'],
        'overdue_our_debts' => $credit_due_dates['overdue_our_debts'],
        'upcoming_their_debts' => $credit_due_dates['upcoming_their_debts'],
        'upcoming_our_debts' => $credit_due_dates['upcoming_our_debts'],
    ]);
    exit();
}
?> 