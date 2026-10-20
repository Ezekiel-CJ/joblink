<?php
$page_title = "Page Not Found";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 120px 0; text-align: center;">
    <div style="max-width: 500px; margin: 0 auto;">
        <i class="fas fa-search" style="font-size: 64px; color: var(--azure-light); margin-bottom: 24px;"></i>
        <h1 style="font-size: 80px;">404</h1>
        <h2>Page Not Found</h2>
        <p class="text-muted-light" style="margin: 20px 0;">The page you're looking for doesn't exist.</p>
        <a href="<?php echo SITE_URL; ?>" class="btn-primary-gradient">Go Back Home</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>