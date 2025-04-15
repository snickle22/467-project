<?php
// 1. Connect to the database using the updated db_connect.php
include 'db_connect.php'; // Provides $legacy_pdo and $new_pdo

// 2. Handle POST request to add stock quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update']) && is_array($_POST['update'])) {
        $stmt = $new_pdo->prepare("UPDATE inventory SET quantity = quantity + :add_stock WHERE part_number = :number");
        foreach ($_POST['update'] as $number => $add_stock) {
            $number = filter_var($number, FILTER_VALIDATE_INT);
            $add_stock = filter_var($add_stock, FILTER_VALIDATE_INT);
            if ($number !== false && $add_stock !== false && $add_stock > 0) {
                $stmt->execute([':add_stock' => $add_stock, ':number' => $number]);
            }
        }
    }
    header("Location: warehouse_receiving.php?updated=1");
    exit;
}

// 3. Handle optional search from GET request
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$inventoryItems = [];

if ($search !== '') {
    if (ctype_digit($search)) {
        $stmt = $new_pdo->prepare("SELECT * FROM inventory WHERE part_number = :search");
        $stmt->bindValue(':search', (int)$search, PDO::PARAM_INT);
    } else {
        $stmt = $new_pdo->prepare("SELECT * FROM inventory WHERE description LIKE :like_search");
        $stmt->bindValue(':like_search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $new_pdo->query("SELECT * FROM inventory");
    $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Warehouse Inventory</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .search-form { margin-bottom: 15px; text-align: center; }
        .scroll-box {
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .scroll-box table {
            width: 80%;
            margin: auto;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table, th, td { border: 1px solid #ccc; }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th:nth-child(1), td:nth-child(1) { width: 8%; }
        th:nth-child(2), td:nth-child(2) { width: 64%; word-wrap: break-word; }
        th:nth-child(3), td:nth-child(3),
        th:nth-child(4), td:nth-child(4) { width: 14%; }

        input[type="number"] {
            width: 50px;
            text-align: center;
            -moz-appearance: textfield;
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .success { color: green; text-align: center; }
        .centered-button { text-align: center; margin-top: 10px; }
        .reset-link { margin-left: 10px; font-size: 0.9em; }

        .qty-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .qty-button {
            width: 30px;
            height: 30px;
            font-size: 18px;
            font-weight: bold;
            border: 1px solid #ccc;
            background: #f4f4f4;
            cursor: pointer;
        }
    </style>
    <script>
        function changeQty(inputId, delta) {
            const input = document.getElementById(inputId);
            let value = parseInt(input.value) || 0;
            value = Math.max(0, value + delta);
            input.value = value;
        }
    </script>
</head>
<body>
    <h1 style="text-align:center;">Warehouse Inventory</h1>
    <?php if (isset($_GET['updated'])): ?>
        <p class="success">Inventory updated successfully.</p>
    <?php endif; ?>

    <div class="search-form">
        <form method="GET" action="warehouse_receiving.php">
            <label for="search">Search by Part Number or Description:</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="submit" value="Search">
            <a href="warehouse_receiving.php" class="reset-link">Reset</a>
        </form>
        <br />
        <div class="centered-button">
            <input type="submit" form="update-form" value="Update Inventory">
        </div>
    </div>

    <form method="POST" action="warehouse_receiving.php" id="update-form">
        <div class="scroll-box">
            <table>
                <thead>
                    <tr>
                        <th>Part #</th>
                        <th>Description</th>
                        <th>Stock</th>
                        <th>Add New Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventoryItems)): ?>
                        <tr><td colspan="4">No matching parts found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventoryItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['part_number']) ?></td>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td>
                                <div class="qty-wrapper">
                                    <button type="button" class="qty-button" onclick="changeQty('qty_<?= $item['part_number'] ?>', -1)">-</button>
                                    <input type="number" name="update[<?= htmlspecialchars($item['part_number']) ?>]" id="qty_<?= $item['part_number'] ?>" min="0" value="">
                                    <button type="button" class="qty-button" onclick="changeQty('qty_<?= $item['part_number'] ?>', 1)">+</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="centered-button">
            <input type="submit" value="Update Inventory">
        </div>
    </form>
</body>
</html>
