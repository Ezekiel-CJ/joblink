<?php
$page_title = "Manage Jobs";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();
$status = $_GET['status'] ?? 'pending';
$jobs = $db->query("SELECT j.*, u.full_name, ep.business_name FROM jobs j JOIN users u ON j.employer_id = u.id LEFT JOIN employer_profiles ep ON u.id = ep.user_id WHERE j.status = '$status' ORDER BY j.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .filter-bar { display: flex; gap: 12px; margin-bottom: 30px; flex-wrap: wrap; }
    .filter-btn { padding: 8px 24px; border-radius: 40px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--white-muted); text-decoration: none; }
    .filter-btn.active { background: linear-gradient(135deg, #2563eb, #06b6d4); color: white; }
    .jobs-table { width: 100%; border-collapse: collapse; }
    .jobs-table th, .jobs-table td { padding: 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .jobs-table th { background: rgba(37,99,235,0.1); }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Job Postings</h1>
    <div class="filter-bar">
        <a href="?status=pending" class="filter-btn <?php echo $status === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="?status=approved" class="filter-btn <?php echo $status === 'approved' ? 'active' : ''; ?>">Approved</a>
        <a href="?status=rejected" class="filter-btn <?php echo $status === 'rejected' ? 'active' : ''; ?>">Declined</a>
        <a href="?status=closed" class="filter-btn <?php echo $status === 'closed' ? 'active' : ''; ?>">Closed</a>
    </div>
    
    <?php if (!empty($jobs)): ?>
        <div style="background: rgba(255,255,255,0.03); border-radius: 20px; overflow-x: auto;">
            <table class="jobs-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Employer</th>
                        <th>Posted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($job['business_name'] ?? $job['full_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                            <td>
                                <?php 
                                // Change badge text from "Rejected" to "Declined"
                                if ($job['status'] == 'rejected') {
                                    echo '<span class="badge badge-danger">Declined</span>';
                                } else {
                                    echo getStatusBadge($job['status']);
                                }
                                ?>
                            </td>
                            <td><a href="job-approve.php?id=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 6px 16px;">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center" style="padding: 60px;">
            <i class="fas fa-inbox" style="font-size: 48px;"></i>
            <h3>No <?php echo $status === 'rejected' ? 'declined' : $status; ?> jobs</h3>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>