<?php
session_start();
require 'database.php'; // your PDO connection

// Fetch cart count if user is logged in
if(isset($_SESSION['user'])){
    $userId = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS cart_count FROM cart WHERE user_id=?");
    $stmt->execute([$userId]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cart_count'] ?? 0;
    $_SESSION['user']['cart_count'] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>PetPantry Shop</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --accent: #FFA500;
      --accent-dark: #CC8400;
      --muted: #6b7280;
      --bg: #f6f7f9;
      --card: #ffffff;
      --radius: 10px;
      --max-width: 1200px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
      background:var(--bg);
      color:#111827;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }
    /* HERO / TOP BANNER */
    .hero {
      width: 100%;
      background-image: url("https://petio.wpbingosite.com/wp-content/uploads/2021/03/breadcumd.jpg");
      background-size: cover;
      background-position: center;
      padding: 150px 20px;
      color: white;
      position: relative;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }
    .hero::after {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.4);
      pointer-events: none;
      z-index: 0;
    }
    .hero-inner {
      position: relative;
      max-width: var(--max-width);
      margin: 0 auto;
      z-index: 1;
    }
    .hero-title h1 {
      font-size: 48px;
      margin: 0 0 8px;
      letter-spacing: 1px;
      font-weight: 900;
    }
    .hero-title p {
      margin: 0;
      opacity: 0.95;
      font-size: 15px;
      color: rgba(255, 255, 255, 0.95);
      font-weight: 700;
    }
    /* MAIN LAYOUT: left filters column + products stretch */
    .page{
      max-width:var(--max-width);
      margin:20px auto;
      padding:0 20px 60px;
      display:flex;
      gap:24px;
      align-items:flex-start;
    }
    .left-col{
      width:240px;
      min-width:240px;   /* ✅ prevents squishing */
      flex-shrink:0;     /* ✅ keep fixed size */
      display:flex;
      flex-direction:column;
      gap:12px;
    }
    .controls{
      background:var(--card);
      padding:14px;
      border-radius:12px;
      box-shadow:0 6px 18px rgba(15,23,42,0.04);
      display:flex;
      flex-direction:column;
      gap:10px;
    }
    .controls label{
      font-size:12px;
      color:var(--muted);
    }
    .controls select,
    .controls input[type="search"]{
      width:100%;                 /* ✅ always full width */
      box-sizing:border-box;       /* ✅ keeps spacing consistent */
      padding:10px 12px;
      border-radius:8px;
      border:1px solid #e6e9ee;
      background:#fff;
      outline:none;
      font-size:14px;
    }
    /* PRODUCTS AREA */
    .main-col{ flex:1; }
    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:16px;
    }
    .result-count{
      color:var(--muted);
      font-size:13px;
    }
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    input[type=number] { -moz-appearance: textfield; }
    .subcategory-group{ margin-bottom:36px; background:transparent; }
    .subcategory-title{
      font-size:18px;
      font-weight:700;
      color:#111827;
      margin:8px 0 12px;
      display:flex;
      align-items:center;
      gap:12px;
    }
    .subcategory-title .view-all{
      margin-left:auto;
      font-size:13px;
      color:var(--muted);
      cursor:pointer;
      text-decoration:underline;
    }
    .grid{
      display:grid;
      grid-template-columns: repeat(6, 1fr);
      gap:18px;
    }
    .card{
      background:var(--card);
      border-radius:12px;
      padding:12px;
      text-align:center;
      border:1px solid #f0f2f5;
      position:relative;
      min-height:220px;
    }
    .card .imgwrap{
      width:100%;
      height:138px;
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
    }
    .card img{ max-width:100%; max-height:100%; object-fit:contain }
    .badge{
      position:absolute;
      top:10px;
      left:10px;
      background:var(--accent);
      color:white;
      padding:6px 8px;
      font-size:12px;
      border-radius:999px;
      font-weight:700;
    }
    .discount{ background:#ef4444; }
    .card h4{
      font-size:14px;
      margin:10px 0 6px;
      color:#111827;
      height:72px;
      overflow:hidden;
    }
    .price{ color:var(--accent); font-weight:700; font-size:15px; }
    .oldprice{
      color:#9ca3af;
      text-decoration:line-through;
      margin-left:8px;
      font-weight:600;
      font-size:13px;
    }
    .card .actions{
      margin-top:10px;
      display:flex;
      gap:8px;
      justify-content:center;
    }
    .btn{ padding:8px 12px;border-radius:8px;border:none; cursor:pointer; font-weight:700; }
    .btn.view{ background:transparent; border:1px solid #e6e9ee; color:#374151; }
    .btn.cart{ background:var(--accent); color:#fff; }
    .pagination{
      display:flex;
      gap:8px;
      justify-content:center;
      margin-top:18px;
      align-items:center;
    }
    .page-btn{
      padding:8px 12px;
      background:#fff;
      border-radius:8px;
      border:1px solid #e6e9ee;
      cursor:pointer;
    }
    @media (max-width:1100px){
      .grid{ grid-template-columns: repeat(4, 1fr); }
      .left-col{ width:200px; min-width:200px; }
    }
    @media (max-width:820px){
      .page{ flex-direction:column; }
      .left-col{ 
        width:100% !important;   /* ✅ full width on mobile */
        min-width:auto; 
        order:2; 
      }
      .main-col{ order:1; }
      .grid{ grid-template-columns: repeat(2, 1fr); }
      .categories{ justify-content:flex-start }
    }
    @media (max-width:420px){
      .grid{ grid-template-columns: repeat(1, 1fr); }
      .hero-title h1{ font-size:28px; }
      .cat .circle{ width:66px; height:66px }
    }
    .modal-image-container span {
      background: none;
      color: black;
      font-size: 28px;
      font-weight: 300;
      user-select: none;
      cursor: pointer;
      padding: 0;
      transition: transform 0.2s ease;
    }
    .modal-image-container span:hover { transform: scale(1.2); }
    
    /* Footer headings */
.footer-heading {
  color: #e56736;
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 0.7rem;
}

/* Form input */
input[type="email"] {
  width: 100%;
  padding: 0.5rem 1rem;
  border-radius: 9999px 0 0 9999px;
  border: none;
  outline: none;
}

/* Submit button in newsletter */
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

body { font-family: 'Inter', sans-serif; margin: 0; }
h1,h2,h3,h4,h5,h6 { font-family: 'Anton', sans-serif; }

.contact-form {
  background: rgba(255,255,255,0.95);
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
  text-align: center;
}

.contact-form .form-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
  </style>

</head>
<body>
 <?php include 'header.php'; ?>


  <!-- HERO -->
  <section class="hero">
    <div class="hero-inner">
      <div class="hero-title">
        <h1>Shop</h1>
        <p>Discover top-quality food, toys, and accessories to keep your pets happy, healthy, and loved — all in one place.</p>
      </div>
    </div>
  </section>

  <div id="catScroll" style="display:none;"></div>

  <!-- Main page: left filters, main product area -->
  <div class="page">
    <aside class="left-col">
<div class="controls">
  <label for="search">Search</label>
  <input id="search" type="search" placeholder="Search products..." >

  <label for="categorySelect">All Categories</label>
  <select id="categorySelect" >
    <option value="">All Categories</option>
  </select>

  <label for="subcategorySelect">All Subcategories</label>
  <select id="subcategorySelect" >
    <option value="">All Subcategories</option>
  </select>

  <label for="sortSelect">Sort By</label>
  <select id="sortSelect" >
    <option value="">Default Sorting</option>
    <option value="price-asc">Price: Low to High</option>
    <option value="price-desc">Price: High to Low</option>
    <option value="name-asc">Name: A-Z</option>
    <option value="name-desc">Name: Z-A</option>
  </select>
</div>

    </aside>
    <main class="main-col">
      <div class="topbar">
        <div class="result-count" id="resultCount">Showing products...</div>
        <div style="color:var(--muted); font-size:13px"></div>
      </div>
      <div id="productList"></div>
      <div class="pagination" id="pagination"></div>
    </main>
  </div>

  <!-- PRODUCT MODAL -->
  <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 overflow-auto p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full relative flex flex-col md:flex-row">
      <!-- Left: Product Images -->
      <div class="md:w-1/2 p-4 flex flex-col items-center">
        <img id="modalImage" src="" alt="Product Image" class="w-full h-96 object-contain rounded-lg mb-4">
        <div id="modalThumbnails" class="flex gap-2 overflow-x-auto w-full"></div>
      </div>
      <!-- Right: Product Details -->
      <div class="md:w-1/2 p-6 flex flex-col justify-between relative">
        <div>
          <h2 id="modalName" class="text-2xl font-bold mb-2 text-gray-900"></h2>
          <p id="modalDescription" class="text-gray-600 mb-4"></p>
          <p class="text-sm text-gray-500 mb-2"><b>Stock:</b> <span id="modalStock"></span></p>
          <p class="text-xl text-orange-500 font-bold mb-4">₱<span id="modalPrice"></span></p>
          <div class="flex items-center gap-4 mb-4">
            <span class="font-semibold">Quantity:</span>
            <div class="flex items-center border rounded-md overflow-hidden">
              <button id="decreaseQty" class="px-3 py-1 text-gray-700 hover:bg-gray-200">-</button>
              <input id="modalQuantity" type="number" min="1" value="1" class="w-16 text-center outline-none border-l border-r border-gray-300">
              <button id="increaseQty" class="px-3 py-1 text-gray-700 hover:bg-gray-200">+</button>
            </div>
          </div>
        </div>
        <button id="modalAddToCart" class="mt-4 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg w-full">
          Add to Cart
        </button>
        <button id="closeModal" class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 text-2xl font-bold">✕</button>
      </div>
    </div>
  </div>

<?php include 'footer.php'; ?>

          
          <script src="shop.js"></script>
          </body>
          
         </html>
