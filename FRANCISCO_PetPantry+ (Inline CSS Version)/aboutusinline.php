<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - PetPantry+</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Inline CSS -->
  <style>
    /* Google Font */
    @import url('https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap');

    /* Global Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Arial', sans-serif;
    }

    body {
        line-height: 1.6;
        color: #333;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    hr {
        border: 0;
        height: 1px;
        background-color: #ddd;
        margin: 20px 0;
    }

    h1, h2, h3 {
        margin-bottom: 15px;
    }

    p {
        margin-bottom: 15px;
    }

    a {
        text-decoration: none;
        color: #333;
    }

    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #000000;
        color: white;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: bold;
        font-size: 14px;
        margin-top: 15px;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid #000000;
    }

    .btn:hover {
        background-color: #333333;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .btn:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.2);
    }

    /* Header Styles */
    header {
        padding: 20px 0;
        background: transparent;
        position: absolute;
        width: 100%;
        z-index: 10;
    }

    nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-size: 24px;
        font-weight: bold;
        color: white;
    }

    .nav-links {
        display: flex;
        list-style: none;
        margin-left: 10px;
    }

    .nav-links li {
        margin-left: 30px;
    }

    .nav-links a {
        font-weight: bold;
        color: white;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .nav-links a:hover {
        color: #ffcc00;
    }

    nav i, nav svg {
        color: white;
        transition: transform 0.3s ease, color 0.3s ease;
        cursor: pointer;
    }

    nav i:hover, nav svg:hover {
        color: #ffcc00;
        transform: scale(1.2) rotate(5deg);
    }

    /* Hero Section */
    .hero {
        text-align: center;
        height: 100vh;
        padding: 140px 0;
        background-image: url(dog2.jpeg);
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center top;
        background-attachment: fixed;
        color: rgb(176, 160, 160);
        position: relative;
        font-family: 'Fredoka One', cursive;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(0, 0, 0, 0.4);
        z-index: 0;
    }

    .hero .container {
        position: relative;
        z-index: 1;
        top: 50%;
        transform: translateY(-50%);
    }

    .hero h1 {
        font-size: 100px;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 30px;
        line-height: 1.1;
        font-weight: 800;
        text-transform: uppercase;
    }

    .hero h1 span.outline {
        color: transparent;
        -webkit-text-stroke: 2px white;
    }

    .hero h1 span.solid {
        color: white;
    }

    @media (min-width: 1024px) {
        .hero {
            background-position: center -180px;
        }
    }

    /* Features Section */
    .features {
        display: block;
        padding: 0;
        margin: 0;
        width: 100%;
        background-image: url(girlwithdog2.jpg);
        background-repeat: no-repeat;
        background-position: center center;
        background-size: cover;
    }

    .feature {
        display: flex;
        justify-content: flex-start;
        align-items: flex-start;
        flex-direction: column;
        position: relative;
        min-height: 100vh;
        width: 100vw;
        color: white;
        padding: 60px;
        margin: 0;
        box-sizing: border-box;
        text-align: left;
    }

    .feature h2,
    .feature p,
    .feature .btn {
        max-width: 600px;
        margin: 0 0 20px 0;
        text-align: left;
        align-self: flex-start;
    }

    .feature h2 {
        font-size: 40px;
        margin-bottom: 30px;
        font-weight: 700;
    }

    .feature p {
        font-size: 29px;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .feature .btn {
        display: inline-block;
        background-color: black;
        color: white;
        padding: 12px 4px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .feature .btn:hover {
        background-color: white;
        color: black;
        border: 2px solid black;
    }

    /* Featuring 2 Section */
    .featuring-section {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        background-image: url(meeting3.jpg); 
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
        color: white;
        height: 100vh;
        position: relative;
        padding: 0;
        width: 100%;
    }

    .featuring {
        display: flex;
        flex-direction: column;
        align-items: flex-end;      /* align text to the right */
        max-width: 600px;
        text-align: right;
    }

    .featuring h2,
    .featuring p,
    .featuring .btn {
        margin-left: auto;
        margin-right: 0;
        text-align: right;
    }

    .featuring h2 {
        font-size: 60px;
        font-weight: bold;
        margin-bottom: 20px;
        color: white;
    }

    .featuring p {
        font-size: 20px;
        line-height: 1.6;
        margin-bottom: 30px;
        color: #e0e0e0;
    }

    .featuring .btn {
        background-color: black;
        color: white;
        padding: 12px 24px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .featuring .btn:hover {
        background-color: white;
        color: black;
        border: 2px solid black;
    }

    /* Testimonial Section */
    .testimonial {
        background-color: #f9f9f9;
        padding: 60px 0;
        text-align: center;
    }

    .testimonial p {
        font-style: italic;
        margin-bottom: 30px;
    }

    .testimonial .author {
        font-weight: bold;
    }

    .social-callout {
        margin-top: 30px;
        font-size: 24px;
        font-weight: bold;
    }

    /* Footer */
    footer {
        background-color: #1d2836;
        color: #d97d1c;
        padding: 40px 0;
    }

    .footer-content {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 30px;
    }

    .footer-column {
        flex: 1;
        min-width: 200px;
        color: #fff;
    }

    .footer-column ul li {
        color: #fff;
        margin-bottom: 8px;
    }

    .footer-column ul li a {
        color: #fff;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-column ul li a:hover {
        color: #ff9800;
    }

    .footer-brand-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .footer-brand-header img {
        height: 40px;
        width: auto;
    }

    .footer-brand-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: bold;
    }

    .contact-info p {
        margin: 5px 0;
    }

    .social-media a {
        margin-right: 10px;
        color: #fff;
        font-size: 18px;
        transition: color 0.3s;
    }

    .social-media a:hover {
        color: #f5f4f3;
    }

    .footer-bottom {
        margin-top: 20px;
        text-align: center;
        font-size: 14px;
        color: #aaa;
        border-top: 1px solid #656464;
        padding-top: 10px;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .features {
            flex-direction: column;
        }

        .feature {
            flex: 0 0 100%;
            margin-bottom: 40px;
        }

        .footer-column {
            flex: 0 0 100%;
        }

        .nav-links {
            display: none;
        }
    }

    /* Navigation Icons */
    .nav-icons {
        display: flex;
        gap: 20px;
    }

    .nav-icons a {
        color: #333;
        font-size: 18px;
        transition: color 0.3s;
    }

    .nav-icons a:hover {
        color: #edbe12;
    }

    /* Shopping Bag with Counter */
    .shopping-bag-container {
        position: relative;
    }

    .badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #010101;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Social Media Styles */
    .social-media {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-media a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #000000;
        color: white;
        transition: all 0.3s ease;
    }

    .social-media a:hover {
        background-color: #e9bb14;
        transform: translateY(-3px);
    }

    @media (max-width: 768px) {
        .social-media {
            justify-content: center;
        }
    }
  </style>
</head>
<body>
        
    <!--Header/Navigation-->
    <header>
        <div class="container">
            <nav>
                <div class="logo">PetPantry+</div>
                <ul class="nav-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Shop</a></li>
                    <li><a href="#">Product</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Page</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="#"><i class="fas fa-search"></i></a>
                    <a href="#"><i class="fas fa-user"></i></a>
                    <div class="shopping-bag-container">
                    <a href="#"><i class="fas fa-shopping-bag"></i></a>
                    <span class="badge">0</span>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
     <section class="hero">
        <div class="container">
            <h1>
                <span class="outline">OWN</span><br>
                <span class="solid">YOUR PETS</span>
            </h1>
        </div>
     </section>

     <!-- Features Section -->
      <section class="features">
        <div class="container">
            <div class="feature">
                <h2>We're always here for our customers.</h2>
                <p>Nullam quis ante. Pellentesque libero tortor, 
                    tincidunt et, tinciduntarnet est.In hac habitasse platea dictumst. 
                    Praesent nec nisi a purus blandit viverra </p>
                    
                    <a href="#" class="btn">Learn More</a>
            </div>
        </div>
      </section>     

        <!-- Featuring-Section-->
         <section class="featuring-section">
    <div class="container">
        <div class="featuring">
            <h2>We work hard, and we win</h2>
            <p>Nullam quis ante. Pellentesque libero tortor, tincidunt 
                et, tinciduntarnet est. In hac habitasse platea dictumst. Praesent nec nisi 
                a purus blandit viverra</p>
            <a href="#" class="btn">Learn More</a>
        </div>
    </div>
</section>
      <!-- Testimonial Section -->
       <section class="testimonial">
        <div class="container">
            <p>Blood bank canine teeth larynx occupational therapist oncologist 
                optician plaque spinal tap stat strep screen violence joints...</p>
                <p class="author">Ann Smith</p>
                <p class="social-callout">#PetPantry+<br>SHARE YOUR LUMINOUS MOMENTS
                WITH PETPANTRY+!</p>
        </div>
       </section>

        <!-- Footer -->
         <footer>
    <div class="container">
        <div class="footer-content">

            <!-- Brand + Contact Column -->
            <div class="footer-column footer-brand">
                <div class="footer-brand-header">
                    <img src="logo.png" alt="PetPantry+ Logo">
                    <h3>PetPantry+</h3>
                </div>
                <div class="contact-info">
                    <p>If you have any question, please contact us at <strong>PetPantry+@gmail.com</strong></p>
                    <p>Quezon City</p>
                    <p>+63 929 683 8372</p>
                    <!-- Social Media Buttons -->
                    <div class="social-media">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>

            <!-- Corporate Column -->
            <div class="footer-column">
                <h3>Corporate</h3>
                <ul>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Code of Ethics</a></li>
                    <li><a href="#">Event Sponsorships</a></li>
                    <li><a href="#">Vendors</a></li>
                    <li><a href="#">Affiliate Program</a></li>
                </ul>
            </div>

            <!-- Customer Service Column -->
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="#">Track Order</a></li>
                    <li><a href="#">Returns</a></li>
                    <li><a href="#">Shipping Info</a></li>
                    <li><a href="#">Recalls & Advisories</a></li>
                    <li><a href="#">Pet Store Locator</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>

            <!-- Services Column -->
            <div class="footer-column">
                <h3>Services</h3>
                <ul>
                    <li><a href="#">Grooming</a></li>
                    <li><a href="#">Positive Dog Training</a></li>
                    <li><a href="#">Veterinary Services</a></li>
                    <li><a href="#">Petco Insurance</a></li>
                    <li><a href="#">Pet Adoption</a></li>
                    <li><a href="#">Resource Center</a></li>
                </ul>
            </div>

        </div>

        <!-- Bottom Copyright -->
        <div class="footer-bottom">
            <p>&copy; 2025 PetPantry+. All rights reserved.</p>
        </div>
    </div>
</footer>
</body>
</html>
