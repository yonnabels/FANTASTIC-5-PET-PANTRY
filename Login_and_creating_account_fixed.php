<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require 'database.php'; // Provides $pdo (PDO instance)

// =======================================================
// Configuration
// =======================================================
const MAIL_FROM      = 'no-reply@petpantry.space';
const MAIL_FROM_NAME = 'PetPantry+';
const MAIL_HOST      = 'smtp.hostinger.com';
const MAIL_USER      = 'no-reply@petpantry.space';
const MAIL_PASS      = 'PetP@ntry123';
const MAIL_PORT      = 465;
const MAIL_SECURE    = PHPMailer::ENCRYPTION_SMTPS;
const BASE_URL       = 'https://petpantry.space/';

// =======================================================
// Helpers
// =======================================================
function setMessage(string $type, string $message): void {
    $_SESSION[$type] = $message;
}

function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

function sendEmail(string $to, string $name, string $subject, string $body): void {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
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

    redirect('verify_2fa.php');
}

// =======================================================
// Registration
// =======================================================
if (!empty($_POST['register'])) {
    $name            = trim($_POST['name'] ?? '');
    $email           = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (!$name || !$email || !$password || !$confirmPassword) {
        setMessage('error', 'All fields are required.');
        redirect('Login_and_creating_account_fixed.php#register');
    }

    if ($password !== $confirmPassword) {
        setMessage('error', 'Passwords do not match.');
        redirect('Login_and_creating_account_fixed.php#register');
    }

    if (strlen($password) < 8) {
        setMessage('error', 'Password must be at least 8 characters.');
        redirect('Login_and_creating_account_fixed.php#register');
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            setMessage('error', 'Email already registered.');
            redirect('Login_and_creating_account_fixed.php#login');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token          = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, account_type, created_at, status, provider, verification_token)
            VALUES (?, ?, ?, 'customer', NOW(), 'pending', 'local', ?)
        ");
        $stmt->execute([$name, $email, $hashedPassword, $token]);

        $verificationLink = BASE_URL . "verify_email.php?email=" 
                          . urlencode($email) . "&token=" . urlencode($token);

        sendEmail($email, $name, 'Verify Your PetPantry+ Account',
            "<p>Hi {$name}, please verify your email: <a href='{$verificationLink}'>{$verificationLink}</a></p>"
        );

        setMessage('success', 'Registration successful! Check your email.');
        redirect('Login_and_creating_account_fixed.php#login');
    } catch (PDOException $e) {
        error_log($e->getMessage());
        setMessage('error', 'Server error. Try again.');
        redirect('Login_and_creating_account_fixed.php#register');
    }
}

// =======================================================
// Login
// =======================================================
if (!empty($_POST['login'])) {
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        setMessage('error', 'Fill both fields.');
        redirect('Login_and_creating_account_fixed.php#login');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            setMessage('error', 'Account does not exist.');
            redirect('Login_and_creating_account_fixed.php#register');
        }

        if ($user['status'] !== 'active') {
            setMessage('error', 'Verify your email first.');
            redirect('Login_and_creating_account_fixed.php#login');
        }

        if (!password_verify($password, $user['password'])) {
            setMessage('error', 'Incorrect password.');
            redirect('Login_and_creating_account_fixed.php#login');
        }

        // Fetch admin roles
        $stmtRoles = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
        $stmtRoles->execute([$user['id']]);
        $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN) ?: [];

        // Session bootstrap
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['roles']   = $roles;

        // Always enforce 2FA
        triggerTwoFactorAuth($pdo, $user, $roles);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        setMessage('error', 'Server error during login.');
        redirect('Login_and_creating_account_fixed.php#login');
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PetPantry+ | Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    :root {
      --primary: #FF7F00;
      --secondary: #000000;
      --light: #FFFFFF;
      --accent: #FFD700;
    }

    html, body {
      overflow-x: hidden;
      margin: 0;
      padding: 0;
      height: 100%;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light);
      background-image: radial-gradient(var(--accent) 1px, transparent 1px),
                        radial-gradient(var(--accent) 1px, transparent 1px);
      background-size: 30px 30px;
      background-position: 0 0, 15px 15px;
    }

    .pet-icon { filter: drop-shadow(0 0 2px rgba(0,0,0,0.2)); }
    .account-type { transition: all 0.3s ease; }
    .account-type.active { background-color: var(--primary); color: var(--light); }
    .paw-print { position: absolute; width: 20px; height: 20px; background-color: var(--primary); border-radius: 50%; opacity: 0.2; }

    .g_id_signin iframe {
      width: 100% !important;
      max-width: 100% !important;
      height: 50px !important;
      border-radius: 0.5rem !important;
      display: block !important;
      margin: 0 auto !important;
    }

    main > div { width: 100%; max-width: 400px; box-sizing: border-box; }
  </style>
