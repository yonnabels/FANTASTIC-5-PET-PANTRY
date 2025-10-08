<?php
session_start();
require 'database.php'; // your PDO connection

if (!isset($_GET['email'], $_GET['token'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: Login_and_creating_account_fixed.php#register");
    exit;
}

$email = trim($_GET['email']);
$token = trim($_GET['token']);

// Find user by email + token
$stmt = $pdo->prepare("SELECT id, status FROM users WHERE email = ? AND verification_token = ?");
$stmt->execute([$email, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if ($user['status'] === 'active') {
        // Already verified
        $_SESSION['success'] = "Your email is already verified. Please log in.";
        header("Location: Login_and_creating_account_fixed.php#login");
        exit;
    }

    // Update status and clear token
    $update = $pdo->prepare("UPDATE users SET status = 'active', verification_token = NULL WHERE id = ?");
    $update->execute([$user['id']]);

    $_SESSION['success'] = "✅ Your account has been verified! You can now log in.";
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
} else {
    $_SESSION['error'] = "Invalid or expired verification link.";
    header("Location: Login_and_creating_account_fixed.php#register");
    exit;
}
