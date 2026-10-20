<?php
$page_title = "Review Job Posting";
require_once '../../includes/config.php';
require_once '../../includes/email.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized access.', 'error');
}

$db = getDB();
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job details with employer info
$job_stmt = $db->prepare("SELECT j.*, u.full_name as employer_name, u.email as employer_email,
                          ep.business_name, ep.business_address, ep.contact_phone
                          FROM jobs j 
                          JOIN users u ON j.employer_id = u.id 
                          LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
                          WHERE j.id = ?");
$job_stmt->bind_param("i", $job_id);
$job_stmt->execute();
$job = $job_stmt->get_result()->fetch_assoc();

if (!$job) {
    redirect('jobs.php', 'Job not found.', 'error');
}

$message = '';
$error = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reason = sanitize($_POST['reason'] ?? '');
    $admin_id = $_SESSION['user_id'];
    
    if ($action === 'approve') {
        $update = $db->prepare("UPDATE jobs SET status = 'approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
        $update->bind_param("ii", $admin_id, $job_id);
        
        if ($update->execute()) {
            logAudit($admin_id, 'approve_job', 'job', $job_id);
            
            // Send email notification to employer
            sendJobApprovalEmail($job_id, $job['title'], $job['employer_email'], $job['employer_name'], 'approved');
            
            $message = "Job approved successfully! It is now visible to job seekers.";
        } else {
            $error = "Failed to approve job.";
        }
        
    } elseif ($action === 'reject') {
        if (empty($reason)) {
            $error = "Please provide a reason for rejection.";
        } else {
            $update = $db->prepare("UPDATE jobs SET status = 'rejected', rejection_reason = ? WHERE id = ?");
            $update->bind_param("si", $reason, $job_id);
            
            if ($update->execute()) {
                logAudit($admin_id, 'reject_job', 'job', $job_id, ['reason' => $reason]);
                
                // Send email notification to employer
                sendJobApprovalEmail($job_id, $job['title'], $job['employer_email'], $job['employer_name'], 'rejected', $reason);
                
                $message = "Job rejected. Employer has been notified.";
            } else {
                $error = "Failed to reject job.";
            }
        }
    }
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    .job-detail {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 32px;
        margin-bottom: 30px;
    }
    
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .info-label {
        width: 160px;
        font-weight: 600;
        color: var(--white-muted);
    }
    
    .info-value {
        flex: 1;
    }
    
    .skill-tag {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin: 2px;
    }
    
    .action-buttons {
        display: flex;
        gap: 16px;
        margin-top: 30px;
    }
    
    .reject-reason {
        margin-top: 20px;
        display: none;
    }
    
    .reject-reason.active {
        display: block;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .status-pending {
        background: rgba(245,158,11,0.15);
        color: #f59e0b;
    }
    
    .status-approved {
        background: rgba(16,185,129,0.15);
        color: #10b981;
    }
    
    .status-rejected {
        background: rgba(239,68,68,0.15);
        color: #ef4444;
    }
    
    .alert-success {
        background: rgba(16,185,129,0.15);
        border: 1px solid #10b981;
        padding: 14px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        color: #10b981;
    }
    
    .alert-danger {
        background: rgba(239,68,68,0.15);
        border: 1px solid #ef4444;
        padding: 14px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        color: #ef4444;
    }
    
    .already-processed {
        background: rgba(100,116,139,0.15);
        border: 1px solid #64748b;
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        margin: 30px 0;
    }
    
    .back-button {
        margin-bottom: 20px;
    }
</style>

<div class="dashboard-main">
    <div style="max-width: 900px; margin: 0 auto;">
        
        <div class="back-button">
            <a href="jobs.php" class="btn-outline-light" style="padding: 8px 20px;">
                <i class="fas fa-arrow-left"></i> Back to Jobs List
            </a>
        </div>
        
        <div class="text-center" data-aos="fade-up">
            <div class="section-tag" style="display: inline-block; margin-bottom: 16px;">
                <i class="fas fa-search"></i> Review Job
            </div>
            <h1 style="font-size: 32px;"><?php echo htmlspecialchars($job['title']); ?></h1>
            <p class="text-muted-light">Review job details before approving or rejecting.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Job Details -->
        <div class="job-detail">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-info-circle"></i> Job Information</h3>
            
            <div class="info-row">
                <div class="info-label">Job Title:</div>
                <div class="info-value"><?php echo htmlspecialchars($job['title']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Employer:</div>
                <div class="info-value"><?php echo htmlspecialchars($job['business_name'] ?: $job['employer_name']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Location:</div>
                <div class="info-value"><?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Salary Range:</div>
                <div class="info-value">₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Employment Type:</div>
                <div class="info-value"><?php echo ucfirst($job['employment_type']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Current Status:</div>
                <div class="info-value">
                    <span class="status-badge status-<?php echo $job['status']; ?>">
                        <?php echo ucfirst($job['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Posted On:</div>
                <div class="info-value"><?php echo date('F d, Y h:i A', strtotime($job['created_at'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
            </div>
            
            <?php if (!empty($job['requirements'])): ?>
                <div class="info-row">
                    <div class="info-label">Requirements:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($job['required_skills'])): 
                $skills = explode(',', $job['required_skills']);
            ?>
                <div class="info-row">
                    <div class="info-label">Required Skills:</div>
                    <div class="info-value">
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Employer Details -->
        <div class="job-detail">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-building"></i> Employer Information</h3>
            
            <div class="info-row">
                <div class="info-label">Business Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($job['business_name'] ?? 'Not provided'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Contact Person:</div>
                <div class="info-value"><?php echo htmlspecialchars($job['employer_name']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">
                    <a href="mailto:<?php echo htmlspecialchars($job['employer_email']); ?>" style="color: var(--azure-light);">
                        <?php echo htmlspecialchars($job['employer_email']); ?>
                    </a>
                </div>
            </div>
            
            <?php if (!empty($job['contact_phone'])): ?>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($job['contact_phone']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($job['business_address'])): ?>
                <div class="info-row">
                    <div class="info-label">Business Address:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($job['business_address'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- ===================================================== -->
        <!-- DECISION SECTION - REMOVED FOR NON-PENDING JOBS -->
        <!-- ===================================================== -->
        
        <?php if ($job['status'] === 'pending'): ?>
            <!-- Show Approve/Reject buttons for PENDING jobs only -->
            <div class="job-detail">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-gavel"></i> Decision</h3>
                
                <form method="POST" action="" id="decisionForm">
                    <div class="action-buttons">
                        <button type="button" id="approveBtn" class="btn-primary-gradient" style="flex: 1; padding: 14px;">
                            <i class="fas fa-check"></i> Approve Job
                        </button>
                        <button type="button" id="rejectBtn" class="btn-outline-light" style="flex: 1; padding: 14px;">
                            <i class="fas fa-times"></i> Reject Job
                        </button>
                    </div>
                    
                    <div id="rejectReason" class="reject-reason">
                        <label class="form-label">Reason for Rejection</label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="Provide detailed reason for rejection..." style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; margin-bottom: 16px;"></textarea>
                        <button type="submit" name="action" value="reject" class="btn-danger" style="background: #ef4444; border: none; padding: 12px 24px; border-radius: 12px; color: white; cursor: pointer;">Confirm Rejection</button>
                        <button type="button" id="cancelReject" class="btn-outline-light">Cancel</button>
                    </div>
                    
                    <input type="hidden" name="action" id="actionInput" value="">
                </form>
            </div>
            
        <?php else: ?>
            <!-- Already processed - No action buttons, just info -->
            <div class="already-processed">
                <i class="fas fa-info-circle" style="font-size: 32px; color: #64748b; margin-bottom: 12px; display: block;"></i>
                <h3>Job Already <?php echo ucfirst($job['status']); ?></h3>
                <p class="text-muted-light" style="margin-top: 8px;">
                    This job has already been <strong><?php echo ucfirst($job['status']); ?></strong>.
                    <?php if ($job['status'] === 'rejected' && !empty($job['rejection_reason'])): ?>
                        <br>Reason: <?php echo htmlspecialchars($job['rejection_reason']); ?>
                    <?php elseif ($job['status'] === 'approved' && !empty($job['approved_at'])): ?>
                        <br>Approved on: <?php echo date('F d, Y h:i A', strtotime($job['approved_at'])); ?>
                    <?php endif; ?>
                </p>
                <div style="margin-top: 20px;">
                    <a href="jobs.php" class="btn-outline-light">Back to Jobs List</a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn = document.getElementById('rejectBtn');
    const rejectReason = document.getElementById('rejectReason');
    const actionInput = document.getElementById('actionInput');
    const decisionForm = document.getElementById('decisionForm');
    const cancelReject = document.getElementById('cancelReject');
    
    if (approveBtn) {
        approveBtn.addEventListener('click', function() {
            if (confirm('Approve this job posting? It will become visible to job seekers.')) {
                actionInput.value = 'approve';
                decisionForm.submit();
            }
        });
    }
    
    if (rejectBtn) {
        rejectBtn.addEventListener('click', function() {
            rejectReason.classList.add('active');
        });
    }
    
    if (cancelReject) {
        cancelReject.addEventListener('click', function() {
            rejectReason.classList.remove('active');
        });
    }
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>