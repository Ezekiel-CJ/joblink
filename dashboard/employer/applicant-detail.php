<?php
$page_title = "Applicant Details";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') redirect(SITE_URL . 'login.php', 'Unauthorized.', 'error');

$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

$applicant = $db->query("SELECT a.*, j.title as job_title, u.full_name, u.email, u.phone, jp.bio, jp.education, jp.experience, jp.skills, jp.resume_path FROM applications a JOIN jobs j ON a.job_id = j.id JOIN users u ON a.jobseeker_id = u.id LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id WHERE a.id = $app_id AND j.employer_id = {$_SESSION['user_id']}")->fetch_assoc();
if (!$applicant) redirect('applicants.php', 'Not found.', 'error');

$skills = $applicant['skills'] ? explode(',', $applicant['skills']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['employer_notes'] ?? '');
    $db->query("UPDATE applications SET status = '$new_status', employer_notes = '$notes', reviewed_by = {$_SESSION['user_id']}, reviewed_at = NOW() WHERE id = $app_id");
    logAudit($_SESSION['user_id'], 'update_application_status', 'application', $app_id, ['status' => $new_status]);
    redirect("applicant-detail.php?id=$app_id", 'Status updated.', 'success');
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    .detail-section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 28px; margin-bottom: 24px; }
    .detail-label { font-size: 12px; color: var(--white-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .skill-tag { background: rgba(6,182,212,0.15); padding: 6px 14px; border-radius: 30px; font-size: 13px; display: inline-block; margin: 4px; }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <div style="max-width: 900px; margin: 0 auto;">
        <a href="applicants.php" style="color: var(--azure-light);">← Back to Applicants</a>
        <h1><?php echo htmlspecialchars($applicant['full_name']); ?></h1>
        
        <div class="detail-section"><div class="detail-label">Current Status</div><div><?php echo getStatusBadge($applicant['status']); ?></div>
        <form method="POST" style="margin-top: 16px;"><div style="display: flex; gap: 12px; flex-wrap: wrap;"><select name="status" class="filter-select"><option value="pending" <?php echo $applicant['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option><option value="reviewed" <?php echo $applicant['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option><option value="shortlisted" <?php echo $applicant['status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlist</option><option value="accepted" <?php echo $applicant['status'] === 'accepted' ? 'selected' : ''; ?>>Accept</option><option value="rejected" <?php echo $applicant['status'] === 'rejected' ? 'selected' : ''; ?>>Decline</option></select><button type="submit" class="btn-primary-gradient">Update Status</button></div>
        <div class="form-group" style="margin-top: 16px;"><label>Private Notes</label><textarea name="employer_notes" rows="3" class="form-control" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border-radius:12px; color:white;"><?php echo htmlspecialchars($applicant['employer_notes'] ?? ''); ?></textarea></div></form></div>
        
        <div class="detail-section"><div class="detail-label">Contact</div><div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($applicant['email']); ?></div><?php if($applicant['phone']): ?><div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($applicant['phone']); ?></div><?php endif; ?></div>
        
        <?php if($applicant['bio']): ?><div class="detail-section"><div class="detail-label">Professional Summary</div><div><?php echo nl2br(htmlspecialchars($applicant['bio'])); ?></div></div><?php endif; ?>
        <?php if(!empty($skills)): ?><div class="detail-section"><div class="detail-label">Skills</div><div><?php foreach($skills as $s): if(trim($s)): ?><span class="skill-tag"><?php echo htmlspecialchars(trim($s)); ?></span><?php endif; endforeach; ?></div></div><?php endif; ?>
        <?php if($applicant['education']): ?><div class="detail-section"><div class="detail-label">Education</div><div><?php echo nl2br(htmlspecialchars($applicant['education'])); ?></div></div><?php endif; ?>
        <?php if($applicant['experience']): ?><div class="detail-section"><div class="detail-label">Work Experience</div><div><?php echo nl2br(htmlspecialchars($applicant['experience'])); ?></div></div><?php endif; ?>
        <?php if($applicant['cover_letter']): ?><div class="detail-section"><div class="detail-label">Cover Letter</div><div><?php echo nl2br(htmlspecialchars($applicant['cover_letter'])); ?></div></div><?php endif; ?>
        <?php if($applicant['resume_path']): ?><div class="detail-section"><div class="detail-label">Resume</div><a href="<?php echo SITE_URL . $applicant['resume_path']; ?>" target="_blank" class="btn-primary-gradient">Download Resume</a></div><?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>