<?php
session_start();
include('database.php');

// Validate order ID
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    exit("Invalid order ID");
}

$orderId = intval($_GET['order_id']);

// Fetch order group with user info
$stmt = $pdo->prepare("
    SELECT og.*, u.name AS username, u.email
    FROM order_groups og
    JOIN users u ON og.user_id = u.id
    WHERE og.id = ?
");
$stmt->execute([$orderId]);
$orderGroup = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orderGroup) exit("Order not found");

// Access control: allow admin OR the user who owns this order
if (!isset($_SESSION['user_id']) || 
    ($_SESSION['account_type'] !== 'admin' && $_SESSION['user_id'] != $orderGroup['user_id'])) {
    exit("You do not have permission to view this invoice.");
}

// Fetch order items
$stmt2 = $pdo->prepare("
    SELECT o.*, p.name AS product_name, p.image
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.order_group_id = ?
");
$stmt2->execute([$orderId]);
$orderItems = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($orderItems as $item) $subtotal += $item['price'] * $item['quantity'];
$shippingFee = 50;
$total = $subtotal + $shippingFee;

// Include PHP QR Code library
require_once __DIR__ . '/phpqrcode/qrlib.php';

// Generate QR code as base64 image
ob_start();
$invoiceUrl = "https://petpantry.space/invoice.php?order_id=$orderId";
QRcode::png($invoiceUrl, null, QR_ECLEVEL_L, 3);
$imageData = ob_get_contents();
ob_end_clean();
$qrCodeBase64 = 'data:image/png;base64,' . base64_encode($imageData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?=$orderId?></title>
<style>
body { font-family: 'Arial', sans-serif; margin:0; padding:20px; background:#f4f6f8; }
.invoice-container { width:100%; max-width:900px; margin:0 auto; background:#fff; padding:20px; border-radius:8px; }
.invoice-header { display:flex; align-items:center; gap:15px; margin-bottom:20px; }
.invoice-header img { width:60px; height:60px; border-radius:50%; border:2px solid #FF8C00; object-fit:cover; }
.invoice-header span { font-weight:bold; font-size:1.75rem; color:#FF8C00; }
.invoice-title { text-align:center; font-size:1.5rem; font-weight:600; margin:10px 0; }
.invoice-flex { width:100%; display:table; margin-bottom:20px; }
.qr-code { display:table-cell; width:130px; vertical-align:top; text-align:center; }
.invoice-info { display:table-cell; padding-left:20px; vertical-align:top; }
table { width:100%; border-collapse:collapse; margin-bottom:15px; }
th, td { padding:8px; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
th { background:#f9fafb; text-align:left; }
td img { width:40px; height:40px; object-fit:cover; border-radius:4px; margin-right:5px; vertical-align:middle; }
.total-row { font-weight:600; text-align:right; margin-top:15px; }
</style>
</head>
<body>

<div class="invoice-container">

    <div class="invoice-header">
        <img src="images/logo.png" alt="PetPantry+ Logo">
        <span>PetPantry+</span>
    </div>

    <div class="invoice-title">Invoice / Packing Slip</div>

    <!-- QR + Info -->
    <div class="invoice-flex">
        <div class="qr-code">
            <img src="<?=$qrCodeBase64?>" alt="QR Code" style="width:120px; height:120px;"><br>
            Scan to view online
        </div>
        <div class="invoice-info">
            <div><strong>Order ID:</strong> <?=$orderId?></div>
            <div><strong>Order Date:</strong> <?=date("F j, Y g:i A", strtotime($orderGroup['created_at']))?></div>
            <div><strong>Customer:</strong> <?=htmlspecialchars($orderGroup['username'])?></div>
            <div><strong>Email:</strong> <?=htmlspecialchars($orderGroup['email'])?></div>
            <div><strong>Shipping Address:</strong> <?=htmlspecialchars($orderGroup['address'] ?? 'N/A')?></div>
            <div><strong>Payment Method:</strong> <?=htmlspecialchars($orderGroup['payment_method'] ?? 'Cash')?></div>
        </div>
    </div>

    <h4>Items</h4>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th style="text-align:right;">Price</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orderItems as $item):
            $itemTotal = $item['price'] * $item['quantity'];
        ?>
            <tr>
                <td>
                    <img src="<?=htmlspecialchars($item['image'])?>" alt="Product">
                    <?=htmlspecialchars($item['product_name'])?>
                </td>
                <td style="text-align:right;">₱<?=number_format($item['price'],2)?></td>
                <td style="text-align:center;"><?=$item['quantity']?></td>
                <td style="text-align:right;">₱<?=number_format($itemTotal,2)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-row">
        <div>Subtotal: ₱<?=number_format($subtotal,2)?></div>
        <div>Shipping: ₱<?=number_format($shippingFee,2)?></div>
        <div>Total: ₱<?=number_format($total,2)?></div>
    </div>

</div>

</body>
</html>
