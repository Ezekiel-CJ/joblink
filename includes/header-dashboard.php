<?php
if (!isset($page_title)) {
    $page_title = 'Dashboard';
}

// Get current user info
$current_user = null;
$user_role = null;
$user_name = null;
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
            
            switch($user_role) {
                case 'jobseeker': $dashboard_link = SITE_URL . 'dashboard/seeker/'; break;
                case 'employer': $dashboard_link = SITE_URL . 'dashboard/employer/'; break;
                case 'lgu_admin': $dashboard_link = SITE_URL . 'dashboard/admin/'; break;
                case 'sys_admin': $dashboard_link = SITE_URL . 'dashboard/sysadmin/'; break;
                default: $dashboard_link = SITE_URL . 'dashboard/seeker/';
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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Cropper.js for Profile Image Cropping -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Cropper.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <style>
/* =====================================================
   RESPONSIVE DESIGN SYSTEM - ALL DEVICES
   Mobile First Approach
===================================================== */

/* CSS Variables for consistent spacing */
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
    --card-border: rgba(255,255,255,0.06);
    --shadow-sm: 0 4px 20px rgba(0,0,0,0.08);
    --shadow-md: 0 8px 30px rgba(0,0,0,0.12);
    --shadow-lg: 0 20px 40px rgba(0,0,0,0.2);
    --glow-blue: 0 0 15px rgba(37,99,235,0.3);
    --transition: all 0.3s cubic-bezier(0.2, 0, 0, 1);
    
    /* Responsive variables */
    --sidebar-width-desktop: 260px;
    --sidebar-width-tablet: 240px;
    --header-height: 70px;
    --spacing-xs: 8px;
    --spacing-sm: 12px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(180deg, var(--navy-deep) 0%, var(--navy-dark) 100%);
    color: var(--white);
    line-height: 1.5;
    overflow-x: hidden;
}

/* Custom Scrollbar */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 10px; }
::-webkit-scrollbar-thumb { background: linear-gradient(135deg, var(--royal), var(--azure)); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: linear-gradient(135deg, var(--azure), var(--royal-light)); }

/* =====================================================
   DASHBOARD LAYOUT
===================================================== */

.dashboard-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: var(--sidebar-width-desktop);
    height: 100vh;
    background: rgba(10, 14, 26, 0.98);
    backdrop-filter: blur(20px);
    border-right: 1px solid var(--card-border);
    padding: 24px 0;
    z-index: 100;
    overflow-y: auto;
    overflow-x: hidden;
    transition: transform 0.3s ease, width 0.3s ease;
}

.dashboard-main {
    margin-left: var(--sidebar-width-desktop);
    padding: var(--spacing-lg);
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

/* Sidebar Brand */
.sidebar-brand {
    padding: 0 20px 20px;
    border-bottom: 1px solid var(--card-border);
    margin-bottom: 20px;
}

.sidebar-brand div {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.sidebar-brand img {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.sidebar-brand h2 {
    font-size: 18px;
    margin: 0;
    color: white;
}

.sidebar-brand span {
    font-size: 11px;
    color: rgba(255,255,255,0.5);
    display: block;
}

/* Sidebar Links */
.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: var(--transition);
    white-space: nowrap;
}

.sidebar-link i {
    width: 24px;
    font-size: 18px;
    text-align: center;
}

.sidebar-link span {
    font-size: 14px;
}

.sidebar-link:hover, .sidebar-link.active {
    background: rgba(37,99,235,0.1);
    color: #3b82f6;
    border-left: 3px solid #3b82f6;
}

/* Mobile Menu Button */
.mobile-menu-btn {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: linear-gradient(135deg, var(--royal), var(--azure));
    border: none;
    padding: 12px;
    border-radius: 12px;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: all 0.2s;
}

.mobile-menu-btn:hover {
    transform: scale(1.05);
}

/* Sidebar Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 99;
    display: none;
}

.sidebar-overlay.active {
    display: block;
}

/* Dashboard Top Bar */
.dashboard-topbar {
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--card-border);
    padding: 12px 20px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.page-title {
    font-size: 20px;
    font-weight: 600;
}

/* User Menu */
.user-menu-dashboard {
    position: relative;
    cursor: pointer;
}

.user-avatar-dashboard {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--royal), var(--azure));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: var(--transition);
}

.user-avatar-dashboard:hover {
    transform: scale(1.05);
    box-shadow: var(--glow-blue);
}

.user-dropdown {
    position: absolute;
    top: 50px;
    right: 0;
    background: rgba(15, 23, 42, 0.98);
    backdrop-filter: blur(16px);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 10px;
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
    z-index: 101;
}

