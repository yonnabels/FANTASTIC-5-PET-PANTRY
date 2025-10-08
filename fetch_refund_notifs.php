<?php
session_start();
include('database.php');

// Only pending refund requests
$sql = "SELECT og.id AS order_group_id, u.name AS customer_name, COUNT(r.id) AS refund_count
        FROM refund_requests r
        JOIN orders o ON r.order_id = o.id
        JOIN order_groups og ON o.order_group_id = og.id
        JOIN users u ON og.user_id = u.id
        WHERE r.status = 'pending'
        GROUP BY og.id, u.name
        ORDER BY og.created_at DESC";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
