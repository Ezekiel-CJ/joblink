<?php
$page_title = "Sign In";
require_once 'includes/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'jobseeker') {
        redirect(SITE_URL . 'dashboard/seeker/');
    } elseif ($user['role'] === 'employer') {
        redirect(SITE_URL . 'dashboard/employer/');
    } elseif ($user['role'] === 'lgu_admin') {
        redirect(SITE_URL . 'dashboard/admin/');
    } elseif ($user['role'] === 'sys_admin') {
        redirect(SITE_URL . 'dashboard/sysadmin/');
    } else {
        redirect(SITE_URL . 'index.php');
    }
}

$error = '';
$success = isset($_GET['registered']) ? 'Account created successfully! Please login.' : '';

// Check if this is admin portal attempt
$is_admin_attempt = isset($_GET['admin']) || isset($_POST['admin_login']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin_login = isset($_POST['admin_login']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("SELECT id, email, password, full_name, role, is_active, is_verified FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // Check if trying to login as admin from regular portal
                if (($user['role'] === 'lgu_admin' || $user['role'] === 'sys_admin') && !$is_admin_login) {
                    $error = "⚠️ This is an admin account. Please use the <a href='?admin=1' style='color: #f87171;'>LGU/Admin Portal</a> to sign in.";
                }
                // Check if regular user trying to login from admin portal
                elseif (($user['role'] === 'jobseeker' || $user['role'] === 'employer') && $is_admin_login) {
                    $error = "⚠️ This is a regular user account. Please use the <a href='login.php' style='color: #f87171;'>regular sign in page</a>.";
                }
                elseif ($password !== $user['password']) {
                    $error = "Invalid email or password.";
                } elseif ($user['is_active'] != 1) {
                    $error = "Your account has been deactivated. Please contact support.";
                } elseif ($user['is_verified'] != 1) {
                    $error = "Please verify your email address first. Check your inbox or spam folder.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['full_name'];
                    
                    logAudit($user['id'], 'login', 'user', $user['id']);
                    
                    if ($user['role'] === 'jobseeker') {
                        redirect(SITE_URL . 'dashboard/seeker/');
                    } elseif ($user['role'] === 'employer') {
                        redirect(SITE_URL . 'dashboard/employer/');
                    } elseif ($user['role'] === 'lgu_admin') {
                        redirect(SITE_URL . 'dashboard/admin/');
                    } elseif ($user['role'] === 'sys_admin') {
                        redirect(SITE_URL . 'dashboard/sysadmin/');
                    } else {
                        redirect(SITE_URL . 'index.php');
                    }
                }
            } else {
                $error = "No account found with this email.";
            }
        }
    }
}

require_once 'includes/header-auth.php';
?>

<form method="POST" action="">
    <?php if ($is_admin_attempt): ?>
        <input type="hidden" name="admin_login" value="1">
        <div style="background: rgba(6,182,212,0.15); border-radius: 16px; padding: 12px; margin-bottom: 20px; text-align: center; border: 1px solid rgba(6,182,212,0.3);">
            <i class="fas fa-shield-alt"></i> <strong>LGU / System Admin Portal</strong>
            <div style="font-size: 12px; margin-top: 4px;">Authorized personnel only</div>
        </div>
    <?php else: ?>
        <div style="background: rgba(59,130,246,0.1); border-radius: 16px; padding: 12px; margin-bottom: 20px; text-align: center;">
            <i class="fas fa-users"></i> <strong>Job Seeker / Employer Portal</strong>
            <div style="font-size: 12px; margin-top: 4px;">Find jobs or hire talent</div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="login_password" placeholder="Enter your password" required>
            <i class="fas fa-eye" id="toggleLoginPassword" style="cursor: pointer; position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"></i>
        </div>
    </div>
    
    <button type="submit" class="btn-auth">
        <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
    
    <div class="auth-links">
        <a href="register.php">Don't have an account? Create one</a>
    </div>
    
    <div class="auth-footer-link">
        <a href="forgot-password.php">Forgot Password?</a>
        <?php if ($is_admin_attempt): ?>
            <span style="color: var(--white-dim); margin: 0 8px">|</span>
            <a href="login.php">Regular User Portal →</a>
        <?php else: ?>
            <span style="color: var(--white-dim); margin: 0 8px">|</span>
            <a href="?admin=1">LGU / Admin Portal →</a>
        <?php endif; ?>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleLoginPassword');
    const passwordField = document.getElementById('login_password');
    
    if (toggleBtn && passwordField) {
        toggleBtn.onclick = function(e) {
            e.preventDefault();
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
            return false;
        };
    }
});
</script>

<?php require_once 'includes/footer-auth.php'; ?>