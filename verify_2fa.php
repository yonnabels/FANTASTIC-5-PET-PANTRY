<?php
session_start();
require_once 'database.php';

// ---- Helper: Flash messaging ----
function flash($type, $msg) {
    $_SESSION['flash'][$type] = $msg;
}

function getFlash($type) {
    if (!empty($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

// ---- Handle OTP verification ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $code    = trim($_POST['code']);
    $userId  = $_SESSION['pending_user_id'] ?? null;

    if (!$userId) {
        flash('error', "Session expired. Please log in again.");
        header("Location: Login_and_creating_account_fixed.php#login");
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, account_type, twofa_code, twofa_expires 
                           FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        flash('error', "User not found.");
    } else {
        $validCode  = $user['twofa_code'];
        $expiresAt  = strtotime($user['twofa_expires']);

        if ($validCode && hash_equals($validCode, $code) && $expiresAt > time()) {
            // ✅ OTP success
            unset($_SESSION['pending_user_id']);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['name']          = $user['name'];
            $_SESSION['account_type']  = $user['account_type'];

            // Clear OTP after use
            $pdo->prepare("UPDATE users 
                           SET twofa_code = NULL, twofa_expires = NULL 
                           WHERE id = ?")->execute([$user['id']]);

            $redirect = $user['account_type'] === 'admin' 
                        ? "adminpanel.php" 
                        : "index.php";
            header("Location: $redirect");
            exit;
        } else {
            flash('error', "Invalid or expired code.");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PetPantry+ | Verify Code</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    :root {
      --primary: #FF7F00;
      --secondary: #000;
      --light: #FFF;
      --accent: #FFD700;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light);
      background-image:
        radial-gradient(var(--accent) 1px, transparent 1px),
        radial-gradient(var(--accent) 1px, transparent 1px);
      background-size: 30px 30px;
      background-position: 0 0, 15px 15px;
    }
    .pet-icon { filter: drop-shadow(0 0 2px rgba(0,0,0,0.2)); }
    .paw-print {
      position: absolute;
      width: 20px; height: 20px;
      background-color: var(--primary);
      border-radius: 50%;
      opacity: 0.2;
    }
  </style>
</head>
<body class="flex flex-col min-h-screen">
  <div id="pawPrints"></div>

  <!-- Main Content -->
  <main class="flex-grow flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-xl overflow-hidden">

      <!-- Header -->
      <div class="bg-black py-6 px-8 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <svg class="pet-icon w-10 h-10 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                  d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 
                  2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 
                  0 011.414 0zm8.586 0a1 1 0 011.414 0l3 
                  3a1 1 0 010 1.414l-3 3a1 1 
                  0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 
                  0 010-1.414z"
                  clip-rule="evenodd"></path>
          </svg>
          <h1 class="text-2xl font-bold text-white">PetPantry<span class="text-yellow-400">+</span></h1>
        </div>
      </div>

      <!-- 2FA Form -->
      <div class="p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Two-Factor Authentication</h2>
        <p class="text-gray-600 mb-6">Enter the 6-digit code sent to your email</p>

        <?php if ($err = getFlash('error')): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($err) ?>
          </div>
        <?php endif; ?>

        <form class="space-y-4" method="POST" novalidate>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
            <input type="text" name="code" maxlength="6" required placeholder="123456"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg text-center text-lg tracking-widest 
                     focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
          </div>
          <button type="submit" name="verify"
            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg transition">
            Verify
          </button>
        </form>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-100 border-t border-gray-300 py-6 text-center text-sm text-gray-600">
    <div class="max-w-4xl mx-auto px-4">
      <p>&copy; <?= date("Y"); ?> <span class="font-semibold">PetPantry+</span>. All rights reserved.</p>
    </div>
  </footer>

  <!-- Paw prints -->
  <script>
    (function generatePawPrints() {
      const container = document.getElementById('pawPrints');
      const w = window.innerWidth, h = window.innerHeight;
      for (let i = 0; i < 20; i++) {
        const paw = document.createElement('div');
        paw.className = 'paw-print';
        paw.style.left = `${Math.random() * w}px`;
        paw.style.top  = `${Math.random() * h}px`;
        paw.style.transform = `rotate(${Math.random()*360}deg) scale(${0.5+Math.random()})`;
        paw.style.opacity = 0.1 + Math.random() * 0.2;
        container.appendChild(paw);
      }
    })();
  </script>
</body>
</html>
