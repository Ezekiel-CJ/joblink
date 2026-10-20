<?php
$page_title = "Apply for Job";
require_once 'includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login to apply for jobs.', 'error');
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$db = getDB();

// Get job details
$job = $db->query("SELECT * FROM jobs WHERE id = $job_id AND status = 'approved'")->fetch_assoc();
if (!$job) {
    redirect('jobs.php', 'Job not found.', 'error');
}

// Check if already applied
$check = $db->query("SELECT id FROM applications WHERE job_id = $job_id AND jobseeker_id = $user_id");
if ($check->num_rows > 0) {
    redirect('dashboard/seeker/applications.php', 'You have already applied.', 'warning');
}

// Check if user has resume
$profile = $db->query("SELECT resume_path FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();

if (empty($profile['resume_path'])) {
    $_SESSION['error'] = "❌ You must upload your resume/CV before applying for jobs. Please update your profile first.";
    redirect(SITE_URL . 'dashboard/seeker/profile.php', 'Please upload your resume/CV first.', 'error');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = sanitize($_POST['cover_letter'] ?? '');
    $resume_path = $profile['resume_path'];
    
    // Handle new resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['resume'], 'resumes/');
        if ($upload['success']) {
            $resume_path = $upload['path'];
            $db->query("UPDATE jobseeker_profiles SET resume_path = '$resume_path' WHERE user_id = $user_id");
        } else {
            $error = $upload['error'];
        }
    }
    
    if (empty($error)) {
        $stmt = $db->prepare("INSERT INTO applications (job_id, jobseeker_id, cover_letter, resume_path, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiss", $job_id, $user_id, $cover_letter, $resume_path);
        
        if ($stmt->execute()) {
            logAudit($user_id, 'apply_job', 'application', $stmt->insert_id);
            $message = "Application submitted successfully!";
            echo '<script>setTimeout(function(){ window.location.href = "dashboard/seeker/applications.php"; }, 2000);</script>';
        } else {
            $error = "Failed to submit application.";
        }
    }
}

// =====================================================
// USE DASHBOARD HEADER/FOTTER FOR LOGGED IN USERS
// =====================================================
require_once 'includes/header-dashboard.php';
?>

<style>
    .apply-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .job-info, .form-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 28px;
        margin-bottom: 24px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .form-group textarea {
        width: 100%;
        padding: 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        color: white;
        font-family: inherit;
        resize: vertical;
    }
    
    .file-input {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        color: white;
    }
    
    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border: none;
        padding: 16px;
        border-radius: 40px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 0 20px rgba(37,99,235,0.4);
    }
    
    .alert-success {
        background: rgba(16,185,129,0.15);
        border: 1px solid #10b981;
        padding: 14px;
        border-radius: 16px;
        margin-bottom: 20px;
        color: #10b981;
    }
    
    .alert-danger {
        background: rgba(239,68,68,0.15);
        border: 1px solid #ef4444;
        padding: 14px;
        border-radius: 16px;
        margin-bottom: 20px;
        color: #ef4444;
    }
    
    .resume-status {
        background: rgba(16,185,129,0.1);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 16px;
        font-size: 13px;
    }
    
    .back-button {
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .apply-container {
            padding: 0 10px;
        }
        .job-info, .form-section {
            padding: 20px;
        }
    }
</style>

<div class="dashboard-main">
    <div class="apply-container">
        
        <!-- Back Button -->
        <div class="back-button">
            <a href="javascript:history.back()" class="btn-outline-light" style="padding: 8px 20px; font-size: 13px;">
                <i class="fas fa-arrow-left"></i> Back to Job
            </a>
        </div>
        
        <h1>Apply for Position</h1>
        
        <!-- Job Info -->
        <div class="job-info">
            <h3><?php echo htmlspecialchars($job['title']); ?></h3>
            <div class="text-muted-light" style="margin-top: 8px;">
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?>
                <span style="margin: 0 12px">•</span>
                <i class="fas fa-tag"></i> ₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?>
            </div>
        </div>
        
        <!-- Resume Status -->
        <div class="resume-status">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <strong>Resume/CV detected:</strong> You have a resume on file.
            <?php if (!empty($profile['resume_path'])): ?>
                <a href="<?php echo SITE_URL . $profile['resume_path']; ?>" target="_blank" style="color: var(--azure-light); margin-left: 8px;">View Resume</a>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Application Form -->
        <form method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <h3><i class="fas fa-envelope"></i> Cover Letter</h3>
                <div class="form-group">
                    <textarea name="cover_letter" rows="6" placeholder="Tell the employer why you're a great fit for this position..."></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-file-alt"></i> Resume / CV</h3>
                <div class="form-group">
                    <input type="file" name="resume" class="file-input" accept=".pdf,.doc,.docx">
                    <p class="text-dim" style="margin-top: 8px; font-size: 12px;">Optional - Upload a new version to replace your current resume</p>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer-dashboard.php'; ?>