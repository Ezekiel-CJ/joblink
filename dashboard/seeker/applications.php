<?php
$page_title = "My Applications";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login to view your applications.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Add is_archived column if not exists
$db->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS is_archived TINYINT DEFAULT 0");
$db->query("ALTER TABLE applications ADD INDEX IF NOT EXISTS idx_archived (is_archived)");

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $app_id = (int)$_GET['delete'];
    $db->query("DELETE FROM applications WHERE id = $app_id AND jobseeker_id = $user_id");
    $_SESSION['success'] = "Application deleted successfully.";
    redirect('applications.php');
}

// Handle Edit
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $app_id = (int)$_GET['edit'];
    $check = $db->query("SELECT id FROM applications WHERE id = $app_id AND jobseeker_id = $user_id AND status = 'pending' AND is_archived = 0");
    if ($check->num_rows > 0) {
        redirect(SITE_URL . 'edit-application.php?id=' . $app_id);
    } else {
        $_SESSION['error'] = "You can only edit pending applications.";
        redirect('applications.php');
    }
}

// Get ONLY active applications (not archived)
$applications = $db->query("SELECT a.*, j.title, j.location, j.salary_min, j.salary_max, j.employment_type,
                            u.full_name as employer_name, ep.business_name, ep.logo_path
                            FROM applications a 
                            JOIN jobs j ON a.job_id = j.id 
                            JOIN users u ON j.employer_id = u.id 
                            LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                            WHERE a.jobseeker_id = $user_id AND a.is_archived = 0
                            ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Calculate rejection counts per job (for cooldown)
$rejection_counts = [];
foreach ($applications as $app) {
    $job_id = $app['job_id'];
    if (!isset($rejection_counts[$job_id])) {
        $rej = $db->query("SELECT COUNT(*) as cnt FROM applications WHERE job_id = $job_id AND jobseeker_id = $user_id AND status = 'rejected'");
        $rejection_counts[$job_id] = $rej->fetch_assoc()['cnt'];
    }
}

$status_counts = ['total'=>0,'pending'=>0,'reviewed'=>0,'shortlisted'=>0,'accepted'=>0,'rejected'=>0];
foreach ($applications as $app) {
    $status_counts['total']++;
    if (isset($status_counts[$app['status']])) $status_counts[$app['status']]++;
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    /* =====================================================
       PREMIUM APPLICATIONS PAGE STYLES
    ===================================================== */
    
    .apps-stats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .apps-stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px 16px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.2, 0, 0, 1);
        backdrop-filter: blur(10px);
    }
    
    .apps-stat-card:hover {
        transform: translateY(-4px);
        background: linear-gradient(135deg, rgba(37,99,235,0.08), rgba(6,182,212,0.04));
        border-color: rgba(37,99,235,0.3);
    }
    
    .apps-stat-number {
        font-size: 36px;
        font-weight: 800;
        letter-spacing: -1px;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #ffffff, #22d3ee);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    .apps-stat-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
    }
    
    /* Filter Bar */
    .apps-filter-bar {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 32px;
        padding: 8px 0;
    }
    
    .apps-filter-btn {
        padding: 8px 24px;
        border-radius: 40px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        color: rgba(255,255,255,0.7);
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 13px;
        font-weight: 500;
    }
    
    .apps-filter-btn:hover {
        background: rgba(37,99,235,0.1);
        border-color: rgba(37,99,235,0.3);
        color: white;
    }
    
    .apps-filter-btn.active {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
        border-color: transparent;
    }
    
    /* Applications Container */
    .apps-container {
        background: rgba(255,255,255,0.02);
        border-radius: 28px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.05);
    }
    
    /* Table Styles */
    .apps-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .apps-table th {
        padding: 18px 20px;
        text-align: left;
        background: rgba(37,99,235,0.06);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.6);
    }
    
    .apps-table td {
        padding: 20px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        vertical-align: middle;
    }
    
    .apps-table tr:last-child td {
        border-bottom: none;
    }
    
    .apps-table tr:hover td {
        background: rgba(37,99,235,0.04);
    }
    
    /* Job Info Cell */
    .job-info-cell {
        min-width: 250px;
    }
    
    .job-title {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 6px;
        color: white;
    }
    
    .job-company {
        font-size: 13px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 4px;
    }
    
    .job-location {
        font-size: 12px;
        color: rgba(255,255,255,0.4);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Employer Cell */
    .employer-cell {
        min-width: 150px;
    }
    
    .employer-name {
        font-weight: 500;
        margin-bottom: 4px;
    }
    
    .employer-type {
        font-size: 11px;
        color: rgba(255,255,255,0.4);
        text-transform: uppercase;
    }
    
    /* Salary Cell */
    .salary-cell {
        min-width: 140px;
    }
    
    .salary-amount {
        font-weight: 600;
        color: #22d3ee;
        font-size: 14px;
        margin-bottom: 4px;
    }
    
    .employment-type {
        font-size: 11px;
        color: rgba(255,255,255,0.4);
        text-transform: capitalize;
    }
    
    /* Status Cell */
    .status-cell {
        min-width: 130px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 14px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    
    .status-pending { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); }
    .status-reviewed { background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2); }
    .status-shortlisted { background: rgba(6,182,212,0.15); color: #06b6d4; border: 1px solid rgba(6,182,212,0.2); }
    .status-accepted { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.2); }
    .status-rejected { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
    
    .reject-badge {
        background: rgba(139,92,246,0.15);
        color: #a78bfa;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 500;
        display: inline-block;
    }
    
    .cooldown-badge {
        background: rgba(245,158,11,0.15);
        color: #fbbf24;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 500;
        margin-top: 8px;
        display: inline-block;
    }
    
    /* Date Cell */
    .date-cell {
        min-width: 100px;
        font-size: 13px;
        color: rgba(255,255,255,0.5);
    }
    
    /* Actions Cell */
    .actions-cell {
        min-width: 200px;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-action {
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }
    
    .btn-view {
        background: rgba(255,255,255,0.05);
        color: rgba(255,255,255,0.7);
    }
    
    .btn-view:hover {
        background: rgba(59,130,246,0.2);
        color: white;
    }
    
    .btn-edit {
        background: rgba(6,182,212,0.1);
        color: #06b6d4;
    }
    
    .btn-edit:hover {
        background: rgba(6,182,212,0.2);
        transform: translateY(-1px);
    }
    
    .btn-delete {
        background: rgba(239,68,68,0.1);
        color: #ef4444;
    }
    
    .btn-delete:hover {
        background: rgba(239,68,68,0.2);
    }
    
    .btn-reapply {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
    }
    
    .btn-reapply:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37,99,235,0.3);
    }
    
    .btn-disabled {
        background: rgba(100,100,100,0.1);
        color: #888;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    /* Empty State */
    .apps-empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .apps-empty-state i {
        font-size: 64px;
        color: rgba(255,255,255,0.06);
        margin-bottom: 20px;
    }
    
    .apps-empty-state h3 {
        font-size: 20px;
        margin-bottom: 10px;
    }
    
    /* Info Note */
    .apps-info-note {
        text-align: center;
        margin-top: 24px;
        padding: 16px;
        background: rgba(6,182,212,0.05);
        border-radius: 16px;
    }
    
    .apps-info-note i {
        color: #06b6d4;
        margin-right: 8px;
    }
    
    /* Responsive */
    @media (max-width: 1100px) {
        .apps-stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .apps-stat-number {
            font-size: 28px;
        }
    }
    
    @media (max-width: 900px) {
        .apps-container {
            overflow-x: auto;
        }
        .apps-table {
            min-width: 800px;
        }
    }
    
    @media (max-width: 600px) {
        .apps-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .action-buttons {
            flex-direction: column;
        }
        .btn-action {
            justify-content: center;
        }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .application-row {
        animation: slideIn 0.3s ease forwards;
    }
        .dashboard-main {
    margin-left: -60px !important;
    padding-left: 200px !important;
}
</style>

<div class="dashboard-main">
    <!-- Page Header -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 32px; font-weight: 700; margin-bottom: 8px;">My Applications</h1>
        <p class="text-muted-light">Track and manage all your job applications in one place.</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="apps-stats-grid">
        <div class="apps-stat-card">
            <div class="apps-stat-number"><?php echo $status_counts['total']; ?></div>
            <div class="apps-stat-label">Total Applications</div>
        </div>
        <div class="apps-stat-card">
            <div class="apps-stat-number" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); -webkit-background-clip: text;"><?php echo $status_counts['pending']; ?></div>
            <div class="apps-stat-label">Pending Review</div>
        </div>
        <div class="apps-stat-card">
            <div class="apps-stat-number" style="background: linear-gradient(135deg, #3b82f6, #60a5fa); -webkit-background-clip: text;"><?php echo $status_counts['reviewed']; ?></div>
            <div class="apps-stat-label">Reviewed</div>
        </div>
        <div class="apps-stat-card">
            <div class="apps-stat-number" style="background: linear-gradient(135deg, #06b6d4, #22d3ee); -webkit-background-clip: text;"><?php echo $status_counts['shortlisted']; ?></div>
            <div class="apps-stat-label">Shortlisted</div>
        </div>
        <div class="apps-stat-card">
            <div class="apps-stat-number" style="background: linear-gradient(135deg, #10b981, #34d399); -webkit-background-clip: text;"><?php echo $status_counts['accepted']; ?></div>
            <div class="apps-stat-label">Accepted</div>
        </div>
        <div class="apps-stat-card">
            <div class="apps-stat-number" style="background: linear-gradient(135deg, #ef4444, #f87171); -webkit-background-clip: text;"><?php echo $status_counts['rejected']; ?></div>
            <div class="apps-stat-label">Rejected</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="apps-filter-bar">
        <button class="apps-filter-btn active" data-filter="all">All Applications</button>
        <button class="apps-filter-btn" data-filter="pending">Pending</button>
        <button class="apps-filter-btn" data-filter="reviewed">Reviewed</button>
        <button class="apps-filter-btn" data-filter="shortlisted">Shortlisted</button>
        <button class="apps-filter-btn" data-filter="accepted">Accepted</button>
        <button class="apps-filter-btn" data-filter="rejected">Rejected</button>
    </div>
    
    <!-- Applications Table -->
    <?php if (!empty($applications)): ?>
        <div class="apps-container">
            <table class="apps-table">
                <thead>
                    <tr>
                        <th>Job Position</th>
                        <th>Employer</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): 
                        $reject_count = $rejection_counts[$app['job_id']] ?? 0;
                        $can_reapply = ($app['status'] == 'rejected');
                        $can_reapply_now = true;
                        $days_left = 0;
                        
                        if ($can_reapply && $reject_count >= 3) {
                            $last = $db->query("SELECT updated_at FROM applications WHERE job_id = {$app['job_id']} AND jobseeker_id = $user_id AND status = 'rejected' ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
                            if ($last) {
                                $days = floor((time() - strtotime($last['updated_at'])) / 86400);
                                if ($days < 7) {
                                    $can_reapply_now = false;
                                    $days_left = 7 - $days;
                                }
                            }
                        }
                    ?>
                        <tr class="application-row show" data-status="<?php echo $app['status']; ?>">
                            <!-- Job Position -->
                            <td class="job-info-cell">
                                <div class="job-title"><?php echo htmlspecialchars($app['title']); ?></div>
                                <div class="job-company">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($app['business_name'] ?? $app['employer_name']); ?>
                                </div>
                                <div class="job-location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location'] ?: 'Remote'); ?>
                                </div>
                            </td>
                            
                            <!-- Employer -->
                            <td class="employer-cell">
                                <div class="employer-name"><?php echo htmlspecialchars($app['employer_name']); ?></div>
                                <div class="employer-type"><?php echo ucfirst(str_replace('-', ' ', $app['employment_type'] ?? 'Full-time')); ?></div>
                            </td>
                            
                            <!-- Salary -->
                            <td class="salary-cell">
                                <div class="salary-amount">₱<?php echo number_format($app['salary_min']); ?> - ₱<?php echo number_format($app['salary_max']); ?></div>
                            </td>
                            
                            <!-- Status -->
                            <td class="status-cell">
                                <span class="status-badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                <?php if ($reject_count > 0 && $app['status'] == 'rejected'): ?>
                                    <div class="reject-badge"><i class="fas fa-times-circle"></i> Rejected <?php echo $reject_count; ?>x</div>
                                <?php endif; ?>
                                <?php if (!$can_reapply_now && $can_reapply): ?>
                                    <div class="cooldown-badge"><i class="fas fa-hourglass-half"></i> Cooldown: <?php echo $days_left; ?>d</div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Date -->
                            <td class="date-cell">
                                <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                            </td>
                            
                            <!-- Actions -->
                            <td class="actions-cell">
                                <div class="action-buttons">
                                    <a href="<?php echo SITE_URL; ?>job-details.php?id=<?php echo $app['job_id']; ?>" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    
                                    <?php if ($app['status'] == 'pending'): ?>
                                        <a href="?edit=<?php echo $app['id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Show delete button only when:
                                    // 1. Status is pending, OR
                                    // 2. Status is rejected AND reapply is available (no cooldown)
                                    $showDelete = false;
                                    
                                    if ($app['status'] == 'pending') {
                                        $showDelete = true;
                                    } elseif ($app['status'] == 'rejected') {
                                        // Only show delete button when reapply is available (cooldown is over)
                                        if ($can_reapply && $can_reapply_now) {
                                            $showDelete = true;
                                        }
                                    }
                                    
                                    if ($showDelete): 
                                    ?>
                                        <a href="?delete=<?php echo $app['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this application? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['status'] == 'rejected' && $can_reapply_now): ?>
                                        <a href="reapply-handler.php?id=<?php echo $app['id']; ?>" class="btn-action btn-reapply" onclick="return confirm('Reapply for this position? Your previous application will be archived.')">
                                            <i class="fas fa-redo-alt"></i> Reapply
                                        </a>
                                    <?php elseif ($app['status'] == 'rejected' && !$can_reapply_now): ?>
                                        <span class="btn-action btn-disabled">
                                            <i class="fas fa-clock"></i> Reapply (<?php echo $days_left; ?>d)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="apps-info-note">
            <i class="fas fa-info-circle"></i>
            <span class="text-muted-light">Only your latest active application is shown. Old applications are archived when you reapply.</span>
        </div>
        
    <?php else: ?>
        <div class="apps-empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Applications Yet</h3>
            <p class="text-muted-light">Start applying to jobs and they will appear here.</p>
            <a href="<?php echo SITE_URL; ?>jobs.php" class="btn-primary-gradient" style="margin-top: 20px; display: inline-block;">
                <i class="fas fa-search"></i> Browse Jobs
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    // Filter functionality
    document.querySelectorAll('.apps-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.apps-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filter rows
            const filter = this.dataset.filter;
            const rows = document.querySelectorAll('.application-row');
            
            rows.forEach(row => {
                if (filter === 'all' || row.dataset.status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>