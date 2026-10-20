<?php
$page_title = "Backup";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();
$tables = ['users','jobseeker_profiles','employer_profiles','jobs','applications','job_categories','skills','audit_logs','system_config'];
$counts = []; foreach($tables as $t){ $c = $db->query("SELECT COUNT(*) FROM $t")->fetch_row()[0]; $counts[$t] = $c; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['export_table'])){ $table = $_POST['table']; $data = $db->query("SELECT * FROM $table")->fetch_all(MYSQLI_ASSOC); header('Content-Type: application/json'); header('Content-Disposition: attachment; filename="'.$table.'_'.date('Y-m-d').'.json"'); echo json_encode($data, JSON_PRETTY_PRINT); exit; }
    if(isset($_POST['full_backup'])){ $backup=[]; foreach($tables as $t){ $backup[$t] = $db->query("SELECT * FROM $t")->fetch_all(MYSQLI_ASSOC); } header('Content-Type: application/json'); header('Content-Disposition: attachment; filename="full_backup_'.date('Y-m-d_H-i-s').'.json"'); echo json_encode($backup, JSON_PRETTY_PRINT); exit; }
}

require_once '../../includes/header-dashboard.php';
?>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Backup & Recovery</h1>
    <div style="background:linear-gradient(135deg,rgba(37,99,235,0.1),rgba(6,182,212,0.05)); border-radius:24px; padding:32px; text-align:center; margin-bottom:40px;"><i class="fas fa-database" style="font-size:48px; color:#10b981;"></i><h2>Complete System Backup</h2><form method="POST"><button type="submit" name="full_backup" class="btn-primary-gradient">Download Full Backup</button></form></div>
    
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:20px;"><?php foreach($counts as $table=>$count): ?><div style="background:rgba(255,255,255,0.03); border-radius:20px; padding:20px;"><h3><?php echo ucfirst(str_replace('_',' ',$table)); ?></h3><div class="stat-number" style="font-size:28px;"><?php echo number_format($count); ?></div><div class="text-muted-light">records</div><form method="POST"><input type="hidden" name="table" value="<?php echo $table; ?>"><button type="submit" name="export_table" class="btn-outline-light" style="margin-top:16px; width:100%;">Export as JSON</button></form></div><?php endforeach; ?></div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>