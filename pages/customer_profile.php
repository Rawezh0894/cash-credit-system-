<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Handle success and error messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Check if customer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ناسنامەی کڕیار نادروستە';
    header('Location: customers.php');
    exit();
}

$customer_id = intval($_GET['id']);
$conn = Database::getInstance();

// Pagination settings for due credit transactions
$records_per_page = isset($_GET['records_per_page']) ? (int)$_GET['records_per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get customer details
try {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        $_SESSION['error_message'] = 'کڕیارەکە نەدۆزرایەوە';
        header('Location: customers.php');
        exit();
    }

    // Get customer transactions with pagination for due credits
    $stmt = $conn->prepare("
        SELECT t.*, u.full_name as creator_name 
        FROM transactions t 
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.customer_id = ? 
        AND t.type = 'credit' 
        AND t.due_date IS NOT NULL 
        AND t.due_date != '0000-00-00'
        ORDER BY t.due_date ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$customer_id, $records_per_page, $offset]);
    $due_credit_transactions = $stmt->fetchAll();

    // Get total count of due credit transactions
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM transactions 
        WHERE customer_id = ? 
        AND type = 'credit' 
        AND due_date IS NOT NULL 
        AND due_date != '0000-00-00'
    ");
    $stmt->execute([$customer_id]);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Get all transactions for statistics
    $stmt = $conn->prepare("
        SELECT t.*, u.full_name as creator_name 
        FROM transactions t 
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.customer_id = ? 
        ORDER BY t.date DESC, t.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $transactions = $stmt->fetchAll();

    // Calculate statistics
    $total_credit = 0;
    $total_cash = 0;
    $total_advance = 0;
    $total_payment = 0;

    foreach ($transactions as $transaction) {
        if ($transaction['type'] === 'credit') {
            // Only count remaining credit
            $remaining = isset($transaction['paid_amount']) ? ($transaction['amount'] - $transaction['paid_amount']) : $transaction['amount'];
            if ($remaining > 0) {
                $total_credit += $remaining;
            }
        } elseif ($transaction['type'] === 'cash') {
            $total_cash += floatval($transaction['amount']);
        } elseif ($transaction['type'] === 'advance') {
            $total_advance += floatval($transaction['amount']);
        } elseif ($transaction['type'] === 'payment') {
            $total_payment += floatval($transaction['amount']);
        }
    }

    // Get transaction files/receipts
    $transaction_ids = array_column($transactions, 'id');
    $transaction_files = [];
    
    if (!empty($transaction_ids)) {
        $placeholders = str_repeat('?,', count($transaction_ids) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT * FROM transaction_files 
            WHERE transaction_id IN ($placeholders)
        ");
        $stmt->execute($transaction_ids);
        
        $all_files = $stmt->fetchAll();
        foreach ($all_files as $file) {
            $transaction_files[$file['transaction_id']][] = $file;
        }
    }

    // گەڕانەوەی collection/payment بۆ ئەم مامەڵەیە بە بەکارهێنانی paid_amount (FIFO)
    foreach ($due_credit_transactions as &$transaction) {
        $transaction['collected'] = $transaction['paid_amount'] ?? 0;
        $transaction['remaining'] = $transaction['amount'] - $transaction['collected'];
    }
    unset($transaction);

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'هەڵە: ' . $e->getMessage();
    header('Location: customers.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl" data-bs-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی کڕیار - <?php echo htmlspecialchars($customer['name']); ?></title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tables.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="bg-body-tertiary">
    <div class="container-fluid py-4">
        <?php include '../includes/navbar.php'; ?>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row d-flex justify-content-between align-items-center">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h2>
                        <span class="icon-circle icon-customers">
                            <i class="bi bi-person"></i>
                        </span>
                        پڕۆفایلی کڕیار: <?php echo htmlspecialchars($customer['name']); ?>
                    </h2>
                    <a href="customers.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right-short"></i> گەڕانەوە بۆ لیستی کڕیارەکان
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Financial Summary Section -->
        <div class="profile-summary-section">
            <div class="row">
                <div class="col-12">
                    <div class="card profile-card customer-summary-card">
                        <div class="card-header" style="background-color: #454e6c; color: white;">
                            <h5 class="mb-0">
                                <i class="bi bi-cash-stack"></i> کورتەی دارایی
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="summary-card credit-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-credit-card"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">کۆی قەرز</span>
                                            <span class="summary-value"><?php echo number_format($total_credit, 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="summary-card cash-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-cash"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">کۆی نەقد</span>
                                            <span class="summary-value"><?php echo number_format($total_cash, 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="summary-card advance-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-arrow-up-circle"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">کۆی پێشەکی</span>
                                            <span class="summary-value"><?php echo number_format($customer['advance_payment'], 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="summary-card balance-card">
                                        <div class="summary-icon">
                                            <i class="bi bi-calculator"></i>
                                        </div>
                                        <div class="summary-data">
                                            <span class="summary-label">باڵانسی قەرز</span>
                                            <span class="summary-value"><?php echo number_format($customer['owed_amount'], 0); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Information Section -->
        <div class="profile-info-section">
            <div class="row">
                <div class="col-12">
                    <div class="card profile-card customer-card">
                        <div class="card-header text-center pt-4 pb-2" style="background-color: #454e6c; color: white;">
                            <div class="mb-3">
                                <span class="icon-circle icon-customers" style="width:64px;height:64px;background-color:white;"><i class="bi bi-person" style="font-size:2.5rem;"></i></span>
                            </div>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($customer['name']); ?></h4>
                        </div>
                        <div class="card-body pt-0">
                            <ul class="list-group list-group-flush profile-info-list">
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی یەکەم:</span> <span class="profile-info-value"><?php echo htmlspecialchars($customer['phone1']); ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی دووەم:</span> <span class="profile-info-value"><?php echo !empty($customer['phone2']) ? htmlspecialchars($customer['phone2']) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">ناوی کەفیل:</span> <span class="profile-info-value"><?php echo !empty($customer['guarantor_name']) ? htmlspecialchars($customer['guarantor_name']) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">ژمارەی مۆبایلی کەفیل:</span> <span class="profile-info-value"><?php echo !empty($customer['guarantor_phone']) ? htmlspecialchars($customer['guarantor_phone']) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">شار:</span> <span class="profile-info-value"><?php echo htmlspecialchars($customer['city']); ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">شوێن:</span> <span class="profile-info-value"><?php echo $customer['location'] === 'inside' ? 'ناو شار' : 'دەرەوەی شار'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">تێبینی:</span> <span class="profile-info-value"><?php echo !empty($customer['notes']) ? nl2br(htmlspecialchars($customer['notes'])) : '-'; ?></span></li>
                                <li class="list-group-item"><span class="profile-info-label">بەرواری زیادکردن:</span> <span class="profile-info-value"><?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Due Credit Transactions Section -->
        <div class="profile-transactions-section">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-alarm"></i> مامەڵە قەرزەکان کە کاتیان بۆ دیاریکراوە
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select form-select-sm records-per-page" style="width: auto;">
                                    <option value="5" <?php echo $records_per_page == 5 ? 'selected' : ''; ?>>5</option>
                                    <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($due_credit_transactions)): ?>
                                <div class="alert alert-info m-3">
                                    <i class="bi bi-info-circle"></i> هیچ مامەڵەیەکی قەرز بە کاتی دیاریکراو نییە.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>بڕی پارە</th>
                                                <th>بەرواری قەرز</th>
                                                <th>کاتی دیاریکراو</th>
                                                <th>ماوەی ماوە</th>
                                                <th>پارەی گەڕاوە</th>
                                                <th>باڵانسی ماوە</th>
                                                <th>تێبینی</th>
                                                <th>زیادکەر</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $counter = ($page - 1) * $records_per_page + 1;
                                            foreach ($due_credit_transactions as $transaction): 
                                                if ($transaction['remaining'] <= 0) continue;
                                                $due_date = new DateTime($transaction['due_date']);
                                                $current_date = new DateTime();
                                                $days_remaining = $current_date->diff($due_date)->days;
                                                $days_remaining_text = '';
                                                $row_class = '';
                                                
                                                if ($due_date < $current_date) {
                                                    $days_remaining_text = $days_remaining . ' ڕۆژ دواکەوتووە';
                                                    $row_class = 'table-danger';
                                                } else {
                                                    $days_remaining_text = $days_remaining . ' ڕۆژ ماوە';
                                                    if ($days_remaining <= 3) {
                                                        $row_class = 'table-warning';
                                                    }
                                                }
                                            ?>
                                            <tr class="<?php echo $row_class; ?>">
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo number_format($transaction['amount'], 0); ?></td>
                                                <td><?php echo $transaction['date']; ?></td>
                                                <td><?php echo $transaction['due_date']; ?></td>
                                                <td><?php echo $days_remaining_text; ?></td>
                                                <td><?php echo number_format($transaction['collected'], 0); ?></td>
                                                <td><?php echo number_format($transaction['remaining'], 0); ?></td>
                                                <td><?php echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($transaction['creator_name']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex justify-content-between align-items-center p-3">
                                    <div class="pagination-info">
                                        نیشاندانی <?php echo ($offset + 1); ?> تا <?php echo min($offset + $records_per_page, $total_records); ?> لە <?php echo $total_records; ?> تۆمار
                                    </div>
                                    <ul class="pagination mb-0">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $customer_id; ?>&page=1&records_per_page=<?php echo $records_per_page; ?>">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $customer_id; ?>&page=<?php echo ($page - 1); ?>&records_per_page=<?php echo $records_per_page; ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);

                                        for ($i = $start_page; $i <= $end_page; $i++):
                                        ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $customer_id; ?>&page=<?php echo $i; ?>&records_per_page=<?php echo $records_per_page; ?>"><?php echo $i; ?></a>
                                        </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $customer_id; ?>&page=<?php echo ($page + 1); ?>&records_per_page=<?php echo $records_per_page; ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $customer_id; ?>&page=<?php echo $total_pages; ?>&records_per_page=<?php echo $records_per_page; ?>">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">دەستکاری زانیاری کڕیار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCustomerForm" action="../includes/functions/customer_functions.php" method="post">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">ناوی کڕیار</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone1" class="form-label">ژمارەی مۆبایلی یەکەم</label>
                                <input type="text" class="form-control" id="phone1" name="phone1" value="<?php echo htmlspecialchars($customer['phone1']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone2" class="form-label">ژمارەی مۆبایلی دووەم (ئیختیاری)</label>
                                <input type="text" class="form-control" id="phone2" name="phone2" value="<?php echo htmlspecialchars($customer['phone2'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guarantor_name" class="form-label">ناوی کەفیل (ئیختیاری)</label>
                                <input type="text" class="form-control" id="guarantor_name" name="guarantor_name" value="<?php echo htmlspecialchars($customer['guarantor_name'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="guarantor_phone" class="form-label">ژمارەی مۆبایلی کەفیل (ئیختیاری)</label>
                                <input type="text" class="form-control" id="guarantor_phone" name="guarantor_phone" value="<?php echo htmlspecialchars($customer['guarantor_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">ناوی شار</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($customer['city']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">شوێن</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location" id="location_inside" value="inside" <?php echo $customer['location'] === 'inside' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="location_inside">ناو شار</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location" id="location_outside" value="outside" <?php echo $customer['location'] === 'outside' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="location_outside">دەرەوەی شار</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="notes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" form="editCustomerForm" class="btn btn-primary">نوێکردنەوە</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/swalAlert2/swalAlert2.js"></script>
    
    <?php if ($success_message): ?>
    <script>
        showSwalAlert2('success', 'سەرکەوتوو!', '<?php echo $success_message; ?>');
    </script>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <script>
        showSwalAlert2('error', 'هەڵە!', '<?php echo $error_message; ?>');
    </script>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dueCreditsTable = document.querySelector('.table-responsive').parentElement;
        const recordsPerPageSelect = document.querySelector('.records-per-page');
        
        // Function to update table content
        async function updateTableContent(page, recordsPerPage) {
            try {
                const response = await fetch(`../includes/ajax/get_due_credits.php?id=<?php echo $customer_id; ?>&page=${page}&records_per_page=${recordsPerPage}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                
                if (data.success) {
                    dueCreditsTable.innerHTML = data.html;
                    
                    // Reattach event listeners after content update
                    attachEventListeners();
                    
                    // Update URL without page reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('records_per_page', recordsPerPage);
                    window.history.pushState({}, '', url);
                } else {
                    showSwalAlert2('error', 'هەڵە!', data.message || 'هەڵەیەک ڕوویدا');
                }
            } catch (error) {
                console.error('Error:', error);
                showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی گۆڕینی داتاکان');
            }
        }

        // Function to attach event listeners
        function attachEventListeners() {
            // Records per page change handler
            const newRecordsPerPageSelect = document.querySelector('.records-per-page');
            if (newRecordsPerPageSelect) {
                newRecordsPerPageSelect.addEventListener('change', function(e) {
                    updateTableContent(1, this.value);
                });
            }

            // Pagination links click handler
            const paginationLinks = document.querySelectorAll('.pagination .page-link');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page');
                    const recordsPerPage = url.searchParams.get('records_per_page');
                    updateTableContent(page, recordsPerPage);
                });
            });
        }

        // Initial attachment of event listeners
        attachEventListeners();
    });
    </script>
</body>
</html> 