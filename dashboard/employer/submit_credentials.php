<?php
$page_title = "Submit Credentials";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Check if already verified
$user = $db->query("SELECT credential_verified FROM users WHERE id = $user_id")->fetch_assoc();
if ($user['credential_verified'] == 1) {
    redirect('index.php', 'Your credentials are already verified.', 'success');
}

$employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = sanitize($_POST['business_name'] ?? '');
    $dti_registration_no = sanitize($_POST['dti_registration_no'] ?? '');
    $tin_no = sanitize($_POST['tin_no'] ?? '');
    $mayors_permit_no = sanitize($_POST['mayors_permit_no'] ?? '');
    $business_permit_expiry = sanitize($_POST['business_permit_expiry'] ?? '');
    
    // Update employer profile
    $update = $db->prepare("UPDATE employer_profiles SET 
        business_name = ?, dti_registration_no = ?, tin_no = ?, 
        mayors_permit_no = ?, business_permit_expiry = ? 
        WHERE user_id = ?");
    $update->bind_param("sssssi", $business_name, $dti_registration_no, $tin_no, $mayors_permit_no, $business_permit_expiry, $user_id);
    $update->execute();
    
    // Handle file uploads
    $uploaded_files = [];
    $upload_fields = ['business_permit', 'dti_certificate', 'tin_certificate'];
    
    foreach ($upload_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES[$field], 'credentials/');
            if ($upload['success']) {
                $uploaded_files[] = [
                    'type' => $field,
                    'path' => $upload['path']
                ];
                
                // Save to credential_submissions table
                $stmt = $db->prepare("INSERT INTO credential_submissions (user_id, user_role, document_type, document_path) VALUES (?, 'employer', ?, ?)");
                $stmt->bind_param("iss", $user_id, $field, $upload['path']);
                $stmt->execute();
            }
        }
    }
    
    // Mark that credentials are pending review
    $db->query("UPDATE users SET credential_verified = 0 WHERE id = $user_id");
    
    // Notify LGU admin
    $admins = $db->query("SELECT email FROM users WHERE role = 'lgu_admin'");
    while ($admin = $admins->fetch_assoc()) {
        queueEmail($admin['email'], "New Employer Credentials Submitted", "Employer $business_name has submitted credentials for verification.");
    }
    
    logAudit($user_id, 'submit_credentials', 'employer_profile', $user_id);
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
    .credential-form { max-width: 800px; margin: 0 auto; }
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
        <h1>Business Credentials Verification</h1>
        <p class="text-muted-light">Submit your business documents for LGU verification.</p>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="alert-info">
            <i class="fas fa-info-circle"></i> Required documents for verification:
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>Business Permit / Mayor's Permit</li>
                <li>DTI Registration (for sole proprietorship) or SEC Certificate (for corporation)</li>
                <li>BIR Certificate of Registration (Form 2303)</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <h3>Business Information</h3>
                <div class="form-group">
                    <label>Business Name *</label>
                    <input type="text" name="business_name" value="<?php echo htmlspecialchars($employer['business_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>DTI / SEC Registration No. *</label>
                    <input type="text" name="dti_registration_no" value="<?php echo htmlspecialchars($employer['dti_registration_no'] ?? ''); ?>" required>
                    <p class="text-dim" style="font-size: 11px; margin-top: 4px;">For sole proprietorship: DTI Registration / For corporation: SEC Certificate No.</p>
                </div>
                <div class="form-group">
                    <label>TIN No. *</label>
                    <input type="text" name="tin_no" value="<?php echo htmlspecialchars($employer['tin_no'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mayor's Permit / Business Permit No. *</label>
                    <input type="text" name="mayors_permit_no" value="<?php echo htmlspecialchars($employer['mayors_permit_no'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Business Permit Expiry Date</label>
                    <input type="date" name="business_permit_expiry" value="<?php echo htmlspecialchars($employer['business_permit_expiry'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-section">
                <h3>Upload Documents</h3>
                <div class="form-group">
                    <label>Business / Mayor's Permit *</label>
                    <input type="file" name="business_permit" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                    <p class="text-dim" style="font-size: 11px; margin-top: 4px;">PDF or Image (Max 5MB)</p>
                </div>
                <div class="form-group">
                    <label>DTI / SEC Certificate *</label>
                    <input type="file" name="dti_certificate" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label>BIR Certificate (Form 2303) *</label>
                    <input type="file" name="tin_certificate" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit for Verification
            </button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>