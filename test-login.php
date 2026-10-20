<?php
require_once 'includes/config.php';

echo "<h1>Test Login</h1>";

$email = 'lgu@joblinklocal.com';
$password = 'Admin123!';

$db = getDB();
$stmt = $db->prepare("SELECT id, email, password, full_name, role, is_active, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    echo "<p>User found: " . $user['email'] . "</p>";
    echo "<p>Role: " . $user['role'] . "</p>";
    echo "<p>Active: " . ($user['is_active'] ? 'Yes' : 'No') . "</p>";
    echo "<p>Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "</p>";
    
    if (password_verify($password, $user['password'])) {
        echo "<p style='color:green'>✅ Password is CORRECT!</p>";
    } else {
        echo "<p style='color:red'>❌ Password is INCORRECT!</p>";
        echo "<p>Stored hash: " . $user['password'] . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ User not found: $email</p>";
}
?>