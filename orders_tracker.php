<?php
session_start();
include('database.php');

// ✅ Role-based admin check
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ✅ Only allow super_admin OR orders role
if (!in_array('super_admin', $roles) && !in_array('orders', $roles)) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// ✅ Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("SELECT user_id, status FROM order_groups WHERE id=?");
    $stmt->execute([$_POST['order_group_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order && $order['status'] !== $_POST['status']) {
        $newStatus = $_POST['status'];

        // Update order_groups table
        $stmt = $pdo->prepare("UPDATE order_groups SET status=? WHERE id=?");
        $stmt->execute([$newStatus, $_POST['order_group_id']]);

        // Build customer notification message
        $message = "Your order #" . (int)$_POST['order_group_id'] .
                   " status has been updated to " . ucfirst(htmlspecialchars($newStatus));

        // Insert notification for the customer
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, order_group_id, account_type, message, type, created_at)
            VALUES (?, ?, 'customer', ?, 'order', NOW())
        ");
        $stmt->execute([$order['user_id'], $_POST['order_group_id'], $message]);
    }
    exit;
}

// ✅ Handle refund status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_refund'])) {
    $stmt = $pdo->prepare("UPDATE refund_requests SET status=? WHERE id=?");
    $stmt->execute([$_POST['refund_status'], $_POST['refund_id']]);
    exit;
}

// ✅ Filtering
$filterStatus = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if ($filterStatus !== 'all') {
    $where = "WHERE LOWER(og.status)=?";
    $params[] = strtolower($filterStatus);
}

// ✅ Fetch orders with user info
$sql = "SELECT og.*, u.name AS username, u.email
        FROM order_groups og
        JOIN users u ON og.user_id = u.id
        $where
        ORDER BY og.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orderGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch order items with refund info
$sqlItems = "SELECT o.*, p.name AS product_name, p.image, o.order_group_id,
                    r.id AS refund_id, r.status AS refund_status, r.reason, r.other_reason
             FROM orders o
             JOIN products p ON o.product_id = p.id
             LEFT JOIN refund_requests r 
             ON r.order_id=o.id AND r.product_id=o.product_id";
