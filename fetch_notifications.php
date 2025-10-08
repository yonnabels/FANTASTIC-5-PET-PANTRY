<?php
session_start();
header('Content-Type: application/json');
include('database.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$account_type = $_SESSION['account_type'] ?? 'customer';

try {
    if ($account_type === 'admin') {
        // 🔹 Admin: fetch latest 50 admin notifications
        $stmt = $pdo->prepare("
            SELECT id, message, is_read, created_at
            FROM notifications
            WHERE account_type = 'admin'
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute();
    } else {
        // 🔹 Customer: fetch latest 50 notifications for the user
        $stmt = $pdo->prepare("
            SELECT id, message, is_read, created_at
            FROM notifications
           WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute(['user_id' => $user_id]);
    }

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert datetime to ISO 8601 for JS
    foreach ($notifications as &$notif) {
        $notif['created_at'] = date('c', strtotime($notif['created_at']));
        // Ensure is_read is integer for JS
        $notif['is_read'] = (int)$notif['is_read'];
    }

    echo json_encode($notifications);

} catch (PDOException $e) {
    // Log error securely; return empty array
    error_log("Fetch notifications failed (user_id={$user_id}): " . $e->getMessage());
    echo json_encode([]);
}
