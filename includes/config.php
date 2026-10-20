<?php
// =====================================================
// JOBLINKLOCAL - MASTER CONFIGURATION
// Premium Platform | Production Ready
// =====================================================
    
// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// DATABASE CONFIGURATION
// =====================================================
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_USER', 'if0_41752523');
define('DB_PASS', 'VJxKH3mBmZzj');
define('DB_NAME', 'if0_41752523_joblinklocal_db');

// =====================================================
// SITE CONFIGURATION
// =====================================================
define('SITE_NAME', 'JobLinkLocal');
define('SITE_URL', 'https://joblinklocal.wuaze.com/');
define('SITE_EMAIL', 'joblinklocal@gmail.com');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// =====================================================
// EMAIL CONFIGURATION (WORKING)
// =====================================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'joblinklocal@gmail.com');
define('SMTP_PASS', 'vtag oqmb tzid sgbs');
define('SMTP_FROM', 'joblinklocal@gmail.com');
define('SMTP_FROM_NAME', 'JobLinkLocal');

// =====================================================
// SECURITY
// =====================================================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 7200); // 2 hours

// =====================================================
// DATABASE CONNECTION
// =====================================================
function getDB() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                error_log("Connection failed: " . $conn->connect_error);
                return null;
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

// =====================================================
// AUTHENTICATION FUNCTIONS
// =====================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    
    $user = getCurrentUser();
    if (!$user) return false;
    
    if (is_array($roles)) {
        return in_array($user['role'], $roles);
    }
    return $user['role'] === $roles;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page.";
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: " . SITE_URL . "index.php");
        exit();
    }
}

// =====================================================
// CSRF PROTECTION
// =====================================================

function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: " . $url);
    exit();
}

function displayFlashMessages() {
    $output = '';
    $types = ['success', 'error', 'warning', 'info'];
    
    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $output .= '<div class="alert alert-' . $type . ' alert-dismissible">' . $_SESSION[$type] . '</div>';
            unset($_SESSION[$type]);
        }
    }
    return $output;
}

function logAudit($userId, $action, $entity = null, $entityId = null, $details = null) {
    $db = getDB();
    if (!$db) return false;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $details_json = $details ? json_encode($details) : null;
    
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississs", $userId, $action, $entity, $entityId, $details_json, $ip, $userAgent);
    return $stmt->execute();
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function formatMoney($amount) {
    if (!$amount) return 'Negotiable';
    return '₱' . number_format($amount, 0, '.', ',');
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'verified' => 'success',
        'active' => 'success',
        'inactive' => 'secondary',
        'reviewed' => 'info',
        'shortlisted' => 'primary',
        'accepted' => 'success',
        'closed' => 'secondary'
    ];
    $type = $badges[$status] ?? 'secondary';
    $label = $status === 'rejected' ? 'Declined' : ucfirst($status);
    
    return '<span class="badge badge-' . $type . '">' . $label . '</span>';
}

function getRoleName($role) {
    $roles = [
        'jobseeker' => 'Job Seeker',
        'employer' => 'Employer',
        'lgu_admin' => 'LGU Officer',
        'sys_admin' => 'System Administrator'
    ];
    return $roles[$role] ?? ucfirst($role);
}

function uploadFile($file, $subfolder = '') {
    $targetDir = UPLOAD_PATH . $subfolder;
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
    
    if (!in_array($extension, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type.'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large. Max 5MB.'];
    }
    
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'assets/uploads/' . $subfolder . $filename];
    }
    
    return ['success' => false, 'error' => 'Upload failed.'];
}

function redirectForRole($role) {
    $paths = [
        'jobseeker' => 'dashboard/seeker/',
        'employer' => 'dashboard/employer/',
        'lgu_admin' => 'dashboard/admin/',
        'sys_admin' => 'dashboard/sysadmin/'
    ];
    return SITE_URL . ($paths[$role] ?? 'index.php');
}
?>