<?php
$page_title = "Job Applicants";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to view applicants.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter = $_GET['status'] ?? 'all';

// Get employer's jobs for filter
$employer_jobs = $db->query("SELECT id, title FROM jobs WHERE employer_id = $user_id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Build query for applicants
$query = "SELECT a.*, j.title as job_title, u.full_name as seeker_name, u.email as seeker_email, u.phone as seeker_phone,
          jp.skills, jp.education, jp.experience, jp.resume_path
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN users u ON a.jobseeker_id = u.id 
          LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id 
          WHERE j.employer_id = $user_id";

if ($job_filter > 0) {
    $query .= " AND a.job_id = $job_filter";
}

if ($status_filter !== 'all') {
    $query .= " AND a.status = '$status_filter'";
}

$query .= " ORDER BY 
            CASE a.status 
                WHEN 'pending' THEN 1 
                WHEN 'reviewed' THEN 2 
                WHEN 'shortlisted' THEN 3 
                WHEN 'accepted' THEN 4 
                WHEN 'rejected' THEN 5 
            END, 
            a.created_at DESC";

$applicants = $db->query($query)->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .filter-select, .filter-bar select {
    background-color: #0f172a !important;
    color: #ffffff !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
}

.filter-select option, .filter-bar select option {
    background-color: #1e293b !important;
    color: #ffffff !important;
}

.filter-select option:hover, .filter-bar select option:hover {
    background-color: #2563eb !important;
}

.filter-select:focus, .filter-bar select:focus {
    outline: none;
    border-color: #2563eb !important;
}
    .filter-bar {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 30px;
        padding: 20px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
    }
    
    .filter-select {
        padding: 10px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
    }
    
    .applicant-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .applicant-card:hover {
        transform: translateY(-2px);
        background: rgba(37,99,235,0.05);
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .status-reviewed { background: rgba(59,130,246,0.15); color: #3b82f6; }
    .status-shortlisted { background: rgba(6,182,212,0.15); color: #06b6d4; }
    .status-accepted { background: rgba(16,185,129,0.15); color: #10b981; }
    .status-rejected { background: rgba(239,68,68,0.15); color: #ef4444; }
    
    .skill-tag {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin: 2px;
    }
    
    .status-actions {
        display: flex;
        gap: 10px;
        margin-top: 16px;
        flex-wrap: wrap;
    }
    
    .accepted-actions {
        display: flex;
        gap: 10px;
        margin-top: 16px;
        flex-wrap: wrap;
    }
    
    .rejected-message {
        background: rgba(239,68,68,0.08);
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        margin-top: 16px;
    }
    
    .accepted-badge {
        background: rgba(16,185,129,0.15);
        color: #10b981;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 12px;
        display: inline-block;
    }
    
    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column;
        }
    }
    .dashboard-main {
    margin-left: -60px !important;
    padding-left: 200px !important;
}
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>Job Applicants</h1>
        <p class="text-muted-light">Review and manage applications from job seekers.</p>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <select id="jobFilter" class="filter-select" onchange="applyFilters()">
            <option value="">All Jobs</option>
            <?php foreach ($employer_jobs as $job): ?>
                <option value="<?php echo $job['id']; ?>" <?php echo $job_filter == $job['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($job['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select id="statusFilter" class="filter-select" onchange="applyFilters()">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
            <option value="shortlisted" <?php echo $status_filter === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
            <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Declined</option>
        </select>
        
        <button class="btn-outline-light" onclick="resetFilters()">Reset Filters</button>
    </div>
    
    <?php if (!empty($applicants)): ?>
        <?php foreach ($applicants as $app): ?>
            <div class="applicant-card">
                <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                    <div>
                        <h3 style="margin-bottom: 4px;"><?php echo htmlspecialchars($app['seeker_name']); ?></h3>
                        <div class="text-muted-light" style="margin-bottom: 8px;">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['seeker_email']); ?>
                            <?php if (!empty($app['seeker_phone'])): ?>
                                <span style="margin-left: 16px;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['seeker_phone']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted-light" style="font-size: 14px;">
                            <i class="fas fa-briefcase"></i> Applied for: <strong><?php echo htmlspecialchars($app['job_title']); ?></strong>
                            <span style="margin-left: 16px;"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($app['created_at'])); ?></span>
                        </div>
                    </div>
                    <div>
                        <?php echo getStatusBadge($app['status']); ?>
                    </div>
                </div>
                
                <?php if (!empty($app['cover_letter'])): ?>
                    <div style="margin-top: 16px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 12px;">
                        <div style="font-size: 13px; color: var(--white-dim); margin-bottom: 6px;">Cover Letter:</div>
                        <div class="text-muted-light" style="font-size: 14px;"><?php echo nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 300))); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($app['skills'])): 
                    $skills = explode(',', $app['skills']);
                ?>
                    <div style="margin-top: 12px;">
                        <div style="font-size: 13px; color: var(--white-dim); margin-bottom: 6px;">Skills:</div>
                        <div>
                            <?php foreach ($skills as $skill): ?>
                                <?php if (trim($skill)): ?>
                                    <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- ===================================================== -->
                <!-- ACTION BUTTONS - DIFFERENT FOR ACCEPTED STATUS -->
                <!-- ===================================================== -->
                
                <?php if ($app['status'] === 'accepted'): ?>
                    <!-- ACCEPTED: Only View Resume and Full Details -->
                    <div class="accepted-actions">
                        <div class="accepted-badge">
                            <i class="fas fa-check-circle"></i> Candidate Hired
                        </div>
                        <?php if (!empty($app['resume_path'])): ?>
                            <a href="<?php echo SITE_URL . $app['resume_path']; ?>" target="_blank" class="btn-outline-light" style="padding: 8px 16px;">
                                <i class="fas fa-file-pdf"></i> View Resume
                            </a>
                        <?php endif; ?>
                        <a href="applicant-detail.php?id=<?php echo $app['id']; ?>" class="btn-outline-light" style="padding: 8px 16px;">
                            <i class="fas fa-user"></i> Full Details
                        </a>
                    </div>
                    
                <?php elseif ($app['status'] === 'rejected'): ?>
                    <!-- REJECTED: No actions, just message -->
                    <div class="rejected-message">
                        <i class="fas fa-ban" style="color: #ef4444; margin-right: 8px;"></i>
                        <span class="text-muted-light">This application has been declined.</span>
                        <?php if (!empty($app['resume_path'])): ?>
                            <a href="<?php echo SITE_URL . $app['resume_path']; ?>" target="_blank" class="btn-outline-light" style="margin-left: 12px; padding: 6px 12px;">
                                <i class="fas fa-file-pdf"></i> View Resume
                            </a>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- PENDING, REVIEWED, SHORTLISTED: Full actions -->
                    <div class="status-actions">
                        <form method="POST" action="update-application-status.php" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="status" value="reviewed">
                            <input type="hidden" name="from_page" value="applicants.php">
                            <input type="hidden" name="job_id" value="<?php echo $job_filter; ?>">
                            <button type="submit" class="btn-outline-light" style="padding: 6px 16px;">
                                <i class="fas fa-check"></i> Mark Reviewed
                            </button>
                        </form>
                        <form method="POST" action="update-application-status.php" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="status" value="shortlisted">
                            <input type="hidden" name="from_page" value="applicants.php">
                            <input type="hidden" name="job_id" value="<?php echo $job_filter; ?>">
                            <button type="submit" class="btn-primary-gradient" style="padding: 6px 16px;">
                                <i class="fas fa-star"></i> Shortlist
                            </button>
                        </form>
                        <form method="POST" action="update-application-status.php" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="status" value="accepted">
                            <input type="hidden" name="from_page" value="applicants.php">
                            <input type="hidden" name="job_id" value="<?php echo $job_filter; ?>">
                            <button type="submit" class="btn-primary-gradient" style="background: #10b981; padding: 6px 16px;" onclick="return confirm('Accept this applicant? This will hire them.');">
                                <i class="fas fa-user-check"></i> Accept & Hire
                            </button>
                        </form>
                        <form method="POST" action="update-application-status.php" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="status" value="rejected">
                            <input type="hidden" name="from_page" value="applicants.php">
                            <input type="hidden" name="job_id" value="<?php echo $job_filter; ?>">
                            <button type="submit" class="btn-outline-light" style="color: #ef4444; padding: 6px 16px;" onclick="return confirm('Decline this applicant? This action cannot be undone.');">
                                <i class="fas fa-times"></i> Decline
                            </button>
                        </form>
                        <?php if (!empty($app['resume_path'])): ?>
                            <a href="<?php echo SITE_URL . $app['resume_path']; ?>" target="_blank" class="btn-outline-light" style="padding: 6px 16px;">
                                <i class="fas fa-file-pdf"></i> View Resume
                            </a>
                        <?php endif; ?>
                        <a href="applicant-detail.php?id=<?php echo $app['id']; ?>" class="btn-outline-light" style="padding: 6px 16px;">
                            <i class="fas fa-user"></i> Full Details
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; background: rgba(255,255,255,0.03); border-radius: 20px;">
            <i class="fas fa-inbox" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
            <h3>No applicants yet</h3>
            <p class="text-muted-light">When job seekers apply, they will appear here.</p>
            <a href="post-job.php" class="btn-primary-gradient" style="margin-top: 16px; display: inline-block;">
                <i class="fas fa-plus-circle"></i> Post a Job
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    function applyFilters() {
        const jobFilter = document.getElementById('jobFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        let url = 'applicants.php?';
        if (jobFilter) url += 'job_id=' + jobFilter + '&';
        if (statusFilter && statusFilter !== 'all') url += 'status=' + statusFilter;
        window.location.href = url;
    }
    
    function resetFilters() {
        window.location.href = 'applicants.php';
    }
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>