.user-menu-dashboard:hover .user-dropdown {
    opacity: 1;
    visibility: visible;
}

.user-dropdown a {
    display: block;
    padding: 10px 16px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    border-radius: 8px;
    transition: var(--transition);
}

.user-dropdown a:hover {
    background: rgba(37,99,235,0.15);
    color: white;
}

/* =====================================================
   RESPONSIVE BREAKPOINTS
===================================================== */

/* Laptop / Small Desktop (1024px - 1366px) */
@media (max-width: 1366px) {
    :root {
        --sidebar-width-desktop: 240px;
    }
    
    .sidebar-link {
        padding: 8px 16px;
    }
    
    .sidebar-link i {
        width: 20px;
        font-size: 16px;
    }
    
    .dashboard-main {
        padding: 16px;
    }
}

/* Tablet Landscape (768px - 1024px) */
@media (max-width: 1024px) {
    .dashboard-sidebar {
        transform: translateX(-100%);
        width: var(--sidebar-width-tablet);
    }
    
    .dashboard-sidebar.open {
        transform: translateX(0);
    }
    
    .dashboard-main {
        margin-left: 0;
        padding: 16px;
        padding-top: 70px;
    }
    
    .mobile-menu-btn {
        display: block;
    }
}

/* Tablet Portrait (481px - 768px) */
@media (max-width: 768px) {
    :root {
        --spacing-lg: 16px;
        --spacing-xl: 20px;
    }
    
    .dashboard-main {
        padding: 12px;
        padding-top: 70px;
    }
    
    .dashboard-topbar {
        padding: 10px 16px;
        margin-bottom: 16px;
    }
    
    .page-title {
        font-size: 18px;
    }
    
    .user-avatar-dashboard {
        width: 35px;
        height: 35px;
        font-size: 14px;
    }
    
    .user-dropdown {
        min-width: 180px;
        right: -10px;
    }
    
    .user-dropdown a {
        padding: 8px 12px;
        font-size: 13px;
    }
    
    /* Stats cards 2 columns */
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px !important;
    }
    
    /* Jobs grid 2 columns */
    .jobs-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 16px !important;
    }
}

/* Mobile Devices (320px - 480px) */
@media (max-width: 480px) {
    .dashboard-sidebar {
        width: 260px;
    }
    
    .dashboard-main {
        padding: 10px;
        padding-top: 65px;
    }
    
    .mobile-menu-btn {
        top: 15px;
        left: 15px;
        padding: 10px;
    }
    
    .dashboard-topbar {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        padding: 12px;
    }
    
    .page-title {
        font-size: 16px;
        text-align: center;
    }
    
    .user-menu-dashboard {
        align-self: center;
    }
    
    .sidebar-link {
        padding: 12px 16px;
    }
    
    .sidebar-link i {
        width: 22px;
        font-size: 16px;
    }
    
    .sidebar-link span {
        font-size: 13px;
    }
    
    /* Single column grids */
    .jobs-grid {
        grid-template-columns: 1fr !important;
    }
    
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .companies-grid {
        grid-template-columns: 1fr !important;
    }
    
    .features-grid {
        grid-template-columns: 1fr !important;
    }
    
    .testimonials-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Buttons full width */
    .btn-primary-gradient,
    .btn-outline-light {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
    
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-btn {
        text-align: center;
    }
    
    /* Form inputs */
    input, select, textarea {
        font-size: 16px !important;
    }
    
    /* Welcome card */
    .welcome-card {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .welcome-card h1 {
        font-size: 22px;
    }
}

/* Small Mobile (320px and below) */
@media (max-width: 360px) {
    .dashboard-sidebar {
        width: 240px;
    }
    
    .sidebar-brand h2 {
        font-size: 16px;
    }
    
    .sidebar-brand span {
        font-size: 10px;
    }
    
    .sidebar-link {
        padding: 10px 14px;
    }
    
    .dashboard-topbar {
        padding: 10px;
    }
}

/* =====================================================
   RESPONSIVE TABLE STYLES
===================================================== */

.responsive-table {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    .responsive-table {
        margin: 0 -10px;
        width: calc(100% + 20px);
        padding: 0 10px;
    }
}

.users-table,
.applications-table,
.jobs-table,
.audit-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th,
.users-table td,
.applications-table th,
.applications-table td,
.jobs-table th,
.jobs-table td,
.audit-table th,
.audit-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.users-table th,
.applications-table th,
.jobs-table th,
.audit-table th {
    background: rgba(37,99,235,0.1);
    font-weight: 600;
}

@media (max-width: 768px) {
    .users-table th,
    .users-table td,
    .applications-table th,
    .applications-table td,
    .jobs-table th,
    .jobs-table td,
    .audit-table th,
    .audit-table td {
        padding: 8px;
        font-size: 12px;
    }
}

/* =====================================================
   RESPONSIVE GRID SYSTEMS
===================================================== */

.jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
    margin: 24px 0;
}

.companies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 24px;
    margin: 24px 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 28px;
    margin: 40px 0;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin: 40px 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .testimonials-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}

