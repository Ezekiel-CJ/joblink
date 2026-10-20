<?php
$page_title = "LGU Admin Dashboard";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');
}

$db = getDB();

// Get counts
$pending_jobs = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status='pending'")->fetch_assoc()['count'] ?? 0;
$pending_employers = $db->query("SELECT COUNT(*) as count FROM employer_profiles WHERE verification_status='pending'")->fetch_assoc()['count'] ?? 0;
$approved_jobs = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status='approved'")->fetch_assoc()['count'] ?? 0;
$verified_employers = $db->query("SELECT COUNT(*) as count FROM employer_profiles WHERE verification_status='verified'")->fetch_assoc()['count'] ?? 0;
$total_applications = $db->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count'] ?? 0;

// Get recent pending jobs (using j.created_at)
$recent_jobs = $db->query("SELECT j.*, u.full_name, ep.business_name 
                           FROM jobs j 
                           JOIN users u ON j.employer_id = u.id 
                           LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
                           WHERE j.status='pending' 
                           ORDER BY j.created_at DESC 
                           LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get recent pending employers (using u.created_at since employer_profiles has no created_at)
$recent_employers = $db->query("SELECT ep.*, u.full_name, u.email, u.created_at 
                                FROM employer_profiles ep 
                                JOIN users u ON ep.user_id = u.id 
                                WHERE ep.verification_status='pending' 
                                ORDER BY u.created_at DESC 
                                LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get recent audit logs
$recent_audits = $db->query("SELECT al.*, u.full_name 
                             FROM audit_logs al 
                             LEFT JOIN users u ON al.user_id = u.id 
                             ORDER BY al.created_at DESC 
                             LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.3s; }
    .stat-card:hover { transform: translateY(-3px); background: rgba(37,99,235,0.05); }
    .stat-number { font-size: 32px; font-weight: 700; color: #06b6d4; }
    .pending-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .audit-item { display: flex; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .dashboard-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    .section-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 24px; margin-bottom: 30px; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    .welcome-card { background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(6,182,212,0.05)); border-radius: 24px; padding: 30px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .verification-badge { display: inline-block; padding: 6px 16px; border-radius: 30px; font-size: 13px; background: rgba(16,185,129,0.15); color: #10b981; }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .dashboard-grid-2col { grid-template-columns: 1fr; } }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <div class="welcome-card">
        <div><h1>LGU Employment Dashboard</h1><p class="text-muted-light">Monitor and manage community employment.</p></div>
        <span class="verification-badge"><i class="fas fa-shield-alt"></i> LGU Officer</span>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card" onclick="location.href='jobs.php?status=pending'"><i class="fas fa-clock" style="font-size: 28px; color: #f59e0b;"></i><div class="stat-number"><?php echo $pending_jobs; ?></div><div class="text-muted-light">Pending Jobs</div></div>
        <div class="stat-card" onclick="location.href='employers.php?status=pending'"><i class="fas fa-building" style="font-size: 28px; color: #f59e0b;"></i><div class="stat-number"><?php echo $pending_employers; ?></div><div class="text-muted-light">Pending Verifications</div></div>
        <div class="stat-card" onclick="location.href='analytics.php'"><i class="fas fa-chart-line" style="font-size: 28px;"></i><div class="stat-number"><?php echo $total_applications; ?></div><div class="text-muted-light">Applications</div></div>
        <div class="stat-card" onclick="location.href='insights.php'"><i class="fas fa-lightbulb" style="font-size: 28px;"></i><div class="stat-number">0</div><div class="text-muted-light">Skill Insights</div></div>
    </div>
    
    <div class="dashboard-grid-2col">
        <div class="section-card"><div class="section-header"><h3>Pending Job Approvals</h3><a href="jobs.php?status=pending" class="text-muted-light">View All</a></div>
            <?php foreach ($recent_jobs as $job): ?>
                <div class="pending-item"><div><strong><?php echo htmlspecialchars($job['title']); ?></strong><div class="text-muted-light"><?php echo htmlspecialchars($job['business_name'] ?? $job['full_name']); ?></div></div><a href="job-approve.php?id=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 6px 12px;">Review</a></div>
            <?php endforeach; ?>
            <?php if(empty($recent_jobs)): ?><div class="text-center" style="padding: 30px;"><p class="text-muted-light">No pending jobs</p></div><?php endif; ?>
        </div>
        <div class="section-card"><div class="section-header"><h3>Pending Verifications</h3><a href="employers.php?status=pending" class="text-muted-light">View All</a></div>
            <?php foreach ($recent_employers as $emp): ?>
                <div class="pending-item"><div><strong><?php echo htmlspecialchars($emp['business_name']); ?></strong><div class="text-muted-light"><?php echo htmlspecialchars($emp['full_name']); ?></div></div><a href="employer-verify.php?id=<?php echo $emp['user_id']; ?>" class="btn-outline-light" style="padding: 6px 12px;">Verify</a></div>
            <?php endforeach; ?>
            <?php if(empty($recent_employers)): ?><div class="text-center" style="padding: 30px;"><p class="text-muted-light">No pending verifications</p></div><?php endif; ?>
        </div>
    </div>
    
    <div class="section-card"><div class="section-header"><h3>Recent Activity</h3><a href="audit-logs.php" class="text-muted-light">View All</a></div>
        <?php foreach ($recent_audits as $audit): ?>
            <div class="audit-item"><div><strong><?php echo htmlspecialchars($audit['full_name'] ?? 'System'); ?></strong> <?php echo str_replace('_', ' ', $audit['action']); ?><div class="text-dim"><?php echo date('M d, h:i A', strtotime($audit['created_at'])); ?></div></div></div>
        <?php endforeach; ?>
        <?php if(empty($recent_audits)): ?><div class="text-center" style="padding: 30px;"><p class="text-muted-light">No recent activity</p></div><?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>