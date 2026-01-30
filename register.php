<?php
// auth/register.php

require_once __DIR__ . '/../utils/auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    jsonResponse(400, 'Incomplete data. Name, email and password required.');
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, 'Invalid email format.');
}

if (strlen($password) < 6) {
    jsonResponse(400, 'Password must be at least 6 characters.');
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    jsonResponse(409, 'Email already registered.');
}

$password_hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $conn->beginTransaction();

    // Insert User
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password_hash]);
    $userId = $conn->lastInsertId();

    // Initialize Settings
    $stmt = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    // Initialize Streaks
    $stmt = $conn->prepare("INSERT INTO streaks (user_id) VALUES (?)");
    $stmt->execute([$userId]);

    $conn->commit();

    jsonResponse(201, 'User registered successfully.');

} catch (Exception $e) {
    $conn->rollBack();
    jsonResponse(500, 'Registration failed: ' . $e->getMessage());
}
?>
