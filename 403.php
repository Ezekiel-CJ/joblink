<?php
$page_title = "Access Denied";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 120px 0; text-align: center;">
    <div style="max-width: 500px; margin: 0 auto;">
        <i class="fas fa-lock" style="font-size: 64px; color: #ef4444; margin-bottom: 24px;"></i>
        <h1 style="font-size: 80px;">403</h1>
        <h2>Access Denied</h2>
        <p class="text-muted-light" style="margin: 20px 0;">You don't have permission to access this page.</p>
        <a href="<?php echo SITE_URL; ?>" class="btn-primary-gradient">Go Back Home</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>