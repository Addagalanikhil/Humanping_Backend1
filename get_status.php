<?php
// subscriptions/get_status.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Method not allowed');
}

try {
    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $sub = $stmt->fetch();

    if (!$sub) {
        jsonResponse(200, 'No active subscription found', [
            'has_subscription' => false,
            'plan_id' => 'free',
            'status' => 'none'
        ]);
    }

    // specific check for expiration
    $isActive = $sub['status'] === 'active';
    if ($sub['end_date'] && new DateTime($sub['end_date']) < new DateTime()) {
        $isActive = false;
        // Ideally we might update the DB status here too, but for now just reporting is fine.
    }

    $sub['has_subscription'] = $isActive;
    // Ensure boolean is true/false in JSON
    $sub['auto_renew'] = (bool)$sub['auto_renew'];

    jsonResponse(200, 'Subscription status retrieved', $sub);

} catch (PDOException $e) {
    jsonResponse(500, 'Database error: ' . $e->getMessage());
}
?>
