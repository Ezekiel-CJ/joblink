<?php
$page_title = "Where Careers Take Flight";
require_once 'includes/config.php';

$db = getDB();

// Get stats with null safety
$total_jobs = 0;
$total_companies = 0;
$total_hires = 0;
$total_seekers = 0;

if ($db) {
    $result = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved'");
    $total_jobs = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
    
    $result = $db->query("SELECT COUNT(*) as count FROM employer_profiles WHERE verification_status = 'verified'");
    $total_companies = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
    
    $result = $db->query("SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'");
    $total_hires = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
    
    $result = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'jobseeker'");
    $total_seekers = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
}

// Get featured jobs
$featured_jobs = [];
if ($db) {
    $result = $db->query("SELECT j.*, c.name as category_name, u.full_name as employer_name, ep.business_name 
                          FROM jobs j 
                          LEFT JOIN job_categories c ON j.category_id = c.id 
                          LEFT JOIN users u ON j.employer_id = u.id 
                          LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
                          WHERE j.status = 'approved' 
                          ORDER BY j.created_at DESC 
                          LIMIT 6");
    $featured_jobs = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get categories for search
$categories = [];
if ($db) {
    $result = $db->query("SELECT * FROM job_categories ORDER BY name LIMIT 10");
    $categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// =====================================================
// SHOW INTRO ON EVERY REFRESH (NO COOKIE CHECK)
// =====================================================
$show_intro = true;  // Always show intro on every page load

require_once 'includes/header.php';
?>

<style>
    /* =====================================================
       CINEMATIC INTRO
    ===================================================== */
    .cinematic-intro {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10000;
        background: linear-gradient(135deg, #0a0e1a 0%, #0f172a 50%, #1a1f3a 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: cinematicFadeOut 0.8s ease-out 2.5s forwards;
        pointer-events: none;
    }
    
    @keyframes cinematicFadeOut {
        0% { opacity: 1; visibility: visible; }
        100% { opacity: 0; visibility: hidden; }
    }
    
    .cinematic-content {
        text-align: center;
        animation: cinematicScale 0.6s ease-out;
    }
    
    @keyframes cinematicScale {
        0% { transform: scale(0.9); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .cinematic-logo {
        width: 120px;
        height: 120px;
        margin: 0 auto 30px;
        animation: cinematicLogo 0.6s ease-out;
    }
    
    .cinematic-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    @keyframes cinematicLogo {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .cinematic-title {
        font-size: 42px;
        font-weight: 800;
        background: linear-gradient(135deg, #ffffff, #22d3ee);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 16px;
        animation: cinematicTitle 0.6s ease-out 0.2s both;
    }
    
    @keyframes cinematicTitle {
        0% { transform: translateY(30px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    
    .cinematic-tagline {
        font-size: 14px;
        color: rgba(255,255,255,0.6);
        letter-spacing: 4px;
        text-transform: uppercase;
        animation: cinematicTagline 0.6s ease-out 0.4s both;
    }
    
    @keyframes cinematicTagline {
        0% { transform: translateY(20px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    
    .cinematic-line {
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #2563eb, #06b6d4);
        margin: 30px auto 0;
        animation: cinematicLine 0.8s ease-out 0.6s forwards;
    }
    
    @keyframes cinematicLine {
        0% { width: 0; }
        100% { width: 180px; }
    }
    
    .loading-dots {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 35px;
    }
    
    .loading-dots span {
        width: 8px;
        height: 8px;
        background: rgba(59,130,246,0.5);
        border-radius: 50%;
        animation: loadingDot 1.4s ease-in-out infinite;
    }
    
    .loading-dots span:nth-child(1) { animation-delay: 0s; }
    .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
    .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
    
    @keyframes loadingDot {
        0%, 100% { transform: scale(0.5); opacity: 0.3; }
        50% { transform: scale(1); opacity: 1; background: #06b6d4; }
    }
    
    /* =====================================================
       HERO SECTION
    ===================================================== */
    .hero-section {
        position: relative;
        min-height: 90vh;
        display: flex;
        align-items: center;
        overflow: hidden;
        isolation: isolate;
    }
    
    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070');
        background-size: cover;
        background-position: center;
        opacity: 0.12;
        z-index: 0;
    }
    
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 30% 20%, rgba(10,14,26,0.4), rgba(10,14,26,0.96));
        z-index: 1;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 1200px;
        margin: 0 auto;
        text-align: center;
        padding: 80px 0 60px;
    }
    
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(37,99,235,0.12);
        border: 1px solid rgba(37,99,235,0.2);
        border-radius: 60px;
        padding: 6px 18px;
        margin-bottom: 28px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .hero-badge i {
        color: #06b6d4;
    }
    
    .hero-title {
        font-size: 64px;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.1;
        margin-bottom: 24px;
    }
    
    .hero-subtitle {
        font-size: 18px;
        color: rgba(255,255,255,0.7);
        max-width: 600px;
        margin: 0 auto 40px;
        line-height: 1.6;
    }
    
    .hero-buttons {
        display: flex;
        gap: 16px;
        justify-content: center;
        margin-bottom: 50px;
        flex-wrap: wrap;
    }
    
    /* =====================================================
       ULTRA-PREMIUM SEARCH PANEL
    ===================================================== */
    .search-premium-container {
        max-width: 1100px;
        margin: 0 auto 50px;
        padding: 0 20px;
    }
    
    .search-premium-card {
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(25px);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 28px;
        padding: 28px 32px;
        transition: all 0.4s ease;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }
    
    .search-premium-card:hover {
        border-color: rgba(59,130,246,0.3);
        transform: translateY(-2px);
        box-shadow: 0 30px 60px -15px rgba(0,0,0,0.3);
    }
    
    .search-premium-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    
    .search-premium-header i {
        font-size: 20px;
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    .search-premium-header span {
        font-size: 13px;
        font-weight: 600;
        color: rgba(255,255,255,0.5);
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }
    
    .search-premium-fields {
        display: flex;
        align-items: center;
        gap: 0;
        flex-wrap: wrap;
    }
    
    .search-premium-field {
        flex: 1;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 8px 20px;
        border-radius: 20px;
        transition: all 0.3s ease;
    }
    
    .search-premium-field:focus-within {
        background: rgba(59,130,246,0.08);
    }
    
    .field-icon {
        flex-shrink: 0;
        margin-top: 4px;
    }
    
    .field-icon i {
        font-size: 20px;
        color: #06b6d4;
        transition: all 0.3s ease;
    }
    
    .search-premium-field:focus-within .field-icon i {
        color: #3b82f6;
        transform: scale(1.05);
    }
    
    .field-content {
        flex: 1;
        min-width: 0;
    }
    
    .field-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.4);
        margin-bottom: 6px;
    }
    
    .field-input,
    .field-select {
        width: 100%;
        background: transparent;
        border: none;
        padding: 6px 0 10px;
        color: #ffffff;
        font-size: 15px;
        font-family: inherit;
    }
    
    .field-input::placeholder {
        color: rgba(255,255,255,0.3);
    }
    
    .field-input:focus,
    .field-select:focus {
        outline: none;
    }
    
    .field-select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2386efac' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0 center;
        background-size: 14px;
        padding-right: 24px;
    }
    
    .field-select option {
        background: #0f172a;
        color: #ffffff;
        padding: 10px;
    }
    
    .search-premium-divider {
        width: 1px;
        height: 50px;
        background: linear-gradient(180deg, transparent, rgba(255,255,255,0.12), transparent);
        flex-shrink: 0;
    }
    
    .search-premium-btn {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border: none;
        padding: 14px 36px;
        border-radius: 60px;
        color: white;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
        margin-left: 16px;
        box-shadow: 0 8px 20px rgba(37,99,235,0.25);
    }
    
    .search-premium-btn i {
        transition: transform 0.3s ease;
    }
    
    .search-premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(37,99,235,0.4);
        gap: 14px;
    }
    
    .search-premium-btn:hover i {
        transform: translateX(4px);
    }
    
    .search-premium-trending {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-top: 24px;
        padding-top: 20px;
        flex-wrap: wrap;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    
    .trending-label {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .trending-label i {
        font-size: 13px;
        color: #f59e0b;
    }
    
    .trending-label span {
        font-size: 12px;
        font-weight: 500;
        color: rgba(255,255,255,0.5);
    }
    
    .trending-tags {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .trending-tags a {
        background: rgba(255,255,255,0.04);
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(255,255,255,0.06);
    }
    
    .trending-tags a:hover {
        background: rgba(59,130,246,0.15);
        color: #3b82f6;
        border-color: rgba(59,130,246,0.3);
        transform: translateY(-1px);
    }
    
    /* Stats Row */
    .stats-row {
        display: flex;
        justify-content: center;
        gap: 60px;
        flex-wrap: wrap;
        margin-top: 20px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 40px;
        font-weight: 800;
        background: linear-gradient(135deg, #ffffff, #22d3ee);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    /* Section Headers */
    .section-header {
        text-align: center;
        margin: 80px 0 50px;
    }
    
    .section-tag {
        display: inline-block;
        background: rgba(37,99,235,0.1);
        padding: 6px 16px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 1px;
        color: #06b6d4;
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 48px;
        font-weight: 700;
        letter-spacing: -0.02em;
        margin-bottom: 16px;
    }
    
    .section-subtitle {
        font-size: 16px;
        color: rgba(255,255,255,0.6);
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* Companies Grid - Centered */
    .companies-container {
        display: flex;
        justify-content: center;
    }
    
    .companies-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 24px;
        max-width: 1200px;
        width: 100%;
        margin: 40px auto;
    }
    
    .company-card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .company-card:hover {
        transform: translateY(-4px);
        background: rgba(37,99,235,0.08);
    }
    
    .company-logo {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, rgba(37,99,235,0.2), rgba(6,182,212,0.2));
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }
    
    .company-logo i {
        font-size: 28px;
        color: #06b6d4;
    }
    
    /* Jobs Grid - Centered */
    .jobs-container {
        display: flex;
        justify-content: center;
    }
    
    .jobs-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
        max-width: 1200px;
        width: 100%;
        margin: 40px auto;
    }
    
    .job-card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .job-card:hover {
        transform: translateY(-4px);
        background: rgba(37,99,235,0.08);
        border-color: rgba(37,99,235,0.3);
        box-shadow: 0 20px 40px -12px rgba(0,0,0,0.3);
    }
    
    .company-placeholder {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, rgba(37,99,235,0.2), rgba(6,182,212,0.2));
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    
    .company-placeholder i {
        font-size: 24px;
        color: #06b6d4;
    }
    
    .salary-tag {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 500;
        color: #22d3ee;
    }
    
    /* Features Grid */
    .features-container {
        display: flex;
        justify-content: center;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 28px;
        max-width: 1200px;
        width: 100%;
        margin: 60px auto;
    }
    
    .feature-card {
        text-align: center;
        padding: 32px 24px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 28px;
        transition: all 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        background: rgba(37,99,235,0.08);
    }
    
    .feature-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, rgba(37,99,235,0.15), rgba(6,182,212,0.15));
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }
    
    .feature-icon i {
        font-size: 32px;
        color: #06b6d4;
    }
    
    /* Testimonials Grid */
    .testimonials-container {
        display: flex;
        justify-content: center;
    }
    
    .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        max-width: 1200px;
        width: 100%;
        margin: 50px auto;
    }
    
    .testimonial-card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 28px;
        padding: 32px;
        transition: all 0.3s ease;
    }
    
    .testimonial-card:hover {
        transform: translateY(-5px);
        background: rgba(37,99,235,0.08);
    }
    
    .testimonial-avatar {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    
    .testimonial-avatar i {
        font-size: 28px;
        color: white;
    }
    
    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(6,182,212,0.05));
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 48px;
        padding: 60px 48px;
        text-align: center;
        margin: 60px 0;
        transition: all 0.3s ease;
    }
    
    .cta-section:hover {
        border-color: rgba(37,99,235,0.4);
        box-shadow: 0 20px 40px -12px rgba(0,0,0,0.3);
    }
    
    /* Admin Portal Box */
    .admin-portal-box {
        background: rgba(6,182,212,0.08);
        border: 1px solid rgba(6,182,212,0.2);
        border-radius: 20px;
        padding: 18px 24px;
        margin: 40px auto;
        max-width: 550px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .admin-portal-box:hover {
        background: rgba(6,182,212,0.12);
        border-color: rgba(6,182,212,0.4);
    }
    
    .admin-portal-box i {
        color: #06b6d4;
        margin-right: 10px;
    }
    
    .admin-portal-box a {
        color: #22d3ee;
        text-decoration: none;
        font-weight: 500;
        margin-left: 8px;
    }
    
    .admin-portal-box a:hover {
        color: #ffffff;
        text-decoration: underline;
    }
    
    /* Responsive */
    @media (max-width: 1100px) {
        .companies-grid { grid-template-columns: repeat(4, 1fr); }
        .jobs-grid { grid-template-columns: repeat(2, 1fr); }
        .features-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 900px) {
        .hero-title { font-size: 48px; }
        .search-premium-fields { flex-direction: column; align-items: stretch; gap: 16px; }
        .search-premium-field { padding: 12px 16px; background: rgba(255,255,255,0.03); border-radius: 20px; }
        .search-premium-divider { display: none; }
        .search-premium-btn { margin-left: 0; margin-top: 8px; justify-content: center; }
        .search-premium-trending { flex-direction: column; align-items: flex-start; gap: 12px; }
        .companies-grid { grid-template-columns: repeat(3, 1fr); }
        .features-grid { grid-template-columns: 1fr; }
        .testimonials-grid { grid-template-columns: 1fr; }
        .stats-row { gap: 30px; }
        .hero-buttons { flex-direction: column; align-items: center; }
    }
    
    @media (max-width: 640px) {
        .hero-title { font-size: 36px; }
        .section-title { font-size: 32px; }
        .companies-grid { grid-template-columns: repeat(2, 1fr); }
        .jobs-grid { grid-template-columns: 1fr; }
        .search-premium-card { padding: 20px; }
        .cta-section { padding: 40px 24px; }
        .cta-section h2 { font-size: 28px; }
        .admin-portal-box { margin: 40px 20px; padding: 12px 16px; }
    }
</style>

<?php if ($show_intro): ?>
<div class="cinematic-intro">
    <div class="cinematic-content">
        <div class="cinematic-logo">
            <img src="/assets/images/logo.jpg" alt="JobLinkLocal Logo">
        </div>
        <div class="cinematic-title">Link Local</div>
        <div class="cinematic-tagline">CONNECTING COMMUNITIES</div>
        <div class="cinematic-line"></div>
        <div class="loading-dots">
            <span></span><span></span><span></span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    
    <div class="container-premium">
        <div class="hero-content">
            <div class="hero-badge" data-aos="fade-up">
                <i class="fas fa-certificate"></i> LGU-Verified Platform
            </div>
            
            <h1 class="hero-title" data-aos="fade-up" data-aos-delay="100">
                Where Talent Meets<br>
                <span class="text-gradient">Opportunity</span>
            </h1>
            
            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="200">
                A unified employment platform built for Local Government Units, local businesses, 
                and job seekers. Find work, hire talent, and shape your community's workforce.
            </p>
            
            <div class="hero-buttons" data-aos="fade-up" data-aos-delay="300">
                <a href="/register.php" class="btn-primary-gradient" style="padding: 14px 36px;">
                    <i class="fas fa-user-plus"></i> Get Started
                </a>
                <a href="/jobs.php" class="btn-outline-light" style="padding: 14px 36px;">
                    <i class="fas fa-search"></i> Browse Jobs
                </a>
            </div>
            
            <!-- Ultra-Premium Search Panel -->
            <div class="search-premium-container" data-aos="fade-up" data-aos-delay="400">
                <div class="search-premium-card">
                    <div class="search-premium-header">
                        <i class="fas fa-search"></i>
                        <span>Find your next opportunity</span>
                    </div>
                    
                    <form action="/jobs.php" method="GET" class="search-premium-form">
                        <div class="search-premium-fields">
                            <div class="search-premium-field">
                                <div class="field-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="field-content">
                                    <label class="field-label">Job title / keyword</label>
                                    <input type="text" name="search" class="field-input" placeholder="e.g., Software Engineer, Marketing" autocomplete="off">
                                </div>
                            </div>
                            
                            <div class="search-premium-divider"></div>
                            
                            <div class="search-premium-field">
                                <div class="field-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="field-content">
                                    <label class="field-label">Location</label>
                                    <input type="text" name="location" class="field-input" placeholder="City, municipality, or remote" autocomplete="off">
                                </div>
                            </div>
                            
                            <div class="search-premium-divider"></div>
                            
                            <div class="search-premium-field">
                                <div class="field-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="field-content">
                                    <label class="field-label">Category</label>
                                    <select name="category" class="field-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="search-premium-btn">
                                <span>Search Jobs</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                    
                    <div class="search-premium-trending">
                        <div class="trending-label">
                            <i class="fas fa-fire"></i>
                            <span>Trending searches:</span>
                        </div>
                        <div class="trending-tags">
                            <a href="/jobs.php?search=Remote">Remote</a>
                            <a href="/jobs.php?search=Full-time">Full-time</a>
                            <a href="/jobs.php?search=Software">Software</a>
                            <a href="/jobs.php?search=Healthcare">Healthcare</a>
                            <a href="/jobs.php?search=Customer Service">Customer Service</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-row" data-aos="fade-up" data-aos-delay="500">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_jobs); ?>+</div>
                    <div class="text-muted-light">Active Jobs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_companies); ?>+</div>
                    <div class="text-muted-light">Verified Employers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_hires); ?>+</div>
                    <div class="text-muted-light">Successful Hires</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_seekers); ?>+</div>
                    <div class="text-muted-light">Job Seekers</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Companies -->
<section class="container-premium">
    <div class="section-header" data-aos="fade-up">
        <div class="section-tag"><i class="fas fa-star"></i> Trusted Partners</div>
        <h2 class="section-title">Premium Companies<br>Hiring Now</h2>
        <p class="section-subtitle">Join industry leaders and innovative startups building the future.</p>
    </div>
    
    <div class="companies-container">
        <div class="companies-grid" data-aos="fade-up">
            <div class="company-card" onclick="location.href='/companies.php'">
                <div class="company-logo"><i class="fas fa-building"></i></div>
                <h4>TechCorp</h4>
                <div class="text-dim" style="font-size: 12px;">23 openings</div>
            </div>
            <div class="company-card" onclick="location.href='/companies.php'">
                <div class="company-logo"><i class="fas fa-hospital"></i></div>
                <h4>MediCare</h4>
                <div class="text-dim" style="font-size: 12px;">15 openings</div>
            </div>
            <div class="company-card" onclick="location.href='/companies.php'">
                <div class="company-logo"><i class="fas fa-chart-line"></i></div>
                <h4>FinServe</h4>
                <div class="text-dim" style="font-size: 12px;">18 openings</div>
            </div>
            <div class="company-card" onclick="location.href='/companies.php'">
                <div class="company-logo"><i class="fas fa-graduation-cap"></i></div>
                <h4>EduGlobal</h4>
                <div class="text-dim" style="font-size: 12px;">12 openings</div>
            </div>
            <div class="company-card" onclick="location.href='/companies.php'">
                <div class="company-logo"><i class="fas fa-truck"></i></div>
                <h4>LogiTech</h4>
                <div class="text-dim" style="font-size: 12px;">9 openings</div>
            </div>
            <div class="company-card" onclick="location.href='/companies.php'">
                <div class="company-logo"><i class="fas fa-laptop-code"></i></div>
                <h4>DevStudio</h4>
                <div class="text-dim" style="font-size: 12px;">27 openings</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Jobs -->
<section class="container-premium">
    <div class="section-header" data-aos="fade-up">
        <div class="section-tag"><i class="fas fa-fire"></i> Latest Opportunities</div>
        <h2 class="section-title">Featured Jobs<br>Just for You</h2>
        <p class="section-subtitle">Curated opportunities matching your skills and preferences.</p>
    </div>
    
    <?php if (!empty($featured_jobs)): ?>
        <div class="jobs-container">
            <div class="jobs-grid" data-aos="fade-up">
                <?php foreach ($featured_jobs as $job): ?>
                    <div class="job-card" onclick="location.href='/job-details.php?id=<?php echo $job['id']; ?>'">
                        <div class="company-placeholder">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3 style="margin-bottom: 8px;"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <div class="text-muted-light" style="margin-bottom: 12px;">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['business_name'] ?? $job['employer_name']); ?>
                        </div>
                        <div class="salary-tag">
                            <i class="fas fa-tag"></i> ₱<?php echo number_format($job['salary_min'] ?? 30000); ?> - ₱<?php echo number_format($job['salary_max'] ?? 50000); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center" style="padding: 60px; background: rgba(255,255,255,0.04); border-radius: 28px; max-width: 600px; margin: 0 auto;">
            <i class="fas fa-briefcase" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
            <h3>No jobs available at the moment</h3>
            <p class="text-muted-light">Check back soon for new opportunities.</p>
        </div>
    <?php endif; ?>
    
    <div class="text-center" style="margin-top: 20px;">
        <a href="/jobs.php" class="btn-outline-light" style="padding: 12px 32px;">Browse All Jobs →</a>
    </div>
</section>

<!-- Why Choose Us -->
<section class="container-premium">
    <div class="section-header" data-aos="fade-up">
        <div class="section-tag"><i class="fas fa-certificate"></i> Why Choose Us</div>
        <h2 class="section-title">Built for Your<br>Career Success</h2>
    </div>
    
    <div class="features-container">
        <div class="features-grid" data-aos="fade-up">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Skill-Based Matching</h3>
                <p class="text-muted-light">Jobs recommended based on your unique skills and profile.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>LGU-Verified Employers</h3>
                <p class="text-muted-light">All businesses undergo verification for legitimacy.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-simple"></i></div>
                <h3>Workforce Analytics</h3>
                <p class="text-muted-light">Real-time insights for community employment planning.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-circle-info"></i></div>
                <h3>Audit Trail & Transparency</h3>
                <p class="text-muted-light">Full accountability with role-based access.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="container-premium">
    <div class="section-header" data-aos="fade-up">
        <div class="section-tag"><i class="fas fa-quote-left"></i> Success Stories</div>
        <h2 class="section-title">Trusted by Thousands<br>of Professionals</h2>
    </div>
    
    <div class="testimonials-container">
        <div class="testimonials-grid" data-aos="fade-up">
            <div class="testimonial-card">
                <div class="testimonial-avatar"><i class="fas fa-user-tie"></i></div>
                <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">"The platform helped me find my dream job within weeks. The skill matching is incredibly accurate!"</p>
                <div><strong>Maria Santos</strong></div>
                <div class="text-muted-light" style="font-size: 13px;">Senior Developer at TechCorp</div>
                <div style="margin-top: 12px;"><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i></div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-avatar"><i class="fas fa-chart-line"></i></div>
                <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">"We've hired 20+ qualified candidates through this platform. The quality of applicants is outstanding."</p>
                <div><strong>James Wilson</strong></div>
                <div class="text-muted-light" style="font-size: 13px;">HR Director, Global Solutions</div>
                <div style="margin-top: 12px;"><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i></div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-avatar"><i class="fas fa-graduation-cap"></i></div>
                <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">"Fresh graduate here! Landed my first job within a month thanks to the career resources."</p>
                <div><strong>John Dela Cruz</strong></div>
                <div class="text-muted-light" style="font-size: 13px;">Junior Developer, DevStudio</div>
                <div style="margin-top: 12px;"><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star" style="color: #f59e0b;"></i><i class="fas fa-star-half-alt" style="color: #f59e0b;"></i></div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="container-premium">
    <div class="cta-section" data-aos="zoom-in">
        <h2 style="font-size: 42px; font-weight: 700; margin-bottom: 16px;">Ready to Transform Your Career?</h2>
        <p style="font-size: 18px; color: var(--white-muted); margin-bottom: 32px;">Join thousands of professionals already succeeding on our platform.</p>
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="/register.php" class="btn-primary-gradient" style="padding: 16px 40px;"><i class="fas fa-user-plus"></i> Get Started Free</a>
            <a href="/login.php" class="btn-outline-light" style="padding: 16px 40px;"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        </div>
        <div style="margin-top: 24px; font-size: 13px; color: var(--white-dim);">No credit card required • Free for job seekers</div>
    </div>
</section>

<!-- Admin Portal Box -->
<div class="container-premium">
    <div class="admin-portal-box">
        <i class="fas fa-shield-alt"></i>
        <span class="text-muted-light">LGU Officers & System Administrators:</span>
        <a href="/login.php?admin=1">Click here for secure portal →</a>
    </div>
</div>

<script>
    setTimeout(function() {
        var intro = document.querySelector('.cinematic-intro');
        if (intro) intro.style.display = 'none';
    }, 3300);
</script>

<?php require_once 'includes/footer.php'; ?>