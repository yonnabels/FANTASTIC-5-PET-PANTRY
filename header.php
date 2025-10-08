<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<header class="bg-white shadow-sm fixed top-0 w-full z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16 items-center">

      <!-- Logo -->
      <a href="index.php" class="flex items-center flex-shrink-0">
        <img class="h-10 w-10 object-contain rounded-full border-2 border-orange-500" src="images/logo.png" alt="PetPantry+ logo">
        <span class="ml-2 font-semibold text-orange-500 text-lg select-none">PetPantry+</span>
      </a>

      <!-- Desktop Navigation -->
      <nav class="hidden md:flex space-x-8 font-semibold text-gray-700">
        <a href="index.php" class="hover:text-orange-500 transition">Home</a>
        <a href="shop.php" class="hover:text-orange-500 transition">Shop</a>
        <a href="orders.php" class="hover:text-orange-500 transition">Orders</a>
        <a href="about.php" class="hover:text-orange-500 transition">About Us</a>
        <a href="contact.php" class="hover:text-orange-500 transition">Contact</a>
      </nav>

      <!-- Right Icons -->
      <div class="hidden md:flex items-center space-x-4 relative">

        <!-- Notification -->
        <div class="relative">
          <button id="notif-btn" class="relative text-gray-700 hover:text-orange-600 flex items-center focus:outline-none">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 0 0-5-5.917V5a2 2 0 1 0-4 0v.083A6 6 0 0 0 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
            </svg>
            <!-- Badge like cart badge -->
            <span id="notif-count" class="absolute -top-1 -right-1 w-4 h-4 text-xs flex items-center justify-center font-bold rounded-full bg-orange-500 text-white">
              0
            </span>
          </button>

          <div id="notif-dropdown" class="absolute right-0 mt-2 w-72 max-w-xs bg-white border border-gray-200 rounded-lg shadow-lg overflow-y-auto opacity-0 transform -translate-y-2 scale-95 transition-all duration-200 pointer-events-none z-50">
            <div class="p-4 text-gray-700 font-semibold border-b border-gray-100">Notifications</div>
            <ul id="notif-list" class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
              <li class="p-3 text-center text-sm text-gray-500">No notifications</li>
            </ul>
          </div>
        </div>

        <!-- Account -->
        <div class="relative">
          <button id="account-btn" class="flex items-center text-gray-700 hover:text-orange-500 focus:outline-none">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </button>
          <div id="account-dropdown" class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg opacity-0 transform -translate-y-2 scale-95 transition-all duration-200 pointer-events-none">
            <?php if(isset($_SESSION['user_id'])): ?>
              <a href="user_settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-orange-500 text-sm">Settings</a>
              <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-orange-500 text-sm">Log Out</a>
            <?php else: ?>
              <a href="Login_and_creating_account_fixed.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-orange-500 text-sm">Log In</a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Cart -->
        <a href="cart.php<?= isset($_SESSION['user']) ? '?user_id=' . $_SESSION['user']['id'] : '' ?>" class="relative text-gray-700 hover:text-orange-500">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="9" cy="21" r="1"/>
            <circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l.89 4.48M5 6h15l-1.68 8.39a2 2 0 0 1-2 1.61H7a2 2 0 0 1-2-1.61L3 6"/>
          </svg>
          <span id="cart-count" class="absolute -top-1 -right-1 w-4 h-4 text-xs flex items-center justify-center font-bold rounded-full bg-orange-500 text-white">
            <?= $_SESSION['user']['cart_count'] ?? 0 ?>
          </span>
        </a>
      </div>

      <!-- Mobile Hamburger -->
<div class="flex items-center md:hidden">
  <button id="mobile-menu-btn" class="text-gray-700 hover:text-orange-500 focus:outline-none">
    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
  </button>
</div>

