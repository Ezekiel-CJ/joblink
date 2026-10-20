<?php
$page_title = "Best Applicants";
require_once '../../includes/config.php';
require_once '../../includes/ai_recommendations.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to access.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Get employer's jobs
$jobs = $db->query("SELECT id, title FROM jobs WHERE employer_id = $user_id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$selected_job = null;
$best_applicants = [];

if ($job_id > 0) {
    // Verify job belongs to employer
    $verify = $db->query("SELECT id, title FROM jobs WHERE id = $job_id AND employer_id = $user_id");
    if ($verify && $verify->num_rows > 0) {
        $selected_job = $verify->fetch_assoc();
        $best_applicants = getBestApplicantsForJob($job_id, 10);
        
        // Track analytics
        trackEmployerMetric($user_id, $job_id, 'job_view');
    } else {
        $job_id = 0;
    }
}

// Get all applicants count per job
$applicant_counts = [];
foreach ($jobs as $job) {
    $count = $db->query("SELECT COUNT(*) as cnt FROM applications WHERE job_id = {$job['id']} AND status != 'rejected'");
    $applicant_counts[$job['id']] = $count ? (int)$count->fetch_assoc()['cnt'] : 0;
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    .job-selector {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 30px;
    }
    
    .applicant-card-best {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .applicant-card-best:hover {
        transform: translateY(-3px);
        background: rgba(37,99,235,0.05);
        border-color: rgba(37,99,235,0.2);
    }
    
    .match-badge-high {
        display: inline-block;
        background: rgba(16,185,129,0.15);
        color: #10b981;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .match-badge-medium {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        color: #06b6d4;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .match-badge-low {
        display: inline-block;
        background: rgba(245,158,11,0.15);
        color: #f59e0b;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .skill-tag-best {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin: 3px;
    }
    
    .reason-item {
        font-size: 12px;
        color: var(--white-muted);
        padding: 4px 0;
    }
    
    .reason-item i {
        color: #10b981;
        margin-right: 8px;
        font-size: 10px;
    }
    
    .ai-badge {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 11px;
        display: inline-block;
        margin-left: 12px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px;
        background: rgba(255,255,255,0.03);
        border-radius: 24px;
    }
    
    @media (max-width: 768px) {
        .applicant-card-best {
            padding: 16px;
        }
        .match-badge-high, .match-badge-medium, .match-badge-low {
            font-size: 11px;
            padding: 4px 10px;
        }
    }
    .dashboard-main {
        margin-left: -60px !important;
        padding-left: 200px !important;
    }
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>AI-Assisted Applicant Recommender</h1>
        <p class="text-muted-light">
            <i class="fas fa-robot"></i> Our AI analyzes applicant profiles against your job requirements to find the best matches.
        </p>
    </div>
    
    <!-- Job Selector -->
    <div class="job-selector">
        <h3 style="margin-bottom: 16px;">Select a Job to Analyze</h3>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <?php foreach ($jobs as $job): ?>
                <a href="?job_id=<?php echo $job['id']; ?>" 
                   class="btn-outline-light <?php echo $job_id == $job['id'] ? 'active' : ''; ?>"
                   style="<?php echo $job_id == $job['id'] ? 'background: linear-gradient(135deg, #2563eb, #06b6d4); color: white;' : ''; ?>">
                    <?php echo htmlspecialchars($job['title'] ?? 'Untitled'); ?>
                    <span style="background: rgba(255,255,255,0.2); border-radius: 20px; padding: 2px 8px; margin-left: 8px;">
                        <?php echo isset($applicant_counts[$job['id']]) ? $applicant_counts[$job['id']] : 0; ?> applicants
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if ($job_id > 0 && $selected_job): ?>
        
        <div class="ai-badge" style="margin-bottom: 20px;">
            <i class="fas fa-brain"></i> AI-Powered Ranking | Based on skill matching, experience, and education
        </div>
        
        <?php if (!empty($best_applicants)): ?>
            
            <?php foreach ($best_applicants as $applicant): 
                $match_class = 'low';
                $match_score = isset($applicant['match_score']) ? $applicant['match_score'] : 0;
                if ($match_score >= 70) $match_class = 'high';
                elseif ($match_score >= 40) $match_class = 'medium';
                
                // Check if full_name exists and is not empty
if (isset($applicant['full_name']) && !empty($applicant['full_name'])) {
    $full_name = $applicant['full_name'];
} elseif (isset($applicant['user_id']) && $applicant['user_id'] > 0) {
    // Try to fetch the name directly from database
    $user_check = $db->query("SELECT full_name FROM users WHERE id = " . intval($applicant['user_id']));
    if ($user_check && $user_row = $user_check->fetch_assoc()) {
        $full_name = $user_row['full_name'];
    } else {
        $full_name = 'User ID: ' . $applicant['user_id'];
    }
} else {
    $full_name = 'Applicant (ID: ' . ($applicant['application_id'] ?? '?') . ')';
}

// Same for email
if (isset($applicant['email']) && !empty($applicant['email'])) {
    $email = $applicant['email'];
} elseif (isset($applicant['user_id']) && $applicant['user_id'] > 0) {
    $user_check = $db->query("SELECT email FROM users WHERE id = " . intval($applicant['user_id']));
    if ($user_check && $user_row = $user_check->fetch_assoc()) {
        $email = $user_row['email'];
    } else {
        $email = 'No email';
    }
} else {
    $email = 'No email';
}
                $application_id = isset($applicant['application_id']) ? $applicant['application_id'] : 0;
                $skills = isset($applicant['skills']) ? $applicant['skills'] : '';
                $reasons = isset($applicant['reasons']) && is_array($applicant['reasons']) ? $applicant['reasons'] : array();
                $education = isset($applicant['education']) ? $applicant['education'] : '';
                $experience = isset($applicant['experience']) ? $applicant['experience'] : '';
                $resume_path = isset($applicant['resume_path']) ? $applicant['resume_path'] : '';
            ?>
                <div class="applicant-card-best">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <h3 style="margin-bottom: 4px;">
                                <?php echo htmlspecialchars($full_name); ?>
                                <span class="ai-badge" style="margin-left: 12px; font-size: 10px;">
                                    <i class="fas fa-chart-line"></i> Score: <?php echo number_format($match_score, 2); ?>%
                                </span>
                            </h3>
                            <div class="text-muted-light" style="margin-bottom: 8px;">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?>
                            </div>
                        </div>
                        <div>
                            <span class="match-badge-<?php echo $match_class; ?>">
                                <?php if ($match_score >= 70): ?>
                                    <i class="fas fa-trophy"></i> Top Match
                                <?php elseif ($match_score >= 40): ?>
                                    <i class="fas fa-thumbs-up"></i> Strong Match
                                <?php else: ?>
                                    <i class="fas fa-info-circle"></i> Potential Match
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Skills -->
                    <?php if (!empty($skills)): 
                        $skills_array = explode(',', $skills);
                    ?>
                        <div style="margin-top: 16px;">
                            <div style="font-size: 13px; color: var(--white-dim); margin-bottom: 8px;">Skills:</div>
                            <div>
                                <?php foreach ($skills_array as $skill): ?>
                                    <?php if (trim($skill)): ?>
                                        <span class="skill-tag-best"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Match Reasons -->
                    <?php if (!empty($reasons)): ?>
                        <div style="margin-top: 16px; padding: 12px; background: rgba(16,185,129,0.05); border-radius: 16px;">
                            <div style="font-size: 12px; color: var(--white-dim); margin-bottom: 8px;">
                                <i class="fas fa-check-circle" style="color: #10b981;"></i> Why this candidate matches:
                            </div>
                            <?php foreach (array_slice($reasons, 0, 4) as $reason): ?>
                                <div class="reason-item">
                                    <i class="fas fa-check"></i> <?php echo htmlspecialchars($reason); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Education & Experience - Full Text -->
<?php if (!empty($education)): ?>
    <div style="margin-top: 16px;">
        <div style="font-size: 11px; color: var(--white-dim); margin-bottom: 6px;">Education</div>
        <div class="text-muted-light" style="font-size: 13px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">
            <?php echo nl2br(htmlspecialchars($education)); ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($experience)): ?>
    <div style="margin-top: 16px;">
        <div style="font-size: 11px; color: var(--white-dim); margin-bottom: 6px;">Experience</div>
        <div class="text-muted-light" style="font-size: 13px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">
            <?php echo nl2br(htmlspecialchars($experience)); ?>
        </div>
    </div>
<?php endif; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="applicant-detail.php?id=<?php echo $application_id; ?>" class="btn-primary-gradient" style="padding: 8px 20px;">
                            <i class="fas fa-user"></i> View Full Profile
                        </a>
                        <?php if (!empty($resume_path)): ?>
                            <a href="<?php echo SITE_URL . $resume_path; ?>" target="_blank" class="btn-outline-light" style="padding: 8px 20px;">
                                <i class="fas fa-file-pdf"></i> View Resume
                            </a>
                        <?php endif; ?>
                        <form method="POST" action="update-application-status.php" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                            <input type="hidden" name="status" value="shortlisted">
                            <input type="hidden" name="from_page" value="best_applicants.php">
                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                            <button type="submit" class="btn-outline-light" style="padding: 8px 20px; background: rgba(6,182,212,0.1);">
                                <i class="fas fa-star"></i> Shortlist
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
                <h3>No Applicants Yet</h3>
                <p class="text-muted-light">When job seekers apply for this position, they will appear here with AI-powered rankings.</p>
                <a href="manage-jobs.php" class="btn-outline-light" style="margin-top: 16px;">Back to Jobs</a>
            </div>
        <?php endif; ?>
        
    <?php elseif (empty($jobs)): ?>
        <div class="empty-state">
            <i class="fas fa-briefcase" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
            <h3>No Jobs Posted Yet</h3>
            <p class="text-muted-light">Post a job to start receiving AI-powered applicant recommendations.</p>
            <a href="post-job.php" class="btn-primary-gradient" style="margin-top: 16px;">Post a Job</a>
        </div>
        
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-magic" style="font-size: 48px; color: var(--azure-light); margin-bottom: 16px;"></i>
            <h3>Select a Job to Analyze</h3>
            <p class="text-muted-light">Choose a job from above to see AI-ranked applicants.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>