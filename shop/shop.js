/*
  Dynamic shop page script:
  - Loads products from get_products.php (expects JSON array of products)
  - product fields used: id, name, price, image, subcategory, old_price (optional), badge (optional)
  - Groups by subcategory for "All" view and shows up to 5 per subcategory
  - Sorting is applied before limiting to 5 for each group
  - Search filters across names (applies within groups)
  - Click category bubble to filter (it will set the select)
*/

const searchInput = document.getElementById('search');
const subcategorySelect = document.getElementById('subcategorySelect');
const sortSelect = document.getElementById('sortSelect');
const productList = document.getElementById('productList');
const resultCount = document.getElementById('resultCount');
const catScroll = document.getElementById('catScroll');
const pagination = document.getElementById('pagination');

let allProducts = [];           // all fetched products
let visibleProducts = [];       // after sort/filter
let categories = [];            // list of subcategories strings

// Fetch products from your PHP endpoint
async function fetchProducts(){
  try{
    const res = await fetch('get_products.php');
    if(!res.ok) throw new Error('Network response was not ok');
    allProducts = await res.json();
  }catch(err){
    console.error('Fetch failed, using fallback demo data', err);
    // fallback demo data so the page still works if PHP is not available
    allProducts = demoProducts();
  } finally {
    init();
  }
}

// Initialize UI after products are loaded
function init(){
  // derive categories
  categories = Array.from(new Set(allProducts.map(p => p.subcategory || 'Uncategorized'))).sort();
  populateCategoryUI();
  // enable controls
  searchInput.disabled = false;
  subcategorySelect.disabled = false;
  sortSelect.disabled = false;

  // wire events
  searchInput.addEventListener('input', applyFilters);
  subcategorySelect.addEventListener('change', applyFilters);
  sortSelect.addEventListener('change', applyFilters);

  // initial render
  applyFilters();
}

// Fill categories in left select and cat-scroll row
function populateCategoryUI(){
  // left select
  subcategorySelect.innerHTML = '<option value="">All Subcategories</option>' +
    categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');

  // cat-scroll icons (using placeholder images if none)
  catScroll.innerHTML = categories.map(c => {
    const icon = encodeURI('https://via.placeholder.com/58?text=' + (c.split(' ')[0].slice(0,2)));
    return `<div class="cat" data-sub="${escapeHtml(c)}" onclick="onCatClick('${escapeJs(c)}')">
              <div class="circle"><img src="${icon}" alt="${escapeHtml(c)}" /></div>
              <span>${escapeHtml(c)}</span>
            </div>`;
  }).join('');
}

// scroll buttons
function scrollCats(amount){
  catScroll.scrollBy({ left: amount, behavior:'smooth' });
}
window.scrollCats = scrollCats;

// Category bubble clicked -> set select and filter
function onCatClick(sub){
  subcategorySelect.value = sub;
  applyFilters();
}
window.onCatClick = onCatClick;

// Main filter + sort workflow
function applyFilters(){
  const q = (searchInput.value||'').trim().toLowerCase();
  const selectedSub = subcategorySelect.value;
  const sortVal = sortSelect.value;

  // copy
  let items = allProducts.slice();

  // search filter
  if(q){
    items = items.filter(p => (p.name||'').toLowerCase().includes(q));
  }

  // apply sort
  if(sortVal){
    items.sort((a,b) => {
      if(sortVal === 'price-asc') return parseFloat(a.price || 0) - parseFloat(b.price || 0);
      if(sortVal === 'price-desc') return parseFloat(b.price || 0) - parseFloat(a.price || 0);
      if(sortVal === 'name-asc') return (a.name||'').localeCompare(b.name||'');
      if(sortVal === 'name-desc') return (b.name||'').localeCompare(a.name||'');
      return 0;
    });
  }

  visibleProducts = items;

  // render (respects 5-per-subcategory limit in "All" view)
  renderProducts(selectedSub);
}