@media (max-width: 768px) {
    .features-grid,
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
}

/* =====================================================
   CARD STYLES
===================================================== */

.job-card,
.company-card,
.feature-card,
.testimonial-card,
.applicant-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--card-border);
    border-radius: 20px;
    padding: 20px;
    transition: var(--transition);
}

.job-card:hover,
.company-card:hover,
.feature-card:hover,
.testimonial-card:hover,
.applicant-card:hover {
    transform: translateY(-3px);
    background: rgba(37,99,235,0.05);
    border-color: rgba(37,99,235,0.2);
}

/* =====================================================
   BUTTON STYLES
===================================================== */

.btn-primary-gradient {
    background: linear-gradient(135deg, var(--royal), var(--azure));
    border: none;
    padding: 10px 24px;
    border-radius: 40px;
    color: white;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
    cursor: pointer;
}

.btn-primary-gradient:hover {
    transform: translateY(-2px);
    box-shadow: var(--glow-blue);
}

.btn-outline-light {
    background: transparent;
    border: 1px solid var(--card-border);
    padding: 10px 24px;
    border-radius: 40px;
    color: white;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
    cursor: pointer;
}

.btn-outline-light:hover {
    border-color: var(--royal);
    background: rgba(37,99,235,0.1);
    transform: translateY(-2px);
}

/* =====================================================
   FILTER BAR RESPONSIVE
===================================================== */

.filter-bar,
.status-filter {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 24px;
    padding: 16px;
    background: rgba(255,255,255,0.03);
    border-radius: 20px;
}

.filter-btn,
.status-filter-btn {
    padding: 8px 20px;
    border-radius: 40px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: var(--white-muted);
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
}

.filter-btn.active,
.status-filter-btn.active {
    background: linear-gradient(135deg, var(--royal), var(--azure));
    color: white;
}

.filter-btn:hover,
.status-filter-btn:hover {
    background: rgba(37,99,235,0.2);
    color: white;
}

/* =====================================================
   MODAL RESPONSIVE
===================================================== */

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    display: none;
}

.modal-content,
.role-modal {
    background: #0f172a;
    border-radius: 24px;
    padding: 32px;
    max-width: 450px;
    width: 90%;
    border: 1px solid rgba(255,255,255,0.1);
}

@media (max-width: 768px) {
    .modal-content,
    .role-modal {
        padding: 20px;
        width: 95%;
    }
}

/* =====================================================
   PAGINATION RESPONSIVE
===================================================== */

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.pagination a {
    padding: 8px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.2s;
}

.pagination a:hover,
.pagination a.active {
    background: linear-gradient(135deg, var(--royal), var(--azure));
    color: white;
}

@media (max-width: 640px) {
    .pagination a {
        padding: 6px 12px;
        font-size: 12px;
    }
}

/* =====================================================
   UTILITY CLASSES
===================================================== */

