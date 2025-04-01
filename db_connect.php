<?php
// Database credentials
$servername = "blitz.cs.niu.edu";
$username = "student";
$password = "student";
$database = "csci467";

try {
    // Create PDO connection with charset and error handling
    $pdo = new PDO("mysql:host=$servername;dbname=$database;charset=utf8mb4", $username, $password);

    // Set PDO error mode to throw exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, display error and exit
    die("Connection failed: " . $e->getMessage());
}
?>