// Render function
function renderProducts(selectedSub){
  productList.innerHTML = '';

  if(visibleProducts.length === 0){
    resultCount.textContent = 'No products found';
    return;
  }

  // If specific subcategory selected -> show up to 5 from that sub
  if(selectedSub){
    const filtered = visibleProducts.filter(p => (p.subcategory||'') === selectedSub).slice(0,5);
    resultCount.textContent = `Showing ${filtered.length} item(s) in "${selectedSub}"`;
    const grid = createGridFor(filtered);
    productList.appendChild(grid);
    return;
  }

  // "All subcategories" mode: group and show up to 5 each
  const grouped = {};
  visibleProducts.forEach(p => {
    const k = p.subcategory || 'Uncategorized';
    grouped[k] = grouped[k] || [];
    if(grouped[k].length < 5) grouped[k].push(p);
  });

  // Count results overall (sum of shown)
  const totalShown = Object.values(grouped).reduce((s,arr) => s + arr.length, 0);
  resultCount.textContent = `Showing ${totalShown} products (${Object.keys(grouped).length} categories)`;

  // For each group, create a subcategory block
  for(const sub of Object.keys(grouped)){
    const groupDiv = document.createElement('div');
    groupDiv.className = 'subcategory-group';
    groupDiv.innerHTML = `<div class="subcategory-title">${escapeHtml(sub)}<span class="view-all" onclick="openCategory('${escapeJs(sub)}')">View all</span></div>`;
    const grid = createGridFor(grouped[sub]);
    groupDiv.appendChild(grid);
    productList.appendChild(groupDiv);
  }
}

// helper to create grid element for array of products
function createGridFor(items){
  const grid = document.createElement('div');
  grid.className = 'grid';
  // render each provided item
  items.forEach(p => {
    const card = document.createElement('div');
    card.className = 'card';
    // Badge
    const badgeHTML = p.badge ? `<div class="badge">${escapeHtml(p.badge)}</div>` : (p.discount ? `<div class="badge discount">-${escapeHtml(String(p.discount))}%</div>` : '');
    const oldPriceHTML = p.old_price ? `<span class="oldprice">₱${parseFloat(p.old_price).toFixed(2)}</span>` : '';
    const img = `<div class="imgwrap"><img src="${escapeHtml(p.image || 'https://via.placeholder.com/180')}" alt="${escapeHtml(p.name)}" /></div>`;
    card.innerHTML = `
      ${badgeHTML}
      ${img}
      <h4>${escapeHtml(p.name)}</h4>
      <div><span class="price">₱${parseFloat(p.price||0).toFixed(2)}</span>${oldPriceHTML}</div>
      <div class="actions">
        <button class="btn view" onclick="viewProduct(${escapeJs(p.id)})">View</button>
        <button class="btn cart" onclick="addToCart(${escapeJs(p.id)})">Add</button>
      </div>
    `;
    grid.appendChild(card);
  });

  // If fewer than 6 columns, and you prefer full 6-column layout (keeps spacing),
  // you can append empty placeholders up to 6 to maintain visual alignment on wide screens.
  const placeholdersNeeded = Math.max(0, 6 - items.length);
  for(let i=0;i<placeholdersNeeded;i++){
    const ph = document.createElement('div');
    ph.className = 'card';
    ph.style.background='transparent';
    ph.style.border='0';
    ph.style.boxShadow='none';
    grid.appendChild(ph);
  }

  return grid;
}

// open category shortcut
function openCategory(sub){
  subcategorySelect.value = sub;
  applyFilters();
}
window.openCategory = openCategory;

// simple product action handlers (stubs)
function viewProduct(id){ alert('View product: ' + id); }
function addToCart(id){ alert('Add to cart: ' + id); }

// safe escaping helpers for inline HTML building
function escapeHtml(s){
  if(s==null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escapeJs(s){
  // returns js-safe quoted string for inline onclick building
  if(s==null) return "''";
  return "'" + String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/\n/g,'\\n') + "'";
}

// Demo fallback products (only used if PHP fetch fails)
function demoProducts(){
  const subs = ['Cat Food','Cat Toys','Dog Beds','Dog Clothing','Accessories','Dental & Ear Care'];
  const out=[];
  let id=1;
  for(const s of subs){
    for(let i=1;i<=7;i++){
      out.push({
        id: id++,
        name: `${s} Product ${i}`,
        price: (Math.random()*300 + 20).toFixed(2),
        old_price: Math.random()>0.7 ? (Math.random()*300 + 50).toFixed(2) : null,
        image: `https://picsum.photos/seed/${encodeURIComponent(s+i)}/300/240`,
        subcategory: s,
        badge: (Math.random()>0.85) ? 'HOT' : '',
        discount: Math.random()>0.8 ? Math.floor(Math.random()*25)+5 : null
      });
    }
  }
  return out;
}

// start
fetchProducts();