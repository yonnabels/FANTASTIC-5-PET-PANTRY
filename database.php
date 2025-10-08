<?php
// database.php - Database connection for Hostinger

$host = "localhost"; // Hostinger database host (check hPanel for exact host)
$dbname = "u296524640_pet_pantry"; // Replace with your Hostinger database name
$username = "u296524640_pet_admin"; // Replace with your Hostinger database username
$password = "Petpantry123"; // Replace with your Hostinger database password

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>