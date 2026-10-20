<?php
$page_title = "Manage Jobs";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to manage jobs.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Handle delete job
if (isset($_GET['delete'])) {
    $job_id = (int)$_GET['delete'];
    $db->query("DELETE FROM jobs WHERE id = $job_id AND employer_id = $user_id");
    logAudit($user_id, 'delete_job', 'job', $job_id);
    redirect('manage-jobs.php', 'Job deleted.', 'success');
}

// Handle close job (manual)
if (isset($_GET['close'])) {
    $job_id = (int)$_GET['close'];
    $db->query("UPDATE jobs SET status = 'closed' WHERE id = $job_id AND employer_id = $user_id AND status = 'approved'");
    logAudit($user_id, 'close_job', 'job', $job_id);
    redirect('manage-jobs.php', 'Job closed successfully.', 'success');
}

// Handle reopen job
if (isset($_GET['reopen'])) {
    $job_id = (int)$_GET['reopen'];
    $db->query("UPDATE jobs SET status = 'approved' WHERE id = $job_id AND employer_id = $user_id AND status = 'closed'");
    logAudit($user_id, 'reopen_job', 'job', $job_id);
    redirect('manage-jobs.php', 'Job reopened successfully.', 'success');
}

// Auto-close jobs that have reached accepted applicants limit
// You can set a custom limit per job by adding a 'hiring_target' column
// For now, we'll close jobs with 3 or more accepted applicants
$auto_close = $db->query("UPDATE jobs j 
                          SET j.status = 'closed' 
                          WHERE j.employer_id = $user_id 
                          AND j.status = 'approved' 
                          AND (
                              SELECT COUNT(*) FROM applications a 
                              WHERE a.job_id = j.id AND a.status = 'accepted'
                          ) >= 3");

if ($auto_close && $db->affected_rows > 0) {
    $_SESSION['info'] = "Some jobs have been automatically closed because they reached the hiring target.";
}

// Get all jobs with applicant counts
$jobs = $db->query("SELECT j.*, c.name as category, 
                    COUNT(DISTINCT a.id) as applicants,
                    SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as hired_count
                    FROM jobs j 
                    LEFT JOIN job_categories c ON j.category_id = c.id 
                    LEFT JOIN applications a ON j.id = a.job_id 
                    WHERE j.employer_id = $user_id 
                    GROUP BY j.id 
                    ORDER BY j.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .filter-bar {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }
    
    .filter-btn {
        padding: 8px 24px;
        border-radius: 40px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: var(--white-muted);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-btn.active {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
    }
    
    .jobs-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .jobs-table th,
    .jobs-table td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .jobs-table th {
        background: rgba(37,99,235,0.1);
        font-weight: 600;
    }
    
    .job-row {
        display: none;
    }
    
    .job-row.show {
        display: table-row;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .hiring-progress {
        width: 100px;
        height: 6px;
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 6px;
    }
    
    .hiring-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #06b6d4);
        border-radius: 3px;
    }
    
    .auto-close-badge {
        background: rgba(6,182,212,0.15);
        color: #06b6d4;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        margin-left: 8px;
    }
    
    @media (max-width: 768px) {
        .jobs-table {
            font-size: 12px;
        }
        .action-buttons {
            flex-direction: column;
        }
    }
        .dashboard-main {
    margin-left: -60px !important;
    padding-left: 200px !important;
}
</style>

<div class="dashboard-main">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Manage Jobs</h1>
        <a href="post-job.php" class="btn-primary-gradient">
            <i class="fas fa-plus-circle"></i> Post New Job
        </a>
    </div>
    
    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="approved">Active</button>
        <button class="filter-btn" data-filter="pending">Pending</button>
        <button class="filter-btn" data-filter="rejected">Declined</button>
        <button class="filter-btn" data-filter="closed">Closed</button>
    </div>
    
    <?php if (!empty($jobs)): ?>
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; overflow-x: auto;">
            <table class="jobs-table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Category</th>
                        <th>Posted</th>
                        <th>Applicants</th>
                        <th>Hired</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): 
                        $show_close_button = ($job['status'] === 'approved');
                        $show_reopen_button = ($job['status'] === 'closed');
                        $can_auto_close = ($job['hired_count'] >= 3 && $job['status'] === 'approved');
                    ?>
                        <tr class="job-row show" data-status="<?php echo $job['status']; ?>">
                            <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($job['category'] ?? '—'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                            <td>
                                <a href="applicants.php?job_id=<?php echo $job['id']; ?>" style="color: var(--azure-light);">
                                    <?php echo $job['applicants']; ?> applicants
                                </a>
                            </td>
                            <td>
                                <?php echo $job['hired_count']; ?> hired
                                <?php if ($can_auto_close): ?>
                                    <div class="auto-close-badge"><i class="fas fa-check-circle"></i> Target reached</div>
                                <?php endif; ?>
                                <div class="hiring-progress">
                                    <div class="hiring-progress-bar" style="width: <?php echo min(100, ($job['hired_count'] / 3) * 100); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                if ($job['status'] == 'rejected') {
                                    echo '<span class="badge badge-danger">Declined</span>';
                                } else {
                                    echo getStatusBadge($job['status']);
                                }
                                ?>
                            </td>
                            <td class="action-buttons">
                                <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 4px 12px;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="applicants.php?job_id=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 4px 12px;">
                                    <i class="fas fa-users"></i> Applicants
                                </a>
                                <?php if ($show_close_button): ?>
                                    <a href="?close=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 4px 12px; color: #f59e0b;" onclick="return confirm('Close this job? It will no longer accept applications.');">
                                        <i class="fas fa-ban"></i> Close
                                    </a>
                                <?php elseif ($show_reopen_button): ?>
                                    <a href="?reopen=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 4px 12px; color: #10b981;" onclick="return confirm('Reopen this job? It will start accepting applications again.');">
                                        <i class="fas fa-play"></i> Reopen
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 4px 12px; color: #ef4444;" onclick="return confirm('Delete this job permanently? This cannot be undone.');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; background: rgba(255,255,255,0.03); border-radius: 20px;">
            <i class="fas fa-briefcase" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
            <h3>No jobs posted yet</h3>
            <a href="post-job.php" class="btn-primary-gradient" style="margin-top: 16px; display: inline-block;">Post a Job</a>
        </div>
    <?php endif; ?>
</div>

<script>
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            document.querySelectorAll('.job-row').forEach(row => {
                if (filter === 'all' || row.dataset.status === filter) row.classList.add('show');
                else row.classList.remove('show');
            });
        });
    });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>