<?php
// missions/complete_mission.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['feeling'])) {
    jsonResponse(400, 'Feeling is required.');
}

$feeling = $data['feeling'];
$note = $data['note'] ?? null;
$locationContext = $data['location'] ?? 'Unknown'; // "At home", "Work", etc.

try {
    $conn->beginTransaction();

    // 1. Get active mission with its point value
    $stmt = $conn->prepare("
        SELECT um.id, mt.points_reward 
        FROM user_missions um
        JOIN mission_templates mt ON um.mission_id = mt.id
        WHERE um.user_id = ? AND um.status = 'assigned' 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $activeMission = $stmt->fetch();

    if (!$activeMission) {
        $conn->rollBack();
        jsonResponse(400, 'No active mission to complete.');
    }
    
    $userMissionId = $activeMission['id'];
    $pointsEarned = $activeMission['points_reward'];

    // 2. Mark as completed
    $stmt = $conn->prepare("UPDATE user_missions SET status = 'completed', completed_at = NOW(), location_context = ? WHERE id = ?");
    $stmt->execute([$locationContext, $userMissionId]);

    // 3. Save reflection
    $stmt = $conn->prepare("INSERT INTO mission_reflections (user_mission_id, feeling, note) VALUES (?, ?, ?)");
    $stmt->execute([$userMissionId, $feeling, $note]);

    // 4. Update Streak & Total Completed
    $stmt = $conn->prepare("SELECT current_streak, last_completed_date, total_completed FROM streaks WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $streakData = $stmt->fetch();

    $currentStreak = $streakData['current_streak'] ?? 0;
    $totalCompleted = ($streakData['total_completed'] ?? 0) + 1;
    $lastDate = $streakData['last_completed_date'];
    $today = date('Y-m-d');
    
    $streakUpdated = false;

    if ($lastDate === $today) {
        $newStreak = $currentStreak;
    } elseif ($lastDate === date('Y-m-d', strtotime('-1 day'))) {
        $newStreak = $currentStreak + 1;
        $streakUpdated = true;
    } else {
        $newStreak = 1; // Reset or first time
        $streakUpdated = true;
    }

    $stmt = $conn->prepare("UPDATE streaks SET current_streak = ?, last_completed_date = ?, total_completed = ? WHERE user_id = ?");
    $stmt->execute([$newStreak, $today, $totalCompleted, $userId]);

    // 5. Update User Points
    $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->execute([$pointsEarned, $userId]);

    $conn->commit();

    jsonResponse(200, 'Mission completed successfully.', [
        'streak' => $newStreak,
        'streak_updated' => $streakUpdated,
        'points_earned' => $pointsEarned,
        'total_completed' => $totalCompleted
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    jsonResponse(500, 'Failed to complete mission: ' . $e->getMessage());
}
?>
