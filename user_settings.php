<?php
session_start();
require 'database.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login_and_creating_account_fixed.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT name, email, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch addresses
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update profile
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if ($name && $email) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $user_id])) {
            $_SESSION['success'] = "Profile updated successfully!";
            $user['name'] = $name;
            $user['email'] = $email;
        } else {
            $_SESSION['error'] = "Failed to update profile.";
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }

    header("Location: user_settings.php");
    exit;
}

// Update password
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Skip verification if user has no password (Google login)
    if ($user['password'] && !password_verify($current, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: user_settings.php");
        exit;
    }

    if ($new === $confirm && strlen($new) >= 6) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hash, $user_id])) {
            $_SESSION['success'] = "Password updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update password.";
        }
    } else {
        $_SESSION['error'] = "Passwords do not match or too short.";
    }

    header("Location: user_settings.php");
    exit;
}

// Add address
if (isset($_POST['add_address'])) {
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($full_name && $address) {
        if ($is_default) {
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, full_name, address, is_default, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$user_id, $full_name, $address, $is_default])) {
            $_SESSION['success'] = "Address added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add address.";
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }

    header("Location: user_settings.php");
    exit;
}

// Update address
if (isset($_POST['update_address'])) {
    $address_id = $_POST['address_id'];
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($full_name && $address) {
        if ($is_default) {
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        $stmt = $pdo->prepare("UPDATE user_addresses SET full_name = ?, address = ?, is_default = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$full_name, $address, $is_default, $address_id, $user_id])) {
            $_SESSION['success'] = "Address updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update address.";
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }

    header("Location: user_settings.php");
    exit;
}

// Delete address
if (isset($_POST['delete_address'])) {
    $address_id = $_POST['address_id'];
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$address_id, $user_id])) {
        $_SESSION['success'] = "Address deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete address.";
    }

    header("Location: user_settings.php");
    exit;
}

// Refresh addresses
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Settings</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
function setActive(sectionId) {
  document.querySelectorAll('main section').forEach(sec => sec.classList.add('hidden'));
  document.getElementById(sectionId).classList.remove('hidden');
  document.querySelectorAll('#sidebar a').forEach(a => a.classList.remove('bg-orange-50','text-orange-600','font-semibold'));
  document.querySelector(`#sidebar a[href="#${sectionId}"]`).classList.add('bg-orange-50','text-orange-600','font-semibold');
}

document.addEventListener("DOMContentLoaded", () => {
  setActive("profile"); 
  document.querySelectorAll('#sidebar a').forEach(a => {
    a.addEventListener('click', e => {
      e.preventDefault();
      setActive(a.getAttribute('href').substring(1));
    });
  });
});

function editAddress(id, fullName, address, isDefault) {
  document.getElementById('address_id').value = id;
  document.getElementById('full_name').value = fullName;
  document.getElementById('address').value = address;
  document.getElementById('is_default').checked = isDefault == 1;
  document.getElementById('saveBtn').classList.add('hidden');
  document.getElementById('updateBtn').classList.remove('hidden');
  document.getElementById('cancelBtn').classList.remove('hidden');
}

function resetForm() {
  document.getElementById('addressForm').reset();
  document.getElementById('address_id').value = "";
  document.getElementById('saveBtn').classList.remove('hidden');
  document.getElementById('updateBtn').classList.add('hidden');
  document.getElementById('cancelBtn').classList.add('hidden');
}

function togglePassword(fieldId) {
  const input = document.getElementById(fieldId);
  input.type = input.type === "password" ? "text" : "password";
}
</script>
</head>
<body class="bg-gray-100 pt-20 flex flex-col min-h-screen">
<?php include 'header.php'; ?>

