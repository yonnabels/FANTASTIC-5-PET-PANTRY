<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'database.php'; // PDO connection
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json; charset=utf-8");

// =======================================================
// Helpers
// =======================================================
function sendEmail(string $to, string $name, string $subject, string $body): void {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@petpantry.space';
        $mail->Password   = 'PetP@ntry123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('no-reply@petpantry.space', 'PetPantry+');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("Email send failed ({$subject}): " . $mail->ErrorInfo);
    }
}

function triggerTwoFactorAuth(PDO $pdo, array $user, array $roles): void {
    $otp     = random_int(100000, 999999);
    $expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $pdo->prepare("UPDATE users SET twofa_code=?, twofa_expires=? WHERE id=?")
        ->execute([$otp, $expires, $user['id']]);

    $subject = !empty($roles)
        ? 'Your PetPantry+ Admin Login Code'
        : 'Your PetPantry+ Login Code';

    $body = "<p>Hi {$user['name']}, your " 
          . (!empty($roles) ? "admin " : "")
          . "login code is: <b>{$otp}</b> (Expires in 5 mins)</p>";

    sendEmail($user['email'], $user['name'], $subject, $body);

    $_SESSION['pending_user_id'] = $user['id'];
    if (!empty($roles)) {
        $_SESSION['pending_roles'] = $roles;
    }

    // 2FA page redirect
    echo json_encode(["status" => "success", "redirect" => "verify_2fa.php"]);
    exit;
}

// =======================================================
// Only allow POST requests
// =======================================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// =======================================================
// Get Google ID token from client
// =======================================================
$id_token = $_POST['credential'] ?? null;
if (!$id_token) {
    echo json_encode(["status" => "error", "message" => "No Google token received."]);
    exit;
}

// =======================================================
// Verify Google token
// =======================================================
$ch = curl_init("https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$payload = json_decode($response, true);
$client_id = "573165116685-u93p40s1qtr97016nrql2n7ht4megmlc.apps.googleusercontent.com";

if (!$payload || $payload['aud'] !== $client_id) {
    echo json_encode(["status" => "error", "message" => "Invalid Google token."]);
    exit;
}

$email = $payload['email'];
$name = $payload['name'] ?? '';
$email_verified = $payload['email_verified'] ?? false;

if (!$email_verified) {
    echo json_encode(["status" => "error", "message" => "Google email not verified."]);
    exit;
}

// =======================================================
// Check if user exists
// =======================================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if ($user['provider'] === 'local') {
        $update = $pdo->prepare("UPDATE users SET google_id = ?, provider = 'google', status = 'active' WHERE id = ?");
        $update->execute([$payload['sub'], $user['id']]);
    }
    $user_id   = $user['id'];
    $user_name = $user['name'];
} else {
    $insert = $pdo->prepare("
        INSERT INTO users (name, email, password, account_type, created_at, status, provider, google_id)
        VALUES (?, ?, NULL, 'customer', NOW(), 'active', 'google', ?)
    ");
    $insert->execute([$name, $email, $payload['sub']]);
    $user_id   = $pdo->lastInsertId();
    $user_name = $name;
}

// =======================================================
// Fetch admin roles if any
// =======================================================
$stmtRoles = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id = ?");
$stmtRoles->execute([$user_id]);
$roles = array_map('trim', $stmtRoles->fetchAll(PDO::FETCH_COLUMN));

// =======================================================
// Start session
// =======================================================
$_SESSION['user_id'] = $user_id;
$_SESSION['name']    = $user_name;
$_SESSION['roles']   = $roles;

// =======================================================
// Trigger 2FA (send email + redirect to verify_2fa.php)
// =======================================================
triggerTwoFactorAuth($pdo, ['id' => $user_id, 'name' => $user_name, 'email' => $email], $roles);
