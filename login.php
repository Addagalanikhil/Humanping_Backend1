<?php
// auth/login.php

require_once __DIR__ . '/../utils/auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    jsonResponse(400, 'Email and password required.');
}

$email = trim($data['email']);
$password = $data['password'];

$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.password_hash, u.created_at, u.points, 
           s.current_streak, s.total_completed, s.last_completed_date
    FROM users u
    LEFT JOIN streaks s ON u.id = s.user_id
    WHERE u.email = ?
");
$stmt->execute([$email]);

if ($stmt->rowCount() === 0) {
    jsonResponse(401, 'Invalid credentials.');
}

$user = $stmt->fetch();

if (password_verify($password, $user['password_hash'])) {
    $token = generateJWT($user['id']);
    
    // Remove hash from response
    unset($user['password_hash']);
    
    // Normalize nulls
    $user['current_streak'] = $user['current_streak'] ?? 0;
    $user['total_completed'] = $user['total_completed'] ?? 0;

    jsonResponse(200, 'Login successful', [
        'user' => $user,
        'token' => $token
    ]);
} else {
    jsonResponse(401, 'Invalid credentials.');
}
?>
