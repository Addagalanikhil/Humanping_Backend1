<?php
// auth/forgot_password.php

require_once __DIR__ . '/../utils/auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email'])) {
    jsonResponse(400, 'Email required.');
}

$email = trim($data['email']);

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch();
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, reset_token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expires_at]);

    // In a real app, send email here.
    // Since we are mocking, we return the token for testing.
    jsonResponse(200, 'Password reset link sent (Mock Mode).', ['mock_token' => $token]);
} else {
    // Return same message to prevent email enumeration
    jsonResponse(200, 'If this email exists, a reset link has been sent.', ['mock_token' => null]);
}
?>
