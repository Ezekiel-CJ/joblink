<?php
$page_title = "Forgot Password";
require_once 'includes/config.php';
require_once 'includes/email.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $update = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expires, $user['id']);
            $update->execute();
            
            sendPasswordResetEmail($email, $token, $user['full_name']);
            $message = "Password reset link has been sent to your email.";
        } else {
            $error = "No account found with this email address.";
        }
    }
}

require_once 'includes/header.php';
?>

<style>
    .forgot-container {
        max-width: 480px;
        margin: 0 auto;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 32px;
        padding: 48px;
        text-align: center;
    }
    .form-group input {
        width: 100%;
        padding: 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        color: white;
    }
    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border: none;
        padding: 14px;
        border-radius: 40px;
        color: white;
        font-weight: 600;
        cursor: pointer;
    }
    .alert-success { background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981; }
    .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444; }
</style>

<div class="container-premium" style="padding: 80px 0;">
    <div class="forgot-container">
        <i class="fas fa-key" style="font-size: 48px; color: var(--azure-light); margin-bottom: 20px;"></i>
        <h1>Forgot Password?</h1>
        <p class="text-muted-light" style="margin-bottom: 32px;">Enter your email and we'll send you a reset link.</p>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <input type="email" name="email" placeholder="Your email address" required>
            </div>
            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>
        
        <div style="margin-top: 24px;">
            <a href="login.php" style="color: var(--azure-light);">Back to Sign In</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>