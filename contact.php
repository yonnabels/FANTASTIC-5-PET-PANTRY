<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
// require 'database.php'; // only if you need DB here

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@petpantry.space'; // your domain email
        $mail->Password   = 'PetP@ntry123';       // replace with actual password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender & Recipient
        $mail->setFrom('no-reply@petpantry.space', 'PetPantry Contact Form');
        $mail->addAddress('no-reply@petpantry.space'); // Receiver (your inbox)
        $mail->addReplyTo($email, $name); // So you can reply to the visitor directly

        // Content
        $mail->isHTML(false);
        $mail->Subject = "New Contact Form Submission from $name";
        $mail->Body    = "Name: $name\nEmail: $email\n\nMessage:\n$message";

        $mail->send();
        $success = "✅ Message sent successfully!";
    } catch (Exception $e) {
        $error = "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
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
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body style="font-family:'Inter',sans-serif; margin:0; background:white;">
  <?php include 'header.php'; ?>
  <main style="padding-top:4rem; min-height:100vh; display:flex; flex-direction:column;">
    
    <!-- Banner -->
    <div style="position:relative; width:100%; height:325px; background:url('images/Dog3.png') center/cover;">
      <div style="position:relative; z-index:10; max-width:1120px; margin:0 auto; padding:5rem 1.5rem 0; text-align:center;">
        <h1 style="font-size:2.25rem; font-weight:bold; color:white; margin-bottom:1.5rem; font-family:'Anton',sans-serif;">Contact</h1>
        <nav style="margin-bottom:2.5rem;">
          <a href="index.php" style="color:white; margin-right:1rem; text-decoration:none;">Home</a>
          <span style="color:#6b7280;">/</span>
          <span style="color:black;">Contact</span>
        </nav>
      </div>
    </div>

    <!-- Contact + Map Section -->
    <section style="flex-grow:1; background:url('images/Cat.png') center/cover; padding:3rem 0;">
      <div style="max-width:1120px; margin:0 auto; padding:0 1.5rem;">
        <div style="display:grid; grid-template-columns:1fr; gap:2.5rem; align-items:stretch;">
          
          <!-- Contact Form -->
          <div>
            <?php if(!empty($success)): ?>
              <p style="color:green; font-weight:600; margin-bottom:1rem;"><?= $success ?></p>
            <?php elseif(!empty($error)): ?>
              <p style="color:red; font-weight:600; margin-bottom:1rem;"><?= $error ?></p>
            <?php endif; ?>

            <form method="POST" action=""
              style="background:rgba(255,255,255,0.95); padding:30px; border-radius:10px; width:100%; min-height:400px; box-shadow:0 4px 8px rgba(0,0,0,0.2); display:flex; flex-direction:column;">
              
              <h2 style="margin-bottom:20px; font-size:22px; font-weight:bold; text-align:center; font-family:'Anton',sans-serif;">SEND US A MESSAGE</h2>
              
              <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:12px;">
                <input type="text" name="name" placeholder="Your name" required
                  style="width:100%; padding:12px; border:1px solid #ccc; border-radius:6px; font-size:14px; outline:none; margin-bottom:10px;">
                <input type="email" name="email" placeholder="Your email" required
                  style="width:100%; padding:12px; border:1px solid #ccc; border-radius:6px; font-size:14px; outline:none;">
              </div>

              <textarea name="message" placeholder="Your message..." required
                style="width:100%; padding:12px; border:1px solid #ccc; border-radius:6px; font-size:14px; outline:none; height:150px; resize:none; margin-bottom:12px;"></textarea>
              
              <button type="submit"
                style="width:100%; padding:14px; margin-top:auto; background:linear-gradient(90deg,#ff7a18,#ffb347); color:white; font-weight:bold; border:none; border-radius:30px; font-size:16px; cursor:pointer; transition:all 0.3s ease;">
                SEND
              </button>
            </form>
          </div>

          <!-- Map -->
          <div style="display:flex; flex-direction:column;">
            <h2 style="font-size:1.5rem; font-weight:bold; color:#1f2937; margin-bottom:1rem; text-align:center; font-family:'Anton',sans-serif;">Find Us Here</h2>
            <div id="map" style="width:100%; height:100%; min-height:400px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.2);"></div>
          </div>

        </div>
      </div>
    </section>
  </main>
  <?php include 'footer.php'; ?>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    const storeLocation = [14.6208, 121.0527];
    const map = L.map('map').setView(storeLocation, 17);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    L.marker(storeLocation).addTo(map).bindPopup('<b>Pet Pantry+</b><br>Gateway Mall, Cubao, Quezon City').openPopup();
  </script>
</body>
</html>
