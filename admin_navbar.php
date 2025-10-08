<?php
// admin_navbar.php
$current = basename($_SERVER['PHP_SELF']); 

// Helper to check role access
function hasRole($userId, $role, $pdo) {
    // super_admin automatically has all roles
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_roles WHERE user_id=? AND (role_name=? OR role_name='super_admin')");
    $stmt->execute([$userId, $role]);
    return $stmt->fetchColumn() > 0;
}

// Get user role once for efficiency
$userId = $_SESSION['user_id'] ?? 0;
$isSuperAdmin = hasRole($userId, 'super_admin', $pdo);
?>

<nav class="w-64 bg-white shadow-lg rounded-lg p-6">
  <h2 class="text-2xl font-bold text-orange-600 mb-6">Admin Dashboard</h2>
  <ul class="space-y-3 text-sm">
    <li>
      <a href="adminpanel.php" 
         class="flex items-center gap-2 font-medium px-3 py-2 rounded transition-colors duration-200 <?php echo $current === 'adminpanel.php' ? 'bg-orange-100 text-orange-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
        📊 Overview
      </a>
    </li>

    <?php if($isSuperAdmin || hasRole($userId, 'inventory', $pdo)): ?>
    <li>
      <a href="admin_inventory.php" 
         class="flex items-center gap-2 font-medium px-3 py-2 rounded transition-colors duration-200 <?php echo $current === 'admin_inventory.php' ? 'bg-orange-100 text-orange-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
        📦 Inventory Management
      </a>
    </li>
    <?php endif; ?>

    <?php if($isSuperAdmin || hasRole($userId, 'pricing_stock', $pdo)): ?>
    <li>
      <a href="admin_stock.php" 
         class="flex items-center gap-2 font-medium px-3 py-2 rounded transition-colors duration-200 <?php echo $current === 'admin_stock.php' ? 'bg-orange-100 text-orange-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
        💰 Pricing & Stock
      </a>
    </li>
    <?php endif; ?>

    <?php if($isSuperAdmin || hasRole($userId, 'orders', $pdo)): ?>
    <li>
      <a href="orders_tracker.php" 
         class="flex items-center gap-2 font-medium px-3 py-2 rounded transition-colors duration-200 <?php echo $current === 'orders_tracker.php' ? 'bg-orange-100 text-orange-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
        🛒 Orders
      </a>
    </li>
    <?php endif; ?>

    <?php if($isSuperAdmin || hasRole($userId, 'users', $pdo)): ?>
    <li>
      <a href="admin_users.php" 
         class="flex items-center gap-2 font-medium px-3 py-2 rounded transition-colors duration-200 <?php echo $current === 'admin_users.php' ? 'bg-orange-100 text-orange-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
        👤 User Management
      </a>
    </li>
    <?php endif; ?>

    <li>
      <a href="Login_and_creating_account_fixed.php?logout=1" 
         class="flex items-center gap-2 text-red-600 font-semibold px-3 py-2 rounded hover:bg-red-100 transition-colors duration-200">
        🚪 Logout
      </a>
    </li>
  </ul>
</nav>