.text-muted-light { color: var(--white-muted); }
.text-dim { color: var(--white-dim); }
.text-gradient {
    background: linear-gradient(135deg, var(--royal-light), var(--azure-light));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.text-center { text-align: center; }

.mt-1 { margin-top: 8px; }
.mt-2 { margin-top: 16px; }
.mt-3 { margin-top: 24px; }
.mb-1 { margin-bottom: 8px; }
.mb-2 { margin-bottom: 16px; }
.mb-3 { margin-bottom: 24px; }

.d-flex { display: flex; }
.align-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-1 { gap: 8px; }
.gap-2 { gap: 16px; }

.w-100 { width: 100%; }

@media (max-width: 768px) {
    .hide-mobile { display: none; }
}

@media (min-width: 769px) {
    .show-mobile { display: none; }
}
        
        /* AI Badge for Sidebar */
.ai-badge-sidebar {
    background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    color: white;
    font-size: 9px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 20px;
    margin-left: 8px;
    letter-spacing: 0.5px;
}
</style>
</head>
<body>

<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<div class="dashboard-sidebar" id="dashboardSidebar">
    <div class="sidebar-brand">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
            <img src="/assets/images/logo.jpg" alt="JobLinkLocal" style="width: 32px; height: 32px; object-fit: contain;">
            <h2>Link Local</h2>
        </div>
        <span><?php echo ucfirst($user_role ?? 'Dashboard'); ?> Portal</span>
    </div>
    
   <?php if ($user_role === 'jobseeker'): ?>
    <a href="<?php echo SITE_URL; ?>dashboard/seeker/index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/seeker/profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/seeker/jobs.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>">
        <i class="fas fa-search"></i> Browse Jobs
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/seeker/submit_credentials.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'submit_credentials.php' ? 'active' : ''; ?>">
        <i class="fas fa-id-card"></i> Submit Credentials
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/seeker/recommended.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'recommended.php' ? 'active' : ''; ?>">
        <i class="fas fa-magic"></i> Recommended
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/seeker/applications.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : ''; ?>">
        <i class="fas fa-paper-plane"></i> My Applications
    </a>

<?php elseif ($user_role === 'employer'): ?>
    <a href="<?php echo SITE_URL; ?>dashboard/employer/index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/employer/profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
        <i class="fas fa-building"></i> Business Profile
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/employer/post-job.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'post-job.php' ? 'active' : ''; ?>">
        <i class="fas fa-plus-circle"></i> Post a Job
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/employer/manage-jobs.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-jobs.php' ? 'active' : ''; ?>">
        <i class="fas fa-list"></i> Manage Jobs
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/employer/submit_credentials.php" class="sidebar-link">
    <i class="fas fa-id-card"></i> Submit Credentials
</a>
    <a href="<?php echo SITE_URL; ?>dashboard/employer/applicants.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'applicants.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> Applicants
    </a>
    <!-- NEW: Analytics Link for Employer -->
    <a href="<?php echo SITE_URL; ?>dashboard/employer/analytics.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Analytics
    </a>
    <!-- NEW: Best Applicants (AI-Assisted) Link for Employer -->
    <a href="<?php echo SITE_URL; ?>dashboard/employer/best_applicants.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'best_applicants.php' ? 'active' : ''; ?>">
        <i class="fas fa-robot"></i> Best Applicants <span class="ai-badge-sidebar">AI</span>
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/employer/verification.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'verification.php' ? 'active' : ''; ?>">
        <i class="fas fa-shield-alt"></i> Verification
    </a>

<?php elseif ($user_role === 'lgu_admin'): ?>
    <a href="<?php echo SITE_URL; ?>dashboard/admin/index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/admin/jobs.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>">
        <i class="fas fa-gavel"></i> Job Approvals
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/admin/verify_credentials.php" class="sidebar-link">
    <i class="fas fa-shield-alt"></i> Verify Credentials
</a>
    <a href="<?php echo SITE_URL; ?>dashboard/admin/employers.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'employers.php' ? 'active' : ''; ?>">
        <i class="fas fa-building"></i> Employers
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/admin/analytics.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Analytics
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/admin/insights.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'insights.php' ? 'active' : ''; ?>">
        <i class="fas fa-lightbulb"></i> Insights
    </a>
    <!-- NEW: Top Rated Jobs Link for LGU Admin -->
    <a href="<?php echo SITE_URL; ?>dashboard/admin/top_jobs.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'top_jobs.php' ? 'active' : ''; ?>">
        <i class="fas fa-trophy"></i> Top Rated Jobs
    </a>
    <!-- NEW: AI Insights Link for LGU Admin -->
    <a href="<?php echo SITE_URL; ?>dashboard/admin/ai_insights.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'ai_insights.php' ? 'active' : ''; ?>">
        <i class="fas fa-brain"></i> AI Insights <span class="ai-badge-sidebar">AI</span>
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/admin/audit-logs.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'audit-logs.php' ? 'active' : ''; ?>">
        <i class="fas fa-history"></i> Audit Logs
    </a>

<?php elseif ($user_role === 'sys_admin'): ?>
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <!-- NEW: User Management (replaces or adds to users.php) -->
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/user_management.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>">
        <i class="fas fa-users-cog"></i> User Management
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/roles.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-shield"></i> RBAC
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/categories.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <i class="fas fa-folder-tree"></i> Categories
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/skills.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'skills.php' ? 'active' : ''; ?>">
        <i class="fas fa-tags"></i> Skills
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/audit-logs.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'audit-logs.php' ? 'active' : ''; ?>">
        <i class="fas fa-history"></i> Audit Trail
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/backup.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>">
        <i class="fas fa-database"></i> Backup
    </a>
    <a href="<?php echo SITE_URL; ?>dashboard/sysadmin/config.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'config.php' ? 'active' : ''; ?>">
        <i class="fas fa-sliders-h"></i> Config
    </a>
<?php endif; ?>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--card-border);">
        <a href="javascript:void(0);" onclick="confirmLogout()" class="sidebar-link" style="color: #ef4444;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="dashboard-main">