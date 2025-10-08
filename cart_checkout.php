<?php
session_start();
if(!isset($_SESSION['user_id'])){
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

// ✅ Validate POST
if(!isset($_POST['cart_ids']) || !isset($_POST['payment_method'])) {
    echo json_encode(['status'=>'error','message'=>'Missing data']);
    exit;
}

$cart_ids = json_decode($_POST['cart_ids'], true);
$payment_method = $_POST['payment_method'];
$paypal_order_id = $_POST['paypal_order_id'] ?? null; // ✅ store PayPal order ID

if(!is_array($cart_ids) || empty($cart_ids)){
    echo json_encode(['status'=>'error','message'=>'Invalid cart items']);
    exit;
}

// ✅ Allow only Cash or PayPal
if(!in_array($payment_method, ['Cash','PayPal'])){
    echo json_encode(['status'=>'error','message'=>'Invalid payment method']);
    exit;
}

// ✅ Database connection
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if($conn->connect_error) die(json_encode(['status'=>'error','message'=>'DB connection failed']));

$address = "";

// ✅ Existing address
if(!empty($_POST['address_id'])) {
    $addr_id = intval($_POST['address_id']);
    $stmt = $conn->prepare("SELECT address FROM user_addresses WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param("ii", $addr_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        $address = $row['address'];
    }
    $stmt->close();
}

// ✅ New address
if(empty($address) && !empty(trim($_POST['new_address'] ?? ''))) {
    $newAddr = trim($_POST['new_address']);
    $fullName = trim($_POST['new_fullName'] ?? "");

    // Save only if not duplicate
    $stmtAddr = $conn->prepare("SELECT id FROM user_addresses WHERE user_id=? AND address=? LIMIT 1");
    $stmtAddr->bind_param("is", $user_id, $newAddr);
    $stmtAddr->execute();
    $res = $stmtAddr->get_result();

    if ($res->num_rows === 0) {
        $stmtInsertAddr = $conn->prepare("INSERT INTO user_addresses (user_id, full_name, address, is_default) VALUES (?, ?, ?, 0)");
        $stmtInsertAddr->bind_param("iss", $user_id, $fullName, $newAddr);
        $stmtInsertAddr->execute();
        $stmtInsertAddr->close();
    }
    $stmtAddr->close();
    $address = $newAddr;
}

// ✅ Must have valid address
if(empty($address)){
    echo json_encode(['status'=>'error','message'=>'No valid address provided']);
    exit;
}

// ✅ Step 1: Insert one main order group
$stmtGroup = $conn->prepare("INSERT INTO order_groups (user_id, status, address, payment_method, paypal_order_id, created_at) 
                             VALUES (?, 'Pending', ?, ?, ?, NOW())");
$stmtGroup->bind_param("isss",$user_id,$address,$payment_method,$paypal_order_id);
$stmtGroup->execute();
$order_group_id = $stmtGroup->insert_id;
$stmtGroup->close();

if(!$order_group_id){
    echo json_encode(['status'=>'error','message'=>'Failed to create order']);
    exit;
}

// ✅ Step 2: Loop through cart items → Insert into orders
foreach($cart_ids as $cart_id){
    $stmt = $conn->prepare("SELECT product_id, quantity FROM cart WHERE id=? AND user_id=?");
    $stmt->bind_param("ii",$cart_id,$user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$item) continue;

    $product_id = $item['product_id'];
    $quantity = $item['quantity'];

    // Get product price + stock
    $stmtPrice = $conn->prepare("SELECT price, stock FROM products WHERE id=?");
    $stmtPrice->bind_param("i",$product_id);
    $stmtPrice->execute();
    $prod = $stmtPrice->get_result()->fetch_assoc();
    $stmtPrice->close();

    if(!$prod || $prod['stock'] < $quantity) continue;

    $price = $prod['price'];

    // Reduce stock
    $stmt2 = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?");
    $stmt2->bind_param("iii",$quantity,$product_id,$quantity);
    $stmt2->execute();
    $affected = $stmt2->affected_rows;
    $stmt2->close();

    if($affected > 0){ 
        // ✅ Insert order item (child of order group)
        $stmt3 = $conn->prepare("INSERT INTO orders (order_group_id, product_id, quantity, price) 
                                 VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("iiid",$order_group_id,$product_id,$quantity,$price);
        $stmt3->execute();
        $stmt3->close();

        // ✅ Delete from cart
        $stmt4 = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
        $stmt4->bind_param("ii",$cart_id,$user_id);
        $stmt4->execute();
        $stmt4->close();
    }
}
// --- Add Notification for the customer ---
$stmtNotif = $conn->prepare("
    SELECT p.name, o.quantity
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.order_group_id = ?
    LIMIT 3
");
$stmtNotif->bind_param("i", $order_group_id);
$stmtNotif->execute();
$resNotif = $stmtNotif->get_result();
$items = $resNotif->fetch_all(MYSQLI_ASSOC);
$stmtNotif->close();

$itemNames = array_map(function($i){
    return $i['quantity']."x ".$i['name'];
}, $items);

$message = "You ordered: " . implode(", ", $itemNames);
if(count($items) >= 3){
    $message .= " and more...";
}

$stmtInsertNotif = $conn->prepare("
    INSERT INTO notifications (user_id, order_group_id, account_type, message, type, created_at)
    VALUES (?, ?, 'customer', ?, 'order', NOW())
");
$stmtInsertNotif->bind_param("iis", $user_id, $order_group_id, $message);
$stmtInsertNotif->execute();
$stmtInsertNotif->close();

// ✅ Return success
echo json_encode(['status'=>'success','message'=>'Checkout complete','order_group_id'=>$order_group_id]);
?>
