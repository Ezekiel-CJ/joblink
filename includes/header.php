<?php
if (!isset($page_title)) {
    $page_title = 'Community Employment Platform';
}

// Get current user info for header
$current_user = null;
$user_role = null;
$user_name = null;
$user_avatar = null;
$dashboard_link = '#';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT id, email, full_name, role, avatar_path FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $current_user = $stmt->get_result()->fetch_assoc();
        
        if ($current_user) {
            $user_role = $current_user['role'];
            $user_name = $current_user['full_name'];
            $user_avatar = $current_user['avatar_path'];
            
            // Set dashboard link based on role
            switch($user_role) {
                case 'jobseeker':
                    $dashboard_link = SITE_URL . 'dashboard/seeker/';
                    break;
                case 'employer':
                    $dashboard_link = SITE_URL . 'dashboard/employer/';
                    break;
                case 'lgu_admin':
                    $dashboard_link = SITE_URL . 'dashboard/admin/';
                    break;
                case 'sys_admin':
                    $dashboard_link = SITE_URL . 'dashboard/sysadmin/';
                    break;
                default:
                    $dashboard_link = SITE_URL . 'dashboard/seeker/';
            }
        }
    }
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
    
    <!-- Cropper.js for Profile Image Cropping -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Cropper.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <style>
        /* =====================================================
           PREMIUM DESIGN SYSTEM
        ===================================================== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --navy-deep: #0a0e1a;
            --navy-dark: #0f172a;
            --navy-mid: #1e293b;
            --royal: #2563eb;
            --royal-light: #3b82f6;
            --azure: #06b6d4;
            --azure-light: #22d3ee;
            --white: #ffffff;
            --white-muted: rgba(255,255,255,0.7);
            --white-dim: rgba(255,255,255,0.4);
            --card-border: rgba(255,255,255,0.06);
            --shadow-sm: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-md: 0 8px 30px rgba(0,0,0,0.12);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.2);
            --glow-blue: 0 0 15px rgba(37,99,235,0.3);
            --transition: all 0.3s cubic-bezier(0.2, 0, 0, 1);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, var(--navy-deep) 0%, var(--navy-dark) 100%);
            color: var(--white);
            overflow-x: hidden;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, var(--royal), var(--azure)); border-radius: 10px; }
        
        /* Premium Navigation */
        .nav-premium {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(10, 14, 26, 0.92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--card-border);
            padding: 16px 0;
            transition: var(--transition);
        }
        
        .nav-premium.scrolled {
            padding: 10px 0;
            background: rgba(10, 14, 26, 0.98);
        }
        
        .container-premium {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }
        
        .nav-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
            width: 100%;
        }
        
        /* Logo - Adjusted left */
        .logo-premium {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: var(--transition);
            margin-left: -250px;  /* Adjusted as requested */
        }
        
        .logo-premium:hover {
            transform: scale(1.02);
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .logo-icon i {
            font-size: 20px;
            color: white;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.3px;
            background: linear-gradient(135deg, var(--white), var(--azure-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Premium Custom Scrollbar for Landing Page */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #1e40af, #06b6d4);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #06b6d4, #1e40af);
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: #1e40af rgba(255, 255, 255, 0.05);
        }
        
        /* Navigation Links - Centered */
        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--white-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--royal-light), var(--azure));
            transition: var(--transition);
            border-radius: 2px;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .nav-links a:hover {
            color: var(--white);
        }
        
        /* Action Buttons - Right aligned */
        .nav-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-right: -240px;  /* Adjusted as requested */
        }
        
        .btn-outline-light {
            padding: 10px 24px;
            border-radius: 40px;
            background: transparent;
            border: 1px solid var(--card-border);
            color: var(--white);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .btn-outline-light:hover {
            border-color: var(--royal-light);
            background: rgba(37,99,235,0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary-gradient {
            padding: 10px 28px;
            border-radius: 40px;
            background: linear-gradient(135deg, var(--royal), var(--azure));
            color: var(--white);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--glow-blue);
        }
        
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(37,99,235,0.5);
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--royal-light), var(--azure));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: var(--glow-blue);
        }
        
        .dropdown-menu-premium {
            position: absolute;
            top: 55px;
            right: 0;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 12px;
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .user-menu:hover .dropdown-menu-premium {
            opacity: 1;
            visibility: visible;
        }
        
        .dropdown-menu-premium a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--white-muted);
            text-decoration: none;
            border-radius: 12px;
            transition: var(--transition);
        }
        
        .dropdown-menu-premium a:hover {
            background: rgba(37,99,235,0.15);
            color: var(--white);
            transform: translateX(4px);
        }
        
        /* Mobile Menu */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 24px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .mobile-toggle:hover {
            color: var(--azure-light);
        }
        
        /* Responsive */
        @media (max-width: 1100px) {
            .logo-premium { margin-left: 0; }
            .nav-links { margin-right: 0; }
        }
        
        @media (max-width: 900px) {
            .mobile-toggle { display: block; }
            .nav-links, .nav-actions { display: none; }
            .nav-links.mobile-open, .nav-actions.mobile-open {
                display: flex;
                flex-direction: column;
                width: 100%;
                padding: 20px 0;
            }
            .nav-flex { flex-wrap: wrap; }
            .logo-premium { margin-left: 0; }
        }
        
        @media (max-width: 640px) {
            .container-premium { padding: 0 20px; }
        }
        
        .text-muted-light { color: var(--white-muted); }
        .text-dim { color: var(--white-dim); }
        .text-gradient {
            background: linear-gradient(135deg, var(--royal-light), var(--azure-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body>

<nav class="nav-premium" id="premiumNav">
    <div class="container-premium">
        <div class="nav-flex">
            <!-- Logo - Left -->
            <a href="<?php echo SITE_URL; ?>" class="logo-premium">
    <div class="logo-icon">
        <img src="/assets/images/logo.jpg" alt="JobLinkLocal" style="width: 40px; height: 40px; object-fit: contain;">
    </div>
    <span class="logo-text">Link Local</span>
</a>
            
            <!-- Mobile Toggle -->
            <button class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Navigation Links - Center -->
            <div class="nav-links" id="navLinks">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <a href="<?php echo SITE_URL; ?>jobs.php">Find Jobs</a>
                <a href="<?php echo SITE_URL; ?>companies.php">Companies</a>
                <a href="<?php echo SITE_URL; ?>about.php">About</a>
                <a href="<?php echo SITE_URL; ?>contact.php">Contact</a>
            </div>
            
            <!-- Actions - Right -->
            <div class="nav-actions" id="navActions">
                <?php if ($current_user): ?>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                        <div class="dropdown-menu-premium">
                            <a href="<?php echo $dashboard_link; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <?php if ($user_role === 'jobseeker'): ?>
                                <a href="<?php echo SITE_URL; ?>dashboard/seeker/profile.php"><i class="fas fa-user"></i> My Profile</a>
                            <?php elseif ($user_role === 'employer'): ?>
                                <a href="<?php echo SITE_URL; ?>dashboard/employer/profile.php"><i class="fas fa-building"></i> My Profile</a>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>profile.php"><i class="fas fa-user"></i> My Profile</a>
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>login.php" class="btn-outline-light"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                    <a href="<?php echo SITE_URL; ?>register.php" class="btn-primary-gradient"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div style="height: 80px;"></div>

<script>
    // Mobile menu toggle
    (function() {
        var toggle = document.getElementById('mobileToggle');
        var navLinks = document.getElementById('navLinks');
        var navActions = document.getElementById('navActions');
        
        if (toggle) {
            toggle.addEventListener('click', function() {
                if (navLinks) navLinks.classList.toggle('mobile-open');
                if (navActions) navActions.classList.toggle('mobile-open');
            });
        }
        
        // Navbar scroll effect
        var nav = document.getElementById('premiumNav');
        if (nav) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            });
        }
    })();
</script>

<main>