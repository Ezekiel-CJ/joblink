<?php
// =====================================================
// EMAIL SYSTEM - PRODUCTION READY
// =====================================================

require_once __DIR__ . '/config.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once $_SERVER['DOCUMENT_ROOT'] . '/PHPMailer/Exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PHPMailer/PHPMailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PHPMailer/SMTP.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_USER, SITE_NAME);
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        error_log("Email sent to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("Email failed to: $to - Error: {$mail->ErrorInfo}");
        return false;
    }
}

function queueEmail($to, $subject, $body, $template = null, $data = null) {
    return sendEmail($to, $subject, $body);
}

function getEmailHeader($title) {
    return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; background: #f0f2f5; }
    .container { max-width: 580px; margin: 20px auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
    .header { background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 40px 30px; text-align: center; }
    .header h1 { color: #ffffff; font-size: 24px; margin: 0; }
    .content { padding: 40px 35px; color: #1e293b; line-height: 1.6; }
    .button { display: inline-block; background: #1e40af; color: #ffffff !important; text-decoration: none; padding: 12px 32px; border-radius: 50px; font-weight: 600; margin: 20px 0; }
    .footer { background: #f8fafc; padding: 25px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    hr { border: none; border-top: 1px solid #e2e8f0; margin: 25px 0; }
</style>
</head>
<body>
<div class="container">
    <div class="header"><h1>' . htmlspecialchars($title) . '</h1></div>
    <div class="content">';
}

function getEmailFooter() {
    return '<hr>
            <p style="font-size: 13px;"><strong>' . SITE_NAME . '</strong><br>Connecting talent to opportunities in your community.</p>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
            <p><a href="' . SITE_URL . '" style="color: #3b82f6;">' . SITE_URL . '</a></p>
        </div>
    </div>
</body>
</html>';
}

function sendWelcomeEmail($user_id, $user_name, $user_email, $user_role) {
    $subject = "Welcome to " . SITE_NAME . "!";
    $body = getEmailHeader("Welcome Aboard!") . '
        <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        <p>Thank you for joining ' . SITE_NAME . '! Your account has been created successfully.</p>
        <p>You can now log in and start using the platform.</p>
        <div style="text-align: center;">
            <a href="' . SITE_URL . 'login.php" class="button">Login to Your Account</a>
        </div>
        <p>Best regards,<br>' . SITE_NAME . ' Team</p>';
    $body .= getEmailFooter();
    return sendEmail($user_email, $subject, $body);
}

function sendVerificationEmail($email, $token, $user_name) {
    $verify_link = SITE_URL . "verify-email.php?token=" . $token;
    $subject = "Verify Your Email - " . SITE_NAME;
    $body = getEmailHeader("Verify Your Email") . '
        <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        <p>Please verify your email address by clicking the button below:</p>
        <div style="text-align: center;">
            <a href="' . $verify_link . '" class="button">Verify Email</a>
        </div>
        <p>This link expires in 24 hours.</p>';
    $body .= getEmailFooter();
    return sendEmail($email, $subject, $body);
}

function sendPasswordResetEmail($email, $token, $user_name) {
    $reset_link = SITE_URL . "reset-password.php?token=" . $token;
    $subject = "Reset Your Password - " . SITE_NAME;
    $body = getEmailHeader("Reset Your Password") . '
        <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        <p>Click the button below to reset your password:</p>
        <div style="text-align: center;">
            <a href="' . $reset_link . '" class="button">Reset Password</a>
        </div>
        <p>This link expires in 1 hour. If you didn\'t request this, ignore this email.</p>';
    $body .= getEmailFooter();
    return sendEmail($email, $subject, $body);
}

function sendJobApprovalEmail($job_id, $job_title, $employer_email, $employer_name, $status, $reason = null) {
    if ($status === 'approved') {
        $subject = "Your Job Has Been Approved - " . SITE_NAME;
        $body = getEmailHeader("Job Approved!") . '
            <p>Dear <strong>' . htmlspecialchars($employer_name) . '</strong>,</p>
            <p>Your job posting "<strong>' . htmlspecialchars($job_title) . '</strong>" has been approved and is now live.</p>
            <div style="text-align: center;">
                <a href="' . SITE_URL . 'dashboard/employer/manage-jobs.php" class="button">View Your Jobs</a>
            </div>';
    } else {
        $subject = "Update on Your Job Posting - " . SITE_NAME;
        $body = getEmailHeader("Job Posting Update") . '
            <p>Dear <strong>' . htmlspecialchars($employer_name) . '</strong>,</p>
            <p>Your job posting "<strong>' . htmlspecialchars($job_title) . '</strong>" needs revision.</p>
            <p><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</p>
            <div style="text-align: center;">
                <a href="' . SITE_URL . 'dashboard/employer/edit-job.php?id=' . $job_id . '" class="button">Edit Job</a>
            </div>';
    }
    $body .= getEmailFooter();
    return sendEmail($employer_email, $subject, $body);
}

function sendApplicationStatusEmail($jobseeker_email, $jobseeker_name, $job_title, $employer_name, $status) {
    $subject = "Application Status Update - " . SITE_NAME;
    $body = getEmailHeader("Application Update") . '
        <p>Dear <strong>' . htmlspecialchars($jobseeker_name) . '</strong>,</p>
        <p>Your application for "<strong>' . htmlspecialchars($job_title) . '</strong>" at ' . htmlspecialchars($employer_name) . ' has been <strong>' . ucfirst($status) . '</strong>.</p>
        <div style="text-align: center;">
            <a href="' . SITE_URL . 'dashboard/seeker/applications.php" class="button">View Applications</a>
        </div>';
    $body .= getEmailFooter();
    return sendEmail($jobseeker_email, $subject, $body);
}
?>