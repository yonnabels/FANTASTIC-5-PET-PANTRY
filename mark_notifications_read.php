<?php
session_start();
header('Content-Type: application/json');
include('database.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if (isset($_POST['id'])) {
        // Mark a single notification as read
        $notif_id = intval($_POST['id']);
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute(['id' => $notif_id, 'user_id' => $user_id]);
    } else {
        // Mark all unread notifications as read
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = :user_id AND is_read = 0
        ");
        $stmt->execute(['user_id' => $user_id]);
    }

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    error_log("Mark notifications read failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to mark notifications as read']);
}
