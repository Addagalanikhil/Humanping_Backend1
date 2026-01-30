<?php
// auth/reset_password.php

require_once __DIR__ . '/../utils/auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token']) || !isset($data['new_password'])) {
    jsonResponse(400, 'Token and new password required.');
}

$token = $data['token'];
$new_password = $data['new_password'];

if (strlen($new_password) < 6) {
    jsonResponse(400, 'Password must be at least 6 characters.');
}

// Validate Token
$stmt = $conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE reset_token = ?");
$stmt->execute([$token]);

if ($stmt->rowCount() === 0) {
    jsonResponse(400, 'Invalid token.');
}

$resetRequest = $stmt->fetch();

if (strtotime($resetRequest['expires_at']) < time()) {
    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE reset_token = ?");
    $stmt->execute([$token]);
    jsonResponse(400, 'Token expired.');
}

// Update Password
$password_hash = password_hash($new_password, PASSWORD_BCRYPT);
try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$password_hash, $resetRequest['user_id']]);

    // Delete used token
    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE reset_token = ?");
    $stmt->execute([$token]);

    $conn->commit();
    jsonResponse(200, 'Password reset successful.');
} catch (Exception $e) {
    $conn->rollBack();
    jsonResponse(500, 'Failed to reset password.');
}
?>
