<?php
// admin_users.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('database.php');

// --- Role-based Access Control ---
$userId = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (
    !$userId || 
    (!in_array('super_admin', $roles) && !in_array('users', $roles))
) {
    // Redirect unauthorized users
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}



// -----------------------------
// Pagination
// -----------------------------
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

// -----------------------------
// Search
// -----------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = $search ? "WHERE u.name LIKE :search OR u.email LIKE :search" : "";

// -----------------------------
// Handle POST actions
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['user_action'])) {
        $targetUserId = $_POST['user_id'];
        $action = $_POST['user_action'];
        switch ($action) {
            case 'block':
                $pdo->prepare("UPDATE users SET status='blocked' WHERE id=?")->execute([$targetUserId]);
                break;
            case 'unblock':
                $pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$targetUserId]);
                break;
            case 'delete':
                $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$targetUserId]);
                $pdo->prepare("DELETE FROM admin_roles WHERE user_id=?")->execute([$targetUserId]);
                break;
            case 'make_admin':
                $pdo->prepare("UPDATE users SET account_type='admin' WHERE id=?")->execute([$targetUserId]);
                break;
            case 'make_customer':
                $pdo->prepare("UPDATE users SET account_type='customer' WHERE id=?")->execute([$targetUserId]);
                $pdo->prepare("DELETE FROM admin_roles WHERE user_id=?")->execute([$targetUserId]);
                break;
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['save_roles']) && isset($_POST['update_roles'])) {
        foreach ($_POST['update_roles'] as $targetUserId => $roles) {
            $pdo->prepare("DELETE FROM admin_roles WHERE user_id=?")->execute([$targetUserId]);
            $roleStmt = $pdo->prepare("INSERT INTO admin_roles (user_id, role_name) VALUES (?, ?)");
            foreach ($roles as $role) {
                $roleStmt->execute([$targetUserId, $role]);
            }
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['create_admin'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $roles = $_POST['admin_roles'] ?? [];
        $newAccountType = 'admin';

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, account_type, status) 
                               VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$name, $email, $password, $newAccountType]);
        $newUserId = $pdo->lastInsertId();

        if (!empty($roles)) {
            $roleStmt = $pdo->prepare("INSERT INTO admin_roles (user_id, role_name) VALUES (?, ?)");
            foreach ($roles as $role) {
                $roleStmt->execute([$newUserId, $role]);
            }
        }
        header("Location: admin_users.php");
        exit;
    }
}

// -----------------------------
// Fetch users
// -----------------------------
$totalUsers = $pdo->prepare("SELECT COUNT(*) FROM users u $searchQuery");
if ($search) $totalUsers->bindValue(':search', "%$search%");
$totalUsers->execute();
$totalUsersCount = $totalUsers->fetchColumn();
$totalPages = ceil($totalUsersCount / $perPage);

$query = "SELECT u.id, u.name, u.email, u.account_type, u.status,
          GROUP_CONCAT(ar.role_name SEPARATOR ',') as roles
          FROM users u
          LEFT JOIN admin_roles ar ON u.id = ar.user_id
          $searchQuery
          GROUP BY u.id
          ORDER BY u.id DESC
          LIMIT $start, $perPage";
$stmt = $pdo->prepare($query);
if ($search) $stmt->bindValue(':search', "%$search%");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management | Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">
<div class="flex min-h-screen">
    <?php include('admin_navbar.php'); ?>
    <div class="flex-1 p-8">
        <header class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-3xl font-extrabold">User Management</h1>
    </div>
    <div class="text-right">
        <span class="text-gray-600">
            Welcome, <strong class="text-orange-600"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong>
        </span>
    </div>
</header>



       <!-- Create Admin Form -->
