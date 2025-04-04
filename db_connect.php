<?php
// Legacy database (Read-Only: Parts data)
$legacy_host = "blitz.cs.niu.edu";
$legacy_port = 3306;
$legacy_dbname = "csci467";
$legacy_user = "student";
$legacy_pass = "student";

// New database (For inventory & orders)
$new_host = "courses";
$new_dbname = "z1946034";  // Change to your actual database name
$new_user = "z1946034";    // Change to your actual username
$new_pass = "2004Sep05";   // Change to your actual password

// Connect to Legacy Product Database using PDO
try {
    $legacy_pdo = new PDO("mysql:host=$legacy_host;dbname=$legacy_dbname;port=$legacy_port", $legacy_user, $legacy_pass);
    $legacy_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Legacy DB Connection failed: " . $e->getMessage());
}

// Connect to New Database using PDO
try {
    $new_pdo = new PDO("mysql:host=$new_host;dbname=$new_dbname", $new_user, $new_pass);
    $new_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("New DB Connection failed: " . $e->getMessage());
}

// Function to update inventory
function updateInventory($part_identifier, $quantity, $legacy_pdo, $new_pdo) {
    // Check if input is numeric (part_number) or a string (description)
    if (is_numeric($part_identifier)) {
        $sql = "SELECT number FROM parts WHERE number = ?";
    } else {
        $sql = "SELECT number FROM parts WHERE description = ?";
    }
    
    // Prepare the query and execute
    $stmt = $legacy_pdo->prepare($sql);
    $stmt->execute([$part_identifier]);
    
    $part_number = $stmt->fetchColumn();
    
    if ($part_number) {
        // Check if the part already exists in inventory
        $check_sql = "SELECT quantity FROM inventory WHERE part_number = ?";
        $check_stmt = $new_pdo->prepare($check_sql);
        $check_stmt->execute([$part_number]);

        if ($check_stmt->rowCount() > 0) {
            // If part exists, update the quantity
            $update_sql = "UPDATE inventory SET quantity = quantity + ? WHERE part_number = ?";
            $update_stmt = $new_pdo->prepare($update_sql);
            $update_stmt->execute([$quantity, $part_number]);
        } else {
            // If part does not exist, insert it
            $insert_sql = "INSERT INTO inventory (part_number, quantity) VALUES (?, ?)";
            $insert_stmt = $new_pdo->prepare($insert_sql);
            $insert_stmt->execute([$part_number, $quantity]);
        }
    } else {
        echo "Part not found in legacy database!<br>";
    }
}
?>