<!-- Mobile Menu Panel -->
<div id="mobile-menu" class="md:hidden fixed top-0 left-0 w-64 h-full bg-white border border-gray-200 rounded-lg shadow-lg transform -translate-x-full transition-transform duration-300 z-50 p-6 flex flex-col overflow-y-auto space-y-4">

  <!-- Menu Links -->
  <ul class="space-y-2 font-semibold text-gray-700">
    <li><a href="index.php" class="block px-4 py-2 rounded hover:bg-gray-100 hover:text-orange-500 transition">Home</a></li>
    <li><a href="shop.php" class="block px-4 py-2 rounded hover:bg-gray-100 hover:text-orange-500 transition">Shop</a></li>
    <li><a href="orders.php" class="block px-4 py-2 rounded hover:bg-gray-100 hover:text-orange-500 transition">Orders</a></li>
    <li><a href="about.php" class="block px-4 py-2 rounded hover:bg-gray-100 hover:text-orange-500 transition">About Us</a></li>
    <li><a href="contact.php" class="block px-4 py-2 rounded hover:bg-gray-100 hover:text-orange-500 transition">Contact</a></li>
  </ul>

  <!-- Mobile Notifications -->
  <div class="relative">
    <button id="mobile-notif-btn" class="w-full text-left px-4 py-2 flex justify-between items-center text-gray-700 hover:text-orange-500 transition font-semibold bg-white border border-gray-200 rounded-lg shadow">
      Notifications
      <span id="mobile-notif-count" class="absolute top-1 right-3 w-4 h-4 text-xs flex items-center justify-center font-bold rounded-full bg-orange-500 text-white">0</span>
    </button>
    <div id="mobile-notif-dropdown" class="absolute left-0 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-y-auto max-h-80 opacity-0 transform -translate-y-2 scale-95 pointer-events-none transition-all duration-200 z-50">
      <div class="p-4 text-gray-700 font-semibold border-b border-gray-100">Notifications</div>
      <ul id="mobile-notif-list" class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
        <li class="p-3 text-center text-sm text-gray-500">No notifications</li>
      </ul>
    </div>
  </div>

  <!-- Mobile Account -->
  <div class="relative">
    <button id="mobile-account-btn" class="w-full text-left px-4 py-2 flex justify-between items-center text-gray-700 hover:text-orange-500 transition font-semibold bg-white border border-gray-200 rounded-lg shadow">
      Account
      <svg class="h-4 w-4 transform transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>
    <div id="mobile-account-dropdown" class="absolute left-0 mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-lg opacity-0 transform -translate-y-2 scale-95 pointer-events-none transition-all duration-200 z-50">
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="user_settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-orange-500 text-sm">Settings</a>
        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-orange-500 text-sm">Log Out</a>
      <?php else: ?>
        <a href="Login_and_creating_account_fixed.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-orange-500 text-sm">Log In</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Mobile Cart -->
  <div class="relative">
    <a href="cart.php<?= isset($_SESSION['user']) ? '?user_id=' . $_SESSION['user']['id'] : '' ?>" class="w-full block px-4 py-2 text-gray-700 hover:text-orange-500 transition font-semibold bg-white border border-gray-200 rounded-lg shadow relative">
      Cart
      <span id="mobile-cart-count" class="absolute top-1 right-3 w-4 h-4 text-xs flex items-center justify-center font-bold rounded-full bg-orange-500 text-white">
        <?= $_SESSION['user']['cart_count'] ?? 0 ?>
      </span>
    </a>
  </div>
</div>





</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // ---------- Generic Dropdown Handler ----------
  function setupDropdown(button, dropdown, onOpenCallback) {
    if (!button || !dropdown) return;

    button.addEventListener('click', e => {
      e.stopPropagation();
      const isOpening = dropdown.classList.contains('opacity-0');
      dropdown.classList.toggle('opacity-0');
      dropdown.classList.toggle('-translate-y-2');
      dropdown.classList.toggle('scale-95');
      dropdown.classList.toggle('pointer-events-none');
      if (isOpening && typeof onOpenCallback === 'function') onOpenCallback();
    });

    document.addEventListener('click', e => {
      if (!button.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('opacity-0', '-translate-y-2', 'scale-95', 'pointer-events-none');
      }
    });
  }

  // ---------- Mobile Menu Toggle ----------
  const mobileBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', () => mobileMenu.classList.toggle('-translate-x-full'));
  }

  // ---------- Update Notification Badge ----------
  function updateNotificationCount(count) {
    const badge = document.getElementById('notif-count');
    if (badge) badge.textContent = count ?? 0;
    const mobileBadge = document.getElementById('mobile-notif-count');
    if (mobileBadge) mobileBadge.textContent = count ?? 0;
  }

  // ---------- Fetch & Render Notifications ----------
  async function fetchNotifications() {
    try {
      const res = await fetch('fetch_notifications.php');
      const data = await res.json();

      // Desktop
      const notifList = document.getElementById('notif-list');
      if (notifList) {
        notifList.innerHTML = '';
        if (!data.length) {
          notifList.innerHTML = '<li class="p-3 text-center text-sm text-gray-500">No notifications</li>';
        } else {
          data.forEach(n => {
            const li = document.createElement('li');
            li.className = 'px-4 py-3 hover:bg-gray-50 flex justify-between items-center space-x-2 cursor-pointer';
            li.dataset.id = n.id;
            li.innerHTML = `
              <div class="flex-1">
                <span class="${n.is_read == 0 ? 'font-bold' : ''}">${n.message}</span>
                <div class="text-xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</div>
              </div>
              <button class="delete-notif text-red-500 text-sm hover:text-red-700 font-bold">&times;</button>
            `;
            notifList.appendChild(li);
          });
        }
      }

      // Mobile
      const mobileList = document.getElementById('mobile-notif-list');
      if (mobileList) {
        mobileList.innerHTML = '';
        if (!data.length) {
          mobileList.innerHTML = '<li class="p-3 text-center text-sm text-gray-500">No notifications</li>';
        } else {
          data.forEach(n => {
            const li = document.createElement('li');
            li.className = 'px-4 py-3 hover:bg-gray-50 flex justify-between items-center space-x-2 cursor-pointer';
            li.dataset.id = n.id;
            li.innerHTML = `
              <div class="flex-1">
                <span class="${n.is_read == 0 ? 'font-bold' : ''}">${n.message}</span>
                <div class="text-xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</div>
              </div>
            `;
            mobileList.appendChild(li);
          });
        }
      }

      // Update badges
      const unreadCount = data.filter(n => n.is_read == 0).length;
      updateNotificationCount(unreadCount);

    } catch (err) {
      console.error('Fetch notifications error:', err);
    }
  }

 // ---------- Notification Click & Delete ----------
