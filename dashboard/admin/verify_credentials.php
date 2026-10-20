<?php
$page_title = "Verify Credentials";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');
}

$db = getDB();
$type = isset($_GET['type']) ? $_GET['type'] : 'employer';
$message = '';
$error = '';

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? sanitize($_POST['reason']) : '';
    
    if ($action === 'approve') {
        $db->query("UPDATE users SET credential_verified = 1, credential_verified_at = NOW(), credential_rejection_reason = NULL WHERE id = $user_id");
        logAudit($_SESSION['user_id'], 'approve_credentials', 'user', $user_id);
        
        // Get user email for notification
        $user = $db->query("SELECT email, full_name FROM users WHERE id = $user_id")->fetch_assoc();
        if ($user) {
            queueEmail($user['email'], "Your Credentials Have Been Approved", "Dear {$user['full_name']},\n\nYour submitted credentials have been approved by LGU. You can now fully access the platform.\n\nBest regards,\nJobLinkLocal Team");
        }
        
        $message = "Credentials approved successfully.";
    } elseif ($action === 'reject') {
        if (empty($reason)) {
            $error = "Please provide a reason for rejection.";
        } else {
            $db->query("UPDATE users SET credential_verified = 0, credential_rejection_reason = '" . $db->real_escape_string($reason) . "' WHERE id = $user_id");
            logAudit($_SESSION['user_id'], 'reject_credentials', 'user', $user_id, ['reason' => $reason]);
            
            $user = $db->query("SELECT email, full_name FROM users WHERE id = $user_id")->fetch_assoc();
            if ($user) {
                queueEmail($user['email'], "Your Credentials Need Revision", "Dear {$user['full_name']},\n\nYour submitted credentials were not approved.\n\nReason: $reason\n\nPlease resubmit corrected documents.\n\nBest regards,\nJobLinkLocal Team");
            }
            
            $message = "Credentials rejected.";
        }
    }
}

