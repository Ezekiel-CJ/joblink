<?php
$page_title = "Server Error";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 120px 0; text-align: center;">
    <div style="max-width: 500px; margin: 0 auto;">
        <i class="fas fa-exclamation-triangle" style="font-size: 64px; color: #f59e0b; margin-bottom: 24px;"></i>
        <h1 style="font-size: 80px;">500</h1>
        <h2>Server Error</h2>
        <p class="text-muted-light" style="margin: 20px 0;">Something went wrong. Please try again later.</p>
        <a href="<?php echo SITE_URL; ?>" class="btn-primary-gradient">Go Back Home</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>