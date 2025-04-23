<?php
include 'db_connect.php';

try {
    $parts_stmt = $legacy_pdo->query("SELECT number, description, price, weight, pictureURL FROM parts");
    $parts = $parts_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching parts: " . $e->getMessage());
}

try {
    $inventory_stmt = $new_pdo->query("SELECT part_number, quantity FROM inventory");
    $inventory_data = $inventory_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Error fetching inventory: " . $e->getMessage());
}

foreach ($parts as &$part) {
    $num = $part['number'];
    $part['quantity'] = $inventory_data[$num] ?? 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parts List and Cart Builder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: url('https://wallpapers.com/images/featured/light-blue-2iuzyh711jo9bmgo.jpg') no-repeat center center/cover;
            color: white;
        }

        .container {
            width: 80%;
            margin: auto;
            padding: 30px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            margin-top: 40px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .parts-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
            background-color: white;
            color: black;
            border-radius: 8px;
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

        input[type="number"] {
            width: 60px;
        }

        .submit-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .submit-container input[type="submit"],
        .submit-container button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: rgb(12, 132, 252);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .submit-container input[type="submit"]:hover,
        .submit-container button:hover {
            background-color: rgb(0, 109, 218);
        }

        .submit-container input[type="submit"]:active,
        .submit-container button:active {
            background-color: #004a99;
            transform: translateY(2px);
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
                            Stock: <?php echo (int)($row['quantity'] ?? 0); ?> available
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
    <div class="submit-container">
        <button type="button" onclick="window.location.href='menu.php'">Back to Menu</button>
    </div>
</div>
</body>
</html>
<?php
$legacy_pdo = null;
$new_pdo = null;
?>

