<?php
$page_title = "Manage Employers";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();
$status = $_GET['status'] ?? 'pending';

$employers = $db->query("SELECT ep.*, u.full_name, u.email, u.created_at 
                         FROM employer_profiles ep 
                         JOIN users u ON ep.user_id = u.id 
                         WHERE ep.verification_status = '$status' 
                         ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .filter-bar { display: flex; gap: 12px; margin-bottom: 30px; }
    .filter-btn { padding: 8px 24px; border-radius: 40px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--white-muted); text-decoration: none; }
    .filter-btn.active { background: linear-gradient(135deg, #2563eb, #06b6d4); color: white; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 14px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .table th { background: rgba(37,99,235,0.1); }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Employers</h1>
    <div class="filter-bar"><a href="?status=pending" class="filter-btn <?php echo $status === 'pending' ? 'active' : ''; ?>">Pending</a><a href="?status=verified" class="filter-btn <?php echo $status === 'verified' ? 'active' : ''; ?>">Verified</a><a href="?status=rejected" class="filter-btn <?php echo $status === 'rejected' ? 'active' : ''; ?>">Rejected</a></div>
    
    <?php if (!empty($employers)): ?><div style="background: rgba(255,255,255,0.03); border-radius: 20px; overflow-x: auto;"><table class="table"><thead><tr><th>Business</th><th>Contact</th><th>Email</th><th>Registered</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($employers as $emp): ?><tr><td><strong><?php echo htmlspecialchars($emp['business_name']); ?></strong></td><td><?php echo htmlspecialchars($emp['full_name']); ?></td><td><?php echo htmlspecialchars($emp['email']); ?></td><td><?php echo date('M d, Y', strtotime($emp['created_at'])); ?></td><td><?php echo getStatusBadge($emp['verification_status']); ?></td><td><a href="employer-verify.php?id=<?php echo $emp['user_id']; ?>" class="btn-outline-light">Review</a></td></tr><?php endforeach; ?></tbody></table></div>
    <?php else: ?><div style="text-align: center; padding: 60px;"><i class="fas fa-building" style="font-size: 48px;"></i><h3>No <?php echo $status; ?> employers</h3></div><?php endif; ?>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>