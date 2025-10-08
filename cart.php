<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: Login_and_creating_account_fixed.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// DB connection
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch cart items
$sql = "SELECT c.id as cart_id, p.id as product_id, p.name, p.price, p.image, c.quantity, p.stock
        FROM cart c
        JOIN products p ON c.product_id=p.id
        WHERE c.user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();
$cartItems = $res->fetch_all(MYSQLI_ASSOC);

// Fetch saved addresses
$addrStmt = $conn->prepare("SELECT id, full_name, address, is_default FROM user_addresses WHERE user_id=?");
$addrStmt->bind_param("i",$user_id);
$addrStmt->execute();
$addresses = $addrStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* Body & Layout */
html { height: 100%; }
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: #f5f5f5;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
main { flex: 1; }

/* Cart Container */
.cart-container {
    max-width: 1000px;
    margin: 100px auto 30px auto;
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* Cart Header & Items */
.cart-header,
.cart-item {
    display: grid;
    grid-template-columns: 50px 2fr 1fr 1fr 1fr 70px;
    align-items: center;
    padding: 10px 0;
}
.cart-header {
    border-bottom: 2px solid #ddd;
    font-weight: bold;
    text-align: center;
}
.cart-item {
    border-bottom: 1px solid #eee;
}
.cart-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}
.cart-item input[type="number"] {
    width: 60px;
    padding: 5px;
}
.cart-item input[type="checkbox"] {
    transform: scale(1.2);
}

/* Buttons */
.remove-btn {
    background: none;
    border: none;
    color: #ff6f00;
    cursor: pointer;
    font-weight: bold;
    text-align: center;
    transition: all 0.2s;
}
.remove-btn:hover {
    text-decoration: underline;
    color: #e65c00;
}
.checkout-btn {
    background: #ff6f00;
    color: #fff;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}
.checkout-btn:hover {
    background: #e65c00;
}

/* Cart Summary */
.cart-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    font-size: 18px;
    background: #f9fafb;
    border-radius: 8px;
    margin-top: 20px;
    gap: 15px;
}
.cart-summary div {
    display: flex;
    gap: 4px;
    align-items: center;
}

/* Receipt Modal */
.receipt-modal {
    max-width: 600px;
    width: 100%;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
}
.receipt-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}
.receipt-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    margin-right: 10px;
}
.receipt-item-details { flex: 1; }
.receipt-total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    padding-top: 10px;
    font-size: 16px;
}
.receipt-info {
    margin-top: 15px;
    font-size: 14px;
}
.receipt-info strong {
    display: block;
    margin-bottom: 2px;
}

/* Responsive for mobile */
@media (max-width: 768px) {
  .cart-header,
  .cart-item {
    display: grid;
    grid-template-columns: 40px 1fr;
    grid-template-rows: auto auto auto;
    gap: 5px;
    text-align: left;
  }

  .cart-item div:nth-child(3),
  .cart-item div:nth-child(4),
  .cart-item div:nth-child(5),
  .cart-item div:nth-child(6) {
    grid-column: 2 / 3;
  }

  .cart-item img {
    width: 50px;
    height: 50px;
  }

  .cart-item input[type="number"] {
    width: 50px;
  }

  .cart-summary {
    flex-direction: column;
    align-items: flex-start;
    font-size: 14px;
    gap: 8px;
  }

  .cart-summary div {
    flex: 1 1 100%;
  }

  .checkout-btn {
    width: 100%;
    text-align: center;
  }

  /* Modals */
  #checkoutModal > div,
  #receiptModal > div {
    width: 95%;
    padding: 15px;
  }

  .receipt-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .receipt-item img {
    margin-bottom: 5px;
  }

  .receipt-total {
    flex-direction: column;
    gap: 5px;
    align-items: flex-start;
  }
}

</style>

