<?php
include('database.php');

// Get top 5 selling products
$stmt = $pdo->query("
    SELECT p.name as product_name, SUM(o.quantity) as total_sold
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN order_groups g ON o.order_group_id = g.id
    WHERE g.status = 'completed'
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 5
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>
