<?php
$page_title = "Support";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">We're Here to Help</div>
        <h1>Support Center</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin: 50px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-question-circle" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>FAQ</h3>
            <p class="text-muted-light">Find answers to common questions</p>
            <a href="faq.php" class="btn-outline-light" style="margin-top: 16px;">View FAQs →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-envelope" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Contact Us</h3>
            <p class="text-muted-light">Reach out to our support team</p>
            <a href="contact.php" class="btn-outline-light" style="margin-top: 16px;">Send Message →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px; text-align: center;">
            <div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;"><i class="fas fa-book" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Documentation</h3>
            <p class="text-muted-light">User guides and tutorials</p>
            <a href="faq.php" class="btn-outline-light" style="margin-top: 16px;">Read Guides →</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>