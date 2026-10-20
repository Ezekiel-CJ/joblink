<?php
$page_title = "Job Details";
require_once 'includes/config.php';

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$db = getDB();

// Get job details
$job = $db->query("SELECT j.*, c.name as category_name, u.full_name as employer_name, u.email as employer_email,
                   ep.business_name, ep.description as company_description, ep.contact_email, ep.contact_phone
                   FROM jobs j 
                   LEFT JOIN job_categories c ON j.category_id = c.id 
                   LEFT JOIN users u ON j.employer_id = u.id
                   LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                   WHERE j.id = $job_id AND j.status = 'approved'")->fetch_assoc();

if (!$job) {
    redirect('jobs.php', 'Job not found.', 'error');
}

// Increment view count
$db->query("UPDATE jobs SET views_count = views_count + 1 WHERE id = $job_id");

// Check application status
$has_applied = false;
$can_apply = false;
$login_needed = false;

if (isLoggedIn()) {
    if ($_SESSION['user_role'] === 'jobseeker') {
        $check = $db->query("SELECT id FROM applications WHERE job_id = $job_id AND jobseeker_id = $user_id");
        $has_applied = $check->num_rows > 0;
        
        $profile = $db->query("SELECT resume_path FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();
        $can_apply = !empty($profile['resume_path']);
    }
} else {
    $login_needed = true;
}

$skills = $job['required_skills'] ? explode(',', $job['required_skills']) : [];

require_once 'includes/header-dashboard.php';
?>

<style>
    .job-detail-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .job-header {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 28px;
        padding: 32px;
        margin-bottom: 30px;
    }
    
    .job-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        margin: 20px 0;
        padding: 20px 0;
        border-top: 1px solid rgba(255,255,255,0.08);
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    
    .job-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--white-muted);
    }
    
    .job-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 28px;
        margin-bottom: 24px;
    }
    
    .job-section h3 {
        margin-bottom: 16px;
        color: var(--azure-light);
    }
    
    .skill-tag {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        margin: 4px;
    }
    
    .apply-box {
        background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(6,182,212,0.05));
        border: 1px solid rgba(37,99,235,0.2);
        border-radius: 28px;
        padding: 40px;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .apply-box h3 {
        margin-bottom: 12px;
        font-size: 24px;
    }
    
    .apply-box p {
        margin-bottom: 24px;
        color: var(--white-muted);
    }
    
    .apply-buttons {
        display: flex;
        gap: 16px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    
    .apply-buttons .btn-primary-gradient,
    .apply-buttons .btn-outline-light {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
    }
    
    .salary-tag-large {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        padding: 8px 24px;
        border-radius: 40px;
        font-size: 18px;
        font-weight: 500;
        color: var(--azure-light);
    }
    
    .cv-warning {
        background: rgba(245,158,11,0.12);
        border: 1px solid #f59e0b;
        border-radius: 16px;
        padding: 16px 20px;
        margin: 20px auto 0;
        max-width: 450px;
        text-align: center;
    }
    
    .cv-warning a {
        color: #f59e0b;
        text-decoration: none;
        font-weight: 500;
    }
    
    .cv-warning a:hover {
        text-decoration: underline;
    }
    
    .back-button {
        margin-bottom: 24px;
    }
    
    .back-button a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
    }
    
    @media (max-width: 768px) {
        .job-detail-container {
            padding: 0 10px;
        }
        .job-header {
            padding: 20px;
        }
        .job-section {
            padding: 20px;
        }
        .job-meta {
            gap: 16px;
        }
        .apply-box {
            padding: 28px 20px;
        }
        .salary-tag-large {
            font-size: 14px;
            padding: 6px 16px;
        }
        .apply-buttons .btn-primary-gradient,
        .apply-buttons .btn-outline-light {
            padding: 10px 20px;
            font-size: 14px;
        }
    }
</style>

