<?php
$page_title = "Workforce Analytics";
require_once 'includes/config.php';

$db = getDB();
$total_jobs = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved'")->fetch_assoc()['count'] ?? 0;
$total_employers = $db->query("SELECT COUNT(*) as count FROM employer_profiles WHERE verification_status = 'verified'")->fetch_assoc()['count'] ?? 0;
$total_applications = $db->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count'] ?? 0;

$top_categories = $db->query("SELECT c.name, COUNT(j.id) as count FROM job_categories c LEFT JOIN jobs j ON c.id = j.category_id AND j.status = 'approved' GROUP BY c.id ORDER BY count DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Community Data</div>
        <h1>Workforce Analytics</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin: 40px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; padding: 32px; text-align: center;"><div class="stat-number" style="font-size: 36px;"><?php echo $total_jobs; ?>+</div><div class="text-muted-light">Active Jobs</div></div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; padding: 32px; text-align: center;"><div class="stat-number" style="font-size: 36px;"><?php echo $total_employers; ?>+</div><div class="text-muted-light">Verified Employers</div></div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; padding: 32px; text-align: center;"><div class="stat-number" style="font-size: 36px;"><?php echo $total_applications; ?>+</div><div class="text-muted-light">Applications</div></div>
    </div>
    
    <?php if (!empty($top_categories)): ?>
        <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px;">
            <h3>Top Job Categories</h3>
            <?php foreach ($top_categories as $cat): ?>
                <div style="margin: 16px 0;"><div style="display: flex; justify-content: space-between;"><span><?php echo htmlspecialchars($cat['name']); ?></span><span><?php echo $cat['count']; ?> jobs</span></div><div style="background: rgba(255,255,255,0.1); border-radius: 10px; height: 6px; margin-top: 6px;"><div style="width: <?php echo min(100, ($cat['count'] / max($top_categories[0]['count'], 1)) * 100); ?>%; background: linear-gradient(90deg, #2563eb, #06b6d4); height: 6px; border-radius: 10px;"></div></div></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>