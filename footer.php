<style>
/* ✅ Scoped only to footer */
.site-footer .footer-heading {
  color: #e56736;
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 0.7rem;
}

.site-footer input[type="email"] {
  width: 100%;
  padding: 0.5rem 1rem;
  border-radius: 9999px 0 0 9999px;
  border: none;
  outline: none;
}

.site-footer button[type="submit"] {
  background-color: #fb7d1b;
  border-radius: 0 9999px 9999px 0;
  border: none;
  padding: 0 1rem;
  color: white;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
.site-footer button[type="submit"]:hover {
  background-color: #de6514;
}

/* ✅ Responsive Mobile Labels */
@media (max-width: 768px) {
  .order-header { display: none; } /* hide header */
  .order-item { 
    grid-template-columns: 1fr; 
    grid-row-gap: 8px; 
    text-align:left; 
    padding:15px 0;
  }
  .order-item div { 
    position: relative;
    padding-left: 110px; /* space for label */
  }
  .order-item div::before {
    content: attr(data-label);
    position: absolute;
    left: 0;
    font-weight: bold;
    color: #555;
  }
  .order-item img { 
    width:80px; 
    height:80px; 
    padding-left: 0;
  }
  .order-item div:first-child::before {
    content: "Product";
  }
}

</style>

<footer class="site-footer bg-gray-900 text-gray-300">
  <div class="max-w-7xl mx-auto px-8 py-16 grid grid-cols-1 md:grid-cols-5 gap-12">
    <div>
      <div class="mb-6 flex items-center space-x-3">
        <div class="text-orange-500 font-extrabold text-xl rounded-full border-2 border-orange-500 w-10 h-10 flex items-center justify-center select-none overflow-hidden">
          <img src="images/logo.png" alt="PetPantry+ logo" class="w-full h-full object-contain">
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

    <div>
      <h4 class="footer-heading">Corporate</h4>
      <ul class="text-sm space-y-2">
        <li><a href="about.php" class="hover:text-orange-400 transition">About Us</a></li>
      </ul>
    </div>

    <div>
      <h4 class="footer-heading">Customer Service</h4>
      <ul class="text-sm space-y-2">
        <li><a href="contact.php" class="hover:text-orange-400 transition">Contact Us</a></li>
      </ul>
    </div>

    <div>
      <h4 class="footer-heading">Services</h4>
      <ul class="text-sm space-y-2">
        <li><a href="shop.php" class="hover:text-orange-400 transition">Shop</a></li>
      </ul>
    </div>

    <div class="flex flex-col justify-start">
      <h4 class="footer-heading mb-4">Sign up for offers</h4>
      <p class="mb-6 max-w-xs text-sm">Sign up for our newsletter to receive exclusive offers & discounts!</p>
      <div class="flex max-w-xs">
        <input type="email" placeholder="Your email address.." aria-label="Email address for newsletter" required />
        <button type="submit" aria-label="Subscribe to newsletter">
          <svg class="w-5 h-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M22 4 11 13"></path>
            <path d="M22 4 15 22 11 13 2 9 22 4z"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
  <p class="text-center text-sm py-4">© 2025 PetPantry+. All rights reserved.</p>
</footer>
