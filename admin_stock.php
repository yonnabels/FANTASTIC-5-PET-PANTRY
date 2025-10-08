<?php
session_start();
include('database.php');

// --- Role-based Access Control ---
$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    header("Location: Login_and_creating_account_fixed.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Only allow super_admin or pricing_stock role
if (!in_array('super_admin', $roles) && !in_array('pricing_stock', $roles)) {
    header("Location: Login_and_creating_account_fixed.php");
    exit;
}

// -----------------------------
// Handle Product Updates
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_product') {
    $id = (int)$_POST['product_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];

    $stmt = $pdo->prepare("UPDATE products SET price=?, stock=? WHERE id=?");
    $stmt->execute([$price, $stock, $id]);

    header("Location: admin_stock.php");
    exit;
}

// -----------------------------
// Handle Promo Add
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_promo') {
    $code = trim($_POST['code']);
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $applies_to = $_POST['applies_to'];
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO promos (code, discount_type, discount_value, applies_to, product_id) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$code, $discount_type, $discount_value, $applies_to, $product_id]);

    header("Location: admin_stock.php");
    exit;
}

// -----------------------------
// Handle Promo Delete
// -----------------------------
if (isset($_GET['delete_promo'])) {
    $id = (int)$_GET['delete_promo'];

    $stmt = $pdo->prepare("DELETE FROM promos WHERE id=?");
    $stmt->execute([$id]);

    header("Location: admin_stock.php");
    exit;
}

// -----------------------------
// Fetch Data
// -----------------------------
$products = $pdo->query("SELECT id, name, price, stock FROM products ORDER BY id DESC")
                ->fetchAll(PDO::FETCH_ASSOC);

$promos = $pdo->query("SELECT * FROM promos ORDER BY id DESC")
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Management | PetPantry+</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">

<div class="flex min-h-screen">
    <?php $section="stock"; include('admin_navbar.php'); ?>

    <main class="flex-1 p-8">
        <header class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold"> Stock & Pricing Dashboard</h1>
                
            </div>
            <div class="text-right">
                <span class="text-gray-600">Welcome, 
                    <strong class="text-orange-600">
                        <?php echo htmlspecialchars($_SESSION['name']); ?>
                    </strong>
                </span>
            </div>
        </header>

        <!-- Products Section -->
        <section class="mb-10">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-orange-100 to-orange-50 flex items-center justify-between">
                    <h2 class="text-lg font-semibold"> Products</h2>
                    <span class="text-sm text-gray-600"><?php echo count($products); ?> total</span>
                </div>

                <!-- Scrollable Product Inventory with Buttons -->
                <div class="relative">
                
                  <!-- Scrollable Table -->
                  <div id="productScroll" class="overflow-y-auto max-h-[700px] overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">ID</th>
                                <th class="px-4 py-2 text-left">Product Name</th>
                                <th class="px-4 py-2 text-left">Price</th>
                                <th class="px-4 py-2 text-left">Stock</th>
                                <th class="px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach($products as $product): ?>
                            <tr class="hover:bg-orange-50">
                                <td class="px-4 py-2"><?php echo $product['id']; ?></td>
                                <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="px-4 py-2">₱<?php echo number_format($product['price'], 2); ?></td>
                                <td class="px-4 py-2"><?php echo $product['stock']; ?></td>
                                <td class="px-4 py-2 text-center">
                                    <form method="POST" class="product-update flex flex-col md:flex-row gap-2 items-center justify-center">
                                        <input type="hidden" name="action" value="update_product">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                                        <input type="number" step="0.01" name="price" 
                                               value="<?php echo $product['price']; ?>" 
                                               class="border border-gray-300 rounded-lg p-1 text-xs w-24" required>
                                        <input type="number" name="stock" 
                                               value="<?php echo $product['stock']; ?>" 
                                               class="border border-gray-300 rounded-lg p-1 text-xs w-20" required>

                                        <button type="submit"
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold shadow">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

      <!-- Promo Codes Section -->
