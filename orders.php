<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if(!isset($_SESSION['user_id'])){
    header('Location: Login_and_creating_account_fixed.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

/* Cancel Order */
if(isset($_POST['submit_cancel'])){
    $groupId = intval($_POST['order_group_id'] ?? 0);
    $reason  = trim($_POST['reason'] ?? '');
    $other   = trim($_POST['other_reason'] ?? '');
    $actualReason = ($reason === 'Other') ? $other : $reason;

    if($groupId && !empty($actualReason)){
        // Verify this order belongs to the user and is still pending
        $stmtCheck = $conn->prepare("SELECT status FROM order_groups WHERE id=? AND user_id=?");
        $stmtCheck->bind_param("ii", $groupId, $user_id);
        $stmtCheck->execute();
        $stmtCheck->bind_result($status);
        if($stmtCheck->fetch() && strtolower($status) === 'pending'){
            $stmtCheck->close();

            // Insert cancel request
            $stmtInsert = $conn->prepare("
                INSERT INTO cancel_requests 
                (order_group_id, user_id, reason, other_reason, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmtInsert->bind_param("iiss", $groupId, $user_id, $reason, $other);
            $stmtInsert->execute();
            $stmtInsert->close();

            // ✅ Update order_groups status immediately
            $stmtUpdate = $conn->prepare("UPDATE order_groups SET status='cancelled' WHERE id=? AND user_id=?");
            $stmtUpdate->bind_param("ii", $groupId, $user_id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            $stmtCheck->close();
        }
    }

    header("Location: orders.php");
    exit;
}



/* Review Submission */
if(isset($_POST['submit_review'])){
    $orderId = intval($_POST['order_id'] ?? 0);
    $rating  = intval($_POST['rating'] ?? 0);
    $review  = trim($_POST['review'] ?? '');

    if($orderId > 0 && $rating >= 1 && $rating <= 5){
        // Insert or update review
        $stmt = $conn->prepare("
            INSERT INTO order_reviews (order_id, user_id, rating, review, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review), updated_at=NOW()
        ");
        if($stmt){
            $stmt->bind_param("iiis", $orderId, $user_id, $rating, $review);
            $stmt->execute();
            $stmt->close();
        }

        // Safely get the review ID
        $review_id = 0;
        $result = $conn->query("SELECT id FROM order_reviews WHERE order_id=$orderId AND user_id=$user_id");
        if($result){
            $row = $result->fetch_assoc();
            if($row) $review_id = intval($row['id']);
        }

        if($review_id > 0){
            // Handle existing images
            $keepExisting = $_POST['keep_existing'] ?? [];
            $keepIds = array_map('intval', $keepExisting);

            $allImageIds = [];
            $resultImg = $conn->query("SELECT id FROM review_images WHERE review_id=$review_id");
            if($resultImg){
                $allImageIds = $resultImg->fetch_all(MYSQLI_ASSOC);
            }

            $idsToDelete = [];
            foreach($allImageIds as $img){
                if(!in_array($img['id'], $keepIds)){
                    $idsToDelete[] = $img['id'];
                }
            }

            if(!empty($idsToDelete)){
                $idsStr = implode(',', $idsToDelete);
                $conn->query("DELETE FROM review_images WHERE id IN ($idsStr)");
            }

            // Upload new images
            if(!empty($_FILES['review_images']['name'][0])){
                $targetDir = "uploads/reviews/";
                if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                foreach($_FILES['review_images']['tmp_name'] as $key => $tmp_name){
                    if($_FILES['review_images']['error'][$key] === UPLOAD_ERR_OK){
                        $fileName = time() . "_" . basename($_FILES['review_images']['name'][$key]);
                        $targetFile = $targetDir . $fileName;

                        if(move_uploaded_file($tmp_name, $targetFile)){
                            $stmtImg = $conn->prepare("INSERT INTO review_images (review_id, image_path) VALUES (?, ?)");
                            if($stmtImg){
                                $stmtImg->bind_param("is", $review_id, $targetFile);
                                $stmtImg->execute();
                                $stmtImg->close();
                            }
                        }
                    }
                }
            }
        }
    }

    header("Location: orders.php");
    exit;
}



/* Refund Request */
if(isset($_POST['submit_refund'])){
    $orderId   = intval($_POST['order_id'] ?? 0);
    $productId = intval($_POST['product_id'] ?? 0);
    $reason    = trim($_POST['reason'] ?? '');
    $other     = trim($_POST['other_reason'] ?? '');
    $actualReason = ($reason === 'Other') ? $other : $reason;

    if($orderId && $productId && !empty($actualReason)){

        // Verify this product belongs to the user
        $stmtCheck = $conn->prepare("
            SELECT o.id 
            FROM orders o
            JOIN order_groups og ON o.order_group_id = og.id
            WHERE o.id=? AND o.product_id=? AND og.user_id=?
        ");
        $stmtCheck->bind_param("iii", $orderId, $productId, $user_id);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if($stmtCheck->num_rows > 0){
            $stmtCheck->close();

            // Check if a refund request already exists
            $stmtExist = $conn->prepare("
                SELECT id FROM refund_requests 
                WHERE order_id=? AND product_id=? AND user_id=?
            ");
            $stmtExist->bind_param("iii", $orderId, $productId, $user_id);
            $stmtExist->execute();
            $stmtExist->store_result();

            if($stmtExist->num_rows === 0){
                $stmtExist->close();

                // Insert refund request
                $stmtInsert = $conn->prepare("
                    INSERT INTO refund_requests 
                    (order_id, product_id, user_id, reason, other_reason, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmtInsert->bind_param("iiiss", $orderId, $productId, $user_id, $reason, $other);
                $stmtInsert->execute();
                $stmtInsert->close();

            } else {
                $stmtExist->close();
                // Optional: set a message "Refund request already exists"
            }

        } else {
            $stmtCheck->close();
            // Optional: set a message "Invalid order or product"
        }
    }

    header("Location: orders.php");
    exit;
}



/* Filters */
$filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = "WHERE og.user_id=?";
$params = [$user_id];
$types = "i";

if($filter !== 'all'){
    $where .= " AND LOWER(og.status)=?";
    $params[] = strtolower($filter);
    $types .= "s";
}
if($search !== ''){
    $where .= " AND (og.id LIKE ? OR EXISTS (SELECT 1 FROM orders o JOIN products p ON o.product_id=p.id WHERE o.order_group_id=og.id AND p.name LIKE ?))";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

/* Fetch Orders */
$sql = "SELECT * FROM order_groups og $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orderGroups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$allOrders = [];
foreach($orderGroups as $group){
    $groupId = $group['id'];
    $sqlItems = "SELECT o.id as order_id, o.product_id, o.quantity, o.price, p.name, p.image,
                        r.id as review_id, r.rating, r.review, r.updated_at
                 FROM orders o
                 JOIN products p ON o.product_id = p.id
                 LEFT JOIN order_reviews r ON r.order_id=o.id AND r.user_id=?
                 WHERE o.order_group_id=?";
    $stmt2 = $conn->prepare($sqlItems);
    $stmt2->bind_param("ii", $user_id, $groupId);
    $stmt2->execute();
    $items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    foreach($items as &$item){
        $item['images'] = [];
        if($item['review_id']){
            $stmtImg = $conn->prepare("SELECT id, image_path FROM review_images WHERE review_id=?");
            $stmtImg->bind_param("i", $item['review_id']);
            $stmtImg->execute();
            $item['images'] = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtImg->close();
        }
        
        // Fetch refund status
    $stmtRefund = $conn->prepare("
        SELECT status FROM refund_requests 
        WHERE order_id=? AND product_id=? AND user_id=?
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmtRefund->bind_param("iii", $item['order_id'], $item['product_id'], $user_id);
    $stmtRefund->execute();
    $resultRefund = $stmtRefund->get_result()->fetch_assoc();
    $item['refund_status'] = $resultRefund['status'] ?? null;
    $stmtRefund->close();
    
    }
    $allOrders[$groupId] = ["info"=>$group, "items"=>$items];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PetPantry - Orders</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
.status { font-weight:600; text-align:center; padding:5px 10px; border-radius:6px; color:white; display:inline-block; }
.status-pending { background-color:#fb7d1b; }
.status-shipping { background-color:#3399ff; }
.status-completed { background-color:#28a745; }
.status-cancelled { background-color:#dc3545; }
/* Refund status colors */
.status-approved { background-color:#28a745; }
.status-rejected { background-color:#e53935; }
.btn-orange { background-color: #fb7d1b; color: white; }
.btn-orange:hover { background-color: #de6514; }
.btn-red { background-color: #e53935; color:white; }
.btn-red:hover { background-color: #c62828; }
.star { cursor:pointer; font-size:22px; color:#ccc; transition:color 0.2s; }
.star.selected, .star.hover { color: gold; }
</style>
</head>
<body class="flex flex-col min-h-screen">
<?php include 'header.php'; ?>

<main class="flex-grow pt-20">
<div class="orders-container max-w-5xl mx-auto px-4">
  <h2 class="text-2xl font-bold mb-4">Your Orders</h2>

  <!-- Filter + Search -->
  <form method="get" class="flex flex-wrap gap-2 mb-6 items-center">
    <div class="flex flex-wrap gap-2">
      <?php $statuses = ['all'=>'All','pending'=>'Pending','shipping'=>'Shipping','completed'=>'Completed','cancelled'=>'Cancelled'];
      foreach($statuses as $key=>$label): ?>
        <button type="submit" name="status" value="<?= $key ?>" 
          class="px-4 py-2 rounded-full text-sm font-medium <?= ($filter === $key) ? 'btn-orange' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
          <?= $label ?>
        </button>
      <?php endforeach; ?>
    </div>
    <div class="ml-auto flex">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
             placeholder="Search Order ID or Product..." 
             class="border rounded-l-full px-4 py-2 w-64">
      <button type="submit" class="btn-orange px-4 rounded-r-full">Search</button>
    </div>
  </form>

  <?php if(empty($allOrders)): ?>
    <p class="p-4 text-center text-gray-500">No orders found.</p>
  <?php else: ?>
    <?php foreach($allOrders as $groupId => $orderGroup): ?>
      <?php $status = strtolower($orderGroup['info']['status']); ?>
      <div class="mb-6 border rounded-lg shadow-sm">
        <!-- Group Header -->
        <button onclick="toggleGroup(<?= $groupId ?>)" 
                class="w-full flex justify-between items-center bg-gray-100 px-4 py-3 rounded-t-lg cursor-pointer">
          <div>
            <span class="font-bold block">Order Group #<?= $groupId ?></span>
            <small class="text-gray-600 block">Placed on <?= date("F j, Y g:i A", strtotime($orderGroup['info']['created_at'])) ?></small>
          </div>
          <div><span class="status status-<?= $status ?>"><?= ucfirst($status) ?></span></div>
        </button>

        <!-- Group Items -->
        <div id="group-<?= $groupId ?>" class="hidden">
          <?php $orderTotal = 0;
          foreach($orderGroup['items'] as $item):
            $itemTotal = $item['price'] * $item['quantity'];
            $orderTotal += $itemTotal; ?>
            <div class="p-3 border-b">
              <div class="flex items-center gap-4">
                <img src="<?= htmlspecialchars($item['image']) ?>" class="w-16 h-16 rounded">
                <div class="flex-1">
                  <div class="font-semibold"><?= htmlspecialchars($item['name']) ?></div>
                  <div>₱<?= number_format($item['price'],2) ?> × <?= $item['quantity'] ?></div>
                </div>
              </div>

              <?php if($status === 'completed'): ?>
              <div class="mt-2 pl-20">
                <?php if(!$item['review_id']): ?>
                  <!-- Quick Review -->
                  <form method="post" class="quick-review mt-2 space-y-2">
                    <input type="hidden" name="order_id" value="<?= $item['order_id'] ?>">
                    <input type="hidden" name="rating" class="rating-input">
                    <div class="flex gap-1">
                      <?php for($i=1;$i<=5;$i++): ?>
                        <span class="star" data-value="<?= $i ?>">★</span>
                      <?php endfor; ?>
                    </div>
                    <button type="submit" name="submit_review" class="btn-orange px-3 py-1 rounded hidden">Submit Rating</button>
                  </form>
                <?php else: ?>
                  <!-- Saved Review -->
                  <div class="review-display mb-2">
                    <div class="flex gap-1 mb-1">
                      <?php for($i=1;$i<=5;$i++): ?>
                        <span class="star <?= ($i <= (int)$item['rating']) ? 'selected' : '' ?>">★</span>
                      <?php endfor; ?>
                    </div>
                    <?php if($item['review']): ?>
                      <p class="text-gray-700"><?= nl2br(htmlspecialchars($item['review'])) ?></p>
                    <?php endif; ?>
                    <?php if(!empty($item['images'])): ?>
                      <div class="mt-2 flex flex-wrap gap-2">
                        <?php foreach($item['images'] as $img): ?>
                          <img src="<?= htmlspecialchars($img['image_path']) ?>" class="w-24 h-24 object-cover rounded border">
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <div class="flex gap-2 mt-2">
                  <button type="button" class="btn-orange px-3 py-1 rounded" onclick="openModal('review-<?= $item['order_id'] ?>')">
                    <?= $item['review_id'] ? 'Edit Review' : 'Write Review' ?>
                  </button>
                  
                  

                  <!-- Refund Button / Status -->
                  <?php if(empty($item['refund_status'])): ?>
                    <button type="button" class="btn-red px-3 py-1 rounded" onclick="openModal('refund-<?= $item['order_id'] ?>')">Request Refund</button>
                  <?php else: ?>
                    <span class="status status-<?= strtolower($item['refund_status']) ?>">
    Refund Status: <?= ucfirst($item['refund_status']) ?>
</span>
                  <?php endif; ?>
                </div>

                <!-- Full Review Modal -->
                <div id="review-<?= $item['order_id'] ?>" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
                  <div class="bg-white rounded-lg p-6 w-96">
                    <h3 class="font-bold mb-4"><?= $item['review_id'] ? 'Edit Review' : 'Write Review' ?></h3>
                    <form method="post" enctype="multipart/form-data">
                      <input type="hidden" name="order_id" value="<?= $item['order_id'] ?>">

                      <div class="flex gap-1 mb-3 modal-stars">
                        <?php for($i=1;$i<=5;$i++): ?>
                          <label>
                            <input type="radio" name="rating" value="<?= $i ?>" class="hidden" <?= ($item['rating']==$i)?'checked':'' ?>>
                            <span class="star <?= ($i <= (int)$item['rating']) ? 'selected' : '' ?>">★</span>
                          </label>
                        <?php endfor; ?>
                      </div>

                      <textarea name="review" rows="3" class="w-full border rounded p-2 mb-3" placeholder="Write your review..."><?= htmlspecialchars($item['review'] ?? '') ?></textarea>

                      <input type="file" name="review_images[]" accept="image/*" multiple onchange="previewImages(this)">
                      <div class="preview mt-2 flex flex-wrap gap-2"></div>

                      <?php if(!empty($item['images'])): ?>
                        <div class="mt-2 flex flex-wrap gap-2 existing-images">
                          <?php foreach($item['images'] as $img): ?>
                            <div class="relative" data-existing="true">
                              <img src="<?= htmlspecialchars($img['image_path']) ?>" class="w-24 h-24 object-cover rounded border">
                              <button type="button" class="absolute top-0 right-0 bg-red-500 text-white text-xs px-1 rounded" onclick="removeExistingImage(this)">✕</button>
                              <input type="hidden" name="keep_existing[]" value="<?= $img['id'] ?>">
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>

                      <div class="mt-4 flex justify-end gap-2">
                        <button type="submit" name="submit_review" class="btn-orange px-3 py-1 rounded">Save Review</button>
                        <button type="button" class="btn-red px-3 py-1 rounded" onclick="closeModal('review-<?= $item['order_id'] ?>')">Cancel</button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Refund Modal -->
<div id="refund-<?= $item['order_id'] ?>" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 w-96">
    <h3 class="font-bold mb-4">Request Refund</h3>
    <form method="post">
      <input type="hidden" name="order_id" value="<?= $item['order_id'] ?>">
      <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">

      <p class="mb-2 font-medium">Select a reason:</p>
      <div class="flex flex-col gap-2 mb-3">
        <?php $reasons = ["Damaged item", "Wrong item received", "Item not delivered", "Quality not as expected"]; ?>
        <?php foreach($reasons as $reason): ?>
          <label class="flex items-center gap-2">
            <input type="radio" name="reason" value="<?= $reason ?>" class="refund-reason-radio">
            <span><?= $reason ?></span>
          </label>
        <?php endforeach; ?>
        <label class="flex items-center gap-2">
          <input type="radio" name="reason" value="Other" class="refund-reason-radio">
          <span>Other</span>
        </label>
      </div>

      <textarea name="other_reason" id="other_reason_<?= $item['order_id'] ?>" 
                class="w-full border rounded p-2 mb-3 hidden" 
                placeholder="Write your reason..."></textarea>

      <div class="mt-4 flex justify-end gap-2">
        <button type="submit" name="submit_refund" class="btn-orange px-3 py-1 rounded">Submit</button>
        <button type="button" class="btn-red px-3 py-1 rounded" onclick="closeModal('refund-<?= $item['order_id'] ?>')">Cancel</button>
      </div>
    </form>
  </div>
</div>

              </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
          <div class="flex justify-between items-center px-4 py-2 bg-gray-50 rounded-b-lg">
            <span class="font-bold text-lg">Total: ₱<?= number_format($orderTotal,2) ?></span>
            <?php if($status === 'pending'): ?>
  <button type="button" class="btn-red px-3 py-1 rounded" onclick="openModal('cancel-<?= $groupId ?>')">Cancel Order</button>

  <!-- Cancel Modal -->
  <div id="cancel-<?= $groupId ?>" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
      <h3 class="font-bold mb-4">Cancel Order</h3>
      <form method="post">
        <input type="hidden" name="order_group_id" value="<?= $groupId ?>">

        <p class="mb-2 font-medium">Select a reason:</p>
        <div class="flex flex-col gap-2 mb-3">
          <?php $cancelReasons = ["Changed my mind", "Found a better price", "Ordered by mistake"]; ?>
          <?php foreach($cancelReasons as $reason): ?>
            <label class="flex items-center gap-2">
              <input type="radio" name="reason" value="<?= $reason ?>" class="cancel-reason-radio">
              <span><?= $reason ?></span>
            </label>
          <?php endforeach; ?>
          <label class="flex items-center gap-2">
            <input type="radio" name="reason" value="Other" class="cancel-reason-radio">
            <span>Other</span>
          </label>
        </div>

        <textarea name="other_reason" id="other_reason_<?= $groupId ?>" 
                  class="w-full border rounded p-2 mb-3 hidden" 
                  placeholder="Write your reason..."></textarea>

        <div class="mt-4 flex justify-end gap-2">
          <button type="submit" name="submit_cancel" class="btn-orange px-3 py-1 rounded">Submit</button>
          <button type="button" class="btn-red px-3 py-1 rounded" onclick="closeModal('cancel-<?= $groupId ?>')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Invoice Button -->
              <button type="button" class="btn-orange px-3 py-1 rounded" onclick="openInvoiceModal(<?= $groupId ?>)">Invoice</button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
  
  
</div>
</main>

<!-- Refund Confirmation Modal -->
<div id="refund-confirm-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 w-96 text-center">
    <h3 class="text-lg font-bold mb-4">Confirm Refund</h3>
    <p class="mb-6">Are you sure you want to request a refund for this item?</p>
    <div class="flex justify-center gap-4">
      <button id="refund-confirm-yes" class="btn-orange px-4 py-2 rounded">Yes</button>
      <button id="refund-confirm-no" class="btn-red px-4 py-2 rounded">No</button>
    </div>
  </div>
</div>

<!-- Invoice Modal -->
<div id="modal-invoice" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 w-11/12 max-w-4xl h-5/6 flex flex-col">
    <span class="self-end cursor-pointer font-bold text-xl" onclick="closeInvoiceModal()">×</span>
    <h3 class="font-bold mb-2 text-lg">Invoice Preview</h3>
    <iframe id="invoice-frame" class="flex-1 border rounded mb-3" style="width:100%; border:1px solid #e5e7eb;"></iframe>
    <div class="flex justify-end gap-2">
      <button class="btn-orange px-3 py-1 rounded" onclick="printInvoice()">Print</button>
      <button class="btn-orange px-3 py-1 rounded" onclick="downloadInvoice()">Download PDF</button>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Toggle order group visibility
function toggleGroup(id){
  document.getElementById('group-' + id).classList.toggle('hidden');
}

// Open modal
function openModal(id){
  const modal = document.getElementById(id);
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

// Close modal
function closeModal(id){
  const modal = document.getElementById(id);
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

// Invoice Modal Logic
let currentInvoiceOrderId = null;

function openInvoiceModal(orderId){
    currentInvoiceOrderId = orderId;
    const iframe = document.getElementById('invoice-frame');
    iframe.src = `invoice.php?order_id=${orderId}`;
    const modal = document.getElementById('modal-invoice');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeInvoiceModal(){
    const modal = document.getElementById('modal-invoice');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('invoice-frame').src = '';
    currentInvoiceOrderId = null;
}

function printInvoice(){
    if(!currentInvoiceOrderId) return;
    const iframe = document.getElementById('invoice-frame');
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
}

function downloadInvoice(){
    if(!currentInvoiceOrderId) return;
    window.location.href = `invoice_pdf.php?order_id=${currentInvoiceOrderId}`;
}

// Quick review stars (inline)
function setupQuickReviewStars(container){
  const stars = container.querySelectorAll(".star");
  const input = container.querySelector(".rating-input");
  let current = parseInt(input.value || 0);

  const render = (hover=0)=>{
    const val = hover || current;
    stars.forEach(s => s.classList.toggle("selected", parseInt(s.dataset.value) <= val));
  };
  render();

  stars.forEach(star=>{
    const val = parseInt(star.dataset.value);
    star.addEventListener("mouseenter",()=>render(val));
    star.addEventListener("mouseleave",()=>render());
    star.addEventListener("click",()=>{
      current = val;
      input.value = current;
      const btn = container.querySelector("button[name=submit_review]");
      if(btn) btn.classList.remove("hidden");
      render();
    });
  });
}

// Modal review stars
function setupModalStars(container, initial=0){
  const stars = container.querySelectorAll(".star");
  const inputs = container.querySelectorAll("input[name='rating']");
  let current = initial;

  const render = (hover=0)=>{
    const val = hover || current;
    stars.forEach((s, idx)=>{
      if(idx < val) s.classList.add("selected");
      else s.classList.remove("selected");
    });
  };
  render();

  stars.forEach((star, idx)=>{
    const value = idx + 1;
    star.addEventListener("mouseenter",()=> render(value));
    star.addEventListener("mouseleave",()=> render());
    star.addEventListener("click",()=>{
      current = value;
      inputs.forEach(r => r.checked = parseInt(r.value) === current);
      render();
    });
  });
}

// Preview images
function previewImages(input){
  const container = input.nextElementSibling;
  container.innerHTML = '';
  Array.from(input.files).forEach(file=>{
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.className = 'w-24 h-24 object-cover rounded border';
    container.appendChild(img);
  });
}

// Remove existing images
function removeExistingImage(btn){
  const container = btn.parentElement;
  const hiddenInput = container.querySelector('input[type=hidden]');
  if(hiddenInput) hiddenInput.remove();
  container.remove();
}

// Refund modal "Other" textarea toggle
document.querySelectorAll('.refund-reason-radio').forEach(radio => {
  radio.addEventListener('change', (e) => {
    const form = e.target.closest('form');
    const textarea = form.querySelector('textarea[name="other_reason"]');
    if(e.target.value === 'Other'){
      textarea.classList.remove('hidden');
    } else {
      textarea.classList.add('hidden');
      textarea.value = '';
    }
  });
});

// Confirm before submitting refund (like cancel)
document.querySelectorAll('form').forEach(form => {
  const refundBtn = form.querySelector('button[name="submit_refund"]');
  if(refundBtn){
    form.addEventListener('submit', function(e){
      if(e.submitter === refundBtn){
        const confirmRefund = confirm("Are you sure you want to request a refund for this item?");
        if(!confirmRefund){
          e.preventDefault(); // cancel submission
        }
      }
    });
  }
});

// Cancel modal "Other" textarea toggle
document.querySelectorAll('.cancel-reason-radio').forEach(radio => {
  radio.addEventListener('change', (e) => {
    const form = e.target.closest('form');
    const textarea = form.querySelector('textarea[name="other_reason"]');
    if(e.target.value === 'Other'){
      textarea.classList.remove('hidden');
    } else {
      textarea.classList.add('hidden');
      textarea.value = '';
    }
  });
});

// Confirm before submitting cancel request (like refund)
document.querySelectorAll('form').forEach(form => {
  if(form.querySelector('button[name="submit_cancel"]')){
    form.addEventListener('submit', function(e){
      const submitBtn = form.querySelector('button[name="submit_cancel"]');
      if(submitBtn && e.submitter === submitBtn){ // only trigger for cancel submit
        const confirmCancel = confirm("Are you sure you want to cancel this order?");
        if(!confirmCancel){
          e.preventDefault(); // cancel submission
        }
      }
    });
  }
});

// Initialize quick review stars
document.querySelectorAll(".quick-review").forEach(container => {
  setupQuickReviewStars(container);
});

// Initialize modal stars
document.querySelectorAll(".modal-stars").forEach(container => {
  const checked = container.querySelector("input[name='rating']:checked");
  const initial = checked ? parseInt(checked.value) : 0;
  setupModalStars(container, initial);
});

</script>
</body>
</html>


