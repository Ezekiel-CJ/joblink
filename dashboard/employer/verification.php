<?php
$page_title = "Verification Status";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') redirect(SITE_URL . 'login.php', 'Please login.', 'error');

$user_id = $_SESSION['user_id'];
$db = getDB();

$employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc();
if (!$employer) { $db->query("INSERT INTO employer_profiles (user_id, business_name, verification_status) VALUES ($user_id, 'Business', 'pending')"); $employer = $db->query("SELECT * FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resubmit'])) {
    $db->query("UPDATE employer_profiles SET verification_status = 'pending', verification_reason = NULL, verified_at = NULL, verified_by = NULL WHERE user_id = $user_id");
    logAudit($user_id, 'resubmit_verification', 'employer_profile', $user_id);
    redirect('verification.php', 'Resubmitted for review.', 'success');
}

$status = $employer['verification_status'];
$config = ['pending' => ['color' => '#f59e0b', 'icon' => 'fa-clock', 'text' => 'Pending Review'], 'verified' => ['color' => '#10b981', 'icon' => 'fa-check-circle', 'text' => 'Verified'], 'rejected' => ['color' => '#ef4444', 'icon' => 'fa-times-circle', 'text' => 'Verification Failed']][$status];

require_once '../../includes/header-dashboard.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 700px; margin: 0 auto;">
        
        <!-- Back to Dashboard Button -->
        <div style="margin-bottom: 30px;">
        </div>
        
        <!-- Verification Status Card -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 28px; padding: 48px; text-align: center;">
            
            <i class="fas <?php echo $config['icon']; ?>" style="font-size: 64px; color: <?php echo $config['color']; ?>; margin-bottom: 24px;"></i>
            
            <h1 style="font-size: 32px; margin-bottom: 16px;"><?php echo $config['text']; ?></h1>
            
            <?php if ($status === 'pending'): ?>
                <p class="text-muted-light" style="margin-bottom: 16px;">Your business verification is being reviewed. This takes 1-2 business days.</p>
                
            <?php elseif ($status === 'verified'): ?>
                <p class="text-muted-light" style="margin-bottom: 24px;">Your business is verified. You can now post jobs.</p>
                <a href="post-job.php" class="btn-primary-gradient" style="padding: 12px 32px; display: inline-block;">
                    <i class="fas fa-plus-circle"></i> Post a Job
                </a>
                
            <?php elseif ($status === 'rejected'): ?>
                <p class="text-muted-light" style="margin-bottom: 16px;">Your verification was not approved.</p>
                <?php if (!empty($employer['verification_reason'])): ?>
                    <div style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; border-radius: 16px; padding: 16px; margin: 20px auto; max-width: 500px; text-align: left;">
                        <strong style="color: #ef4444;">Reason:</strong>
                        <p class="text-muted-light" style="margin-top: 8px;"><?php echo htmlspecialchars($employer['verification_reason']); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Resubmit Button - Fixed spacing -->
                <div style="margin-top: 32px;">
                    <form method="POST" action="">
                        <button type="submit" name="resubmit" class="btn-primary-gradient" style="padding: 14px 32px;">
                            <i class="fas fa-redo"></i> Resubmit for Review
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 40px; background: rgba(6,182,212,0.1); border-radius: 20px; padding: 20px;">
                <i class="fas fa-info-circle" style="color: var(--azure-light);"></i>
                <p class="text-muted-light" style="margin-top: 8px; font-size: 13px;">
                    Complete your business profile to get verified.
                </p>
                <a href="profile.php" class="btn-outline-light" style="margin-top: 12px; display: inline-block;">
                    Update Business Profile
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>