<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('database.php');

// --- Role-based Access Control ---
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$userId || (!in_array('super_admin', $roles) && !in_array('admin_dashboard', $roles))) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// --- Fetch summary stats ---
// Users
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type='admin'")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type='customer'")->fetchColumn();

// Orders
$totalOrders = $pdo->query("SELECT COUNT(*) FROM order_groups")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='pending'")->fetchColumn();
$shippingOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='shipping'")->fetchColumn();
$completedOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='completed'")->fetchColumn();
$cancelledOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='cancelled'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard | PetPantry+</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">

<div class="flex min-h-screen">
  <?php include('admin_navbar.php'); ?>

  <div class="flex-1 p-8">
   <header class="flex justify-between items-center mb-6 py-4 px-2 md:px-0 border-b border-gray-200">
  <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
  <span class="text-gray-600">Welcome, <strong class="text-orange-500"><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
</header>


    <!-- Summary Cards -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white p-4 rounded shadow text-center">
        <h3 class="text-lg font-semibold">Total Users</h3>
        <p class="text-2xl text-blue-600 font-bold"><?php echo $totalUsers; ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow text-center">
        <h3 class="text-lg font-semibold">Customers</h3>
        <p class="text-2xl text-green-600 font-bold"><?php echo $totalCustomers; ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow text-center">
        <h3 class="text-lg font-semibold">Admins</h3>
        <p class="text-2xl text-purple-600 font-bold"><?php echo $totalAdmins; ?></p>
      </div>
    </section>

    <!-- Charts Section -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <!-- Orders Summary -->
      <div class="bg-white p-6 rounded shadow text-center">
        <h3 class="text-lg font-semibold mb-4">Orders Summary</h3>
        <canvas id="ordersChart"></canvas>
      </div>

      <!-- Total Profit (Line Chart with Range Filter) -->
      <div class="bg-white p-6 rounded shadow text-center">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Total Profit</h3>
          <select id="profitRange" class="border rounded px-2 py-1 text-sm">
            <option value="today">Today</option>
            <option value="yesterday">Yesterday</option>
            <option value="weekly" selected>Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="annually">Annually</option>
          </select>
        </div>
        <div class="w-full h-64 mt-6">
          <canvas id="profitChart"></canvas>
        </div>
      </div>

      <!-- Top Seller -->
      <div class="bg-white p-6 rounded shadow text-center">
        <h3 class="text-lg font-semibold mb-4">Top Seller</h3>
        <canvas id="topSellerChart"></canvas>
      </div>
    </section>

    <div class="text-center mb-8">
      <button onclick="refreshCharts()" 
              class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700">
        Refresh
      </button>
    </div>

  </div>
</div>

<script>
let ordersChart, profitChart, topSellerChart;

function loadOrdersSummary() {
  const ctx = document.getElementById('ordersChart').getContext('2d');
  if (ordersChart) ordersChart.destroy();
  ordersChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Pending', 'Shipping', 'Completed', 'Cancelled'],
      datasets: [{
        data: [
          <?php echo $pendingOrders; ?>,
          <?php echo $shippingOrders; ?>,
          <?php echo $completedOrders; ?>,
          <?php echo $cancelledOrders; ?>
        ],
        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
        borderWidth: 1
      }]
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: true,
      aspectRatio: 1,
      plugins: { legend: { position: 'bottom' } } 
    }
  });
}

function loadTotalProfit() {
  const range = document.getElementById('profitRange').value;
  fetch('profit_data.php?range=' + range)
    .then(res => res.json())
    .then(data => {
      if (profitChart) profitChart.destroy();

      const labels = data.map(item => item.date);
      const profits = data.map(item => item.profit);

      profitChart = new Chart(document.getElementById('profitChart'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Total Profit (₱)',
            data: profits,
            borderColor: '#4CAF50',
            backgroundColor: 'rgba(76, 175, 80, 0.2)',
            tension: 0.3,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#4CAF50'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: true, position: 'top' }
          },
          scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Profit (₱)' } },
            x: { title: { display: true, text: range === 'annually' ? 'Month' : 'Date' } }
          }
        }
      });
    });
}

function loadTopSeller() {
  fetch('top_seller.php')
    .then(res => res.json())
    .then(data => {
      if (topSellerChart) topSellerChart.destroy();
      topSellerChart = new Chart(document.getElementById('topSellerChart'), {
        type: 'doughnut',
        data: { 
          labels: data.map(item => item.product_name), 
          datasets: [{ 
            data: data.map(item => item.total_sold), 
            backgroundColor: ['#FF5733', '#33FF57', '#3357FF', '#F1C40F', '#8E44AD'] 
          }] 
        },
        options: { 
          responsive: true, 
          plugins: { 
            legend: { 
              position: 'bottom', 
              labels: { usePointStyle: true, padding: 20, font: { size: 12 } } 
            } 
          } 
        }
      });
    });
}

function refreshCharts() {
  loadOrdersSummary();
  loadTotalProfit();
  loadTopSeller();
}

// Re-fetch Total Profit when dropdown changes
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('profitRange').addEventListener('change', loadTotalProfit);
});

// Load everything on page load
refreshCharts();
</script>

</body>
</html>
    