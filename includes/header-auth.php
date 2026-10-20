<?php
if (!isset($page_title)) {
    $page_title = 'Authentication';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobLinkLocal - <?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --navy-deep: #0a0e1a;
            --navy-dark: #0f172a;
            --royal: #2563eb;
            --royal-light: #3b82f6;
            --azure: #06b6d4;
            --azure-light: #22d3ee;
            --white: #ffffff;
            --white-muted: rgba(255,255,255,0.7);
            --white-dim: rgba(255,255,255,0.4);
            --card-border: rgba(255,255,255,0.08);
            --glow-blue: 0 0 15px rgba(37,99,235,0.3);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy-dark) 100%);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        /* Auth Container */
        .auth-wrapper {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .auth-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--card-border);
            border-radius: 32px;
            padding: 48px 40px;
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }
        
        .auth-card:hover {
            border-color: rgba(37,99,235,0.3);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        /* Logo Section */
        .auth-logo {
            text-align: center;
            margin-bottom: 12px;
        }
        
        .auth-logo-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        
        .auth-logo-icon i {
            font-size: 32px;
            color: var(--white);
        }
        
        .auth-logo h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--white), var(--azure-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 8px;
        }
        
        .auth-logo p {
            font-size: 14px;
            color: var(--white-muted);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--white-muted);
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            color: var(--white);
            font-family: inherit;
            font-size: 15px;
            transition: var(--transition);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--royal);
            background: rgba(37,99,235,0.05);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        
        .form-group input::placeholder {
            color: var(--white-dim);
        }
        
        /* Password Wrapper */
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 50px;
        }
        
        .password-wrapper i {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--white-dim);
            transition: var(--transition);
            z-index: 10;
        }
        
        .password-wrapper i:hover {
            color: var(--azure-light);
        }
        
        /* Button */
        .btn-auth {
            width: 100%;
            background: linear-gradient(135deg, var(--royal), var(--azure));
            border: none;
            padding: 16px;
            border-radius: 40px;
            color: var(--white);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 8px;
        }
        
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-blue);
        }
        
        /* Links */
        .auth-links {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--card-border);
        }
        
        .auth-links a {
            color: var(--azure-light);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .auth-links a:hover {
            color: var(--white);
            text-decoration: underline;
        }
        
        .auth-footer-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .auth-footer-link a {
            color: var(--white-dim);
            text-decoration: none;
            font-size: 13px;
        }
        
        .auth-footer-link a:hover {
            color: var(--azure-light);
        }
        
        /* Premium Custom Scrollbar for Auth Pages */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #06b6d4, #2563eb);
        }
        
        /* Alerts */
        .alert-danger {
            background: rgba(239,68,68,0.15);
            border: 1px solid #ef4444;
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 24px;
            color: #ef4444;
            font-size: 14px;
        }
        
        .alert-danger a {
            color: #f87171;
            text-decoration: underline;
        }
        
        .alert-success {
            background: rgba(16,185,129,0.15);
            border: 1px solid #10b981;
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 24px;
            color: #10b981;
            font-size: 14px;
        }
        
        .info-box {
            background: rgba(6,182,212,0.08);
            border: 1px solid rgba(6,182,212,0.15);
            border-radius: 16px;
            padding: 14px 18px;
            margin-top: 16px;
            font-size: 13px;
            color: var(--azure-light);
        }
        
        /* Role Selector */
        .role-selector {
            display: flex;
            gap: 16px;
            margin-top: 8px;
        }
        
        .role-option {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 20px;
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
        }
        
        .role-option:hover {
            background: rgba(37,99,235,0.1);
        }
        
        .role-option.selected {
            background: rgba(37,99,235,0.15);
            border-color: var(--royal);
        }
        
        .role-option i {
            font-size: 32px;
            color: var(--azure-light);
            margin-bottom: 12px;
        }
        
        .role-option h3 {
            font-size: 18px;
            margin-bottom: 6px;
        }
        
        .role-option p {
            font-size: 12px;
            color: var(--white-muted);
        }
        
        /* Terms Checkbox */
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 24px 0;
        }
        
        .terms-checkbox input {
            width: 18px;
            height: 18px;
            margin-top: 3px;
            cursor: pointer;
        }
        
        .terms-checkbox label {
            color: var(--white-muted);
            font-size: 13px;
            line-height: 1.5;
        }
        
        .terms-checkbox a {
            color: var(--azure-light);
            text-decoration: none;
        }
        
        .terms-checkbox a:hover {
            text-decoration: underline;
        }
        
        /* Password Wrapper - Ensure icon visibility */
.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    width: 100%;
    padding-right: 50px !important;
}

.password-wrapper i {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: rgba(255,255,255,0.6);
    font-size: 18px;
    transition: all 0.2s ease;
    z-index: 10;
    pointer-events: auto;
}

.password-wrapper i:hover {
    color: #06b6d4;
}
        
        /* Responsive */
        @media (max-width: 550px) {
            .auth-card {
                padding: 32px 24px;
            }
            
            .role-selector {
                flex-direction: column;
                gap: 12px;
            }
            
            .auth-logo h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card" data-aos="fade-up">
            <div class="auth-logo">
                <div class="auth-logo-icon">
                <img src="/assets/images/logo.jpg" alt="JobLinkLocal" style="width: 70px; height: 70px; object-fit: contain;">
            </div>
                <h1>Link Local</h1>
                <p><?php echo $page_title; ?></p>
            </div>