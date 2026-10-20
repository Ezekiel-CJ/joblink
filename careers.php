<?php
$page_title = "Careers";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Join Our Team</div>
        <h1>Careers at JobLinkLocal</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin: 50px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-code" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Software Engineer</h3>
            <p class="text-muted-light">Full-time • Remote</p>
            <a href="contact.php" class="btn-outline-light" style="margin-top: 16px;">Apply Now →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-chart-line" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Product Manager</h3>
            <p class="text-muted-light">Full-time • Hybrid</p>
            <a href="contact.php" class="btn-outline-light" style="margin-top: 16px;">Apply Now →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-headset" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Customer Success</h3>
            <p class="text-muted-light">Full-time • On-site</p>
            <a href="contact.php" class="btn-outline-light" style="margin-top: 16px;">Apply Now →</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>