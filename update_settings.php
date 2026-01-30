<?php
// settings/update_settings.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['notifications_enabled'])) {
    jsonResponse(400, 'notifications_enabled is required.');
}

$notifEnabled = filter_var($data['notifications_enabled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

try {
    $stmt = $conn->prepare("UPDATE user_settings SET notifications_enabled = ? WHERE user_id = ?");
    $stmt->execute([$notifEnabled, $userId]);

    jsonResponse(200, 'Settings updated successfully.', ['notifications_enabled' => (bool)$notifEnabled]);
} catch (Exception $e) {
    jsonResponse(500, 'Failed to update settings.');
}
?>
