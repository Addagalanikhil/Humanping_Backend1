<?php
// missions/get_current_mission.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Method not allowed');
}

// 1. Check if user has an active mission
$stmt = $conn->prepare("
    SELECT um.id as mission_instance_id, mt.*, um.assigned_at
    FROM user_missions um
    JOIN mission_templates mt ON um.mission_id = mt.id
    WHERE um.user_id = ? AND um.status = 'assigned'
    LIMIT 1
");
$stmt->execute([$userId]);
$activeMission = $stmt->fetch();

function processMissionData($mission) {
    if (!$mission) return null;
    // Decode instructions if JSON string
    if (!empty($mission['instructions'])) {
        $decoded = json_decode($mission['instructions'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $mission['instructions'] = $decoded;
        } else {
            // Fallback if not valid JSON
            $mission['instructions'] = [$mission['instructions']]; 
        }
    } else {
         $mission['instructions'] = [];
    }
    return $mission;
}

if ($activeMission) {
    jsonResponse(200, 'Current active mission retrieved.', processMissionData($activeMission));
}

// 2. Assign a new mission
$stmt = $conn->prepare("
    SELECT * FROM mission_templates 
    WHERE id NOT IN (
        SELECT mission_id FROM user_missions WHERE user_id = ?
    ) AND is_active = 1
    ORDER BY RAND()
    LIMIT 1
");
$stmt->execute([$userId]);
$newMission = $stmt->fetch();

if (!$newMission) {
    // If ran out of unique missions, maybe allow reusing old ones (completed long ago)?
    // For now, strict unique logic.
    jsonResponse(200, 'No more new missions available! You are a social master.', null);
}

// 3. Assign the mission
try {
    $stmt = $conn->prepare("INSERT INTO user_missions (user_id, mission_id, status) VALUES (?, ?, 'assigned')");
    $stmt->execute([$userId, $newMission['id']]);
    $missionInstanceId = $conn->lastInsertId();

    $newMission['mission_instance_id'] = $missionInstanceId;
    $newMission['assigned_at'] = date('Y-m-d H:i:s');
    
    jsonResponse(200, 'New mission assigned.', processMissionData($newMission));

} catch (Exception $e) {
    jsonResponse(500, 'Failed to assign mission: ' . $e->getMessage());
}
?>
