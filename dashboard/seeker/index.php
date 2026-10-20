<?php
$page_title = "Dashboard";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login to access your dashboard.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();
$user = getCurrentUser();

// Get profile
$profile = $db->query("SELECT * FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();

// Get stats
$total_apps = $db->query("SELECT COUNT(*) as count FROM applications WHERE jobseeker_id = $user_id")->fetch_assoc()['count'] ?? 0;
$pending_apps = $db->query("SELECT COUNT(*) as count FROM applications WHERE jobseeker_id = $user_id AND status = 'pending'")->fetch_assoc()['count'] ?? 0;
$shortlisted = $db->query("SELECT COUNT(*) as count FROM applications WHERE jobseeker_id = $user_id AND status = 'shortlisted'")->fetch_assoc()['count'] ?? 0;
$accepted = $db->query("SELECT COUNT(*) as count FROM applications WHERE jobseeker_id = $user_id AND status = 'accepted'")->fetch_assoc()['count'] ?? 0;

// Get recent jobs
$recent_jobs = $db->query("SELECT * FROM jobs WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Get recent applications
$recent_apps = $db->query("SELECT a.*, j.title FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.jobseeker_id = $user_id ORDER BY a.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .dashboard-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; text-align: center; transition: all 0.3s; }
    .stat-card:hover { transform: translateY(-3px); background: rgba(37,99,235,0.05); }
    .stat-number { font-size: 32px; font-weight: 700; color: var(--azure-light); }
    .welcome-card { background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(6,182,212,0.05)); border-radius: 24px; padding: 30px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .job-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 20px; transition: all 0.3s; }
    .job-card:hover { transform: translateY(-3px); background: rgba(37,99,235,0.05); }
    .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-top: 20px; margin-bottom: 40px; }
    .salary-tag { display: inline-block; background: rgba(6,182,212,0.15); padding: 4px 12px; border-radius: 20px; font-size: 13px; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    .status-pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .status-shortlisted { background: rgba(6,182,212,0.15); color: #06b6d4; }
    .status-accepted { background: rgba(16,185,129,0.15); color: #10b981; }
    .quick-actions { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 40px; }
    .profile-complete { background: rgba(16,185,129,0.15); padding: 8px 16px; border-radius: 30px; font-size: 13px; color: #10b981; }
    @media (max-width: 768px) { .dashboard-stats { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="container-premium" style="padding: 30px 0;">
    
    <div class="welcome-card">
        <div>
            <h1 style="font-size: 28px; margin-bottom: 8px;">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
            <p class="text-muted-light">Your job search dashboard is ready.</p>
        </div>
        <div>
            <?php if (empty($profile['skills']) || empty($profile['bio'])): ?>
                <a href="profile.php" class="btn-primary-gradient">Complete Your Profile</a>
            <?php else: ?>
                <span class="profile-complete"><i class="fas fa-check-circle"></i> Profile Complete</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card"><i class="fas fa-paper-plane" style="font-size: 28px; color: var(--azure-light);"></i><div class="stat-number"><?php echo $total_apps; ?></div><div class="text-muted-light">Total Applications</div></div>
        <div class="stat-card"><i class="fas fa-clock" style="font-size: 28px; color: #f59e0b;"></i><div class="stat-number"><?php echo $pending_apps; ?></div><div class="text-muted-light">Pending Review</div></div>
        <div class="stat-card"><i class="fas fa-star" style="font-size: 28px; color: #06b6d4;"></i><div class="stat-number"><?php echo $shortlisted; ?></div><div class="text-muted-light">Shortlisted</div></div>
        <div class="stat-card"><i class="fas fa-check-circle" style="font-size: 28px; color: #10b981;"></i><div class="stat-number"><?php echo $accepted; ?></div><div class="text-muted-light">Accepted</div></div>
    </div>
    
    <div class="quick-actions">
        <a href="<?php echo SITE_URL; ?>jobs.php" class="btn-primary-gradient"><i class="fas fa-search"></i> Browse Jobs</a>
        <a href="profile.php" class="btn-outline-light"><i class="fas fa-user-edit"></i> Update Profile</a>
        <a href="applications.php" class="btn-outline-light"><i class="fas fa-list"></i> My Applications</a>
        <a href="recommended.php" class="btn-outline-light"><i class="fas fa-magic"></i> Recommended</a>
    </div>
    
    <h2 style="margin-bottom: 20px;">Latest Job Opportunities</h2>
    
    <?php if (!empty($recent_jobs)): ?>
        <div class="jobs-grid">
            <?php foreach ($recent_jobs as $job): ?>
                <div class="job-card">
                    <h3 style="margin-bottom: 8px;"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <div class="text-muted-light" style="margin-bottom: 12px;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></div>
                    <div class="salary-tag" style="margin-bottom: 16px;">₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?></div>
                    <a href="<?php echo SITE_URL; ?>job-details.php?id=<?php echo $job['id']; ?>" class="btn-primary-gradient" style="display: block; text-align: center; padding: 10px;">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; background: rgba(255,255,255,0.03); border-radius: 20px;"><i class="fas fa-briefcase" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i><p class="text-muted-light">No jobs available at the moment.</p></div>
    <?php endif; ?>
    
    <?php if (!empty($recent_apps)): ?>
        <h2 style="margin: 40px 0 20px;">Recent Applications</h2>
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: rgba(37,99,235,0.1);"><tr><th style="padding: 14px; text-align: left;">Job Title</th><th style="padding: 14px; text-align: left;">Status</th><th style="padding: 14px; text-align: left;">Applied On</th><th style="padding: 14px; text-align: left;">Action</th></tr></thead>
                <tbody>
                    <?php foreach ($recent_apps as $app): ?>
                        <tr style="border-top: 1px solid rgba(255,255,255,0.05);"><td style="padding: 14px;"><strong><?php echo htmlspecialchars($app['title']); ?></strong></td><td style="padding: 14px;"><span class="status-badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span></td><td style="padding: 14px;"><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td><td style="padding: 14px;"><a href="<?php echo SITE_URL; ?>job-details.php?id=<?php echo $app['job_id']; ?>" style="color: var(--azure-light);">View Job</a></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 20px;"><a href="applications.php" class="btn-outline-light">View All Applications</a></div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>