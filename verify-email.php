<?php
$page_title = "Verify Email";
require_once 'includes/config.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Invalid verification link.";
} else {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $update = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("i", $user['id']);
        
        if ($update->execute()) {
            $message = "Email verified successfully! You can now login.";
        } else {
            $error = "Verification failed. Please try again.";
        }
    } else {
        $error = "Invalid or expired verification link.";
    }
}

require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 80px 0; text-align: center;">
    <div style="max-width: 500px; margin: 0 auto;">
        <?php if ($message): ?>
            <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; border-radius: 24px; padding: 40px;">
                <i class="fas fa-check-circle" style="font-size: 64px; color: #10b981; margin-bottom: 20px;"></i>
                <h2>Email Verified!</h2>
                <p style="color: var(--white-muted); margin: 20px 0;"><?php echo $message; ?></p>
                <a href="login.php" class="btn-primary-gradient" style="padding: 12px 32px;">Login Now</a>
            </div>
        <?php else: ?>
            <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; border-radius: 24px; padding: 40px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 64px; color: #ef4444; margin-bottom: 20px;"></i>
                <h2>Verification Failed</h2>
                <p style="color: var(--white-muted); margin: 20px 0;"><?php echo $error; ?></p>
                <a href="register.php" class="btn-primary-gradient" style="padding: 12px 32px;">Register Again</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>