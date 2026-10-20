<?php
$page_title = "Pricing";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<style>
    .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin: 50px 0; }
    .pricing-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 32px; padding: 40px 30px; text-align: center; position: relative; }
    .pricing-card.popular { border: 1px solid rgba(6,182,212,0.4); transform: scale(1.02); }
    .popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #2563eb, #06b6d4); padding: 6px 16px; border-radius: 30px; font-size: 12px; }
    .price { font-size: 48px; font-weight: 800; margin: 20px 0; }
    .price span { font-size: 18px; font-weight: 400; color: var(--white-muted); }
    .feature-list { list-style: none; margin: 30px 0; }
    .feature-list li { padding: 10px 0; color: var(--white-muted); border-bottom: 1px solid rgba(255,255,255,0.05); }
    .feature-list i { color: #10b981; margin-right: 10px; }
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Simple Pricing</div>
        <h1>Pricing Plans</h1>
    </div>
    
    <div class="pricing-grid">
        <div class="pricing-card"><h3>Free</h3><div class="price">₱0 <span>/month</span></div><ul class="feature-list"><li><i class="fas fa-check"></i> 3 Active Jobs</li><li><i class="fas fa-check"></i> Basic Analytics</li><li><i class="fas fa-check"></i> Applicant Management</li><li><i class="fas fa-check"></i> LGU Verification</li></ul><a href="register.php?role=employer" class="btn-outline-light">Get Started</a></div>
        <div class="pricing-card popular"><div class="popular-badge">Most Popular</div><h3>Pro</h3><div class="price">₱1,499 <span>/month</span></div><ul class="feature-list"><li><i class="fas fa-check"></i> 15 Active Jobs</li><li><i class="fas fa-check"></i> Advanced Analytics</li><li><i class="fas fa-check"></i> 5 Featured Jobs</li><li><i class="fas fa-check"></i> Email Support</li></ul><a href="register.php?role=employer" class="btn-primary-gradient">Start Pro Plan</a></div>
        <div class="pricing-card"><h3>Enterprise</h3><div class="price">Custom</div><ul class="feature-list"><li><i class="fas fa-check"></i> Unlimited Jobs</li><li><i class="fas fa-check"></i> Custom Analytics</li><li><i class="fas fa-check"></i> Dedicated Manager</li><li><i class="fas fa-check"></i> Priority Support</li></ul><a href="contact-sales.php" class="btn-outline-light">Contact Sales</a></div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>