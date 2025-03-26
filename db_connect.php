<?php
// Database credentials
$servername = "blitz.cs.niu.edu";
$username = "student";
$password = "student";
$database = "csci467";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
