<?php
// =====================================================
// REAPPLY HANDLER - Archive old, create new
// =====================================================

require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    $_SESSION['error'] = "Please login to reapply.";
    header("Location: " . SITE_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$db = getDB();
$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$app_id) {
    $_SESSION['error'] = "Invalid application.";
    header("Location: " . SITE_URL . "dashboard/seeker/applications.php");
    exit;
}

// Get the application details
$result = $db->query("SELECT job_id, cover_letter, resume_path FROM applications WHERE id = $app_id AND jobseeker_id = $user_id");

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Application not found.";
    header("Location: " . SITE_URL . "dashboard/seeker/applications.php");
    exit;
}

$app = $result->fetch_assoc();
$job_id = $app['job_id'];

// Check if job is still active
$job_check = $db->query("SELECT status FROM jobs WHERE id = $job_id");
$job = $job_check->fetch_assoc();

if ($job['status'] !== 'approved') {
    $_SESSION['error'] = "This job is no longer accepting applications.";
    header("Location: " . SITE_URL . "dashboard/seeker/applications.php");
    exit;
}

// Count total rejections for this job (from ALL records, including archived)
$rej_count = $db->query("SELECT COUNT(*) as cnt FROM applications WHERE job_id = $job_id AND jobseeker_id = $user_id AND status = 'rejected'")->fetch_assoc()['cnt'];

// Cooldown after 3 rejections
if ($rej_count >= 3) {
    $last_rej = $db->query("SELECT updated_at FROM applications WHERE job_id = $job_id AND jobseeker_id = $user_id AND status = 'rejected' ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
    
    if ($last_rej) {
        $days_passed = floor((time() - strtotime($last_rej['updated_at'])) / 86400);
        if ($days_passed < 7) {
            $days_left = 7 - $days_passed;
            $_SESSION['error'] = "You have been rejected $rej_count times. Please wait $days_left more day(s) before reapplying.";
            header("Location: " . SITE_URL . "dashboard/seeker/applications.php");
            exit;
        }
    }
}

// =====================================================
// STEP 1: Archive ALL existing applications for this job
// =====================================================
$db->query("UPDATE applications SET is_archived = 1 WHERE job_id = $job_id AND jobseeker_id = $user_id");

// =====================================================
// STEP 2: Create NEW application
// =====================================================
$insert = $db->prepare("INSERT INTO applications (job_id, jobseeker_id, cover_letter, resume_path, status, is_archived, created_at, updated_at) VALUES (?, ?, ?, ?, 'pending', 0, NOW(), NOW())");
$insert->bind_param("iiss", $job_id, $user_id, $app['cover_letter'], $app['resume_path']);

if ($insert->execute()) {
    $_SESSION['success'] = "You have successfully reapplied for this position!";
} else {
    $_SESSION['error'] = "Failed to reapply. Please try again.";
}

header("Location: " . SITE_URL . "dashboard/seeker/applications.php");
exit;
?>