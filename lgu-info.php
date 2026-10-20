<?php
$page_title = "LGU Program";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Local Government</div>
        <h1>LGU Employment Program</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin: 50px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px;"><div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-gavel" style="font-size: 32px; color: var(--azure-light);"></i></div><h3>Job Posting Approval</h3><p class="text-muted-light">Review and approve job postings before they go live.</p></div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px;"><div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-chart-line" style="font-size: 32px; color: var(--azure-light);"></i></div><h3>Workforce Analytics</h3><p class="text-muted-light">Track employment trends and skill gaps.</p></div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px;"><div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-building" style="font-size: 32px; color: var(--azure-light);"></i></div><h3>Employer Monitoring</h3><p class="text-muted-light">Verify businesses and ensure compliance.</p></div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px;"><div style="width: 70px; height: 70px; background: rgba(37,99,235,0.15); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-lightbulb" style="font-size: 32px; color: var(--azure-light);"></i></div><h3>Community Insights</h3><p class="text-muted-light">Identify in-demand skills and shortages.</p></div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>