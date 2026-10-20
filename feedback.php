<?php
$page_title = "Feedback";
require_once 'includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_type = sanitize($_POST['feedback_type'] ?? '');
    $feedback = sanitize($_POST['feedback'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($feedback)) {
        $error = "Please enter your feedback.";
    } else {
        $body = "Feedback Type: $feedback_type\nEmail: $email\n\nFeedback:\n$feedback";
        queueEmail('feedback@joblinklocal.com', "User Feedback: $feedback_type", $body);
        if (isLoggedIn()) logAudit($_SESSION['user_id'], 'feedback', 'feedback', null, ['type' => $feedback_type]);
        $message = "Thank you for your feedback!";
    }
}

require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.03); border-radius: 32px; padding: 48px;">
        <h1>Send Feedback</h1>
        <?php if ($message): ?>
            <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981;"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <select name="feedback_type" style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 16px;"><option value="">Feedback Type</option><option>Suggestion</option><option>Bug Report</option><option>Feature Request</option><option>Other</option></select>
            <textarea name="feedback" rows="5" placeholder="Your feedback *" required style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; font-family: inherit; margin-bottom: 16px;"></textarea>
            <input type="email" name="email" placeholder="Your email (optional)" style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 20px;">
            <button type="submit" class="btn-primary-gradient" style="width: 100%;">Submit Feedback</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>