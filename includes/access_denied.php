<?php
// Get the page name for displaying in the message
$page_name = isset($page_title) ? $page_title : basename($_SERVER['PHP_SELF']);
$required_permission = isset($required_permission) ? $required_permission : '';

// Base path for redirection
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $base_path = './dashboard.php'; // We're in pages directory, go to dashboard
} else {
    $base_path = 'pages/dashboard.php'; // We're in root, go to pages/dashboard
}
?>
<div class="unauthorized-outer">
    <div class="unauthorized-card">
        <div class="icon"><i class="bi bi-lock-fill"></i></div>
        <div class="title">ڕێگەپێدانی ناتەواو</div>
        <div class="desc">
            ببووره، توانای دەستگەیشتنیت نییە بەم پەڕەیە.<br>
            <?php if (!empty($required_permission)): ?>
            <span class="text-muted" style="font-size:13px;">ڕێگەپێدانی پێویست: <span class="badge bg-secondary"><?php echo htmlspecialchars($required_permission); ?></span></span><br>
            <?php endif; ?>
            <span style="font-size:13px;">پەڕە: <strong><?php echo htmlspecialchars($page_name); ?></strong></span>
        </div>
     
    </div>
</div> 