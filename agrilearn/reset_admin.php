<?php
// One-time helper: run this once in your browser to (re)create the admin
// login with a known password, then DELETE this file for security.
require_once __DIR__ . '/config/db.php';

$username = 'admin';
$password = 'admin123'; // change this, then delete this file after running
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
$stmt->execute([$username]);
if ($row = $stmt->fetch()) {
    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->execute([$hash, $row['id']]);
    echo "Admin password reset. You can now log in with username '$username' and password '$password'.<br>";
} else {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?,?,?, 'admin','active')");
    $stmt->execute([$username, 'admin@agrilearn.local', $hash]);
    echo "Admin account created. Username '$username', password '$password'.<br>";
}
echo "<strong>Important:</strong> delete reset_admin.php now.";
