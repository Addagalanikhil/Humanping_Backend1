<?php
// feedback/submit_feedback.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['message'])) {
    jsonResponse(400, 'Message is required.');
}

$message = trim($data['message']);

try {
    $stmt = $conn->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
    $stmt->execute([$userId, $message]);

    jsonResponse(200, 'Feedback submitted. Thank you!');
} catch (Exception $e) {
    jsonResponse(500, 'Failed to submit feedback.');
}
?>
