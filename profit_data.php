<?php
header('Content-Type: application/json');
include('database.php');

// Get selected range from query string (default: weekly)
$range = $_GET['range'] ?? 'weekly';

switch ($range) {
    case 'today':
        $sql = "
            SELECT 
                DATE(og.created_at) AS date,
                SUM(o.price * o.quantity) AS profit
            FROM orders o
            INNER JOIN order_groups og ON o.order_group_id = og.id
            WHERE og.status = 'Completed'
              AND DATE(og.created_at) BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE()
            GROUP BY DATE(og.created_at)
            ORDER BY DATE(og.created_at)
        ";
        break;

    case 'yesterday':
        $sql = "
            SELECT 
                DATE(og.created_at) AS date,
                SUM(o.price * o.quantity) AS profit
            FROM orders o
            INNER JOIN order_groups og ON o.order_group_id = og.id
            WHERE og.status = 'Completed'
              AND DATE(og.created_at) BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE()
            GROUP BY DATE(og.created_at)
            ORDER BY DATE(og.created_at)
        ";
        break;

    case 'weekly':
        $sql = "
            SELECT 
                DATE(og.created_at) AS date,
                SUM(o.price * o.quantity) AS profit
            FROM orders o
            INNER JOIN order_groups og ON o.order_group_id = og.id
            WHERE og.status = 'Completed'
              AND og.created_at >= CURDATE() - INTERVAL 7 DAY
            GROUP BY DATE(og.created_at)
            ORDER BY DATE(og.created_at)
        ";
        break;

    case 'monthly':
        $sql = "
            SELECT 
                DATE(og.created_at) AS date,
                SUM(o.price * o.quantity) AS profit
            FROM orders o
            INNER JOIN order_groups og ON o.order_group_id = og.id
            WHERE og.status = 'Completed'
              AND og.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
            GROUP BY DATE(og.created_at)
            ORDER BY DATE(og.created_at)
        ";
        break;

    case 'annually':
        $sql = "
            SELECT 
                DATE_FORMAT(og.created_at, '%Y-%m') AS date,
                SUM(o.price * o.quantity) AS profit
            FROM orders o
            INNER JOIN order_groups og ON o.order_group_id = og.id
            WHERE og.status = 'Completed'
              AND og.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            GROUP BY DATE_FORMAT(og.created_at, '%Y-%m')
            ORDER BY DATE_FORMAT(og.created_at, '%Y-%m')
        ";
        break;

    default: // fallback to weekly
        $sql = "
            SELECT 
                DATE(og.created_at) AS date,
                SUM(o.price * o.quantity) AS profit
            FROM orders o
            INNER JOIN order_groups og ON o.order_group_id = og.id
            WHERE og.status = 'Completed'
              AND og.created_at >= CURDATE() - INTERVAL 7 DAY
            GROUP BY DATE(og.created_at)
            ORDER BY DATE(og.created_at)
        ";
        break;
}

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Always return JSON, even if empty
echo json_encode($data ?: []);
