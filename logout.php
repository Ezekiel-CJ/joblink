<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    logAudit($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
}

$_SESSION = array();
session_destroy();

setcookie('remember_token', '', time() - 3600, '/');

redirect(SITE_URL . 'index.php', 'You have been logged out successfully.', 'success');
?>