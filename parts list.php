<?php
// Include the database connection with both legacy and new DBs
include 'db_connect.php';

// Step 1: Get parts from the legacy (blitz) database
try {
    $parts_stmt = $legacy_pdo->query("SELECT number, description, price, weight, pictureURL FROM parts");
    $parts = $parts_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching parts: " . $e->getMessage());
}

// Step 2: Get stock from the new (courses) database
try {
    $inventory_stmt = $new_pdo->query("SELECT part_number, stock FROM inventory");
    $inventory_data = $inventory_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // part_number => stock
} catch (PDOException $e) {
    die("Error fetching inventory: " . $e->getMessage());
}

// Step 3: Merge stock into parts array
foreach ($parts as &$part) {
    $num = $part['number'];
    $part['stock'] = $inventory_data[$num] ?? 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parts List and Cart Builder</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; }
        .parts-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
        }
        .part {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        .part img {
            width: 100px;
            height: auto;
            margin-right: 20px;
        }
        .part div {
            flex-grow: 1;
        }
        form label {
            margin-right: 5px;
        }
        .submit-container {
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Available Parts</h1>
    <form method="post" action="cart.php">
        <div class="submit-container">
            <input type="submit" value="Add Selected Items to Cart">
        </div>
        <div class="parts-container">
            <?php if (!empty($parts)) : ?>
                <?php foreach ($parts as $row) : ?>
                    <div class="part">
                        <img src="<?php echo htmlspecialchars($row['pictureURL'] ?? ''); ?>" alt="Part Image">
                        <div>
                            <strong><?php echo htmlspecialchars($row['description'] ?? ''); ?></strong><br>
                            Price: $<?php echo number_format($row['price'], 2); ?> |
                            Weight: <?php echo htmlspecialchars($row['weight'] ?? ''); ?> lbs |
                            Stock: <?php echo (int)($row['stock'] ?? 0); ?> available
                        </div>
                        <label for="quantity_<?php echo htmlspecialchars($row['number'] ?? ''); ?>">Quantity:</label>
                        <input type="number" name="items[<?php echo htmlspecialchars($row['number'] ?? ''); ?>]"
                               id="quantity_<?php echo htmlspecialchars($row['number'] ?? ''); ?>" min="0" value="0">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No parts available.</p>
            <?php endif; ?>
        </div>
        <input type="hidden" name="action" value="bulk_add">
    </form>
</div>
</body>
</html>
<?php
// Close both connections
$legacy_pdo = null;
$new_pdo = null;
?>

