<?php
// streaks/get_streak.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Method not allowed');
}

$stmt = $conn->prepare("SELECT current_streak, last_completed_date FROM streaks WHERE user_id = ?");
$stmt->execute([$userId]);
$streak = $stmt->fetch();

if (!$streak) {
    // Should exist if registered, but just in case
    $streak = ['current_streak' => 0, 'last_completed_date' => null];
}

jsonResponse(200, 'Streak info retrieved.', $streak);
?>
