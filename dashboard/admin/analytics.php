<?php
$page_title = "Analytics";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();

$monthly = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM jobs GROUP BY month ORDER BY month DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$categories = $db->query("SELECT c.name, COUNT(j.id) as count FROM job_categories c LEFT JOIN jobs j ON c.id = j.category_id AND j.status='approved' GROUP BY c.id ORDER BY count DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$skills = $db->query("SELECT required_skills FROM jobs WHERE status='approved' AND required_skills IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
$skill_counts = []; foreach($skills as $j){ $sk = explode(',', $j['required_skills']); foreach($sk as $s){ $k = trim($s); if($k) $skill_counts[$k] = ($skill_counts[$k] ?? 0) + 1; } } arsort($skill_counts); $top_skills = array_slice($skill_counts, 0, 5, true);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .chart-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 24px; margin-bottom: 30px; }
    .progress-bar { background: rgba(255,255,255,0.1); border-radius: 10px; height: 8px; overflow: hidden; margin: 8px 0; }
    .progress-fill { background: linear-gradient(90deg, #2563eb, #06b6d4); height: 100%; border-radius: 10px; }
    .skill-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Workforce Analytics</h1>
    
    <div class="chart-card"><h3>Monthly Job Postings</h3><?php $max = max(array_column($monthly, 'count')) ?: 1; foreach(array_reverse($monthly) as $m): $p = ($m['count']/$max)*100; ?><div><div style="display:flex; justify-content:space-between;"><span><?php echo date('M Y', strtotime($m['month'].'-01')); ?></span><span><?php echo $m['count']; ?> jobs</span></div><div class="progress-bar"><div class="progress-fill" style="width:<?php echo $p; ?>%"></div></div></div><?php endforeach; ?></div>
    
    <div class="chart-card"><h3>Top Categories</h3><?php $max = max(array_column($categories, 'count')) ?: 1; foreach($categories as $c): $p = ($c['count']/$max)*100; ?><div><div style="display:flex; justify-content:space-between;"><span><?php echo htmlspecialchars($c['name']); ?></span><span><?php echo $c['count']; ?> jobs</span></div><div class="progress-bar"><div class="progress-fill" style="width:<?php echo $p; ?>%"></div></div></div><?php endforeach; ?></div>
    
    <div class="chart-card"><h3>Top In-Demand Skills</h3><?php $max = max($top_skills) ?: 1; foreach($top_skills as $skill=>$count): $p = ($count/$max)*100; ?><div class="skill-item"><span><?php echo htmlspecialchars($skill); ?></span><div style="flex:1; margin:0 16px;"><div class="progress-bar"><div class="progress-fill" style="width:<?php echo $p; ?>%"></div></div></div><span><?php echo $count; ?></span></div><?php endforeach; ?></div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>