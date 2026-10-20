<?php
$page_title = "Verify Employer";
require_once '../../includes/config.php';
require_once '../../includes/email.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized.', 'error');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

$employer = $db->query("SELECT ep.*, u.full_name, u.email FROM employer_profiles ep JOIN users u ON ep.user_id = u.id WHERE ep.user_id = $user_id")->fetch_assoc();
if (!$employer) redirect('employers.php', 'Not found.', 'error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reason = sanitize($_POST['reason'] ?? '');
    if ($action === 'verify') {
        $db->query("UPDATE employer_profiles SET verification_status='verified', verified_by={$_SESSION['user_id']}, verified_at=NOW(), verification_reason=NULL WHERE user_id=$user_id");
        logAudit($_SESSION['user_id'], 'verify_employer', 'employer_profile', $user_id);
        sendEmployerVerificationEmail($employer['email'], $employer['full_name'], $employer['business_name'], 'verified');
        redirect('employers.php', 'Employer verified.', 'success');
    } elseif ($action === 'reject') {
        if (empty($reason)) $error = "Reason required.";
        else { $db->query("UPDATE employer_profiles SET verification_status='rejected', verification_reason='$reason' WHERE user_id=$user_id"); logAudit($_SESSION['user_id'], 'reject_employer', 'employer_profile', $user_id, ['reason'=>$reason]); sendEmployerVerificationEmail($employer['email'], $employer['full_name'], $employer['business_name'], 'rejected', $reason); redirect('employers.php', 'Employer rejected.', 'success'); }
    }
}

require_once '../../includes/header-dashboard.php';
?>

<div class="container-premium" style="padding: 60px 0;"><div style="max-width: 700px; margin: 0 auto;">
    <h1>Verify Business: <?php echo htmlspecialchars($employer['business_name']); ?></h1>
    <div class="detail-section"><div class="info-row"><div class="info-label">Business Name:</div><div><?php echo htmlspecialchars($employer['business_name']); ?></div></div>
    <div class="info-row"><div class="info-label">Contact Person:</div><div><?php echo htmlspecialchars($employer['full_name']); ?></div></div>
    <div class="info-row"><div class="info-label">Email:</div><div><?php echo htmlspecialchars($employer['email']); ?></div></div>
    <?php if($employer['business_address']): ?><div class="info-row"><div class="info-label">Address:</div><div><?php echo htmlspecialchars($employer['business_address']); ?></div></div><?php endif; ?>
    <?php if($employer['business_permit_no']): ?><div class="info-row"><div class="info-label">Permit No:</div><div><?php echo htmlspecialchars($employer['business_permit_no']); ?></div></div><?php endif; ?>
    <?php if($employer['description']): ?><div class="info-row"><div class="info-label">Description:</div><div><?php echo nl2br(htmlspecialchars($employer['description'])); ?></div></div><?php endif; ?>
    
    <form method="POST"><div style="display: flex; gap: 16px; margin: 30px 0;"><button type="button" id="verifyBtn" class="btn-primary-gradient">Verify</button><button type="button" id="rejectBtn" class="btn-outline-light">Reject</button></div>
    <div id="rejectReason" style="display: none;"><textarea name="reason" rows="3" placeholder="Reason for rejection..." style="width:100%; padding:12px; margin-bottom:16px; border-radius:12px;"></textarea><button type="submit" name="action" value="reject" class="btn-danger">Confirm Rejection</button><button type="button" id="cancelReject" class="btn-outline-light">Cancel</button></div>
    <input type="hidden" name="action" id="actionInput"></form>
</div></div>

<script>
    document.getElementById('verifyBtn').onclick = () => { if(confirm('Verify this business?')){ document.getElementById('actionInput').value='verify'; document.getElementById('actionInput').form.submit(); } };
    document.getElementById('rejectBtn').onclick = () => { document.getElementById('rejectReason').style.display='block'; };
    document.getElementById('cancelReject')?.addEventListener('click',()=>{ document.getElementById('rejectReason').style.display='none'; });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>