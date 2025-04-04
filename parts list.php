<?php
// Include the database connection script
include 'db_connect.php';  // This will include the connection to both legacy and new databases

// Check if legacy connection is successful
if (!$legacy_pdo) {
    die("Connection to legacy database failed: " . $legacy_pdo->errorInfo());
}

// Query to fetch part details from the legacy product database (Read-Only)
$sql = "
    SELECT number, description, price, weight, pictureURL
    FROM parts
";

// Execute the query and fetch the results using PDO
try {
    $stmt = $legacy_pdo->query($sql);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error executing query: " . $e->getMessage());
}

// Fetch the inventory for each part
$inventory = [];
foreach ($parts as $part) {
    // Query the inventory for each part number using PDO
    $inventory_sql = "SELECT quantity FROM inventory WHERE part_number = ?";
    try {
        $inv_stmt = $new_pdo->prepare($inventory_sql);
        $inv_stmt->execute([$part['number']]);
        
        // If inventory exists, fetch it
        $quantity = $inv_stmt->fetchColumn();
        
        if ($quantity !== false) {
            $inventory[$part['number']] = $quantity;
        } else {
            // If no inventory record exists for the part, set quantity to 0
            $inventory[$part['number']] = 0;
        }
    } catch (PDOException $e) {
        die("Error executing inventory query: " . $e->getMessage());
    }
}

// Check if any parts are returned from the query
$no_parts_message = empty($parts) ? "No parts available." : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parts List and Cart Builder</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; }

        /* Container for the scrollable parts list */
        .parts-container {
            max-height: 600px;
            overflow-y: auto;  /* Enables vertical scroll if content exceeds height */
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
        }

        /* Styling for each part entry */
        .part {
            display: flex;  /* Use flex layout for horizontal arrangement */
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        /* Style the image for each part */
        .part img {
            width: 100px;
            height: auto;
            margin-right: 20px;
        }

        /* Allows text description area to grow and fill available space */
        .part div {
            flex-grow: 1;
        }

        /* Label spacing for quantity inputs */
        form label {
            margin-right: 5px;
        }

        /* Center the submit button */
        .submit-container {
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Available Parts</h1>

        <!-- Form to submit selected parts and quantities -->
        <form method="post" action="cart.php">

            <!-- Button to add all selected items to the cart -->
            <div class="submit-container">
                <input type="submit" value="Add Selected Items to Cart">
            </div>

            <!-- Scrollable container displaying all parts -->
            <div class="parts-container">
                <?php if ($no_parts_message) : ?>
                    <!-- Display a message if no parts are available -->
                    <p><?php echo $no_parts_message; ?></p>
                <?php else : ?>
                    <?php foreach ($parts as $row) : ?>
                        <div class="part">
                            <!-- Display the image associated with the part -->
                            <img src="<?php echo htmlspecialchars($row['pictureURL'] ?? ''); ?>" alt="Part Image">

                            <!-- Display part description, price, and weight -->
                            <div>
                                <strong><?php echo htmlspecialchars($row['description'] ?? ''); ?></strong><br>
                                Price: $<?php echo number_format($row['price'], 2); ?> | 
                                Weight: <?php echo htmlspecialchars($row['weight'] ?? ''); ?> lbs
                            </div>

                            <!-- Display inventory quantity for this part -->
                            <div>
                                Available Quantity: <?php echo $inventory[$row['number']] ?? '0'; ?>
                            </div>

                            <!-- Input for selecting quantity of each part -->
                            <label for="quantity_<?php echo htmlspecialchars($row['number'] ?? ''); ?>">Quantity:</label>
                            <input type="number" name="items[<?php echo htmlspecialchars($row['number'] ?? ''); ?>]"
                                   id="quantity_<?php echo htmlspecialchars($row['number'] ?? ''); ?>" min="0" value="0">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Hidden input to define the action for form processing -->
            <input type="hidden" name="action" value="bulk_add">
        </form>
    </div>
</body>
</html>

<?php
// Close the legacy database connection (PDO doesn't require explicit close, but it can be done)
$legacy_pdo = null;
?>