// Get pending verifications
if ($type === 'employer') {
    $pending = $db->query("SELECT u.id, u.email, u.full_name, u.created_at, 
                           ep.business_name, ep.dti_registration_no, ep.tin_no, 
                           ep.mayors_permit_no, ep.business_permit_expiry,
                           cs.document_path, cs.document_type, cs.submitted_at
                           FROM users u
                           JOIN employer_profiles ep ON u.id = ep.user_id
                           LEFT JOIN credential_submissions cs ON u.id = cs.user_id AND cs.user_role = 'employer'
                           WHERE (u.credential_verified = 0 OR u.credential_verified IS NULL) AND u.role = 'employer'
                           GROUP BY u.id
                           ORDER BY cs.submitted_at ASC");
} else {
    $pending = $db->query("SELECT u.id, u.email, u.full_name, u.created_at,
                           jp.valid_id_type, jp.valid_id_number, jp.valid_id_path, jp.birth_certificate_path,
                           cs.document_path, cs.document_type, cs.submitted_at
                           FROM users u
                           JOIN jobseeker_profiles jp ON u.id = jp.user_id
                           LEFT JOIN credential_submissions cs ON u.id = cs.user_id AND cs.user_role = 'jobseeker'
                           WHERE (u.credential_verified = 0 OR u.credential_verified IS NULL) AND u.role = 'jobseeker'
                           GROUP BY u.id
                           ORDER BY cs.submitted_at ASC");
}

$pending_list = $pending ? $pending->fetch_all(MYSQLI_ASSOC) : [];

require_once '../../includes/header-dashboard.php';
?>

<style>
/* Override dashboard main alignment for this page only */
    .dashboard-main {
        margin-left: 0 !important;
        padding-left: 150px !important;
    }
    
    @media (max-width: 1024px) {
        .dashboard-main {
            padding-left: 20px !important;
            padding-top: 70px !important;
        }
    }

    .filter-bar { display: flex; gap: 12px; margin-bottom: 30px; }
    .filter-btn { padding: 8px 24px; border-radius: 40px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--white-muted); text-decoration: none; }
    .filter-btn.active { background: linear-gradient(135deg, #2563eb, #06b6d4); color: white; }
    .credential-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 24px; margin-bottom: 24px; }
    .doc-link { color: var(--azure-light); text-decoration: none; margin-right: 16px; }
    .doc-link:hover { text-decoration: underline; }
    .reason-textarea { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; margin: 12px 0; display: none; }
    .btn-approve { background: rgba(16,185,129,0.15); color: #10b981; border: none; padding: 8px 20px; border-radius: 30px; cursor: pointer; }
    .btn-reject { background: rgba(239,68,68,0.15); color: #ef4444; border: none; padding: 8px 20px; border-radius: 30px; cursor: pointer; margin-left: 12px; }
    .alert-success { background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981; }
    .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444; }
    .info-row { margin-bottom: 6px; }
    .info-label { font-size: 12px; color: var(--white-dim); }
    .info-value { font-size: 14px; }
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>Verify Credentials</h1>
        <p class="text-muted-light">Review and verify submitted credentials from employers and job seekers.</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="filter-bar">
        <a href="?type=employer" class="filter-btn <?php echo $type === 'employer' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i> Employers
        </a>
        <a href="?type=jobseeker" class="filter-btn <?php echo $type === 'jobseeker' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> Job Seekers
        </a>
    </div>
    
    <?php if (!empty($pending_list)): ?>
        <?php foreach ($pending_list as $item): ?>
            <div class="credential-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
                    <div>
                        <h3><?php echo htmlspecialchars($item['full_name'] ?? ''); ?></h3>
                        <div class="text-muted-light"><?php echo htmlspecialchars($item['email'] ?? ''); ?></div>
                        
                        <?php if ($type === 'employer'): ?>
                            <div class="info-row" style="margin-top: 12px;">
                                <div class="info-label">Business Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($item['business_name'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">DTI/SEC Registration No.</div>
                                <div class="info-value"><?php echo htmlspecialchars($item['dti_registration_no'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">TIN No.</div>
                                <div class="info-value"><?php echo htmlspecialchars($item['tin_no'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Mayor's/Business Permit No.</div>
                                <div class="info-value"><?php echo htmlspecialchars($item['mayors_permit_no'] ?? 'Not provided'); ?></div>
                            </div>
                            <?php if (!empty($item['business_permit_expiry'])): ?>
                            <div class="info-row">
                                <div class="info-label">Permit Expiry</div>
                                <div class="info-value"><?php echo date('M d, Y', strtotime($item['business_permit_expiry'])); ?></div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="info-row" style="margin-top: 12px;">
                                <div class="info-label">Valid ID Type</div>
                                <div class="info-value"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($item['valid_id_type'] ?? 'Not provided'))); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">ID Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($item['valid_id_number'] ?? 'Not provided'); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-dim" style="font-size: 12px;">
                        <?php if (!empty($item['submitted_at'])): ?>
                            Submitted: <?php echo date('M d, Y h:i A', strtotime($item['submitted_at'])); ?>
                        <?php else: ?>
                            Submitted: Pending
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.05);">
                    <strong>Documents:</strong>
                    <?php if ($type === 'employer'): ?>
                        <div style="margin-top: 8px;">
                            <?php
                            $docs = $db->query("SELECT document_type, document_path FROM credential_submissions WHERE user_id = {$item['id']} AND user_role = 'employer'");
                            if ($docs && $docs->num_rows > 0):
                                while ($doc = $docs->fetch_assoc()):
                            ?>
                                <a href="<?php echo SITE_URL . $doc['document_path']; ?>" target="_blank" class="doc-link">
                                    <i class="fas fa-file-pdf"></i> <?php echo str_replace('_', ' ', ucfirst($doc['document_type'])); ?>
                                </a>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <span class="text-dim">No documents uploaded yet</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 8px;">
                            <?php if (!empty($item['valid_id_path'])): ?>
                                <a href="<?php echo SITE_URL . $item['valid_id_path']; ?>" target="_blank" class="doc-link">
                                    <i class="fas fa-id-card"></i> Valid ID
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($item['birth_certificate_path'])): ?>
                                <a href="<?php echo SITE_URL . $item['birth_certificate_path']; ?>" target="_blank" class="doc-link">
                                    <i class="fas fa-file-alt"></i> Birth Certificate
                                </a>
                            <?php endif; ?>
                            <?php if (empty($item['valid_id_path']) && empty($item['birth_certificate_path'])): ?>
                                <span class="text-dim">No documents uploaded yet</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" style="margin-top: 20px;">
                    <input type="hidden" name="user_id" value="<?php echo $item['id']; ?>">
                    
                    <textarea name="reason" class="reason-textarea" id="reason_<?php echo $item['id']; ?>" placeholder="Reason for rejection..."></textarea>
                    
                    <div>
                        <button type="submit" name="action" value="approve" class="btn-approve" onclick="return confirm('Approve this user\'s credentials?')">
                            <i class="fas fa-check-circle"></i> Approve
                        </button>
                        <button type="button" class="btn-reject" onclick="showRejectReason(<?php echo $item['id']; ?>)">
                            <i class="fas fa-times-circle"></i> Decline
                        </button>
                        <button type="submit" name="action" value="reject" class="btn-reject" id="submit_reject_<?php echo $item['id']; ?>" style="display: none;">
                            <i class="fas fa-check"></i> Confirm Decline
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center" style="padding: 60px; background: rgba(255,255,255,0.03); border-radius: 20px;">
            <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981;"></i>
            <h3>No Pending Verifications</h3>
            <p class="text-muted-light">All <?php echo $type; ?> credentials have been verified.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    function showRejectReason(userId) {
        var reasonField = document.getElementById('reason_' + userId);
        var submitBtn = document.getElementById('submit_reject_' + userId);
        if (reasonField) reasonField.style.display = 'block';
        if (submitBtn) submitBtn.style.display = 'inline-block';
    }
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>