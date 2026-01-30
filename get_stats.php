<?php
// profile/get_stats.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Method not allowed');
}

$stmt = $conn->prepare("
    SELECT u.points, 
           COALESCE(s.current_streak, 0) as current_streak, 
           COALESCE(s.total_completed, 0) as total_completed
    FROM users u
    LEFT JOIN streaks s ON u.id = s.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

if (!$stats) {
    jsonResponse(404, 'Stats not found');
}

// Simple Badge Logic (Frontend can also calculate, but sending badge count helps)
// E.g. 1 Badge for First Mission, 1 for 10 miissions, 1 for 7 day streak
$badgeCount = 0;
if ($stats['total_completed'] >= 1) $badgeCount++;
if ($stats['total_completed'] >= 10) $badgeCount++;
if ($stats['total_completed'] >= 50) $badgeCount++;
if ($stats['current_streak'] >= 3) $badgeCount++;
if ($stats['current_streak'] >= 7) $badgeCount++;

$stats['badges_count'] = $badgeCount;

jsonResponse(200, 'Stats retrieved.', $stats);
?>
