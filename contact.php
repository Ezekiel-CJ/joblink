<?php
$page_title = "Contact Us";
require_once 'includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $msg = sanitize($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($msg)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$msg";
        queueEmail(SITE_EMAIL, "Contact Form: $subject", $body);
        
        if (isLoggedIn()) {
            logAudit($_SESSION['user_id'], 'contact_form', 'contact', null, ['subject' => $subject]);
        }
        
        $message = "Thank you for your message. We'll get back to you within 24 hours.";
    }
}

require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <div class="text-center">
            <div class="section-tag" style="display: inline-block;">Get in Touch</div>
            <h1>Contact Us</h1>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-top: 50px;">
            <div>
                <div style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px;">
                    <h3>Get in Touch</h3>
                    <div style="margin: 24px 0; display: flex; gap: 16px;">
                        <i class="fas fa-map-marker-alt" style="font-size: 24px; color: var(--azure-light);"></i>
                        <div>LGU Office<br>Municipal Hall</div>
                    </div>
                    <div style="margin: 24px 0; display: flex; gap: 16px;">
                        <i class="fas fa-phone" style="font-size: 24px; color: var(--azure-light);"></i>
                        <div>+63 (2) 1234 5678</div>
                    </div>
                    <div style="margin: 24px 0; display: flex; gap: 16px;">
                        <i class="fas fa-envelope" style="font-size: 24px; color: var(--azure-light);"></i>
                        <div>support@joblinklocal.com</div>
                    </div>
                </div>
            </div>
            
            <div>
                <form method="POST" action="" style="background: rgba(255,255,255,0.03); border-radius: 28px; padding: 32px;">
                    <?php if ($message): ?>
                        <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981;"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444;"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <input type="text" name="name" id="contactName" placeholder="Your Name" autocomplete="name" required>
                    <input type="email" name="email" id="contactEmail" placeholder="Your Email" autocomplete="email" required>
                    <input type="text" name="subject" id="contactSubject" placeholder="Subject" autocomplete="off" required>
                    <textarea name="message" id="contactMessage" rows="5" placeholder="Your Message" autocomplete="off" required></textarea>
                    <button type="submit" class="btn-primary-gradient" style="width: 100%;">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>