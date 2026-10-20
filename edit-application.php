<?php
$page_title = "Edit Application";
require_once 'includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login.', 'error');
}

$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$db = getDB();

// Get application details
$app = $db->query("SELECT a.*, j.title, j.location, j.salary_min, j.salary_max 
                   FROM applications a 
                   JOIN jobs j ON a.job_id = j.id 
                   WHERE a.id = $app_id AND a.jobseeker_id = $user_id AND a.status = 'pending'")->fetch_assoc();

if (!$app) {
    redirect('dashboard/seeker/applications.php', 'Application not found or cannot be edited.', 'error');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = sanitize($_POST['cover_letter'] ?? '');
    
    $update = $db->prepare("UPDATE applications SET cover_letter = ? WHERE id = ?");
    $update->bind_param("si", $cover_letter, $app_id);
    
    if ($update->execute()) {
        logAudit($user_id, 'edit_application', 'application', $app_id);
        $_SESSION['success'] = "Application updated successfully!";
        redirect('dashboard/seeker/applications.php');
    } else {
        $error = "Failed to update application.";
    }
}

require_once 'includes/header-dashboard.php';
?>

<style>
    .edit-container {
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
    
    .btn-update {
        width: 100%;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border: none;
        padding: 16px;
        border-radius: 40px;
        color: white;
        font-weight: 600;
        cursor: pointer;
    }
</style>

<div class="dashboard-main">
    <div class="edit-container">
        <div style="margin-bottom: 20px;">
            <a href="dashboard/seeker/applications.php" class="btn-outline-light" style="padding: 8px 20px;">
                <i class="fas fa-arrow-left"></i> Back to Applications
            </a>
        </div>
        
        <h1>Edit Application</h1>
        
        <div class="job-info">
            <h3><?php echo htmlspecialchars($app['title']); ?></h3>
            <div class="text-muted-light">
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location'] ?: 'Remote'); ?>
                <span style="margin: 0 12px">•</span>
                <i class="fas fa-tag"></i> ₱<?php echo number_format($app['salary_min']); ?> - ₱<?php echo number_format($app['salary_max']); ?>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-section">
                <h3><i class="fas fa-envelope"></i> Cover Letter</h3>
                <div class="form-group">
                    <textarea name="cover_letter" rows="8" placeholder="Update your cover letter..."><?php echo htmlspecialchars($app['cover_letter']); ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn-update">
                <i class="fas fa-save"></i> Update Application
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer-dashboard.php'; ?>