<?php
$page_title = "Employer Dashboard";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to access your dashboard.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();
$user = getCurrentUser();

$employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc();
if (!$employer) {
    $db->query("INSERT INTO employer_profiles (user_id, business_name, verification_status) VALUES ($user_id, '{$user['full_name']}'s Business', 'pending')");
    $employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc();
}

$total_jobs = $db->query("SELECT COUNT(*) as count FROM jobs WHERE employer_id = $user_id")->fetch_assoc()['count'] ?? 0;
$pending_jobs = $db->query("SELECT COUNT(*) as count FROM jobs WHERE employer_id = $user_id AND status = 'pending'")->fetch_assoc()['count'] ?? 0;
$approved_jobs = $db->query("SELECT COUNT(*) as count FROM jobs WHERE employer_id = $user_id AND status = 'approved'")->fetch_assoc()['count'] ?? 0;
$total_applicants = $db->query("SELECT COUNT(DISTINCT a.id) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = $user_id")->fetch_row()[0] ?? 0;

$recent_jobs = $db->query("SELECT j.*, COUNT(a.id) as applicants FROM jobs j LEFT JOIN applications a ON j.id = a.job_id WHERE j.employer_id = $user_id GROUP BY j.id ORDER BY j.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_applicants = $db->query("SELECT a.*, j.title, u.full_name FROM applications a JOIN jobs j ON a.job_id = j.id JOIN users u ON a.jobseeker_id = u.id WHERE j.employer_id = $user_id ORDER BY a.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; text-align: center; transition: all 0.3s; }
    .stat-card:hover { transform: translateY(-3px); background: rgba(37,99,235,0.05); }
    .stat-number { font-size: 32px; font-weight: 700; color: var(--azure-light); }
    .welcome-card { background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(6,182,212,0.05)); border-radius: 24px; padding: 30px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .verification-badge { display: inline-block; padding: 6px 16px; border-radius: 30px; font-size: 13px; }
    .verified { background: rgba(16,185,129,0.15); color: #10b981; }
    .pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .rejected { background: rgba(239,68,68,0.15); color: #ef4444; }
    .job-item, .applicant-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .quick-actions { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 40px; }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <div class="welcome-card">
        <div><h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1><p class="text-muted-light">Manage your jobs and applicants.</p></div>
        <div><?php if ($employer['verification_status'] === 'verified'): ?><span class="verification-badge verified"><i class="fas fa-check-circle"></i> Verified Business</span>
        <?php elseif ($employer['verification_status'] === 'pending'): ?><span class="verification-badge pending"><i class="fas fa-clock"></i> Verification Pending</span><a href="verification.php" class="btn-outline-light" style="margin-left: 12px;">Submit</a>
        <?php else: ?><span class="verification-badge rejected"><i class="fas fa-times-circle"></i> Verification Rejected</span><a href="verification.php" class="btn-outline-light" style="margin-left: 12px;">Resubmit</a><?php endif; ?></div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card"><i class="fas fa-briefcase" style="font-size: 28px;"></i><div class="stat-number"><?php echo $total_jobs; ?></div><div class="text-muted-light">Total Jobs</div></div>
        <div class="stat-card"><i class="fas fa-clock" style="font-size: 28px; color: #f59e0b;"></i><div class="stat-number"><?php echo $pending_jobs; ?></div><div class="text-muted-light">Pending Approval</div></div>
        <div class="stat-card"><i class="fas fa-check-circle" style="font-size: 28px; color: #10b981;"></i><div class="stat-number"><?php echo $approved_jobs; ?></div><div class="text-muted-light">Active Jobs</div></div>
        <div class="stat-card"><i class="fas fa-users" style="font-size: 28px;"></i><div class="stat-number"><?php echo $total_applicants; ?></div><div class="text-muted-light">Total Applicants</div></div>
    </div>
    
    <div class="quick-actions">
        <a href="post-job.php" class="btn-primary-gradient"><i class="fas fa-plus-circle"></i> Post a Job</a>
        <a href="manage-jobs.php" class="btn-outline-light"><i class="fas fa-list"></i> Manage Jobs</a>
        <a href="applicants.php" class="btn-outline-light"><i class="fas fa-users"></i> View Applicants</a>
        <a href="profile.php" class="btn-outline-light"><i class="fas fa-building"></i> Business Profile</a>
    </div>
    
    <?php if (!empty($recent_jobs)): ?>
        <h2>Recent Job Postings</h2>
        <div style="background: rgba(255,255,255,0.03); border-radius: 20px; overflow: hidden;">
            <?php foreach ($recent_jobs as $job): ?>
                <div class="job-item"><div><strong><?php echo htmlspecialchars($job['title']); ?></strong><div class="text-muted-light">Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></div></div>
                <div style="display: flex; gap: 12px; align-items: center;"><?php echo getStatusBadge($job['status']); ?><span class="text-muted-light"><?php echo $job['applicants']; ?> applicants</span><a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 6px 12px;">Edit</a></div></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; background: rgba(255,255,255,0.03); border-radius: 20px;"><i class="fas fa-briefcase" style="font-size: 48px;"></i><h3>No jobs yet</h3><a href="post-job.php" class="btn-primary-gradient" style="margin-top: 16px;">Post a Job</a></div>
    <?php endif; ?>
    
    <?php if (!empty($recent_applicants)): ?>
        <h2 style="margin-top: 40px;">Recent Applicants</h2>
        <div style="background: rgba(255,255,255,0.03); border-radius: 20px; overflow: hidden;">
            <?php foreach ($recent_applicants as $app): ?>
                <div class="applicant-item"><div><strong><?php echo htmlspecialchars($app['full_name']); ?></strong><div class="text-muted-light">Applied for: <?php echo htmlspecialchars($app['title']); ?></div></div>
                <div style="display: flex; gap: 12px;"><?php echo getStatusBadge($app['status']); ?><a href="applicant-detail.php?id=<?php echo $app['id']; ?>" class="btn-outline-light" style="padding: 6px 12px;">Review</a></div></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>