<?php
// admin_access.php
session_start();
include('database.php');

// Check if user is logged in and an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// Helper to check role access
function hasRole($userId, $role, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_roles WHERE user_id=? AND (role_name=? OR role_name='super_admin')");
    $stmt->execute([$userId, $role]);
    return $stmt->fetchColumn() > 0;
}

// Define the current page's required role
// Example: set $requiredRole = 'inventory'; on inventory page
if (!isset($requiredRole)) $requiredRole = null;

// If the page has a required role and user doesn't have it, redirect
if ($requiredRole && !hasRole($_SESSION['user_id'], $requiredRole, $pdo)) {
    header("Location: adminpanel.php");
    exit;
}
?>
