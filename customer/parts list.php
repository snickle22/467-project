<?php

// Correct database credentials
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

// SQL query to fetch parts
$sql = "SELECT number, description, price, weight, pictureURL FROM parts";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parts List</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; }
        .part { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .part img { width: 100px; height: auto; margin-right: 20px; }
        .part div { flex-grow: 1; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Available Parts</h1>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='part'>";
                echo "<img src='" . htmlspecialchars($row["pictureURL"]) . "' alt='Part Image'>";
                echo "<div><strong>" . htmlspecialchars($row["description"]) . "</strong><br>";
                echo "Price: $" . number_format($row["price"], 2) . " | Weight: " . $row["weight"] . " lbs</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No parts available.</p>";
        }
        ?>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
