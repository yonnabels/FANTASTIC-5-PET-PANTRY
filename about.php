<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - PetPantry+</title>
  <script src="https://cdn.tailwindcss.com"></script>
  
  <style>
    .outline { color: transparent; -webkit-text-stroke: 2px #fff; }
    .solid { color: #fff; }
    .overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.55); }
    .accent { color: #f97316; } /* navbar/footer orange */
    .bg-accent { background-color: #f97316; }
    .bg-accent-hover:hover { background-color: #ea580c; }
    
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
  </style>
</head>
<body class="about-page bg-white text-gray-800">

  <?php include 'header.php'; ?>

  <!-- HERO -->
  <section class="relative h-[100vh] bg-center bg-cover flex items-center justify-center" style="background-image:url('images/dog2.jpeg')">
    <div class="overlay"></div>
    <div class="z-10 text-center px-4">
      <h1 class="text-5xl md:text-7xl font-extrabold uppercase leading-tight tracking-wide">
        <span class="outline">Own</span><br>
        <span class="solid">Your Pets</span>
      </h1>
      <p class="mt-6 text-xl md:text-2xl text-gray-200 max-w-2xl mx-auto">
        Because pets are more than companions—they are family.
      </p>
    </div>
  </section>

 

<!-- OUR STORY 1 -->
<section class="min-h-[70vh] flex items-center bg-center bg-cover relative" style="background-image:url('images/aboutbg1.jpg')">
  <div class="container mx-auto relative z-10 px-6 md:px-12 grid md:grid-cols-2 gap-12 items-center">
    <div class="text-black">
      <h2 class="text-4xl md:text-5xl font-bold mb-6">Our Story</h2>
      <p class="text-lg md:text-xl leading-relaxed mb-4">
        From humble beginnings, we set out to create a place where pets are more than just companions — 
        they are family. Every product we choose is inspired by love, care, and the joy pets bring into our lives.
      </p>
      <p class="text-lg md:text-xl leading-relaxed">
        Our mission is simple: to make every tail wag and every paw happy.  
      </p>
    </div>
    <div></div>
  </div>
</section>

<!-- OUR STORY 2 -->
<section class="min-h-[80vh] flex items-center bg-center bg-cover relative" style="background-image:url('images/aboutbg2.jpg')">
  <div class="container mx-auto relative z-10 px-6 md:px-12 grid md:grid-cols-2 gap-12 items-center">
    <div></div>
    <div class="text-black">
      <h2 class="text-4xl md:text-5xl font-bold mb-6">Growing Together</h2>
      <p class="text-lg md:text-xl leading-relaxed mb-4">
        What started as a small vision has grown into a vibrant community of pet lovers. 
        With every step, we’ve expanded not just our products, but our commitment to quality, sustainability, and care.
      </p>
      <p class="text-lg md:text-xl leading-relaxed">
        Our journey continues with you — the pet parents who inspire us to keep raising the standard 
        for what pets deserve.
      </p>
    </div>
  </div>
</section>

 
<!-- TESTIMONIAL -->
<section class="py-24 bg-white relative">
  <div class="container mx-auto px-6 md:px-12 text-center relative">
    <!-- Quote Icon -->
    <div class="text-4xl text-gray-400 mb-4">“</div>

    <!-- Stars -->
    <div id="testimonial-stars" class="flex justify-center mb-4 text-yellow-400 text-xl"></div>

    <!-- Testimonial Text -->
    <p id="testimonial-text" class="text-gray-700 text-xl md:text-2xl max-w-3xl mx-auto leading-relaxed mb-6">
      Shopping at PetPantry+ gave me peace of mind I never expected. Every item is sustainable and my pets love them!
    </p>

    <!-- Navigation Arrows -->
    <button class="absolute top-1/2 left-6 transform -translate-y-1/2 text-orange-500 text-2xl" onclick="prevTestimonial()">&#8592;</button>
    <button class="absolute top-1/2 right-6 transform -translate-y-1/2 text-orange-500 text-2xl" onclick="nextTestimonial()">&#8594;</button>

    <!-- Author -->
    <div class="mt-8">
      <img id="testimonial-img" src="images/ppl2.jpg" alt="Ann Smith" class="mx-auto rounded-full w-16 h-16 mb-2">
      <p id="testimonial-author" class="font-semibold text-gray-900">— Ann Smith</p>
    </div>
  </div>
</section>
 <?php include 'footer.php'; ?>
<script>
const testimonials = [
  {
    text: "Shopping at PetPantry+ gave me peace of mind I never expected. Every item is sustainable and my pets love them!",
    author: "Ann Smith",
    img: "images/ppl2.jpg",
    stars: 4
  },
  {
    text: "PetPantry+ has transformed how I feed and care for my pets. Quality and service are unmatched!",
    author: "John Doe",
    img: "images/ppl12.jpg",
    stars: 5
  },
  {
    text: "I love how easy it is to find everything my pets need in one place. Excellent products and customer service!",
    author: "Maria Lopez",
    img: "images/ppl31.jpg",
    stars: 5
  },
  {
    text: "Affordable, high-quality, and sustainable. PetPantry+ is now my go-to for all pet supplies.",
    author: "Kevin Tan",
    img: "images/ppl41.jpg",
    stars: 4
  },
  {
    text: "My pets are happier than ever! The products are safe, eco-friendly, and they actually enjoy them.",
    author: "Sophia Reyes",
    img: "images/ppl5.jpg",
    stars: 5
  }
];

let current = 0;

function showTestimonial(index) {
  const t = testimonials[index];
  document.getElementById('testimonial-text').innerText = t.text;
  document.getElementById('testimonial-author').innerText = `— ${t.author}`;
  document.getElementById('testimonial-img').src = t.img;

  // Stars
  const starsContainer = document.getElementById('testimonial-stars');
  starsContainer.innerHTML = '';
  for (let i = 0; i < t.stars; i++) starsContainer.innerHTML += '★';
  for (let i = t.stars; i < 5; i++) starsContainer.innerHTML += '☆';
}

// Initialize first testimonial
showTestimonial(current);

function prevTestimonial() {
  current = (current - 1 + testimonials.length) % testimonials.length;
  showTestimonial(current);
}

function nextTestimonial() {
  current = (current + 1) % testimonials.length;
  showTestimonial(current);
}
</script>



</html>
