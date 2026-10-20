<?php
$page_title = "Manage Skills";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = sanitize($_POST['name']); $cat = sanitize($_POST['category']);
    $db->query("INSERT INTO skills (name, category) VALUES ('$name', '$cat')");
    logAudit($_SESSION['user_id'], 'add_skill', 'skill', $db->insert_id);
    redirect('skills.php', 'Skill added.', 'success');
}
if (isset($_GET['delete'])) { $id = (int)$_GET['delete']; $db->query("DELETE FROM skills WHERE id=$id"); logAudit($_SESSION['user_id'], 'delete_skill', 'skill', $id); redirect('skills.php', 'Deleted.', 'success'); }

$skills = $db->query("SELECT * FROM skills ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
$grouped = []; foreach($skills as $s){ $cat = $s['category'] ?? 'Uncategorized'; $grouped[$cat][] = $s; }

require_once '../../includes/header-dashboard.php';
?>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Skill Tags</h1>
    <form method="POST" style="background:rgba(255,255,255,0.03); border-radius:20px; padding:24px; margin-bottom:30px; display:flex; gap:16px; flex-wrap:wrap;"><input type="text" name="name" placeholder="Skill Name" required style="flex:2; padding:12px; background:rgba(255,255,255,0.05); border-radius:12px; color:white;"><input type="text" name="category" placeholder="Category" style="flex:1; padding:12px; background:rgba(255,255,255,0.05); border-radius:12px; color:white;"><button type="submit" name="add" class="btn-primary-gradient">Add</button></form>
    
    <?php foreach($grouped as $cat => $items): ?><div style="background:rgba(255,255,255,0.03); border-radius:20px; padding:20px; margin-bottom:20px;"><h3><?php echo htmlspecialchars($cat); ?></h3><div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:16px;"><?php foreach($items as $s): ?><span class="skill-tag" style="background:rgba(6,182,212,0.15); padding:6px 14px; border-radius:30px;"><?php echo htmlspecialchars($s['name']); ?> <a href="?delete=<?php echo $s['id']; ?>" style="color:#ef4444; margin-left:8px;" onclick="return confirm('Delete?')"><i class="fas fa-times-circle"></i></a></span><?php endforeach; ?></div></div><?php endforeach; ?>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>