<section class="bg-white rounded-xl shadow-lg p-6 mb-6 max-w-3xl mx-auto">
    <h2 class="text-xl font-semibold mb-4 text-orange-500 border-b pb-2">Create New Admin</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <input type="text" name="name" placeholder="Name" required 
            class="p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none">
        <input type="email" name="email" placeholder="Email" required 
            class="p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none">
        <input type="password" name="password" placeholder="Password" required 
            class="p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none">
        <div class="flex flex-col gap-1 p-3 border border-gray-300 rounded-lg bg-gray-50">
            <label><input type="checkbox" name="admin_roles[]" value="inventory"> Inventory Admin</label>
            <label><input type="checkbox" name="admin_roles[]" value="pricing_stock"> Pricing & Stock Admin</label>
            <label><input type="checkbox" name="admin_roles[]" value="orders"> Orders Admin</label>
            <label><input type="checkbox" name="admin_roles[]" value="users"> User Admin</label>
            <label><input type="checkbox" name="admin_roles[]" value="super_admin"> Super Admin (Full Access)</label>
        </div>
        <button type="submit" name="create_admin" 
            class="md:col-span-2 px-6 py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition">
            Create Admin
        </button>
    </form>
</section>


        <!-- Search Bar -->
        <div class="flex justify-end mb-4">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" class="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none">
                <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">Search</button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto bg-white rounded-xl shadow-lg border border-gray-200">
            <table class="w-full table-auto text-sm border-collapse">
                <thead class="bg-orange-50 text-orange-600 uppercase text-left">
                    <tr>
                        <th class="px-4 py-3 border-b border-gray-200">ID</th>
                        <th class="px-4 py-3 border-b border-gray-200">Name</th>
                        <th class="px-4 py-3 border-b border-gray-200">Email</th>
                        <th class="px-4 py-3 border-b border-gray-200">Role</th>
                        <th class="px-4 py-3 border-b border-gray-200">Admin Roles</th>
                        <th class="px-4 py-3 border-b border-gray-200">Status</th>
                        <th class="px-4 py-3 border-b border-gray-200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($users as $user): ?>
                    <tr class="hover:bg-orange-50 transition">
                        <td class="px-3 py-2 border-b border-gray-200"><?php echo $user['id']; ?></td>
                        <td class="px-3 py-2 border-b border-gray-200 font-medium"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td class="px-3 py-2 border-b border-gray-200"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-3 py-2 border-b border-gray-200 capitalize"><?php echo $user['account_type']; ?></td>
                        <td class="px-3 py-2 border-b border-gray-200">
                            <?php if ($user['account_type'] === 'admin'): ?>
                                <form method="POST" class="flex flex-col gap-1">
                                    <?php
                                    $roleOptions = ['inventory', 'pricing_stock', 'orders', 'users', 'super_admin'];
                                    $userRoles = explode(',', $user['roles'] ?? '');
                                    ?>
                                    <?php foreach ($roleOptions as $role): ?>
                                        <label class="text-gray-700 text-sm"><input type="checkbox" name="update_roles[<?php echo $user['id']; ?>][]" value="<?php echo $role; ?>"
                                            <?php echo in_array($role, $userRoles) ? 'checked' : ''; ?>>
                                            <?php echo ucfirst(str_replace('_', ' ', $role)); ?></label>
                                    <?php endforeach; ?>
                                    <button type="submit" name="save_roles" class="mt-1 px-2 py-1 text-xs bg-orange-500 text-white rounded hover:bg-orange-600 transition">Save</button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 border-b border-gray-200">
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $user['status']=='active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 border-b border-gray-200 flex flex-wrap gap-1">
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <?php if ($user['status'] === 'active'): ?>
                                    <form method="POST"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="user_action" value="block">
                                        <button type="submit" class="text-yellow-600 text-xs underline">Block</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="user_action" value="unblock">
                                        <button type="submit" class="text-green-600 text-xs underline">Unblock</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="user_action" value="delete">
                                    <button type="submit" class="text-red-600 text-xs underline">Delete</button>
                                </form>
                                <?php if ($user['account_type'] === 'customer'): ?>
                                    <form method="POST" onsubmit="return confirm('Promote to Admin?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="user_action" value="make_admin">
                                        <button type="submit" class="text-orange-500 text-xs underline">Make Admin</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" onsubmit="return confirm('Demote to Customer?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="user_action" value="make_customer">
                                        <button type="submit" class="text-orange-500 text-xs underline">Make Customer</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-4 gap-2 flex-wrap">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 rounded <?php echo $i==$page ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-orange-400 transition"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>
</body>
</html>
