<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'دەستت ناگات بەم بەشە']);
    exit();
}

// Get parameters
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$records_per_page = isset($_GET['records_per_page']) ? (int)$_GET['records_per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

try {
    $conn = Database::getInstance();

    // Get due credit transactions with pagination
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

    // Get total count
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

    // Start building HTML content
    ob_start();
    
    if (empty($due_credit_transactions)): ?>
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
                        <th>تێبینی</th>
                        <th>زیادکەر</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = ($page - 1) * $records_per_page + 1;
                    foreach ($due_credit_transactions as $transaction): 
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
                        <td><?php echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($transaction['creator_name']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
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
    <?php endif;

    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_records' => $total_records
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە: ' . $e->getMessage()
    ]);
} 