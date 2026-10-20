<?php
$page_title = "System Config";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach($_POST['config'] as $key=>$val){ $val = sanitize($val); $db->query("UPDATE system_config SET config_value='$val' WHERE config_key='$key'"); }
    logAudit($_SESSION['user_id'], 'update_system_config', 'system_config', null, ['keys'=>array_keys($_POST['config'])]);
    redirect('config.php', 'Settings saved.', 'success');
}

$configs = $db->query("SELECT * FROM system_config")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<div class="container-premium" style="padding: 30px 0;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1>System Configuration</h1>
        <form method="POST" style="background:rgba(255,255,255,0.03); border-radius:24px; padding:32px;">
            <?php foreach($configs as $cfg): ?>
            <div style="margin-bottom:24px;"><label class="form-label"><?php echo ucfirst(str_replace('_',' ',$cfg['config_key'])); ?></label>
            <div class="text-muted-light" style="font-size:12px; margin-bottom:8px;"><?php echo htmlspecialchars($cfg['description'] ?? ''); ?></div>
            <?php if($cfg['config_key'] === 'matching_weights'): ?>
                <textarea name="config[<?php echo $cfg['config_key']; ?>]" rows="3" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border-radius:12px; color:white; font-family:monospace;"><?php echo htmlspecialchars($cfg['config_value']); ?></textarea>
            <?php else: ?>
                <input type="text" name="config[<?php echo $cfg['config_key']; ?>]" value="<?php echo htmlspecialchars($cfg['config_value']); ?>" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border-radius:12px; color:white;">
            <?php endif; ?></div>
            <?php endforeach; ?>
            <button type="submit" class="btn-primary-gradient" style="width:100%;">Save Settings</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>