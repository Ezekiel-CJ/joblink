<?php
$page_title = "Create Your Account";
require_once 'includes/config.php';
require_once 'includes/email.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    redirect(redirectForRole($user['role']));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'jobseeker';
    $terms_accepted = isset($_POST['terms_accepted']) ? 1 : 0;
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!$terms_accepted) {
        $error = "You must accept the Terms & Conditions.";
    } elseif (!in_array($role, ['jobseeker', 'employer'])) {
        $error = "Invalid role selected.";
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already registered. Please <a href='login.php'>login instead</a>.";
        } else {
            $verification_token = bin2hex(random_bytes(32));
            $terms_accepted_at = date('Y-m-d H:i:s');
            
            $stmt = $db->prepare("INSERT INTO users (email, password, full_name, role, terms_accepted, terms_accepted_at, verification_token, is_verified, is_active) VALUES (?, ?, ?, ?, 1, ?, ?, 0, 1)");
            $stmt->bind_param("ssssss", $email, $password, $full_name, $role, $terms_accepted_at, $verification_token);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                
                if ($role === 'jobseeker') {
                    $db->query("INSERT INTO jobseeker_profiles (user_id) VALUES ($user_id)");
                } elseif ($role === 'employer') {
                    $business_name = $full_name . "'s Business";
                    $db->query("INSERT INTO employer_profiles (user_id, business_name, verification_status) VALUES ($user_id, '$business_name', 'pending')");
                }
                
                logAudit($user_id, 'register', 'user', $user_id, ['role' => $role]);
                
                sendWelcomeEmail($user_id, $full_name, $email, $role);
                sendVerificationEmail($email, $verification_token, $full_name);
                
                $success = "Account created! A verification email has been sent to <strong>$email</strong>.<br><br>Please check your inbox and spam folder.";
                
                echo '<script>setTimeout(function(){ window.location.href = "login.php?registered=1"; }, 4000);</script>';
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

require_once 'includes/header-auth.php';
?>

<form method="POST" action="">
    <?php if ($error): ?>
        <div class="alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert-success"><?php echo $success; ?></div>
        <div class="info-box">
            <i class="fas fa-envelope"></i> Didn't receive the email? Check your <strong>Spam/Junk folder</strong>.
        </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label>I am a...</label>
        <div class="role-selector">
            <div class="role-option <?php echo (!isset($_POST['role']) || $_POST['role'] == 'jobseeker') ? 'selected' : ''; ?>" data-role="jobseeker">
                <i class="fas fa-user"></i>
                <h3>Job Seeker</h3>
                <p>Looking for work</p>
            </div>
            <div class="role-option <?php echo (isset($_POST['role']) && $_POST['role'] == 'employer') ? 'selected' : ''; ?>" data-role="employer">
                <i class="fas fa-building"></i>
                <h3>Employer</h3>
                <p>Hiring talent</p>
            </div>
        </div>
        <input type="hidden" name="role" id="selectedRole" value="<?php echo $_POST['role'] ?? 'jobseeker'; ?>">
    </div>
    
    <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="reg_password" placeholder="Minimum 6 characters" required>
            <i class="fas fa-eye" id="toggleRegPassword" style="cursor: pointer; position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"></i>
        </div>
    </div>
    
    <div class="form-group">
        <label>Confirm Password</label>
        <div class="password-wrapper">
            <input type="password" name="confirm_password" id="reg_confirm_password" placeholder="Re-enter your password" required>
            <i class="fas fa-eye" id="toggleRegConfirmPassword" style="cursor: pointer; position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"></i>
        </div>
    </div>
    
    <div class="terms-checkbox">
        <input type="checkbox" name="terms_accepted" id="terms_accepted">
        <label for="terms_accepted">
            I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
        </label>
    </div>
    
    <button type="submit" class="btn-auth">
        <i class="fas fa-user-plus"></i> Create Account
    </button>
    
    <div class="auth-links">
        <a href="login.php">Already have an account? Sign In</a>
    </div>
</form>

<script>
// Role selection
document.querySelectorAll('.role-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('selectedRole').value = this.dataset.role;
    });
});

// Password toggle for registration page
document.addEventListener('DOMContentLoaded', function() {
    // Toggle for password
    var toggleBtn = document.getElementById('toggleRegPassword');
    var passwordField = document.getElementById('reg_password');
    
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
    
    // Toggle for confirm password
    var toggleConfirmBtn = document.getElementById('toggleRegConfirmPassword');
    var confirmField = document.getElementById('reg_confirm_password');
    
    if (toggleConfirmBtn && confirmField) {
        toggleConfirmBtn.onclick = function(e) {
            e.preventDefault();
            if (confirmField.type === 'password') {
                confirmField.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                confirmField.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
            return false;
        };
    }
});
</script>

<?php require_once 'includes/footer-auth.php'; ?>