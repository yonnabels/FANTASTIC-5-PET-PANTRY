<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'database.php'; // expects $pdo

// --- Helper function to fetch products ---
function fetchProducts($pdo, $query) {
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ==============================
   QUERIES
   ============================== */

// Best Sellers (most ordered)
$bestSellers = fetchProducts($pdo, "
    SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
           COUNT(o.id) AS total_orders
    FROM products p
    JOIN orders o ON p.id = o.product_id
    GROUP BY p.id
    ORDER BY total_orders DESC
    LIMIT 8
");

// Featured Items (highest rated products)
$featuredItems = fetchProducts($pdo, "
    SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
           AVG(r.rating) AS avg_rating
    FROM products p
    JOIN orders o ON p.id = o.product_id
    JOIN order_reviews r ON o.id = r.order_id
    GROUP BY p.id
    HAVING avg_rating >= 4
    ORDER BY avg_rating DESC
    LIMIT 8
");

// Promotional Items
$promoItems = fetchProducts($pdo, "
    SELECT id, name, description, price, subcategory, image, stock, images
    FROM products
    WHERE promo = 1
    ORDER BY id DESC
    LIMIT 8
");

// New Arrivals
$newArrivals = fetchProducts($pdo, "
    SELECT id, name, description, price, subcategory, image, stock, images
    FROM products
    ORDER BY id DESC
    LIMIT 8
");

/* ==============================
   CAROUSEL RENDERER
   ============================== */
function renderProductCarousel($id, $products) {
    ?>
    <div class="relative overflow-hidden">
      <button class="absolute left-0 top-1/2 -translate-y-1/2 bg-orange-500 text-white p-3 rounded-full shadow z-10"
              onclick="slideCarousel('<?= $id ?>', -1)">&#10094;</button>
      <div id="<?= $id ?>" class="flex transition-transform duration-500 ease-in-out">
        <?php foreach ($products as $p): ?>
          <?php $imgSrc = $p['image'] ?: 'https://via.placeholder.com/300'; ?>
          <article class="w-1/2 md:w-1/4 flex-shrink-0 px-2">
            <div class="border rounded-lg p-4 shadow-sm relative hover:shadow-lg cursor-pointer"
                 onclick="viewProduct(<?= $p['id'] ?>)">
              <div class="h-48 flex items-center justify-center mb-3">
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                     class="max-h-full max-w-full object-contain">
              </div>
              <div class="text-center">
                <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($p['name']) ?></p>
                <p class="text-orange-500 font-semibold text-lg">₱<?= number_format($p['price']) ?></p>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <button class="absolute right-0 top-1/2 -translate-y-1/2 bg-orange-500 text-white p-3 rounded-full shadow z-10"
              onclick="slideCarousel('<?= $id ?>', 1)">&#10095;</button>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PetPantry+</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="index.css">
  <style>
    .carousel-item { opacity: 0; position: absolute; inset: 0; transition: opacity 1s ease-in-out; }
    .carousel-item.opacity-100 { opacity: 1; position: relative; }
  </style>
</head>
<body class="bg-white">

<?php include 'header.php'; ?>

<main>
  <!-- Hero Carousel -->
<section class="relative h-[720px] overflow-hidden">
  <!-- Slides -->
  <div class="carousel relative w-full h-full">
    <div class="carousel-inner relative w-full h-full">
      <!-- Slide 1 -->
      <div class="carousel-item opacity-100">
        <img 
          src="images/bg1.png" 
          alt="Happy pets enjoying premium food" 
          class="w-full h-full object-cover"
        >
      </div>
      <!-- Slide 2 -->
      <div class="carousel-item">
        <img 
          src="images/bg2.png" 
          alt="Nutritious pet food bowl" 
          class="w-full h-full object-cover"
        >
      </div>
      <!-- Slide 3 -->
      <div class="carousel-item">
        <img 
          src="images/bg3.png" 
          alt="Healthy pets running outdoors" 
          class="w-full h-full object-cover"
        >
      </div>
    </div>

    <!-- Dots -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2 z-20">
      <button 
        class="carousel-dot w-3 h-3 rounded-full bg-white/50" 
        aria-label="Go to slide 1" 
        data-index="0"
      ></button>
      <button 
        class="carousel-dot w-3 h-3 rounded-full bg-white/50" 
        aria-label="Go to slide 2" 
        data-index="1"
      ></button>
      <button 
        class="carousel-dot w-3 h-3 rounded-full bg-white/50" 
        aria-label="Go to slide 3" 
        data-index="2"
      ></button>
    </div>
  </div>

  <!-- Hero Content -->
  <div class="absolute inset-0 flex flex-col md:flex-row items-center max-w-7xl mx-auto px-8 py-16 gap-6 z-10">
    <div class="flex-1 text-center md:text-left max-w-lg md:ml-32">
      <h1 class="text-4xl md:text-5xl font-extrabold uppercase text-white leading-tight">
        High Quality <br>
        <span class="text-5xl md:text-6xl">Pet Food</span>
      </h1>
      <p class="mt-3 text-sm text-white/80">
        Your Pet Deserves the Best
      </p>
      <a 
        href="shop.php" 
        class="btn-black mt-6 inline-block"
        aria-label="Shop now for high quality pet food"
      >
        Shop Now
      </a>
    </div>
  </div>
</section>


  <!-- Sections -->
  <section class="py-16 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10"><span class="text-orange-500">Best </span>Sellers</h2>
    <?php renderProductCarousel("bestSeller", $bestSellers); ?>
  </section>

  <section class="py-16 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10"><span class="text-orange-500">Featured </span>Items</h2>
    <?php renderProductCarousel("featuredItems", $featuredItems); ?>
  </section>

  <section class="py-16 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10"><span class="text-orange-500">Promotional </span>Items</h2>
    <?php renderProductCarousel("promoItems", $promoItems); ?>
  </section>

  <section class="py-16 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10"><span class="text-orange-500">New </span>Arrivals</h2>
    <?php renderProductCarousel("newArrivals", $newArrivals); ?>
  </section>

  <!-- Testimonial -->
<section class="relative min-h-[60vh] mt-24 flex items-center">
  <!-- Background image -->
  <img 
    src="images/bg4.png" 
    alt="Taste Guarantee background" 
    class="absolute inset-0 w-full h-full object-cover"
  >

  <!-- Content -->
  <div class="relative z-10 max-w-xl px-6 py-12 mx-auto text-center md:text-left md:ml-24">
    <h3 class="text-base md:text-lg mb-4 text-gray-700">Taste Guarantee</h3>
    <h2 class="text-2xl md:text-3xl font-bold mb-3 text-gray-700">
      Taste it, love it or we’ll replace it... Guaranteed!
    </h2>
    <p class="text-sm md:text-base leading-relaxed mb-6 text-gray-700">
      At PetPantry+, we believe your dog and cat will love their food so much that if they don’t, we’ll help you find a replacement. That's our taste guarantee.
    </p>
    <button class="btn-black">Find out more</button>
  </div>
</section>

  
    <!-- Popular Brands Section -->
  <section class="py-14 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10">
      <span class="text-orange-500">Popular </span><span>Brands</span>
    </h2>
    <div class="bg-white border rounded-lg p-6 grid grid-cols-5 gap-6 shadow-sm max-w-xl mx-auto">
      <img src="images/brand1.png" alt="Brand 1 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand2.png" alt="Brand 2 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand3.png" alt="Brand 3 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand4.png" alt="Brand 4 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand5.png" alt="Brand 5 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
    </div>
  </section>
  
</main>



<?php include 'footer.php'; ?>

<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 overflow-auto p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full relative flex flex-col md:flex-row">
    <div class="md:w-1/2 p-4 flex flex-col items-center">
      <img id="modalImage" src="" alt="Product Image" class="w-full h-96 object-contain rounded-lg mb-4">
      <div id="modalThumbnails" class="flex gap-2 overflow-x-auto w-full"></div>
    </div>
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



<script>
const productsData = {
  bestSellers: <?= json_encode($bestSellers) ?>,
  featuredItems: <?= json_encode($featuredItems) ?>,
  promoItems: <?= json_encode($promoItems) ?>,
  newArrivals: <?= json_encode($newArrivals) ?>
};
</script>

<script src="index.js"></script>
<script>
/* Hero carousel */
document.addEventListener('DOMContentLoaded', () => {
  const items = document.querySelectorAll('.carousel-item');
  const dots = document.querySelectorAll('.carousel-dot');
  let current = 0;

  function showSlide(i){
    items[current].classList.remove('opacity-100');
    dots[current].classList.remove('bg-orange-500');
    current = i;
    items[current].classList.add('opacity-100');
    dots[current].classList.add('bg-orange-500');
  }
  dots.forEach((dot,i)=>dot.addEventListener('click',()=>showSlide(i)));
  setInterval(()=>showSlide((current+1)%items.length),7000);
});



/* Product carousels */
let carouselPositions = {};
function slideCarousel(id, dir){
  const carousel = document.getElementById(id);
  const item = carousel.querySelector("article");
  if (!item) return;
  const itemWidth = item.offsetWidth;
  const visible = window.innerWidth < 768 ? 2 : 4;
  const total = carousel.children.length;
  if (!carouselPositions[id]) carouselPositions[id] = 0;
  carouselPositions[id] += dir * itemWidth;
  const max = (total - visible) * itemWidth;
  if (carouselPositions[id] < 0) carouselPositions[id] = 0;
  if (carouselPositions[id] > max) carouselPositions[id] = max;
  carousel.style.transform = `translateX(-${carouselPositions[id]}px)`;
}
</script>

</body>
</html>
