<?php
$page_title = "Business Profile";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to manage your profile.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

$employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc();
if (!$employer) {
    $db->query("INSERT INTO employer_profiles (user_id, business_name, verification_status) VALUES ($user_id, 'Business Name', 'pending')");
    $employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc();
}

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $business_name = sanitize($_POST['business_name'] ?? '');
    $business_type = sanitize($_POST['business_type'] ?? '');
    $business_address = sanitize($_POST['business_address'] ?? '');
    $business_permit_no = sanitize($_POST['business_permit_no'] ?? '');
    $contact_email = sanitize($_POST['contact_email'] ?? $_SESSION['user_email']);
    $contact_phone = sanitize($_POST['contact_phone'] ?? '');
    $website = sanitize($_POST['website'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($business_name)) $error = "Business name is required.";
    else {
        $update = $db->prepare("UPDATE employer_profiles SET business_name = ?, business_type = ?, business_address = ?, business_permit_no = ?, contact_email = ?, contact_phone = ?, website = ?, description = ? WHERE user_id = ?");
        $update->bind_param("ssssssssi", $business_name, $business_type, $business_address, $business_permit_no, $contact_email, $contact_phone, $website, $description, $user_id);
        if ($update->execute()) { $message = "Profile updated!"; logAudit($user_id, 'update_employer_profile', 'employer_profile', $user_id); $employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc(); }
        else $error = "Failed to update.";
    }
}

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload = uploadFile($_FILES['logo'], 'companies/');
    if ($upload['success']) { $db->query("UPDATE employer_profiles SET logo_path = '{$upload['path']}' WHERE user_id = $user_id"); $message = "Logo uploaded!"; $employer['logo_path'] = $upload['path']; }
    else $error = $upload['error'];
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    .profile-section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 32px; margin-bottom: 30px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group textarea { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-family: inherit; }
    .logo-preview { width: 100px; height: 100px; border-radius: 20px; object-fit: cover; margin-bottom: 16px; border: 2px solid var(--azure-light); }
    .alert-success { background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981; }
    .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444; }
    .btn-save { width: 100%; background: linear-gradient(135deg, #2563eb, #06b6d4); border: none; padding: 14px; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; }
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1>Business Profile</h1>
        
        <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-danger"><?php echo $error; ?></div><?php endif; ?>
        
        <div class="profile-section" style="text-align: center;">
            <h3>Company Logo</h3>
            <img src="<?php echo !empty($employer['logo_path']) ? SITE_URL . $employer['logo_path'] : 'https://ui-avatars.com/api/?background=2563eb&color=fff&name=' . urlencode(substr($employer['business_name'], 0, 2)); ?>" id="logoPreview" class="logo-preview">
            <form method="POST" enctype="multipart/form-data" id="logoForm"><input type="file" name="logo" id="logoInput" accept="image/jpeg,image/png,image/webp" style="display: none;"><button type="button" class="btn-outline-light" onclick="document.getElementById('logoInput').click();"><i class="fas fa-camera"></i> Change Logo</button></form>
        </div>
        
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="profile-section">
                <h3>Business Information</h3>
                <div class="form-group"><label>Business Name *</label><input type="text" name="business_name" value="<?php echo htmlspecialchars($employer['business_name']); ?>" required></div>
                <div class="form-group"><label>Business Type</label><input type="text" name="business_type" value="<?php echo htmlspecialchars($employer['business_type'] ?? ''); ?>" placeholder="e.g., Technology, Healthcare, Retail"></div>
                <div class="form-group"><label>Business Address</label><textarea name="business_address" rows="2"><?php echo htmlspecialchars($employer['business_address'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Business Permit Number</label><input type="text" name="business_permit_no" value="<?php echo htmlspecialchars($employer['business_permit_no'] ?? ''); ?>"></div>
                <div class="form-group"><label>Contact Email</label><input type="email" name="contact_email" value="<?php echo htmlspecialchars($employer['contact_email'] ?? $_SESSION['user_email']); ?>"></div>
                <div class="form-group"><label>Contact Phone</label><input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($employer['contact_phone'] ?? ''); ?>"></div>
                <div class="form-group"><label>Website</label><input type="url" name="website" value="<?php echo htmlspecialchars($employer['website'] ?? ''); ?>"></div>
                <div class="form-group"><label>Company Description</label><textarea name="description" rows="4" placeholder="Tell job seekers about your company..."><?php echo htmlspecialchars($employer['description'] ?? ''); ?></textarea></div>
            </div>
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Profile</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('logoInput')?.addEventListener('change', function() { if(this.files && this.files[0]) { const reader = new FileReader(); reader.onload = function(e) { document.getElementById('logoPreview').src = e.target.result; }; reader.readAsDataURL(this.files[0]); this.form.submit(); } });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>