</head>
<body class="flex flex-col min-h-screen">
  <div id="pawPrints"></div>

  <main class="flex-grow flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl overflow-hidden w-full">

      <!-- Header -->
      <div class="bg-black py-6 px-8 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <svg class="pet-icon w-10 h-10 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
          </svg>
          <h1 class="text-2xl font-bold text-white">PetPantry<span class="text-yellow-400">+</span></h1>
        </div>
        <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/f7d39043-0bf2-4be9-947c-debf61962de4.png" alt="Logo" class="w-10 h-10 rounded-full border-2 border-yellow-400">
      </div>

      <!-- Login Form -->
      <div class="p-8">
        <div id="loginForm">
          <h2 class="text-2xl font-bold text-gray-800 mb-1">Welcome back!</h2>
          <p class="text-gray-600 mb-6">Sign in to your PetPantry+ account</p>

          <form class="space-y-4" method="POST" action="">
            <?php if (isset($_SESSION['error'])): ?>
              <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
              </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
              <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
              </div>
            <?php endif; ?>

            <input type="hidden" name="login" value="1">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>

            <div class="relative flex items-center">
              <input type="password" id="loginPassword" name="password" required class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
              <i class="fa fa-eye toggle-password absolute right-3 cursor-pointer text-gray-500" data-target="loginPassword"></i>
            </div>

            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-medium py-3 px-4 rounded-lg transition duration-300">Sign In</button>
          </form>
        </div>

        <!-- Google Sign-In -->
        <div class="my-4 flex justify-center w-full">
          <div class="w-full max-w-md">
            <div id="g_id_onload"
                 data-client_id="573165116685-u93p40s1qtr97016nrql2n7ht4megmlc.apps.googleusercontent.com"
                 data-callback="handleCredentialResponse">
            </div>
            <div class="g_id_signin"></div>
          </div>
        </div>

        <p class="text-center text-gray-600">
          Don't have an account? <a href="#" id="showRegister" class="text-orange-600 hover:underline font-medium">Sign up</a>
        </p>
      </div>

      <!-- Register Form -->
      <div id="registerForm" class="hidden p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-1">Join PetPantry<span class="text-orange-500">+</span></h2>
        <p class="text-gray-600 mb-6">Create your free account</p>

        <form class="space-y-4" method="POST" action="">
          <input type="hidden" name="register" value="1">

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <div class="relative">
              <input type="password" id="registerPassword" name="password" minlength="8" required class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
              <i class="fa fa-eye toggle-password absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500" data-target="registerPassword"></i>
            </div>
            <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <div class="relative">
              <input type="password" id="registerConfirmPassword" name="confirmPassword" required class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
              <i class="fa fa-eye toggle-password absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500" data-target="registerConfirmPassword"></i>
            </div>
          </div>

          <input type="hidden" id="accountType" name="accountType" value="customer">

          <div class="flex items-center">
            <input type="checkbox" id="terms" required class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
            <label for="terms" class="ml-2 text-sm text-gray-600">
              I agree to the <a href="#" class="text-orange-600 hover:underline">Terms</a> & <a href="#" class="text-orange-600 hover:underline">Privacy Policy</a>
            </label>
          </div>

          <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg transition duration-300">Create Account</button>

          <p class="text-center text-gray-600">
            Already have an account? <a href="#" id="showLogin" class="text-orange-600 hover:underline font-medium">Sign in</a>
          </p>
        </form>
      </div>
    </div>
  </main>

  <footer class="bg-gray-100 border-t border-gray-300 py-6 text-center text-sm text-gray-600">
    <div class="max-w-4xl mx-auto px-4">
      <p>&copy; <?= date("Y"); ?> <span class="font-semibold">PetPantry+</span>. All rights reserved.</p>
      <div class="mt-2 flex justify-center space-x-6">
        <a href="about.php" class="hover:text-orange-600 transition">About Us</a>
        <a href="contact.php" class="hover:text-orange-600 transition">Contact</a>
        <a href="privacy.php" class="hover:text-orange-600 transition">Privacy Policy</a>
      </div>
    </div>
  </footer>

  <script>
    // Eye toggle
    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', function () {
        const target = document.getElementById(this.dataset.target);
        if (target.type === "password") {
          target.type = "text";
          this.classList.replace("fa-eye", "fa-eye-slash");
        } else {
          target.type = "password";
          this.classList.replace("fa-eye-slash", "fa-eye");
        }
      });
    });

    // Toggle login/register forms
    document.getElementById('showRegister').addEventListener('click', e => {
      e.preventDefault();
      document.getElementById('loginForm').classList.add('hidden');
      document.getElementById('registerForm').classList.remove('hidden');
    });
    document.getElementById('showLogin').addEventListener('click', e => {
      e.preventDefault();
      document.getElementById('registerForm').classList.add('hidden');
      document.getElementById('loginForm').classList.remove('hidden');
    });

    // Paw prints
    function generatePawPrints() {
      const container = document.getElementById('pawPrints');
      const screenWidth = window.innerWidth;
      const screenHeight = window.innerHeight;
      for (let i = 0; i < 20; i++) {
        const paw = document.createElement('div');
        paw.className = 'paw-print';
        paw.style.left = Math.random() * (screenWidth - 20) + 'px';
        paw.style.top = Math.random() * (screenHeight - 20) + 'px';
        paw.style.transform = `rotate(${Math.random() * 360}deg) scale(${0.5 + Math.random()})`;
        paw.style.opacity = 0.1 + Math.random() * 0.2;
        container.appendChild(paw);
      }
    }
    generatePawPrints();

    // Google Sign-In callback
    function handleCredentialResponse(response) {
      if (!response.credential) return alert("Google login failed: No credential.");
      fetch("google_login.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `credential=${encodeURIComponent(response.credential)}`
      })
      .then(res => res.json())
      .then(res => {
        if (res.status === "success") window.location.href = res.redirect;
        else alert("Google login failed: " + (res.message || "Unknown error"));
      })
      .catch(err => console.error("Fetch error:", err));
    }
  </script>
</body>
</html>



  