<div class="flex-grow">
  <div class="max-w-7xl mx-auto p-6 flex space-x-8 items-start">

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white rounded-lg shadow p-5">
      <h2 class="text-xl font-semibold mb-5 text-gray-800">My Account</h2>
      <nav class="flex flex-col space-y-2">
        <a href="#profile" class="px-4 py-3 rounded hover:bg-orange-50 text-gray-700">👤 Profile</a>
        <a href="#password" class="px-4 py-3 rounded hover:bg-orange-50 text-gray-700">🔒 Change Password</a>
        <a href="#addresses" class="px-4 py-3 rounded hover:bg-orange-50 text-gray-700">🏠 Addresses</a>
      </nav>
    </aside>

    <!-- Content -->
    <main class="flex-1">

      <!-- Flash Messages -->
      <?php if(isset($_SESSION['success'])): ?>
        <div class="p-3 mb-5 bg-green-100 text-green-700 rounded"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>
      <?php if(isset($_SESSION['error'])): ?>
        <div class="p-3 mb-5 bg-red-100 text-red-700 rounded"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- Profile -->
      <section id="profile" class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-6 border-b pb-2">Profile</h2>
        <form method="post" class="space-y-5">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500">
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500">
          </div>
          <button type="submit" name="update_profile" class="px-5 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Save Changes</button>
        </form>
      </section>

<!-- Password -->
<section id="password" class="bg-white p-6 rounded-lg shadow hidden">
  <h2 class="text-xl font-semibold mb-6 border-b pb-2">Change Password</h2>
  <form method="post" class="space-y-5">

    <?php if ($user['password']): ?>
    <!-- Current Password only shown if user has a password (local login) -->
    <div>
      <label class="block text-sm text-gray-600 mb-1">Current Password</label>
      <div class="relative">
        <input type="password" name="current_password" id="current_password" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500 pr-10">
        <button type="button" onclick="togglePassword('current_password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>
    </div>
    <?php endif; ?>

    <!-- New Password -->
    <div>
      <label class="block text-sm text-gray-600 mb-1">New Password</label>
      <div class="relative">
        <input type="password" name="new_password" id="new_password" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500 pr-10">
        <button type="button" onclick="togglePassword('new_password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Confirm Password -->
    <div>
      <label class="block text-sm text-gray-600 mb-1">Confirm Password</label>
      <div class="relative">
        <input type="password" name="confirm_password" id="confirm_password" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500 pr-10">
        <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>
    </div>

    <button type="submit" name="update_password" class="px-5 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Update Password</button>
  </form>
</section>



      <!-- Addresses -->
      <section id="addresses" class="bg-white p-6 rounded-lg shadow hidden">
        <h2 class="text-xl font-semibold mb-6 border-b pb-2">Addresses</h2>
        <?php foreach($addresses as $addr): ?>
          <div class="flex justify-between items-center border-b py-3">
            <div>
              <p class="font-medium"><?= htmlspecialchars($addr['full_name']) ?> <?= $addr['is_default'] ? '<span class="ml-2 text-xs text-green-600">(Default)</span>' : '' ?></p>
              <p class="text-sm text-gray-600"><?= htmlspecialchars($addr['address']) ?></p>
            </div>
            <div class="flex space-x-3">
              <button type="button" onclick="editAddress(<?= $addr['id'] ?>, '<?= htmlspecialchars($addr['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($addr['address'], ENT_QUOTES) ?>', <?= $addr['is_default'] ?>)" class="text-blue-500 hover:underline text-sm">Edit</button>
              <form method="post">
                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                <button type="submit" name="delete_address" class="text-red-500 hover:underline text-sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

        <form method="post" class="space-y-5 mt-6" id="addressForm">
          <input type="hidden" name="address_id" id="address_id">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500">
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Address</label>
            <textarea name="address" id="address" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500"></textarea>
          </div>
          <label class="flex items-center space-x-2">
            <input type="checkbox" name="is_default" id="is_default" value="1" class="rounded text-orange-500">
            <span class="text-sm">Set as default</span>
          </label>
          <div class="flex space-x-3">
            <button type="submit" name="add_address" id="saveBtn" class="px-5 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Add Address</button>
            <button type="submit" name="update_address" id="updateBtn" class="hidden px-5 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Update Address</button>
            <button type="button" onclick="resetForm()" id="cancelBtn" class="hidden px-5 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
          </div>
        </form>
      </section>

    </main>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
