<?php
$page_title = "Reset Password";
require_once 'includes/config.php';
require_once 'includes/email.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    redirect('forgot-password.php', 'Invalid reset link.', 'error');
}

$db = getDB();
$stmt = $db->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    redirect('forgot-password.php', 'Invalid or expired reset link.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->bind_param("si", $hashed, $user['id']);
        
        if ($update->execute()) {
            logAudit($user['id'], 'password_reset', 'user', $user['id']);
            $success = "Password reset successfully! Redirecting to login...";
            echo '<script>setTimeout(function(){ window.location.href = "login.php"; }, 2000);</script>';
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}

require_once 'includes/header.php';
?>

<style>
    .reset-container {
        max-width: 480px;
        margin: 0 auto;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 32px;
        padding: 48px;
    }
    .password-wrapper { position: relative; }
    .password-wrapper i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--white-muted);
    }
    .form-group input {
        width: 100%;
        padding: 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        color: white;
        padding-right: 45px;
    }
    .btn-reset {
        width: 100%;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border: none;
        padding: 14px;
        border-radius: 40px;
        color: white;
        font-weight: 600;
        cursor: pointer;
    }
    .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444; }
    .alert-success { background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981; }
</style>

<div class="container-premium" style="padding: 80px 0;">
    <div class="reset-container">
        <i class="fas fa-lock" style="font-size: 48px; color: var(--azure-light); margin-bottom: 20px;"></i>
        <h1>Reset Password</h1>
        <p class="text-muted-light" style="margin-bottom: 32px;">Create a new password for <?php echo htmlspecialchars($user['email']); ?></p>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="New Password (min 6 characters)" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
            </div>
            <div class="form-group">
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
                    <i class="fas fa-eye" id="toggleConfirmPassword"></i>
                </div>
            </div>
            <button type="submit" class="btn-reset">Reset Password</button>
        </form>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="login.php" style="color: var(--azure-light);">Back to Sign In</a>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.classList.toggle('fa-eye-slash');
        });
    }
    const toggleConfirm = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');
    if (toggleConfirm && confirmPassword) {
        toggleConfirm.addEventListener('click', function() {
            const type = confirmPassword.type === 'password' ? 'text' : 'password';
            confirmPassword.type = type;
            this.classList.toggle('fa-eye-slash');
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>