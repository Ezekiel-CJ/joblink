<?php
require_once '../../includes/config.php';
require_once '../../includes/email.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Unauthorized.', 'error');
}

$app_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
$from_page = isset($_POST['from_page']) ? $_POST['from_page'] : 'applicants.php';
$job_filter = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$db = getDB();

// Verify employer owns this application and get details
$check = $db->query("SELECT a.id, a.jobseeker_id, a.status as current_status, j.id as job_id, j.title, u.email, u.full_name, u2.full_name as employer_name 
                     FROM applications a 
                     JOIN jobs j ON a.job_id = j.id 
                     JOIN users u ON a.jobseeker_id = u.id 
                     JOIN users u2 ON j.employer_id = u2.id 
                     WHERE a.id = $app_id AND j.employer_id = {$_SESSION['user_id']}");

if ($check->num_rows > 0) {
    $app = $check->fetch_assoc();
    
    // Check if already rejected - cannot update further
    if ($app['current_status'] === 'rejected') {
        $_SESSION['error'] = "This application has already been rejected. No further changes allowed.";
        $redirect_url = $from_page;
        if ($job_filter > 0) {
            $redirect_url .= '?job_id=' . $job_filter;
        }
        redirect($redirect_url);
    }
    
    // Update status
    $db->query("UPDATE applications SET status = '$status', reviewed_by = {$_SESSION['user_id']}, reviewed_at = NOW() WHERE id = $app_id");
    logAudit($_SESSION['user_id'], 'update_application_status', 'application', $app_id, ['status' => $status]);
    
    // Send email notification to job seeker
    sendApplicationStatusEmail($app['email'], $app['full_name'], $app['title'], $app['employer_name'], $status);
    
    $_SESSION['success'] = "Application status updated to " . ucfirst($status);
} else {
    $_SESSION['error'] = "Application not found or you don't have permission.";
}

// Redirect back
$redirect_url = $from_page;
if ($job_filter > 0) {
    $redirect_url .= '?job_id=' . $job_filter;
}

redirect($redirect_url);
?>