$stmt2 = $pdo->query($sqlItems);
$items = [];
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $items[$row['order_group_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Orders Tracker</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body { font-family:'Inter',sans-serif; background:#f4f6f8; margin:0; }
.order-card { background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 4px 15px rgba(0,0,0,0.05); transition:all 0.2s; }
.order-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.08); }
.order-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.order-info { font-size:0.95rem; color:#4b5563; }
.status-badge { padding:4px 12px; border-radius:9999px; font-weight:500; font-size:0.875rem; color:white; display:inline-block; }
.status-pending { background:#f97316; }
.status-shipping { background:#3b82f6; }
.status-completed { background:#10b981; }
.status-cancelled { background:#ef4444; }
.order-items { display:none; margin-top:15px; border-top:1px solid #e5e7eb; padding-top:10px; }
.order-item { display:flex; flex-wrap:wrap; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #e5e7eb; }
.order-item img { width:60px; height:60px; border-radius:8px; object-fit:cover; }
.total-row { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; font-weight:600; color:#111827; }
select.status-select, select.refund-select { padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; font-size:0.875rem; }
.btn-update, .btn-print { padding:6px 14px; border-radius:8px; border:none; cursor:pointer; font-size:0.875rem; transition:all 0.2s; }
.btn-update, .btn-print { background:#f97316; color:white; }
.btn-update:hover, .btn-print:hover { background:#ea580c; }
.collapsible { cursor:pointer; color:#f97316; font-size:0.875rem; font-weight:500; }
.notification { position:relative; display:inline-block; }
.notification-badge { position:absolute; top:-5px; right:-5px; background:red; color:white; font-size:0.7rem; font-weight:bold; padding:2px 5px; border-radius:9999px; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:100; }
.modal-content { background:white; padding:20px; border-radius:12px; max-width:400px; width:90%; }
.modal-close { float:right; cursor:pointer; font-weight:bold; }
.modal h3 { font-weight:600; margin-bottom:10px; }
.modal form { display:flex; flex-direction:column; gap:10px; }
.status-filter a { transition:all 0.2s; }
.status-filter a.bg-orange-500:hover { background:#ea580c; }
</style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">
<div class="flex min-h-screen">
<?php include('admin_navbar.php'); ?>

<main class="flex-1 p-6 mt-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-3">
    <!-- Page Title -->
       <h1 class="text-3xl font-extrabold text-gray-800">Orders Tracker</h1>

    <!-- Admin Greeting + Refund Notifications -->
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
        <!-- Greeting -->
        <span class="text-gray-600 text-base">
            Welcome, <strong class="text-orange-600 font-bold"><?=htmlspecialchars($_SESSION['name'] ?? 'Admin')?></strong>
        </span>

        <!-- Refund Notification Dropdown -->
        <div class="notification relative">
            <button id="notif-btn" class="px-4 py-2 bg-orange-500 text-white rounded flex items-center gap-2">
                Refund Notifications
                <span class="notification-badge" id="notif-count">0</span>
                ▼
            </button>
            <div id="notif-dropdown" class="absolute right-0 mt-2 w-80 max-h-64 overflow-y-auto bg-white shadow-lg rounded-lg z-50 hidden"></div>
        </div>
    </div>
</div>

    <!-- Status Filter -->
    <div class="mb-4 flex gap-2 status-filter">
        <?php
        $statuses = ['all'=>'All','pending'=>'Pending','shipping'=>'Shipping','completed'=>'Completed','cancelled'=>'Cancelled'];
        foreach($statuses as $key=>$label): ?>
            <a href="?status=<?=$key?>" class="px-3 py-1 rounded <?=($filterStatus===$key)?'bg-orange-500 text-white':'bg-gray-200'?>">
                <?=$label?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Orders Cards -->
    <?php foreach($orderGroups as $group):
        $groupId = $group['id'];
        $orderItems = $items[$groupId] ?? [];
        $orderTotal = 0;
        foreach($orderItems as $it) $orderTotal += $it['price']*$it['quantity'];
        $status = strtolower($group['status']);
        switch($status) {
            case 'pending': $statusClass='status-pending'; break;
            case 'shipping': $statusClass='status-shipping'; break;
            case 'completed': $statusClass='status-completed'; break;
            case 'cancelled': $statusClass='status-cancelled'; break;
            default: $statusClass='status-pending';
        }
    ?>
    <div class="order-card" id="order-card-<?=$groupId?>">
        <div class="order-header">
            <div>
                <div class="font-semibold">Order #<?=$groupId?></div>
                <div class="order-info"><?=htmlspecialchars($group['username'])?> | <?=htmlspecialchars($group['email'])?></div>
                <div class="order-info text-sm"><?=date("F j, Y g:i A", strtotime($group['created_at']))?></div>
            </div>
            <div class="flex items-center gap-4">
                <span class="status-badge <?=$statusClass?>"><?=ucfirst($status)?></span>
                <span class="font-bold">₱<?=number_format($orderTotal,2)?></span>
                <span class="collapsible" onclick="toggleItems(<?=$groupId?>)">▼ View Details</span>
            </div>
        </div>

        <div class="order-items" id="items-<?=$groupId?>">
            <?php 
                $subtotal = 0;
                foreach($orderItems as $item):
                    $itemTotal = $item['price']*$item['quantity'];
                    $subtotal += $itemTotal;
            ?>
            <div class="order-item">
                <img src="<?=htmlspecialchars($item['image'])?>" alt="">
                <div class="flex-1"><?=htmlspecialchars($item['product_name'])?></div>
                <div>₱<?=number_format($item['price'],2)?></div>
                <div>x<?=$item['quantity']?></div>
                <div>= ₱<?=number_format($itemTotal,2)?></div>
                <?php if($item['refund_id']): ?>
                <button class="btn-update ml-2" onclick="openRefundModal('<?=addslashes($item['product_name'])?>','<?=addslashes($item['reason'])?>','<?=addslashes($item['other_reason'])?>',<?=$item['refund_id']?>,'<?=$item['refund_status']?>')">Refund</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="total-row">
                <form method="post" class="flex gap-2 flex-wrap items-center" onsubmit="updateOrderStatus(event, <?=$groupId?>)">
                    <input type="hidden" name="order_group_id" value="<?=$groupId?>">
                    <select name="status" class="status-select" id="status-<?=$groupId?>">
                        <option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option>
                        <option value="shipping" <?=$status==='shipping'?'selected':''?>>Shipping</option>
                        <option value="completed" <?=$status==='completed'?'selected':''?>>Completed</option>
                        <option value="cancelled" <?=$status==='cancelled'?'selected':''?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn-update">Update</button>
                </form>
                <button class="btn-print" onclick="openInvoiceModal(<?=$groupId?>)">Print Invoice</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</main>

</div>

<!-- Refund Modal -->
<div id="modal-refund" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeRefundModal()">×</span>
        <h3>Refund Request</h3>
        <p><strong>Product:</strong> <span id="modal-product-name"></span></p>
        <p><strong>Reason:</strong> <span id="modal-reason"></span></p>
        <p><strong>Other:</strong> <span id="modal-other-reason"></span></p>
        <form id="refund-form">
            <input type="hidden" id="modal-refund-id">
            <label for="modal-refund-status">Status:</label>
            <select id="modal-refund-status" class="refund-select">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
            <button type="submit" class="btn-update">Update Refund</button>
        </form>
    </div>
</div>

<!-- Invoice Modal -->
<div id="modal-invoice" class="modal">
    <div class="modal-content" style="max-width:800px; width:90%; height:90%;">
        <span class="modal-close" onclick="closeInvoiceModal()">×</span>
        <h3>Invoice Preview</h3>
        <iframe id="invoice-frame" style="width:100%; height:80%; border:1px solid #e5e7eb; border-radius:6px;"></iframe>
        <div class="flex justify-end gap-2 mt-2">
            <button class="btn-update" onclick="printInvoice()">Print</button>
            <button class="btn-update" onclick="downloadInvoice()">Download PDF</button>
        </div>
    </div>
</div>

<script>
// Toggle order items
function toggleItems(orderId) {
    const el = document.getElementById(`items-${orderId}`);
    if (!el) return;
    el.style.display = el.style.display === 'block' ? 'none' : 'block';
}

// Refund modal
function openRefundModal(product, reason, other, refundId, status) {
    document.getElementById('modal-product-name').innerText = product;
    document.getElementById('modal-reason').innerText = reason;
    document.getElementById('modal-other-reason').innerText = other || '-';
    document.getElementById('modal-refund-id').value = refundId;
    document.getElementById('modal-refund-status').value = status;
    document.getElementById('modal-refund').style.display='flex';
}
function closeRefundModal(){document.getElementById('modal-refund').style.display='none';}
document.getElementById('refund-form').addEventListener('submit', e=>{
    e.preventDefault();
    const refundId = document.getElementById('modal-refund-id').value;
    const status = document.getElementById('modal-refund-status').value;
    if(!refundId || !status) return;
    if(!confirm(`Change refund status to "${status}"?`)) return;
    fetch('orders_tracker.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`update_refund=1&refund_id=${refundId}&refund_status=${status}`
    }).then(()=>{closeRefundModal(); loadRefundNotifs(); alert('Refund updated'); location.reload();});
});

// Update order status
function updateOrderStatus(e, orderId){
    e.preventDefault();
    const select = document.getElementById(`status-${orderId}`);
    if(!select) return;
    const status = select.value;
    if(!confirm(`Change order status to "${status}"?`)) return;
    fetch('orders_tracker.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`update_status=1&order_group_id=${orderId}&status=${status}`
    }).then(()=>{loadRefundNotifs(); alert('Order updated'); location.reload();});
}

// Refund notifications
const notifBtn = document.getElementById('notif-btn');
const notifDropdown = document.getElementById('notif-dropdown');
const notifCount = document.getElementById('notif-count');
notifBtn.addEventListener('click', e=>{e.stopPropagation(); notifDropdown.classList.toggle('hidden');});
document.addEventListener('click',()=>notifDropdown.classList.add('hidden'));
function loadRefundNotifs(){
    fetch('fetch_refund_notifs.php')
    .then(res=>res.json())
    .then(data=>{
        notifDropdown.innerHTML='';
        notifCount.textContent=data.length;
        if(!data.length){
            const empty=document.createElement('div');
            empty.className='px-4 py-2 text-gray-500';
            empty.textContent='No refund notifications';
            notifDropdown.appendChild(empty);
            return;
        }
        data.forEach(n=>{
            const div=document.createElement('div');
            div.className='block px-4 py-2 hover:bg-gray-100 cursor-pointer';
            div.textContent=`${n.customer_name} requested ${n.refund_count} refund(s) for Order #${n.order_group_id}`;
            div.onclick=()=>window.location.href=`orders_tracker.php?open_order=${n.order_group_id}`;
            notifDropdown.appendChild(div);
        });
    }).catch(err=>console.error(err));
}
loadRefundNotifs(); setInterval(loadRefundNotifs,10000);

// Auto open order
window.addEventListener('DOMContentLoaded',()=>{
    const params=new URLSearchParams(window.location.search);
    const orderId=params.get('open_order');
    if(!orderId) return;
    const row=document.getElementById(`items-${orderId}`);
    if(row){ row.style.display='block'; row.scrollIntoView({behavior:'smooth', block:'center'}); }
    history.replaceState(null,'',window.location.pathname);
});

// Invoice modal
let currentInvoiceOrderId=null;
function openInvoiceModal(orderId){currentInvoiceOrderId=orderId; document.getElementById('invoice-frame').src=`invoice.php?order_id=${orderId}`; document.getElementById('modal-invoice').style.display='flex';}
function closeInvoiceModal(){document.getElementById('invoice-frame').src=''; currentInvoiceOrderId=null; document.getElementById('modal-invoice').style.display='none';}
function printInvoice(){const iframe=document.getElementById('invoice-frame'); if(iframe?.contentWindow) iframe.contentWindow.print();}
function downloadInvoice(){if(!currentInvoiceOrderId)return; window.location.href=`invoice_pdf.php?order_id=${currentInvoiceOrderId}`;}
</script>

</body>
</html>