<section>
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-orange-100 to-orange-50 flex items-center justify-between">
            <h2 class="text-lg font-semibold"> Promo Codes</h2>
            <span class="text-sm text-gray-600"><?php echo count($promos); ?> active</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Code</th>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-left">Value</th>
                        <th class="px-4 py-2 text-left">Applies To</th>
                        <th class="px-4 py-2 text-left">Product</th>
                        <th class="px-4 py-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach($promos as $promo): ?>
                    <tr class="hover:bg-orange-50">
                        <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($promo['code']); ?></td>
                        <td class="px-4 py-2"><?php echo $promo['discount_type']; ?></td>
                        <td class="px-4 py-2">
                            <?php echo $promo['discount_type']==='percent' ? $promo['discount_value'].'%' : '₱'.$promo['discount_value']; ?>
                        </td>
                        <td class="px-4 py-2"><?php echo $promo['applies_to']; ?></td>
                        <td class="px-4 py-2">
                            <?php
                                if ($promo['product_id']) {
                                    $pstmt = $pdo->prepare("SELECT name FROM products WHERE id=?");
                                    $pstmt->execute([$promo['product_id']]);
                                    echo htmlspecialchars($pstmt->fetchColumn() ?: '—');
                                } else echo '—';
                            ?>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <a href="admin_stock.php?delete_promo=<?php echo $promo['id']; ?>" 
                               class="delete-promo bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-bold shadow">
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Promo Form -->
        <div class="p-6 border-t bg-gray-50">
            <form method="POST" class="promo-add grid grid-cols-1 md:grid-cols-6 gap-3">
                <input type="hidden" name="action" value="add_promo">
                <input type="text" name="code" placeholder="Promo Code" class="border border-gray-300 rounded-lg p-2 text-sm" required>
                <select name="discount_type" class="border border-gray-300 rounded-lg p-2 text-sm">
                    <option value="percent">Percent</option>
                    <option value="fixed">Fixed</option>
                </select>
                <input type="number" step="0.01" name="discount_value" placeholder="Value" class="border border-gray-300 rounded-lg p-2 text-sm" required>
                <select name="applies_to" class="border border-gray-300 rounded-lg p-2 text-sm">
                    <option value="global">Global</option>
                    <option value="product">Product Specific</option>
                </select>
                <select name="product_id" class="border border-gray-300 rounded-lg p-2 text-sm">
                    <option value="">-- Optional Product --</option>
                    <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow">
                    Add Promo
                </button>
            </form>
        </div>
    </div>
</section>

    </main>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-40"></div>
    <div class="bg-white rounded-lg shadow-xl z-10 w-full max-w-sm mx-4">
        <div class="px-6 py-4">
            <h4 id="confirmMessage" class="text-lg font-semibold text-gray-800">Are you sure?</h4>
            <div class="flex justify-end gap-2 mt-4">
                <button id="confirmCancel" class="px-4 py-2 rounded border">Cancel</button>
                <button id="confirmOk" class="px-4 py-2 rounded bg-red-600 text-white">Yes</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ---- Confirmation Modal Logic ----
    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmCancel = document.getElementById('confirmCancel');
    const confirmOk = document.getElementById('confirmOk');
    let confirmCallback = null;

    function showConfirm(message, callback) {
        confirmMessage.textContent = message;
        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
        confirmCallback = callback;
    }

    confirmCancel.addEventListener('click', () => {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
    });

    confirmOk.addEventListener('click', () => {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        if (confirmCallback) confirmCallback();
    });

    // ---- Hook into actions ----

    // Delete promo
    document.querySelectorAll('.delete-promo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            showConfirm("Delete this promo code permanently?", () => {
                window.location.href = url;
            });
        });
    });

    // Update product
    document.querySelectorAll('form.product-update').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm("Update this product's price/stock?", () => {
                form.submit();
            });
        });
    });

    // Add promo
    document.querySelectorAll('form.promo-add').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm("Add this new promo code?", () => {
                form.submit();
            });
        });
    });
</script>
</body>
</html>
