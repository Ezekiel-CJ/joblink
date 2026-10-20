<?php
$page_title = "Submit Credentials";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

$user = $db->query("SELECT credential_verified FROM users WHERE id = $user_id")->fetch_assoc();
if ($user['credential_verified'] == 1) {
    redirect('index.php', 'Your credentials are already verified.', 'success');
}

$profile = $db->query("SELECT * FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valid_id_type = sanitize($_POST['valid_id_type'] ?? '');
    $valid_id_number = sanitize($_POST['valid_id_number'] ?? '');
    
    $update = $db->prepare("UPDATE jobseeker_profiles SET valid_id_type = ?, valid_id_number = ? WHERE user_id = ?");
    $update->bind_param("ssi", $valid_id_type, $valid_id_number, $user_id);
    $update->execute();
    
    $uploaded_files = [];
    
    if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['valid_id'], 'credentials/');
        if ($upload['success']) {
            $db->query("UPDATE jobseeker_profiles SET valid_id_path = '{$upload['path']}' WHERE user_id = $user_id");
            $stmt = $db->prepare("INSERT INTO credential_submissions (user_id, user_role, document_type, document_path) VALUES (?, 'jobseeker', 'valid_id', ?)");
            $stmt->bind_param("is", $user_id, $upload['path']);
            $stmt->execute();
        }
    }
    
    if (isset($_FILES['birth_certificate']) && $_FILES['birth_certificate']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['birth_certificate'], 'credentials/');
        if ($upload['success']) {
            $db->query("UPDATE jobseeker_profiles SET birth_certificate_path = '{$upload['path']}' WHERE user_id = $user_id");
            $stmt = $db->prepare("INSERT INTO credential_submissions (user_id, user_role, document_type, document_path) VALUES (?, 'jobseeker', 'birth_certificate', ?)");
            $stmt->bind_param("is", $user_id, $upload['path']);
            $stmt->execute();
        }
    }
    
    $admins = $db->query("SELECT email FROM users WHERE role = 'lgu_admin'");
    while ($admin = $admins->fetch_assoc()) {
        queueEmail($admin['email'], "New Job Seeker Credentials Submitted", "A job seeker has submitted credentials for verification.");
    }
    
    logAudit($user_id, 'submit_credentials', 'jobseeker_profile', $user_id);
    $message = "Credentials submitted successfully! LGU will review within 2-3 business days.";
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    /* Override dashboard main alignment for this page only */
    .dashboard-main {
        margin-left: 0 !important;
        padding-left: 130px !important;
    }
    
    @media (max-width: 1024px) {
        .dashboard-main {
            padding-left: 20px !important;
            padding-top: 70px !important;
        }
    }
    .credential-form { max-width: 600px; margin: 0 auto; }
    .form-section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 28px; margin-bottom: 24px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; }
    .file-input { padding: 10px; background: rgba(255,255,255,0.05); border: 1px dashed rgba(6,182,212,0.3); border-radius: 12px; color: white; width: 100%; }
    .alert-success { background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981; }
    .alert-info { background: rgba(6,182,212,0.15); border: 1px solid #06b6d4; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #06b6d4; }
    .btn-submit { width: 100%; background: linear-gradient(135deg, #2563eb, #06b6d4); border: none; padding: 14px; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; }
</style>

<div class="dashboard-main">
    <div class="credential-form">
        <h1>Identity Verification</h1>
        <p class="text-muted-light">Submit your valid IDs for verification.</p>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="alert-info">
            <i class="fas fa-info-circle"></i> Required documents for verification:
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>Government-issued ID (Passport, Driver's License, National ID, UMID, PRC ID)</li>
                <li>Birth Certificate (PSA/NSO)</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <h3>Valid ID Information</h3>
                <div class="form-group">
                    <label>ID Type *</label>
                    <select name="valid_id_type" required>
                        <option value="">Select ID Type</option>
                        <option value="passport">Passport</option>
                        <option value="drivers_license">Driver's License</option>
                        <option value="national_id">Philippine National ID</option>
                        <option value="umid">UMID</option>
                        <option value="prc_id">PRC ID</option>
                        <option value="postal_id">Postal ID</option>
                        <option value="voters_id">Voter's ID</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID Number *</label>
                    <input type="text" name="valid_id_number" placeholder="Enter your ID number" required>
                </div>
                <div class="form-group">
                    <label>Upload Valid ID (Front & Back) *</label>
                    <input type="file" name="valid_id" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label>Upload Birth Certificate (PSA/NSO) *</label>
                    <input type="file" name="birth_certificate" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit for Verification
            </button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>