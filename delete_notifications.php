<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
include('database.php'); // Make sure this path is correct

// Log every request for debugging
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - delete_notification.php called\n", FILE_APPEND);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    file_put_contents('debug.log', "Not logged in\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notif_id = $_POST['id'] ?? null;

file_put_contents('debug.log', "User ID: $user_id, Notification ID: $notif_id\n", FILE_APPEND);

if (!$notif_id) {
    echo json_encode(['status' => 'error', 'message' => 'Notification ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => intval($notif_id), 'user_id' => $user_id]);
    
    file_put_contents('debug.log', "Rows affected: " . $stmt->rowCount() . "\n", FILE_APPEND);

    echo json_encode(['status' => 'success', 'rows_deleted' => $stmt->rowCount()]);
} catch (PDOException $e) {
    file_put_contents('debug.log', "PDOException: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete notification']);
}
