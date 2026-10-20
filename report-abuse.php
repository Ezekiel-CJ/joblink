<?php
$page_title = "Report Abuse";
require_once 'includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = sanitize($_POST['report_type'] ?? '');
    $details = sanitize($_POST['details'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($report_type) || empty($details)) {
        $error = "Please fill in all required fields.";
    } else {
        $body = "Report Type: $report_type\nReporter Email: $email\n\nDetails:\n$details";
        queueEmail('abuse@joblinklocal.com', "Abuse Report: $report_type", $body);
        if (isLoggedIn()) logAudit($_SESSION['user_id'], 'report_abuse', 'report', null, ['type' => $report_type]);
        $message = "Thank you for your report. We will investigate.";
    }
}

require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.03); border-radius: 32px; padding: 48px;">
        <h1>Report Abuse</h1>
        <?php if ($message): ?>
            <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981;"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <select name="report_type" required style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 16px;">
                <option value="">Select Type</option>
                <option>Fake Job Posting</option><option>Spam or Scam</option><option>Harassment</option><option>Inappropriate Content</option><option>Other</option>
            </select>
            <input type="email" name="email" placeholder="Your Email (optional)" style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 16px;">
            <textarea name="details" rows="5" placeholder="Please provide detailed information..." required style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; font-family: inherit; margin-bottom: 20px;"></textarea>
            <button type="submit" class="btn-primary-gradient" style="width: 100%;">Submit Report</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>