<div class="dashboard-main">
    <div class="job-detail-container">
        
        <!-- Back Button -->
        <div class="back-button">
            <a href="javascript:history.back()" class="btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Jobs
            </a>
        </div>
        
        <!-- Job Header -->
        <div class="job-header">
            <h1 style="font-size: 32px; margin-bottom: 12px;"><?php echo htmlspecialchars($job['title']); ?></h1>
            <div class="text-muted-light" style="font-size: 16px;">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['business_name'] ?? $job['employer_name']); ?>
            </div>
            
            <div class="job-meta">
                <div class="job-meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></div>
                <div class="job-meta-item"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($job['category_name'] ?? 'General'); ?></div>
                <div class="job-meta-item"><i class="fas fa-clock"></i> Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?></div>
                <div class="job-meta-item"><i class="fas fa-eye"></i> <?php echo number_format($job['views_count']); ?> views</div>
            </div>
            
            <div class="salary-tag-large">
                <i class="fas fa-tag"></i> ₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?>
            </div>
        </div>
        
        <!-- Job Description -->
        <div class="job-section">
            <h3><i class="fas fa-file-alt"></i> Job Description</h3>
            <div class="text-muted-light" style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
        </div>
        
        <!-- Requirements -->
        <?php if (!empty($job['requirements'])): ?>
        <div class="job-section">
            <h3><i class="fas fa-list-check"></i> Requirements</h3>
            <div class="text-muted-light" style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></div>
        </div>
        <?php endif; ?>
        
        <!-- Required Skills -->
        <?php if (!empty($skills)): ?>
        <div class="job-section">
            <h3><i class="fas fa-code"></i> Required Skills</h3>
            <div>
                <?php foreach ($skills as $skill): ?>
                    <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- About Company -->
        <div class="job-section">
            <h3><i class="fas fa-building"></i> About the Company</h3>
            <div class="text-muted-light" style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars($job['company_description'] ?? 'No company description available.')); ?></div>
            <?php if (!empty($job['contact_email'])): ?>
                <div style="margin-top: 12px;">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($job['contact_email']); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Apply Section - Fixed -->
        <div class="apply-box">
            <?php if ($login_needed): ?>
                <i class="fas fa-lock" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
                <h3>Sign in to Apply</h3>
                <p>Please sign in or create an account to apply for this job.</p>
                <div class="apply-buttons">
                    <a href="login.php" class="btn-primary-gradient"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                    <a href="register.php" class="btn-outline-light"><i class="fas fa-user-plus"></i> Create Account</a>
                </div>
                
            <?php elseif ($has_applied): ?>
                <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 16px;"></i>
                <h3>Application Submitted!</h3>
                <p>You have already applied for this position.</p>
                <div class="apply-buttons">
                    <a href="dashboard/seeker/applications.php" class="btn-primary-gradient">
                        <i class="fas fa-list"></i> View My Applications
                    </a>
                </div>
                
            <?php elseif ($_SESSION['user_role'] === 'jobseeker' && !$can_apply): ?>
                <i class="fas fa-file-alt" style="font-size: 48px; color: #f59e0b; margin-bottom: 16px;"></i>
                <h3>Resume Required</h3>
                <p>You need to upload your resume/CV before applying.</p>
                <div class="cv-warning">
                    <i class="fas fa-info-circle"></i> No resume found in your profile
                    <a href="dashboard/seeker/profile.php"><i class="fas fa-upload"></i> Upload Resume Now</a>
                </div>
                
            <?php elseif ($_SESSION['user_role'] === 'jobseeker'): ?>
                <i class="fas fa-paper-plane" style="font-size: 48px; color: var(--azure-light); margin-bottom: 16px;"></i>
                <h3>Ready to apply?</h3>
                <p>Submit your application for this position.</p>
                <div class="apply-buttons">
                    <a href="apply.php?id=<?php echo $job['id']; ?>" class="btn-primary-gradient">
                        <i class="fas fa-paper-plane"></i> Apply Now
                    </a>
                </div>
                
            <?php elseif ($_SESSION['user_role'] === 'employer'): ?>
                <i class="fas fa-building" style="font-size: 48px; color: var(--azure-light); margin-bottom: 16px;"></i>
                <h3>Employer Account</h3>
                <p>You are viewing this job as an employer.</p>
                <div class="apply-buttons">
                    <a href="dashboard/employer/index.php" class="btn-outline-light">Go to Dashboard</a>
                </div>
                
            <?php else: ?>
                <i class="fas fa-user-shield" style="font-size: 48px; color: var(--azure-light); margin-bottom: 16px;"></i>
                <h3>Administrator</h3>
                <p>You are viewing this job as an administrator.</p>
                <div class="apply-buttons">
                    <a href="dashboard/admin/index.php" class="btn-outline-light">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Share Section -->
        <div style="text-align: center; margin-top: 20px; padding: 20px 0;">
            <p class="text-dim" style="font-size: 12px;">
                <i class="fas fa-share-alt"></i> Share this job:
                <a href="#" style="color: var(--azure-light); margin-left: 8px;">Facebook</a> |
                <a href="#" style="color: var(--azure-light);">Twitter</a> |
                <a href="#" style="color: var(--azure-light);">LinkedIn</a>
            </p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer-dashboard.php'; ?>