document.addEventListener('click', async (e) => {
  // Delete button
  const deleteBtn = e.target.closest('.delete-notif');
  if (deleteBtn) {
    e.stopPropagation();
    const li = deleteBtn.closest('li');
    if (!li || !li.dataset.id) return;
    const notifId = li.dataset.id;

    try {
      const res = await fetch('delete_notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${notifId}`
      });
      const data = await res.json();
      if (data.status === 'success') {
        li.style.transition = 'all 0.3s';
        li.style.opacity = 0;
        li.style.height = 0;
        li.style.margin = 0;
        li.style.padding = 0;
        setTimeout(() => li.remove(), 300);
        fetchNotifications();
      } else {
        alert('Failed to delete notification: ' + (data.message || 'Unknown error'));
      }
    } catch (err) {
      console.error('Fetch error:', err);
      alert('An error occurred while deleting the notification.');
    }
    return; // Stop further processing if it's delete
  }

  // Notification click -> redirect to orders.php
  const notifItem = e.target.closest('#notif-list li, #mobile-notif-list li');
  if (notifItem && !e.target.classList.contains('delete-notif')) {
    window.location.href = 'orders.php';
  }
});


  // ---------- Mark All Notifications Read ----------
  async function markNotificationsRead() {
    try {
      const res = await fetch('mark_notifications_read.php', { method: 'POST' });
      const data = await res.json();
      if (data.status === 'success') {
        document.querySelectorAll('#notif-list li span, #mobile-notif-list li span').forEach(span => span.classList.remove('font-bold'));
        updateNotificationCount(0);
      }
    } catch (err) { console.error(err); }
  }

  // ---------- Setup Dropdowns ----------
  setupDropdown(document.getElementById('account-btn'), document.getElementById('account-dropdown'));
  setupDropdown(document.getElementById('mobile-account-btn'), document.getElementById('mobile-account-dropdown'));
  setupDropdown(document.getElementById('notif-btn'), document.getElementById('notif-dropdown'), markNotificationsRead);
  setupDropdown(document.getElementById('mobile-notif-btn'), document.getElementById('mobile-notif-dropdown'), markNotificationsRead);

  // ---------- Update Cart Count ----------
  async function updateCartCount() {
    try {
      const res = await fetch('cart_action.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: '' });
      const data = await res.json();
      if (data.status === 'success') {
        const desktopBadge = document.getElementById('cart-count');
        const mobileBadge = document.getElementById('mobile-cart-count');
        if (desktopBadge) desktopBadge.textContent = data.cart_count ?? 0;
        if (mobileBadge) mobileBadge.textContent = data.cart_count ?? 0;
      }
    } catch (err) { console.error('Update cart error:', err); }
  }
  
  

  // ---------- Initial Load & Intervals ----------
  updateCartCount();
  fetchNotifications();
  setInterval(updateCartCount, 10000);
  setInterval(fetchNotifications, 10000);
});
</script>




