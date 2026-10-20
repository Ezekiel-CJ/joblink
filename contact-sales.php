<?php
$page_title = "Contact Sales";
require_once 'includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $company = sanitize($_POST['company'] ?? '');
    $employees = sanitize($_POST['employees'] ?? '');
    $msg = sanitize($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($msg)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $body = "Name: $name\nEmail: $email\nCompany: $company\nEmployees: $employees\n\nMessage:\n$msg";
        queueEmail('sales@joblinklocal.com', "Sales Inquiry from $name", $body);
        $message = "Thank you! Our sales team will contact you within 24 hours.";
    }
}

require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.03); border-radius: 32px; padding: 48px;">
        <h1>Contact Sales</h1>
        <?php if ($message): ?>
            <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981;"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name *" required style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 16px;">
            <input type="email" name="email" placeholder="Email Address *" required style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 16px;">
            <input type="text" name="company" placeholder="Company Name" style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 16px;">
            <select name="employees" style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; margin-bottom: 16px;"><option value="">Number of Employees</option><option>1-10</option><option>11-50</option><option>51-200</option><option>201-500</option><option>500+</option></select>
            <textarea name="message" rows="5" placeholder="How can we help you? *" required style="width: 100%; padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: white; font-family: inherit; margin-bottom: 20px;"></textarea>
            <button type="submit" class="btn-primary-gradient" style="width: 100%;">Submit Inquiry</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>