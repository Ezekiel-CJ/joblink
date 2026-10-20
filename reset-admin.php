<?php
require_once 'includes/config.php';

$db = getDB();

// Password: Admin123!
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Delete existing admins
$db->query("DELETE FROM users WHERE role IN ('lgu_admin', 'sys_admin')");

// Insert LGU Admin
$email1 = 'lgu@joblinklocal.com';
$name1 = 'LGU Employment Officer';
$role1 = 'lgu_admin';

$stmt1 = $db->prepare("INSERT INTO users (email, password, full_name, role, is_active, is_verified, terms_accepted, terms_accepted_at, created_at) VALUES (?, ?, ?, ?, 1, 1, 1, NOW(), NOW())");
$stmt1->bind_param("ssss", $email1, $hash, $name1, $role1);
$stmt1->execute();

// Insert System Admin
$email2 = 'sysadmin@joblinklocal.com';
$name2 = 'System Administrator';
$role2 = 'sys_admin';

$stmt2 = $db->prepare("INSERT INTO users (email, password, full_name, role, is_active, is_verified, terms_accepted, terms_accepted_at, created_at) VALUES (?, ?, ?, ?, 1, 1, 1, NOW(), NOW())");
$stmt2->bind_param("ssss", $email2, $hash, $name2, $role2);
$stmt2->execute();

echo "<h1>✅ Admin Accounts Reset Successfully!</h1>";
echo "<h2>Login Credentials:</h2>";
echo "<p><strong>LGU Admin:</strong> lgu@joblinklocal.com / Admin123!</p>";
echo "<p><strong>System Admin:</strong> sysadmin@joblinklocal.com / Admin123!</p>";

// Verify
$result = $db->query("SELECT id, email, role FROM users WHERE role IN ('lgu_admin', 'sys_admin')");
echo "<h3>Verification:</h3>";
while($row = $result->fetch_assoc()) {
    echo "<p>✅ " . $row['email'] . " - " . $row['role'] . " (ID: " . $row['id'] . ")</p>";
}
?>