</head>
<?php include 'header.php'; ?>
<body>
<main>
<div class="cart-container">
  <div class="cart-header">
    <div><input type="checkbox" id="select-all"></div>
    <div>Product</div><div>Price</div><div>Quantity</div><div>Total</div><div>Action</div>
  </div>

  <?php foreach($cartItems as $item): ?>
  <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>">
    <div><input type="checkbox" class="item-check"></div>
    <div style="display:flex;align-items:center;gap:10px;">
      <img src="<?= htmlspecialchars($item['image'] ?: 'https://via.placeholder.com/60') ?>" alt="">
      <span class="product-name"><?= htmlspecialchars($item['name']) ?></span>
    </div>
    <div>₱<?= number_format($item['price'],2) ?></div>
    <div><input type="number" class="qty" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"></div>
    <div class="item-total">₱<?= number_format($item['price']*$item['quantity'],2) ?></div>
    <div><button class="remove-btn">Delete</button></div>
  </div>
  <?php endforeach; ?>

  <div class="cart-summary">
    <div><strong>Selected Items: <span id="selected-count">0</span></strong></div>
    <div><strong>Subtotal: ₱<span id="grand-total">0</span></strong></div>
    <div><strong>Shipping: ₱<span id="shipping-fee">0</span></strong></div>
    <div><strong>Total: ₱<span id="final-total">0</span></strong></div>
    <button class="checkout-btn">Check out (0)</button>
  </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg w-11/12 max-w-lg p-6 relative">
    <h2 class="text-xl font-bold mb-4">Checkout</h2>
    <form id="checkoutForm" class="space-y-4">
      <div>
        <label class="block font-semibold">Choose Address</label>
        <?php if(count($addresses) > 0): ?>
          <select id="addressSelect" name="address_id" class="w-full p-2 border rounded">
            <?php foreach($addresses as $addr): ?>
              <option value="<?= $addr['id'] ?>" <?= $addr['is_default']?'selected':'' ?>>
                <?= htmlspecialchars($addr['full_name']).' - '.htmlspecialchars($addr['address']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <p class="text-sm text-gray-500">No saved addresses yet. Please add one.</p>
        <?php endif; ?>
      </div>
      <div>
        <label class="block font-semibold">Or Add New Address</label>
        <input type="text" name="new_fullName" placeholder="Full Name" class="w-full p-2 border rounded mb-2">
        <textarea name="new_address" placeholder="Delivery Address" class="w-full p-2 border rounded" rows="3"></textarea>
      </div>
      <div>
        <label class="block font-semibold">Payment Method</label>
        <select id="paymentMethod" name="paymentMethod" class="w-full p-2 border rounded" required>
          <option value="">Select Payment</option>
          <option value="Cash">Cash</option>
          <option value="PayPal">PayPal</option>
        </select>
      </div>

      <div id="paypal-container" class="hidden mt-4">
        <div id="paypal-button-container"></div>
      </div>

      <div class="flex justify-end space-x-2">
        <button type="button" id="cancelCheckout" class="px-4 py-2 border rounded">Cancel</button>
        <button type="button" id="toReceiptBtn" class="px-4 py-2 bg-orange-500 text-white rounded">Next</button>
      </div>
    </form>
    <button id="closeCheckoutModal" class="absolute top-2 right-2">&times;</button>
  </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="receipt-modal relative">
    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
    <div id="receiptItems" class="max-h-60 overflow-y-auto"></div>

    <div class="receipt-total">
      <span>Subtotal</span><span>₱<span id="receiptSubtotal">0</span></span>
    </div>
    <div class="receipt-total">
      <span>Shipping</span><span>₱<span id="receiptShipping">0</span></span>
    </div>
    <div class="receipt-total">
      <span>Total</span><span>₱<span id="receiptTotal"></span></span>
    </div>

    <div class="receipt-info">
      <strong>Delivery Address</strong>
      <p id="receiptAddress"></p>
      <strong>Payment Method</strong>
      <p id="receiptPayment"></p>
    </div>

    <div class="flex justify-end gap-2 mt-4">
      <button id="backToCheckout" class="px-4 py-2 border rounded">Back</button>
      <button id="confirmReceipt" class="px-4 py-2 bg-orange-500 text-white rounded">Place Order</button>
    </div>
  </div>
</div>

</main>
<?php include 'footer.php'; ?>

<script src="https://www.paypal.com/sdk/js?client-id=AVeMJ4UPAQ0Mfsuu1uQwWLXWFfypk1iTTJloTyvUANRj3g_ACcg5o-qzZY1by8-a4qMan8u78Q1NkTNm&currency=PHP"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const SHIPPING_FEE = 50;

  // DOM Elements
  const selectAll = document.getElementById("select-all");
  const checkoutBtn = document.querySelector(".checkout-btn");
  const selectedCount = document.getElementById("selected-count");
  const grandTotal = document.getElementById("grand-total");
  const shippingElem = document.getElementById("shipping-fee");
  const finalTotal = document.getElementById("final-total");

  const headerCartBadge = document.querySelector('a[aria-label="Cart"] span');
  const checkoutModal = document.getElementById("checkoutModal");
  const receiptModal = document.getElementById("receiptModal");
  const toReceiptBtn = document.getElementById("toReceiptBtn");
  const backToCheckout = document.getElementById("backToCheckout");
  const confirmReceipt = document.getElementById("confirmReceipt");
  const checkoutForm = document.getElementById("checkoutForm");

  const receiptItems = document.getElementById("receiptItems");
  const receiptAddress = document.getElementById("receiptAddress");
  const receiptPayment = document.getElementById("receiptPayment");
  const receiptTotal = document.getElementById("receiptTotal");

  const paymentMethod = document.getElementById("paymentMethod");
  const paypalContainer = document.getElementById("paypal-container");

  const formatCurrency = num => `₱${num.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;

  const getSelectedCartItems = () => 
    Array.from(document.querySelectorAll(".cart-item .item-check:checked:not(:disabled)"))
      .map(checkbox => checkbox.closest(".cart-item"));

  function updateCart() {
    let count = 0, subtotal = 0;
    document.querySelectorAll(".cart-item").forEach(item => {
      const checkbox = item.querySelector(".item-check");
      const qtyInput = item.querySelector(".qty");
      const qty = Math.min(Math.max(parseInt(qtyInput.value)||1,1),parseInt(qtyInput.max));
      qtyInput.value = qty;

      const price = parseFloat(item.children[2].textContent.replace(/[₱,]/g,""))||0;
      item.querySelector(".item-total").textContent = formatCurrency(price*qty);

      if(checkbox.checked && !checkbox.disabled){
        count++;
        subtotal += price*qty;
      }

      if(parseInt(qtyInput.max)===0){
        qtyInput.disabled = true;
        checkbox.disabled = true;
      }
    });

    selectedCount.textContent = count;
    grandTotal.textContent = subtotal.toLocaleString();
    const shipping = subtotal>0?SHIPPING_FEE:0;
    shippingElem.textContent = shipping.toLocaleString();
    finalTotal.textContent = (subtotal+shipping).toLocaleString();
    checkoutBtn.textContent = `Check out (${count})`;
    toReceiptBtn.disabled = count===0;

    if(headerCartBadge){
      const totalQty = Array.from(document.querySelectorAll(".cart-item .qty")).reduce((sum,q)=>sum+(parseInt(q.value)||0),0);
      headerCartBadge.textContent = totalQty;
    }
  }

  document.querySelectorAll(".item-check").forEach(cb=>cb.addEventListener("change",updateCart));
  selectAll.addEventListener("change",()=>{document.querySelectorAll(".item-check").forEach(cb=>{if(!cb.disabled) cb.checked=selectAll.checked}); updateCart()});

  document.querySelectorAll(".qty").forEach(input=>{
    input.addEventListener("input",()=>{
      const qty = Math.min(Math.max(parseInt(input.value)||1,1),parseInt(input.max));
      input.value = qty;
      const cartId = input.closest(".cart-item").dataset.cartId;
      fetch("cart_action.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:`update_id=${cartId}&quantity=${qty}`})
      .then(res=>res.json()).then(data=>{if(data.status!=="success") alert(data.message); updateCart();}).catch(console.error);
    });
  });

  document.querySelectorAll(".remove-btn").forEach(btn=>{
    btn.addEventListener("click",()=>{
      const cartItem = btn.closest(".cart-item");
      const cartId = cartItem.dataset.cartId;
      fetch("cart_action.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:`remove_id=${cartId}`})
      .then(res=>res.json()).then(data=>{if(data.status==="success"){cartItem.remove();updateCart();}else alert(data.message);}).catch(console.error);
    });
  });

  checkoutBtn.addEventListener("click",()=>{
    if(getSelectedCartItems().length===0) return alert("No items selected!");
    checkoutModal.classList.remove("hidden");
    updateCart();
  });

  ["closeCheckoutModal","cancelCheckout"].forEach(id=>document.getElementById(id).onclick=()=>checkoutModal.classList.add("hidden"));

 toReceiptBtn.addEventListener("click", ()=>{
    const selectedItems = getSelectedCartItems();
    if(selectedItems.length===0) return alert("No items selected!");
    receiptItems.innerHTML = "";
    let subtotal = 0;

    selectedItems.forEach(item=>{
        const name = item.querySelector(".product-name").textContent;
        const qty = parseInt(item.querySelector(".qty").value)||1;
        const price = parseFloat(item.children[2].textContent.replace(/[₱,]/g,''))||0;
        subtotal += price * qty;
        const div = document.createElement("div");
        div.className="flex justify-between border-b py-1";
        div.innerHTML=`<span>${name} x${qty}</span><span>${formatCurrency(price*qty)}</span>`;
        receiptItems.appendChild(div);
    });

    const addrSelect = checkoutForm.querySelector("#addressSelect");
    const newAddr = checkoutForm.querySelector("[name='new_address']").value.trim();
    const fullName = checkoutForm.querySelector("[name='new_fullName']").value.trim();
    let addressText="N/A";
    if(newAddr && fullName) addressText=`${fullName} - ${newAddr}`;
    else if(addrSelect && addrSelect.selectedOptions.length>0) addressText=addrSelect.selectedOptions[0].text;

    receiptAddress.textContent = addressText;
    receiptPayment.textContent = checkoutForm.querySelector("#paymentMethod").value || "N/A";

    // Fix: set the subtotal element
    document.getElementById("receiptSubtotal").textContent = subtotal.toLocaleString();

    const shipping = subtotal > 0 ? SHIPPING_FEE : 0;
    document.getElementById("receiptShipping").textContent = shipping.toLocaleString();
    receiptTotal.textContent = (subtotal + shipping).toLocaleString();

    checkoutModal.classList.add("hidden");
    receiptModal.classList.remove("hidden");
});


  backToCheckout.addEventListener("click",()=>{receiptModal.classList.add("hidden");checkoutModal.classList.remove("hidden");});

  confirmReceipt.addEventListener("click", async()=>{
    receiptModal.classList.add("hidden");
    await handleCheckout();
  });

  async function handleCheckout(extra={}) {
    const selectedItems = getSelectedCartItems().map(item=>item.dataset.cartId);
    if(selectedItems.length===0){ alert("No items selected!"); return; }

    const formData = new FormData();
    formData.append("cart_ids",JSON.stringify(selectedItems));

    const newAddr = checkoutForm.querySelector("[name='new_address']").value.trim();
    if(newAddr){ formData.append("new_address",newAddr); formData.append("new_fullName",checkoutForm.querySelector("[name='new_fullName']").value.trim()); }
    else { const addrSelect = checkoutForm.querySelector("#addressSelect"); if(addrSelect) formData.append("address_id",addrSelect.value); }

    formData.append("payment_method",checkoutForm.querySelector("#paymentMethod").value);
    if(extra.paypal_order_id) formData.append("paypal_order_id",extra.paypal_order_id);
    formData.append("shipping_fee", SHIPPING_FEE);

    try{
      const res = await fetch("cart_checkout.php",{method:"POST",body:formData});
      const data = await res.json();
      if(data.status==="success"){ alert("Order placed!"); location.reload(); } else alert(data.message);
    }catch(err){ console.error(err); alert("Checkout failed!"); }
  }

  paymentMethod.addEventListener("change", function(){
    if(this.value==="PayPal"){
      paypalContainer.classList.remove("hidden");
      toReceiptBtn.classList.add("hidden");
      document.getElementById("paypal-button-container").innerHTML="";
      paypal.Buttons({
        createOrder: (data, actions)=>{
          const total = parseFloat(finalTotal.textContent.replace(/,/g,''))||0;
          return actions.order.create({ purchase_units:[{amount:{value:total.toFixed(2),currency_code:"PHP"}}] });
        },
        onApprove: (data, actions)=>{
          return actions.order.capture().then(async details=>{
            alert("✅ Payment completed by "+details.payer.name.given_name);
            await handleCheckout({paypal_order_id:data.orderID});
          });
        }
      }).render("#paypal-button-container");
    }else{ paypalContainer.classList.add("hidden"); toReceiptBtn.classList.remove("hidden"); }
  });

  updateCart();
});
</script>

</body>
</html>
