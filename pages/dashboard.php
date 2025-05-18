<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Check permission to view dashboard
requirePermission('view_dashboard');

// Include the data processing file
$dashboardData = require __DIR__ . '/../process/dashboard/dashboard.php';

// Extract the data
$user = $dashboardData['user'];
$stats = $dashboardData['stats'];
$transaction_types = $dashboardData['transaction_types'];
$recent_transactions = $dashboardData['recent_transactions'];
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبۆرد - سیستەمی پارە و کریت</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="bg-body-tertiary">


    <div class="container-fluid py-4">
    <?php include '../includes/navbar.php'; ?>
        <div class="container-fluid">
      
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                                <div class="welcome-text mb-3 mb-md-0">
                                    <h1 class="h3 mb-2">بەخێربێیت، <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                                    <p class="text-muted mb-0">ئەمڕۆ <?php echo date('Y/m/d'); ?>، چۆنیت؟</p>
                                </div>
                                <div class="d-flex">
                                    <a href="transactions.php?action=add" class="btn btn-primary me-2">
                                        <i class="bi bi-plus-circle me-1"></i> زیادکردنی مامەڵە
                                    </a>
                                    <a href="reports.php" class="btn btn-primary">
                                        <i class="bi bi-file-earmark-bar-graph me-1"></i> ڕاپۆرت
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary-subtle text-primary rounded-circle p-3 me-3">
                                    <i class="bi bi-people fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fs-4 mb-0"><?php echo $stats['total_customers']; ?></h3>
                                    <p class="text-muted mb-0">کۆی کڕیاران</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success-subtle text-success rounded-circle p-3 me-3">
                                    <i class="bi bi-shop fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fs-4 mb-0"><?php echo $stats['total_suppliers']; ?></h3>
                                    <p class="text-muted mb-0">کۆی دابینکەران</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info-subtle text-info rounded-circle p-3 me-3">
                                    <i class="bi bi-bank fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fs-4 mb-0"><?php echo $stats['total_mixed_accounts']; ?></h3>
                                    <p class="text-muted mb-0">هەژمارە تێکەڵەکان</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning-subtle text-warning rounded-circle p-3 me-3">
                                    <i class="bi bi-receipt fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fs-4 mb-0"><?php echo $stats['total_transactions']; ?></h3>
                                    <p class="text-muted mb-0">کۆی مامەڵەکان</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Recent Activities -->
            <div class="row g-4">
                <!-- Quick Actions -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-4 pb-2">
                            <h5 class="card-title mb-0">کردارە خێراکان</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-4">
                                    <a href="transactions.php?action=add" class="card text-center quick-action border-0 h-100 p-3">
                                        <div class="quick-action-icon bg-primary-subtle text-primary rounded-circle mx-auto mb-3">
                                            <i class="bi bi-plus-circle"></i>
                                        </div>
                                        <span class="d-block">زیادکردنی مامەڵە</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4">
                                    <a href="customers.php" class="card text-center quick-action border-0 h-100 p-3">
                                        <div class="quick-action-icon bg-info-subtle text-info rounded-circle mx-auto mb-3">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <span class="d-block">کڕیاران</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4">
                                    <a href="suppliers.php" class="card text-center quick-action border-0 h-100 p-3">
                                        <div class="quick-action-icon bg-warning-subtle text-warning rounded-circle mx-auto mb-3">
                                            <i class="bi bi-shop"></i>
                                        </div>
                                        <span class="d-block">دابینکەران</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4">
                                    <a href="mixed_accounts.php" class="card text-center quick-action border-0 h-100 p-3">
                                        <div class="quick-action-icon bg-success-subtle text-success rounded-circle mx-auto mb-3">
                                            <i class="bi bi-bank"></i>
                                        </div>
                                        <span class="d-block">هەژمارە تێکەڵەکان</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4">
                                    <a href="transactions.php" class="card text-center quick-action border-0 h-100 p-3">
                                        <div class="quick-action-icon bg-danger-subtle text-danger rounded-circle mx-auto mb-3">
                                            <i class="bi bi-receipt"></i>
                                        </div>
                                        <span class="d-block">مامەڵەکان</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-4">
                                    <a href="reports.php" class="card text-center quick-action border-0 h-100 p-3">
                                        <div class="quick-action-icon bg-primary-subtle text-primary rounded-circle mx-auto mb-3">
                                            <i class="bi bi-file-earmark-bar-graph"></i>
                                        </div>
                                        <span class="d-block">ڕاپۆرتەکان</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-4 pb-2">
                            <h5 class="card-title mb-0">دوایین چالاکییەکان</h5>
                        </div>
                        <div class="card-body">
                            <div class="activity-list">
                                <?php if (empty($recent_transactions)): ?>
                                <div class="text-center text-muted py-3">
                                    هیچ چالاکییەک نییە
                                </div>
                                <?php else: ?>
                                    <?php foreach ($recent_transactions as $index => $transaction): 
                                        // Format transaction type
                                        $typeText = "";
                                        $typeIcon = "";
                                        $typeClass = "";
                                        
                                        switch ($transaction['type']) {
                                            case 'cash':
                                                $typeText = "نەقد";
                                                $typeIcon = "cash-coin";
                                                $typeClass = "success";
                                                break;
                                            case 'credit':
                                                $typeText = "قەرز";
                                                $typeIcon = "credit-card";
                                                $typeClass = "danger";
                                                break;
                                            case 'advance':
                                                $typeText = "پێشەکی";
                                                $typeIcon = "arrow-up-circle";
                                                $typeClass = "primary";
                                                break;
                                            case 'payment':
                                                $typeText = "قەرز دانەوە";
                                                $typeIcon = "arrow-right-circle";
                                                $typeClass = "warning";
                                                break;
                                            case 'collection':
                                                $typeText = "قەرز وەرگرتنەوە";
                                                $typeIcon = "arrow-left-circle";
                                                $typeClass = "info";
                                                break;
                                            default:
                                                $typeText = $transaction['type'];
                                                $typeIcon = "question-circle";
                                                $typeClass = "secondary";
                                        }
                                        
                                        // Get account name
                                        $accountName = "";
                                        if (!empty($transaction['customer_id']) && !empty($transaction['customer_name'])) {
                                            $accountName = $transaction['customer_name'];
                                        } elseif (!empty($transaction['supplier_id']) && !empty($transaction['supplier_name'])) {
                                            $accountName = $transaction['supplier_name'];
                                        } elseif (!empty($transaction['mixed_account_id']) && !empty($transaction['mixed_account_name'])) {
                                            $accountName = $transaction['mixed_account_name'];
                                            if ($transaction['direction'] === "sale") {
                                                $accountName .= " (فرۆشتن)";
                                            } elseif ($transaction['direction'] === "purchase") {
                                                $accountName .= " (کڕین)";
                                            }
                                        }
                                        
                                        // Calculate time ago
                                        $created_at = new DateTime($transaction['created_at']);
                                        $now = new DateTime();
                                        $interval = $now->getTimestamp() - $created_at->getTimestamp();
                                        
                                        // Convert timestamp difference to appropriate unit
                                        if ($interval < 60) {
                                            // Less than a minute
                                            $timeAgo = $interval . ' چرکە لەمەوبەر';
                                        } elseif ($interval < 3600) {
                                            // Less than an hour
                                            $minutes = floor($interval / 60);
                                            $timeAgo = $minutes . ' خولەک لەمەوبەر';
                                        } elseif ($interval < 86400) {
                                            // Less than a day
                                            $hours = floor($interval / 3600);
                                            $timeAgo = $hours . ' کاتژمێر لەمەوبەر';
                                        } elseif ($interval < 2592000) {
                                            // Less than a month (30 days)
                                            $days = floor($interval / 86400);
                                            $timeAgo = $days . ' ڕۆژ لەمەوبەر';
                                        } elseif ($interval < 31536000) {
                                            // Less than a year
                                            $months = floor($interval / 2592000);
                                            $timeAgo = $months . ' مانگ لەمەوبەر';
                                        } else {
                                            // More than a year
                                            $years = floor($interval / 31536000);
                                            $timeAgo = $years . ' ساڵ لەمەوبەر';
                                        }
                                        
                                        $isLast = ($index === count($recent_transactions) - 1);
                                    ?>
                                    <div class="activity-item d-flex align-items-start <?php echo !$isLast ? 'mb-3 pb-3 border-bottom' : ''; ?>">
                                        <div class="activity-icon bg-<?php echo $typeClass; ?>-subtle text-<?php echo $typeClass; ?> rounded-circle p-2 me-3">
                                            <i class="bi bi-<?php echo $typeIcon; ?>"></i>
                                        </div>
                                        <div class="activity-info">
                                            <h6 class="mb-1"><?php echo $typeText; ?> تۆمارکرا</h6>
                                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($accountName); ?></p>
                                            <p class="mb-0 text-muted"><?php echo number_format($transaction['amount']); ?> دینار</p>
                                            <small class="text-muted"><?php echo $timeAgo; ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 