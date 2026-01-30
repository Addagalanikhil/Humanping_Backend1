<?php
// settings/get_settings.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Method not allowed');
}

$stmt = $conn->prepare("SELECT notifications_enabled FROM user_settings WHERE user_id = ?");
$stmt->execute([$userId]);
$settings = $stmt->fetch();

if (!$settings) {
    // Should be created at register, but valid fallback
    $settings = ['notifications_enabled' => 1];
}

jsonResponse(200, 'Settings retrieved.', $settings);
?>
