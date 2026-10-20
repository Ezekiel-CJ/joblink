<?php
$page_title = "Help Center";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">How Can We Help?</div>
        <h1>Help Center</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin: 50px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <i class="fas fa-user-circle" style="font-size: 48px; color: var(--azure-light); margin-bottom: 16px;"></i>
            <h3>Getting Started</h3>
            <a href="faq.php" class="btn-outline-light" style="margin-top: 16px;">Learn More →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <i class="fas fa-search" style="font-size: 48px; color: var(--azure-light); margin-bottom: 16px;"></i>
            <h3>Finding Jobs</h3>
            <a href="faq.php#jobseekers" class="btn-outline-light" style="margin-top: 16px;">Learn More →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <i class="fas fa-building" style="font-size: 48px; color: var(--azure-light); margin-bottom: 16px;"></i>
            <h3>For Employers</h3>
            <a href="faq.php#employers" class="btn-outline-light" style="margin-top: 16px;">Learn More →</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>