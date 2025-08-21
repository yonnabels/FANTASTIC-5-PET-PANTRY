<?php
// Contact form handler
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = htmlspecialchars($_POST['name']);
    $email   = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Example: send an email (requires server mail configuration)
    $to = "petpantry@gmail.com";
    $subject = "New Contact Form Submission from $name";
    $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

    if (mail($to, $subject, $body)) {
        $success = "Message sent successfully!";
    } else {
        $error = "Sorry, your message could not be sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact Us - PetPantry+</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Custom fonts & styles */
    @import url('https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;600&display=swap');

    body {
      font-family: 'Inter', sans-serif;
      color: #333333;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Anton', sans-serif;
    }

    h1 {
      font-size: 2.5rem;
      margin-bottom: 2.5rem;
    }

    /* Contact Form Section */
    .contact-section {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
      background: url("Cat.png") no-repeat center center/cover;
    }

    .contact-form {
      background: rgba(255, 255, 255, 0.95);
      padding: 30px;
      border-radius: 10px;
      max-width: 600px;
      width: 100%;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .contact-form h2 {
      margin-bottom: 20px;
      font-size: 22px;
      font-weight: bold;
    }

    .contact-form .form-row {
      display: flex;
      gap: 10px;
    }

    .contact-form .form-row input {
      flex: 1;
    }

    input[type="text"], 
    input[type="email"], 
    textarea,
    .contact-form input,
    .contact-form textarea {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      box-sizing: border-box;
      outline: none;
    }

    input[type="text"]:focus, 
    input[type="email"]:focus, 
    textarea:focus {
      border-color: #f97316;
    }

    .contact-form textarea {
      height: 150px;
      resize: none;
    }

    .contact-form button {
      width: 100%;
      padding: 14px;
      background: linear-gradient(90deg, #ff7a18, #ffb347);
      color: white;
      font-weight: bold;
      border: none;
      border-radius: 30px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      letter-spacing: 1px;
    }

    .contact-form button:hover {
      background: linear-gradient(90deg, #ffb347, #ff7a18);
      transform: translateY(-3px);
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
    }

    .footer-heading {
      color: #e56736;
      font-weight: 700;
      font-size: 1rem;
      margin-bottom: 0.7rem;
    }

    input[type="email"].newsletter {
      width: 100%;
      padding: 0.5rem 1rem;
      border-radius: 9999px 0 0 9999px;
      border: none;
      outline: none;
    }

    button[type="submit"] {
      background-color: #fb7d1b;
      border-radius: 0 9999px 9999px 0;
      border: none;
      padding: 0 1rem;
      color: white;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button[type="submit"]:hover {
      background-color: #de6514;
    }
  </style>
</head>
<body class="relative bg-white">

  <!-- Header -->
  <header class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-sm shadow-sm">
    <nav class="max-w-7xl mx-auto flex items-center justify-between py-4 px-6">
      <div class="flex items-center space-x-3">
        <div class="rounded-full border-2 border-orange-500 w-10 h-10 flex items-center justify-center overflow-hidden">
          <img src="logo.png" alt="PetPantry+ logo" class="w-full h-full object-contain">
        </div>
        <span class="font-semibold text-lg text-orange-500 select-none">PetPantry+</span>
      </div>
      <ul class="hidden md:flex items-center space-x-10 font-semibold text-gray-700 font-inter">
        <li><a href="#" class="hover:text-orange-500 transition">Home</a></li>
        <li><a href="#" class="hover:text-orange-500 transition">Shop</a></li>
        <li><a href="#" class="hover:text-orange-500 transition">About us</a></li>
        <li><a href="#" class="hover:text-orange-500 transition">Contact us</a></li>
      </ul>
    </nav>
  </header>

  <!-- Main Content -->
  <main class="pt-20 pb-16 relative" style="min-height: calc(100vh - 128px);">
    <div class="relative w-full h-[325px] bg-cover bg-center" style="background-image: url('Dog3.png');">
      <div class="relative z-10 max-w-7xl mx-auto px-6 text-center pt-10">
        <h1 class="text-4xl font-bold text-white mt-20 mb-6">Contact</h1>
        <nav class="mb-10">
          <a href="#" class="text-white hover:underline mr-4">Home</a>
          <span class="text-gray-500">/</span>
          <span class="text-black">Contact</span>
        </nav>
      </div>
    </div>

    <!-- Contact Form -->
    <section class="contact-section">
      <?php if(!empty($success)): ?>
        <p class="text-green-600 font-semibold mb-4"><?= $success ?></p>
      <?php elseif(!empty($error)): ?>
        <p class="text-red-600 font-semibold mb-4"><?= $error ?></p>
      <?php endif; ?>

      <form class="contact-form" method="POST" action="">
        <h2>SEND US A MESSAGE</h2>
        <div class="form-row">
          <input type="text" name="name" placeholder="Your name" required>
          <input type="email" name="email" placeholder="Your email" required>
        </div>
        <textarea name="message" placeholder="Your message..." required></textarea>
        <button type="submit">SEND</button>
      </form>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-900 text-gray-300">
    <div class="max-w-7xl mx-auto px-8 py-16 grid grid-cols-1 md:grid-cols-5 gap-12">
      <div>
        <div class="mb-6 flex items-center space-x-3">
          <div class="text-orange-500 font-extrabold text-xl rounded-full border-2 border-orange-500 w-10 h-10 flex items-center justify-center select-none">
            <img src="logo.png" alt="PetPantry+ logo" class="w-full h-full object-contain">
          </div>
          <span class="font-semibold text-lg text-orange-500 select-none">PetPantry+</span>
        </div>
        <p class="text-sm mb-4 max-w-xs">If you have any question, please contact us at 
          <a href="mailto:petpantry@gmail.com" class="text-orange-500 underline">petpantry@gmail.com</a>
        </p>
        <address class="not-italic text-sm space-y-4">
          <p>Quezon City</p>
          <p>+63 929 683 8372</p>
        </address>
      </div>
    </div>
    <p class="text-center text-sm py-4">© 2025 PetPantry+. All rights reserved.</p>
  </footer>

</body>
</html>
