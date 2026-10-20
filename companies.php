<?php
$page_title = "Companies";
require_once 'includes/config.php';

$db = getDB();
$companies = $db->query("SELECT ep.*, u.full_name, COUNT(j.id) as job_count 
                         FROM employer_profiles ep 
                         JOIN users u ON ep.user_id = u.id 
                         LEFT JOIN jobs j ON u.id = j.employer_id AND j.status = 'approved'
                         WHERE ep.verification_status = 'verified'
                         GROUP BY ep.user_id
                         ORDER BY job_count DESC")->fetch_all(MYSQLI_ASSOC);

require_once 'includes/header.php';
?>

<style>
    .companies-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }
    .company-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        text-align: center;
        transition: all 0.3s;
    }
    .company-card:hover { transform: translateY(-5px); background: rgba(37,99,235,0.05); }
    .company-logo {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, rgba(37,99,235,0.15), rgba(6,182,212,0.15));
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }
    .company-logo i { font-size: 36px; color: var(--azure-light); }
    .verified-badge {
        display: inline-block;
        background: rgba(16,185,129,0.15);
        color: #10b981;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        margin: 12px 0;
    }
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Trusted Partners</div>
        <h1>Premium Companies<br>Hiring Now</h1>
        <p class="text-muted-light">Join industry leaders and innovative startups.</p>
    </div>
    
    <div class="companies-grid">
        <?php if (!empty($companies)): ?>
            <?php foreach ($companies as $company): ?>
                <div class="company-card">
                    <div class="company-logo">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($company['business_name']); ?></h3>
                    <div class="verified-badge"><i class="fas fa-check-circle"></i> Verified Employer</div>
                    <div class="text-muted-light" style="margin: 12px 0;"><?php echo $company['job_count']; ?> open positions</div>
                    <a href="jobs.php?company=<?php echo urlencode($company['business_name']); ?>" class="btn-outline-light" style="display: inline-block;">View Jobs</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px; background: rgba(255,255,255,0.03); border-radius: 24px; grid-column: 1/-1;">
                <i class="fas fa-building" style="font-size: 48px; color: var(--white-dim);"></i>
                <h3>No companies yet</h3>
                <p class="text-muted-light">Check back soon for verified employers.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>