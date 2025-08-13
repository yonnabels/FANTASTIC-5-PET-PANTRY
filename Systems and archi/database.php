<?php
// database.php - Database connection

$host = "localhost"; // XAMPP default
$dbname = "petpantry"; // Database name
$username = "root"; // XAMPP default username
$password = ""; // XAMPP default password (empty)

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
