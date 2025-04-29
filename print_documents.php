<?php
include 'db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Step 1: Make sure the order_id was sent
if (!isset($_POST['order_id'])) {
    die('Order ID not provided.');
}

$orderId = intval($_POST['order_id']);

// Step 2: Fetch order info
$orderStmt = $new_pdo->prepare("
    SELECT o.order_id, o.customer_email, o.customer_name, o.shipping_address, o.order_date, o.total_price, o.tracking_number
    FROM orders o
    WHERE o.order_id = ?
");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found.');
}

// Step 3: Fetch ordered items (only from order_items)
$itemsStmt = $new_pdo->prepare("
    SELECT product_id, quantity
    FROM order_items
    WHERE order_id = ?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Now fetch descriptions separately from legacy database
foreach ($items as &$item) {
    $descStmt = $legacy_pdo->prepare("
        SELECT description
        FROM parts
        WHERE number = ?
    ");
    $descStmt->execute([$item['product_id']]);
    $item['description'] = $descStmt->fetchColumn();
}
unset($item); // break reference

// Step 4: If items are empty, show error
if (!$items) {
    die('No items found for this order.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Documents for Order #<?= htmlspecialchars($orderId) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        h1, h2 {
            text-align: center;
        }
        .section {
            margin-bottom: 40px;
            page-break-after: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        .label-box {
            border: 2px dashed black;
            padding: 20px;
            font-size: 20px;
            margin: auto;
            width: 60%;
        }
        .nav-buttons {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .nav-buttons a {
            color: #cce6ff;
            font-weight: bold;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .nav-buttons a:hover {
            text-decoration: underline;
        }

        /* Hide nav-buttons when printing */
        @media print {
            .nav-buttons {
                display: none;
            }
        }

    </style>
</head>
<body onload="window.print()">
    <div class="nav-buttons">
        <a href="menu.php">Home</a>
        <a href="warehouse_packing.php">Back to Warehouse Packing</a>
    </div>

    <div class="section">
        <h1>Shipping Label</h1>
        <div class="label-box">
            <strong>Ship To:</strong><br>
            <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br><br>
            <strong>Tracking #:</strong> <?= htmlspecialchars($order['tracking_number'] ?: 'Pending') ?><br>
            <strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?><br>
            <strong>Customer Email:</strong> <?= htmlspecialchars($order['customer_email']) ?><br>
        </div>
    </div>

    <div class="section">
        <h1>Invoice</h1>
        <strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?><br>
        <strong>Order Date:</strong> <?= htmlspecialchars($order['order_date']) ?><br>
        <strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?><br>
        <strong>Customer Email:</strong> <?= htmlspecialchars($order['customer_email']) ?><br><br>

        <h2>Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Quantity</th>
                    <th>Product Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Total: $<?= htmlspecialchars(number_format($order['total_price'], 2)) ?></h2>
    </div>
</body>
</html>
