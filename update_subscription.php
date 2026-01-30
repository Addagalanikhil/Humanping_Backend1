<?php
// subscriptions/update_subscription.php

require_once __DIR__ . '/../utils/auth_middleware.php';

$userId = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['plan_id'])) {
    jsonResponse(400, 'Plan ID is required.');
}

$planId = $data['plan_id'];
$status = $data['status'] ?? 'active';
$startDate = $data['start_date'] ?? date('Y-m-d H:i:s');
$endDate = $data['end_date'] ?? null;
$paymentMethod = $data['payment_method'] ?? 'google_play';
$autoRenew = isset($data['auto_renew']) ? (int)$data['auto_renew'] : 1;

try {
    // Check if subscription exists
    $stmt = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existingSub = $stmt->fetch();

    if ($existingSub) {
        // Update
        $updateStmt = $conn->prepare("
            UPDATE subscriptions 
            SET plan_id = ?, status = ?, start_date = ?, end_date = ?, payment_method = ?, auto_renew = ?
            WHERE user_id = ?
        ");
        $updateStmt->execute([$planId, $status, $startDate, $endDate, $paymentMethod, $autoRenew, $userId]);
        jsonResponse(200, 'Subscription updated successfully.');
    } else {
        // Insert
        $insertStmt = $conn->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, start_date, end_date, payment_method, auto_renew)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$userId, $planId, $status, $startDate, $endDate, $paymentMethod, $autoRenew]);
        jsonResponse(201, 'Subscription created successfully.');
    }
} catch (PDOException $e) {
    jsonResponse(500, 'Database error: ' . $e->getMessage());
}
?>
