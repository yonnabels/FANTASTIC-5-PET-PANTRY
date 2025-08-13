<?php
// Start session
session_start();

// Include database connection
include('database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ===================== REGISTRATION =====================
    if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirmPassword']);
        $accountType = $_POST['accountType']; // 'customer' or 'admin'

        if (empty($name) || empty($email) || empty($password) || empty($confirmPassword) || empty($accountType)) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: index.php#register");
            exit;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: index.php#register");
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
            header("Location: index.php#register");
            exit;
        }

        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $_SESSION['error'] = "Email already registered. Please log in.";
            header("Location: index.php#login");
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, account_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $accountType]);

        $_SESSION['success'] = "Registration successful! Please log in.";
        header("Location: index.php#login");
        exit;
    }

    // ===================== LOGIN =====================
    if (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Please fill in both fields.";
            header("Location: index.php#login");
            exit;
        }

        // Check if account exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = "Account does not exist. Please register first.";
            header("Location: index.php#register");
            exit;
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = "Incorrect password.";
            header("Location: index.php#login");
            exit;
        }

        // Save user session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['account_type'] = $user['account_type'];

        // Redirect based on account type
        if ($user['account_type'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: customer_dashboard.php");
        }
        exit;
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #FF7F00; /* orange */
            --secondary: #000000; /* black */
            --light: #FFFFFF; /* white */
            --accent: #FFD700; /* yellow */
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            background-image: radial-gradient(var(--accent) 1px, transparent 1px),
                              radial-gradient(var(--accent) 1px, transparent 1px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
        }
        
        .pet-icon {
            filter: drop-shadow(0 0 2px rgba(0,0,0,0.2));
        }
        
        .account-type {
            transition: all 0.3s ease;
        }
        
        .account-type.active {
            background-color: var(--primary);
            color: var(--light);
        }
        
        .paw-print {
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: var(--primary);
            border-radius: 50%;
            opacity: 0.2;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <!-- Random paw prints decoration -->
    <div id="pawPrints"></div>
    
    <div class="w-full max-w-md bg-white rounded-xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-black py-6 px-8 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <svg class="pet-icon w-10 h-10 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                <h1 class="text-2xl font-bold text-white">PetPantry<span class="text-yellow-400">+</span></h1>
            </div>
            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/f7d39043-0bf2-4be9-947c-debf61962de4.png" alt="Paw print logo in yellow and black colors" class="w-10 h-10 rounded-full border-2 border-yellow-400">
        </div>
        
        <!-- Main Content -->
        <div class="p-8">
            <div id="loginForm">
                <h2 class="text-2xl font-bold text-gray-800 mb-1">Welcome back!</h2>
                <p class="text-gray-600 mb-6">Sign in to your PetPantry+ account</p>
                
                <form id="loginFormElement" class="space-y-4" method="POST" action="">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="login" value="1">
                    <div>
                        <label for="loginEmail" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="loginEmail" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div>
                        <label for="loginPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="loginPassword" name="password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" id="rememberMe"
                                   class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                            <label for="rememberMe" class="ml-2 text-sm text-gray-600">Remember me</label>
                        </div>
                        <a href="#" class="text-sm text-orange-600 hover:underline">Forgot password?</a>
                    </div>
                    
                    <button type="submit"
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-medium py-2 px-4 rounded-lg transition duration-300">
                        Sign In
                    </button>
                    
                    <p class="text-center text-gray-600">
                        Don't have an account? 
                        <a href="#" id="showRegister" class="text-orange-600 hover:underline font-medium">Sign up</a>
                    </p>
                </form>
            </div>
            
            <div id="registerForm" class="hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-1">Join PetPantry<span class="text-orange-500">+</span></h2>
                <p class="text-gray-600 mb-6">Create your free account</p>
                
                <form id="registerFormElement" class="space-y-4" method="POST" action="">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="register" value="1">
                    <div>
                        <label for="registerName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="registerName" name="name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div>
                        <label for="registerEmail" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="registerEmail" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div>
                        <label for="registerPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="registerPassword" name="password" minlength="8" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
                    </div>
                    
                    <div>
                        <label for="registerConfirmPassword" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" id="registerConfirmPassword" name="confirmPassword" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div id="userType" 
                                 class="account-type p-4 border border-gray-300 rounded-lg cursor-pointer text-center active">
                                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ef745749-37f6-4d2e-a2a6-0a62895718e7.png" alt="Happy dog with a shopping basket" class="mx-auto h-16 w-16 object-cover rounded-full mb-2">
                                <p class="font-medium">Pet Parent</p>
                                <p class="text-xs text-gray-500">Shop for your pets</p>
                            </div>
                            <div id="adminType" 
                                 class="account-type p-4 border border-gray-300 rounded-lg cursor-pointer text-center">
                                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/43956a76-a755-4051-b8d5-ccb98625430b.png" alt="Professional veterinarian with a clipboard" class="mx-auto h-16 w-16 object-cover rounded-full mb-2">
                                <p class="font-medium">Pet Pro</p>
                                <p class="text-xs text-gray-500">Manage products & orders</p>
                            </div>
                        </div>
                        <input type="hidden" id="accountType" name="accountType" value="customer">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="terms" required
                               class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                        <label for="terms" class="ml-2 text-sm text-gray-600">
                            I agree to the <a href="#" class="text-orange-600 hover:underline">Terms of Service</a> and 
                            <a href="#" class="text-orange-600 hover:underline">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit"
                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300">
                        Create Account
                    </button>
                    
                    <p class="text-center text-gray-600">
                        Already have an account? 
                        <a href="#" id="showLogin" class="text-orange-600 hover:underline font-medium">Sign in</a>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-100 px-8 py-4 text-center text-sm text-gray-600">
            <p>© 2023 PetPantry+. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Generate random paw prints decoration
        function generatePawPrints() {
            const container = document.getElementById('pawPrints');
            const screenWidth = window.innerWidth;
            const screenHeight = window.innerHeight;
            
            for (let i = 0; i < 20; i++) {
                const paw = document.createElement('div');
                paw.className = 'paw-print';
                paw.style.left = `${Math.random() * screenWidth}px`;
                paw.style.top = `${Math.random() * screenHeight}px`;
                paw.style.transform = `rotate(${Math.random() * 360}deg) scale(${0.5 + Math.random()})`;
                paw.style.opacity = 0.1 + Math.random() * 0.2;
                container.appendChild(paw);
            }
        }
        
        // Toggle between login and register forms
        document.getElementById('showRegister').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
        });
        
        document.getElementById('showLogin').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('loginForm').classList.remove('hidden');
        });
        
        // Handle account type selection
        document.getElementById('userType').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('adminType').classList.remove('active');
            document.getElementById('accountType').value = 'customer';
        });
        
        document.getElementById('adminType').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('userType').classList.remove('active');
            document.getElementById('accountType').value = 'admin';
        });
        
        // Initialize
        generatePawPrints();
    </script>
</body>
</html>