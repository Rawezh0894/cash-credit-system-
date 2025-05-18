<?php
// Don't start a new session as it's already started in the parent file
// session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in is done in the parent file

// Get user data
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
    'total_roles' => $db->query("SELECT COUNT(*) FROM roles")->fetchColumn(),
    'total_permissions' => $db->query("SELECT COUNT(*) FROM permissions")->fetchColumn()
];

// Get transactions statistics
$stats['total_customers'] = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$stats['total_suppliers'] = $db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$stats['total_mixed_accounts'] = $db->query("SELECT COUNT(*) FROM mixed_accounts")->fetchColumn();
$stats['total_transactions'] = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

// Get transactions by type statistics
$transaction_types = $db->query("
    SELECT type, COUNT(*) as count, SUM(amount) as total_amount
    FROM transactions
    WHERE type != ''
    GROUP BY type
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities (latest 5 transactions)
$recent_transactions = $db->query("
    SELECT 
        t.id, 
        t.type, 
        t.amount, 
        t.date, 
        t.customer_id, 
        t.supplier_id, 
        t.mixed_account_id, 
        t.direction, 
        t.created_at,
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
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Return all the data to be used in the dashboard
return [
    'user' => $user,
    'stats' => $stats,
    'transaction_types' => $transaction_types,
    'recent_transactions' => $recent_transactions
];
