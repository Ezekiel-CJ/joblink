<?php
$page_title = "About Us";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Who We Are</div>
        <h1>Connecting Talent to<br>Opportunities in Your Community</h1>
        <p class="text-muted-light" style="max-width: 700px; margin: 20px auto;">JobLinkLocal is a community-based employment platform built for Local Government Units, local businesses, and job seekers.</p>
    </div>
    
    <div style="background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(6,182,212,0.05)); border-radius: 32px; padding: 48px; margin: 50px 0; text-align: center;">
        <h2>Our Mission</h2>
        <p class="text-muted-light" style="max-width: 700px; margin: 20px auto 0;">To empower local communities by connecting job seekers with verified employment opportunities, while providing LGUs with data-driven insights for workforce planning.</p>
    </div>
    
    <div class="feature-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin: 50px 0;">
        <div class="feature-card" style="text-align: center; padding: 32px; background: rgba(255,255,255,0.03); border-radius: 28px;">
            <div class="feature-icon" style="margin: 0 auto 20px; width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-shield-alt" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Trust & Verification</h3>
            <p class="text-muted-light">All employers undergo LGU verification for legitimacy.</p>
        </div>
        <div class="feature-card" style="text-align: center; padding: 32px; background: rgba(255,255,255,0.03); border-radius: 28px;">
            <div class="feature-icon" style="margin: 0 auto 20px; width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-chart-line" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Skill-Based Matching</h3>
            <p class="text-muted-light">Advanced algorithms match jobs to your unique skills.</p>
        </div>
        <div class="feature-card" style="text-align: center; padding: 32px; background: rgba(255,255,255,0.03); border-radius: 28px;">
            <div class="feature-icon" style="margin: 0 auto 20px; width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-users" style="font-size: 32px; color: var(--azure-light);"></i></div>
            <h3>Community Focus</h3>
            <p class="text-muted-light">Built specifically for local government communities.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>