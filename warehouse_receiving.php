<?php
include 'db_connect.php';

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
    <title>Warehouse Receiving</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 40px 20px;
            background: url('https://wallpapers.com/images/featured/light-blue-2iuzyh711jo9bmgo.jpg') no-repeat center center/cover;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            background: rgba(0, 0, 0, 0.6);
            padding: 30px;
            border-radius: 15px;
            max-width: 1100px;
            width: 100%;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
        }

        a {
            color: #cce6ff;
            font-weight: bold;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .search-form {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-form input[type="text"] {
            padding: 8px;
            width: 250px;
            border-radius: 5px;
            border: none;
        }

        .scroll-box {
            max-height: 65vh;
            overflow-y: auto;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            color: black;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }

        th {
            background-color: #f2f2f2;
        }

        input[type="number"] {
            width: 60px;
            text-align: center;
        }

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
            border-radius: 5px;
        }

        .centered-button {
            text-align: center;
            margin: 20px 0;
        }

        .menu-button {
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
            color: #fff;
            background-color: rgb(12, 132, 252);
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px rgba(0, 0, 0, 0.2);
            transition: background 0.2s ease-in-out;
        }

        .menu-button:hover {
            background-color: rgb(0, 109, 218);
        }

        .menu-button:active {
            background-color: #004a99;
        }

        .success {
            color: #90ee90;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .reset-link {
            margin-left: 10px;
            font-size: 0.9em;
            color: #ddd;
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
    <div class="container">
        <a href="menu.php">Home</a>
        <h1>Warehouse Receiving</h1>

        <?php if (isset($_GET['updated'])): ?>
            <p class="success">Inventory updated successfully.</p>
        <?php endif; ?>

        <div class="search-form">
            <form method="GET" action="warehouse_receiving.php">
                <label for="search">Search by Part # or Description:</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="submit" value="Search" class="menu-button">
                <a href="warehouse_receiving.php" class="reset-link">Reset</a>
            </form>
        </div>

        <form method="POST" action="warehouse_receiving.php">
            <div class="centered-button">
                <input type="submit" value="Update Inventory" class="menu-button">
            </div>
            <div class="scroll-box">
                <table>
                    <thead>
                        <tr>
                            <th>Part #</th>
                            <th>Description</th>
                            <th>Current Stock</th>
                            <th>Add Stock</th>
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
                                        <input type="number" name="update[<?= htmlspecialchars($item['part_number']) ?>]" id="qty_<?= $item['part_number'] ?>" min="0">
                                        <button type="button" class="qty-button" onclick="changeQty('qty_<?= $item['part_number'] ?>', 1)">+</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</body>
</html>
