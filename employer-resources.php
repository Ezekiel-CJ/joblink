<?php
$page_title = "Employer Resources";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">For Employers</div>
        <h1>Employer Resources</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin: 50px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-file-signature" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Job Posting Templates</h3>
            <a href="#" class="btn-outline-light" style="margin-top: 16px;">Download →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-chart-bar" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Hiring Analytics Guide</h3>
            <a href="#" class="btn-outline-light" style="margin-top: 16px;">Read Guide →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-gavel" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>LGU Compliance Guide</h3>
            <a href="lgu-info.php" class="btn-outline-light" style="margin-top: 16px;">Learn More →</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>