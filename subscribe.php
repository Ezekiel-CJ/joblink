<?php
require_once 'includes/config.php';

$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Please enter a valid email address.";
} else {
    $subject = "Welcome to JobLinkLocal Newsletter";
    $body = "Thank you for subscribing to JobLinkLocal newsletter!\n\nYou'll receive the latest job postings and career insights.\n\nBest regards,\nJobLinkLocal Team";
    queueEmail($email, $subject, $body);
    $_SESSION['success'] = "Successfully subscribed! Check your email.";
}

redirect(SITE_URL . 'index.php');
?>