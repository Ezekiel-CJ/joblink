<?php
$page_title = "System Admin";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_jobs = $db->query("SELECT COUNT(*) FROM jobs")->fetch_row()[0];
$total_apps = $db->query("SELECT COUNT(*) FROM applications")->fetch_row()[0];
$audit_24h = $db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0];

$recent_audits = $db->query("SELECT al.*, u.full_name FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; text-align: center; transition: all 0.3s; }
    .stat-number { font-size: 32px; font-weight: 700; color: var(--azure-light); }
    .quick-actions { display: flex; gap: 16px; flex-wrap: wrap; margin: 30px 0; }
    .audit-item { display: flex; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <div class="welcome-card"><div><h1>System Administrator</h1><p class="text-muted-light">Full platform control.</p></div><span class="verification-badge verified"><i class="fas fa-shield-alt"></i> Super Admin</span></div>
    
    <div class="stats-grid">
        <div class="stat-card"><i class="fas fa-users" style="font-size: 28px;"></i><div class="stat-number"><?php echo $total_users; ?></div><div class="text-muted-light">Total Users</div></div>
        <div class="stat-card"><i class="fas fa-briefcase" style="font-size: 28px;"></i><div class="stat-number"><?php echo $total_jobs; ?></div><div class="text-muted-light">Total Jobs</div></div>
        <div class="stat-card"><i class="fas fa-paper-plane" style="font-size: 28px;"></i><div class="stat-number"><?php echo $total_apps; ?></div><div class="text-muted-light">Applications</div></div>
        <div class="stat-card"><i class="fas fa-history" style="font-size: 28px;"></i><div class="stat-number"><?php echo $audit_24h; ?></div><div class="text-muted-light">Events (24h)</div></div>
    </div>
    
    <div class="quick-actions"><a href="users.php" class="btn-primary-gradient"><i class="fas fa-users-cog"></i> Manage Users</a><a href="categories.php" class="btn-outline-light"><i class="fas fa-folder-tree"></i> Categories</a><a href="skills.php" class="btn-outline-light"><i class="fas fa-tags"></i> Skills</a><a href="audit-logs.php" class="btn-outline-light"><i class="fas fa-history"></i> Audit Trail</a><a href="backup.php" class="btn-outline-light"><i class="fas fa-database"></i> Backup</a><a href="config.php" class="btn-outline-light"><i class="fas fa-sliders-h"></i> Config</a></div>
    
    <div class="section-card"><div class="section-header"><h3>Recent Activity</h3><a href="audit-logs.php" class="text-muted-light">View All</a></div>
        <?php foreach ($recent_audits as $audit): ?><div class="audit-item"><div><strong><?php echo htmlspecialchars($audit['full_name'] ?? 'System'); ?></strong><div class="text-dim"><?php echo str_replace('_', ' ', $audit['action']); ?> • <?php echo date('M d, h:i A', strtotime($audit['created_at'])); ?></div></div></div><?php endforeach; ?>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>