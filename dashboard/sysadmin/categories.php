<?php
$page_title = "Manage Categories";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = sanitize($_POST['name']);
    $desc = sanitize($_POST['description']);
    $db->query("INSERT INTO job_categories (name, description) VALUES ('$name', '$desc')");
    logAudit($_SESSION['user_id'], 'add_category', 'job_category', $db->insert_id);
    redirect('categories.php', 'Category added.', 'success');
}
if (isset($_GET['delete'])) { $id = (int)$_GET['delete']; $db->query("DELETE FROM job_categories WHERE id=$id"); logAudit($_SESSION['user_id'], 'delete_category', 'job_category', $id); redirect('categories.php', 'Deleted.', 'success'); }

$categories = $db->query("SELECT * FROM job_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Job Categories</h1>
    <form method="POST" style="background:rgba(255,255,255,0.03); border-radius:20px; padding:24px; margin-bottom:30px; display:flex; gap:16px; flex-wrap:wrap;"><input type="text" name="name" placeholder="Category Name" required style="flex:1; padding:12px; background:rgba(255,255,255,0.05); border-radius:12px; color:white;"><input type="text" name="description" placeholder="Description" style="flex:2; padding:12px; background:rgba(255,255,255,0.05); border-radius:12px; color:white;"><button type="submit" name="add" class="btn-primary-gradient">Add</button></form>
    
    <div style="background:rgba(255,255,255,0.03); border-radius:20px; overflow-x:auto;"><table style="width:100%; border-collapse:collapse;"><thead><tr style="background:rgba(37,99,235,0.1);"><th style="padding:14px;">ID</th><th style="padding:14px;">Name</th><th style="padding:14px;">Description</th><th style="padding:14px;">Actions</th></tr></thead><tbody><?php foreach($categories as $c): ?><tr style="border-top:1px solid rgba(255,255,255,0.05);"><td style="padding:14px;"><?php echo $c['id']; ?></td><td style="padding:14px;"><strong><?php echo htmlspecialchars($c['name']); ?></strong></td><td style="padding:14px;"><?php echo htmlspecialchars($c['description'] ?? '—'); ?></td><td style="padding:14px;"><a href="?delete=<?php echo $c['id']; ?>" class="btn-outline-light" style="padding:4px 12px;" onclick="return confirm('Delete?')">Delete</a></td></tr><?php endforeach; ?></tbody></table></div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>