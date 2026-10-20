<?php
$page_title = "Community Insights";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();

$seekers = $db->query("SELECT skills FROM jobseeker_profiles WHERE skills IS NOT NULL AND skills != ''")->fetch_all(MYSQLI_ASSOC);
$jobs = $db->query("SELECT required_skills FROM jobs WHERE status='approved' AND required_skills IS NOT NULL")->fetch_all(MYSQLI_ASSOC);

$supply = []; foreach($seekers as $s){ $sk = explode(',', $s['skills']); foreach($sk as $skl){ $k = trim($skl); if($k) $supply[$k] = ($supply[$k] ?? 0) + 1; } }
$demand = []; foreach($jobs as $j){ $sk = explode(',', $j['required_skills']); foreach($sk as $skl){ $k = trim($skl); if($k) $demand[$k] = ($demand[$k] ?? 0) + 1; } }

$all_skills = array_unique(array_merge(array_keys($supply), array_keys($demand)));
$gaps = []; foreach($all_skills as $skill){ $d = $demand[$skill] ?? 0; $s = $supply[$skill] ?? 0; $gaps[] = ['skill'=>$skill, 'demand'=>$d, 'supply'=>$s, 'gap'=>$d - $s]; }
usort($gaps, function($a,$b){ return $b['gap'] - $a['gap']; });
$shortages = array_filter($gaps, fn($g)=>$g['gap'] > 0);
$surpluses = array_filter($gaps, fn($g)=>$g['gap'] < 0);

require_once '../../includes/header-dashboard.php';
?>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Community Insights</h1>
    
    <div class="chart-card"><h3>Skill Shortages (Demand > Supply)</h3><?php if(!empty($shortages)): ?><table class="table"><thead><tr><th>Skill</th><th>Demand</th><th>Supply</th><th>Gap</th></tr></thead><tbody><?php foreach(array_slice($shortages,0,10) as $g): ?><tr><td><?php echo htmlspecialchars($g['skill']); ?></td><td><?php echo $g['demand']; ?></td><td><?php echo $g['supply']; ?></td><td style="color:#ef4444;">+<?php echo $g['gap']; ?></td></tr><?php endforeach; ?></tbody></table><?php else: ?><p class="text-muted-light">No skill shortages detected.</p><?php endif; ?></div>
    
    <div class="chart-card"><h3>Skill Surpluses (Supply > Demand)</h3><?php if(!empty($surpluses)): ?><table class="table"><thead><tr><th>Skill</th><th>Demand</th><th>Supply</th><th>Surplus</th></tr></thead><tbody><?php foreach(array_slice($surpluses,0,10) as $g): ?><tr><td><?php echo htmlspecialchars($g['skill']); ?></td><td><?php echo $g['demand']; ?></td><td><?php echo $g['supply']; ?></td><td style="color:#10b981;"><?php echo abs($g['gap']); ?></td></tr><?php endforeach; ?></tbody></table><?php else: ?><p class="text-muted-light">No skill surpluses detected.</p><?php endif; ?></div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>