<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(['status'=>'error','message'=>'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "u296524640_pet_admin", "Petpantry123", "u296524640_pet_pantry");
if ($conn->connect_error){
    echo json_encode(['status'=>'error','message'=>'Database connection failed']);
    exit;
}

$responseMsg = '';

// REMOVE ITEM
if(isset($_POST['remove_id'])){
    $cart_id = intval($_POST['remove_id']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    if($stmt->execute()){
        $responseMsg = 'Item removed from cart';
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to remove item']);
        exit;
    }
}

// UPDATE QUANTITY
if(isset($_POST['update_id'], $_POST['quantity'])){
    $cart_id = intval($_POST['update_id']);
    $qty = max(1, intval($_POST['quantity']));

    $stmt = $conn->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id=p.id WHERE c.id=? AND c.user_id=?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        $row = $res->fetch_assoc();
        if($qty > $row['stock']){
            $qty = $row['stock'];
            $responseMsg = 'Quantity adjusted to available stock';
        }
        $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii",$qty,$cart_id,$user_id);
        $stmt->execute();
        if(!$responseMsg) $responseMsg = 'Quantity updated';
    }
}

// ADD ITEM
if(isset($_POST['add_id'], $_POST['quantity'])){
    $product_id = intval($_POST['add_id']);
    $quantity = max(1, intval($_POST['quantity']));

    $stmt = $conn->prepare("SELECT stock FROM products WHERE id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows === 0){
        echo json_encode(['status'=>'error','message'=>'Product not found']);
        exit;
    }
    $product = $res->fetch_assoc();
    if($quantity > $product['stock']){
        $quantity = $product['stock'];
        $responseMsg = 'Quantity adjusted to available stock';
    }

    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
    $stmt->bind_param("ii",$user_id,$product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        $row = $res->fetch_assoc();
        $newQty = min($row['quantity'] + $quantity, $product['stock']);
        $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii",$newQty,$row['id'],$user_id);
        $stmt->execute();
        if(!$responseMsg) $responseMsg = 'Cart updated';
    } else {
        $stmt = $conn->prepare("INSERT INTO cart(user_id,product_id,quantity) VALUES(?,?,?)");
        $stmt->bind_param("iii",$user_id,$product_id,$quantity);
        $stmt->execute();
        if(!$responseMsg) $responseMsg = 'Item added to cart';
    }
}

// Return updated cart count
$countRes = $conn->prepare("SELECT SUM(quantity) AS cart_count FROM cart WHERE user_id=?");
$countRes->bind_param("i", $user_id);
$countRes->execute();
$countData = $countRes->get_result()->fetch_assoc();
$cart_count = intval($countData['cart_count'] ?? 0);

echo json_encode(['status'=>'success', 'cart_count'=>$cart_count, 'message'=>$responseMsg]);
$conn->close();
?>
