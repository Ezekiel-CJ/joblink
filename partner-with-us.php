<?php
$page_title = "Partner With Us";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Collaborate</div>
        <h1>Partner With Us</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin: 50px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-building" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>For LGUs</h3>
            <a href="contact.php" class="btn-outline-light" style="margin-top: 16px;">Learn More →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-graduation-cap" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Educational Institutions</h3>
            <a href="contact.php" class="btn-outline-light" style="margin-top: 16px;">Learn More →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-hand-holding-heart" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>NGOs & Non-profits</h3>
            <a href="contact.php" class="btn-outline-light" style="margin-top: 16px;">Learn More →</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>