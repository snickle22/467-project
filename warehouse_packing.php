<?php
include 'db_connect.php'; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Step 1: Get all orders marked as 'pending' from new DB
$orderStmt = $new_pdo->prepare("
    SELECT o.order_id, o.customer_email, o.order_status, oi.product_id, oi.quantity
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.order_status = 'pending'
    ORDER BY o.order_id
");
$orderStmt->execute();
$rows = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Step 2: Ensure all pending orders have tracking numbers
foreach ($rows as $row) {
    $orderId = $row['order_id'];

    // Check if tracking number is missing
    $trackingCheck = $new_pdo->prepare("SELECT tracking_number FROM orders WHERE order_id = ?");
    $trackingCheck->execute([$orderId]);
    $trackingNumber = $trackingCheck->fetchColumn();

    if (empty($trackingNumber)) {
        // If no tracking number, create one
        $newTracking = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $updateTracking = $new_pdo->prepare("UPDATE orders SET tracking_number = :tracking WHERE order_id = :id");
        $updateTracking->execute([
            ':tracking' => $newTracking,
            ':id' => $orderId
        ]);
    }
}

// Step 3: Organize orders
$orders = [];

foreach ($rows as $row) {
    $orderId = $row['order_id'];

    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'order_id' => $orderId,
            'customer_email' => $row['customer_email'],
            'order_status' => $row['order_status'],
            'items' => [],
            'tracking_number' => '' // To store tracking number
        ];
    }

    // Step 4: Lookup description from legacy DB
    $descStmt = $legacy_pdo->prepare("SELECT description FROM parts WHERE number = ?");
    $descStmt->execute([$row['product_id']]);
    $description = $descStmt->fetchColumn() ?: 'Unknown Part';

    $orders[$orderId]['items'][] = "{$row['quantity']} x $description";
    
    // Get the tracking number
    $trackingStmt = $new_pdo->prepare("SELECT tracking_number FROM orders WHERE order_id = ?");
    $trackingStmt->execute([$orderId]);
    $orders[$orderId]['tracking_number'] = $trackingStmt->fetchColumn();
}

// Step 5: Convert items to string
foreach ($orders as &$order) {
    $order['items'] = implode(', ', $order['items']);
}
unset($order);

// Step 6: Handle shipping form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ship_order_id'])) {
    $orderId = intval($_POST['ship_order_id']);

    // Fetch the existing tracking number
    $trackingQuery = $new_pdo->prepare("SELECT tracking_number FROM orders WHERE order_id = :id");
    $trackingQuery->execute([':id' => $orderId]);
    $trackingNumber = $trackingQuery->fetchColumn();

    // Mark order as shipped (no need to change tracking number)
    $update = $new_pdo->prepare("UPDATE orders SET order_status = 'shipped', tracking_number = :tracking, shipment_confirmation_sent = TRUE WHERE order_id = :id");
    $update->execute([':id' => $orderId, 'tracking' => $trackingNumber]);

    // Fetch customer email
    $emailQuery = $new_pdo->prepare("SELECT customer_email FROM orders WHERE order_id = :id");
    $emailQuery->execute([':id' => $orderId]);
    $email = $emailQuery->fetchColumn();

    // Log email confirmation
    file_put_contents('emails.log', "Order $orderId shipped (Tracking: $trackingNumber) - email sent to $email\n", FILE_APPEND);

    // Redirect
    header("Location: warehouse_packing.php?shipped=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Warehouse Packing</title>
    <style>
        html, body {
            height: 100%;
            font-family: Arial, sans-serif;
            margin: 0;
            background: url('https://wallpapers.com/images/featured/light-blue-2iuzyh711jo9bmgo.jpg') no-repeat center center/cover;
            background-size: cover;
            color: white;
        }

        .container {
            width: 90%;
            margin: auto;
            padding: 30px;
            margin-top: 40px;
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 12px;
            position: relative;
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

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            color: black;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: center;
            word-wrap: break-word;
        }

        th {
            background-color: rgb(12, 132, 252);
            color: white;
        }

        td:nth-child(2) {
            max-width: 300px;
            white-space: normal;
            word-break: break-word;
        }

        tr:hover {
            background-color: #f2f2f2;
        }

        .center {
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            border: 2px rgb(12, 132, 252) solid;
            background-color: white;
            color: black;
            text-decoration: none;
            transition: 0.3s;
            text-align: center;
        }

        .button:hover {
            background-color: rgb(12, 132, 252);
            color: white;
        }

        .button2 {
            background-color: white;
            color: black;
            border: 2px rgb(12, 132, 252) solid;
        }

        .button2:hover {
            background-color: rgb(12, 132, 252);
            color: white;
        }

        .success-msg {
            text-align: center;
            color: #00ff88;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="menu.php">Home</a>
        <h1>Warehouse Packing Interface</h1>

        <?php if (isset($_GET['shipped'])): ?>
            <p class="success-msg">Order marked as shipped and email sent!</p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Items</th>
                    <th>Customer Email</th>
                    <th>Status</th>
                    <th>Tracking Number</th>
                    <th>Print</th>
                    <th>Mark as Shipped</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                    <td><?= htmlspecialchars($order['items']) ?></td>
                    <td><?= htmlspecialchars($order['customer_email']) ?></td>
                    <td><?= htmlspecialchars($order['order_status']) ?></td>
                    <td><?= htmlspecialchars($order['tracking_number']) ?></td>
                    <td>
                    <form method="POST" action="print_documents.php" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <button type="submit" class="button">Print</button>
                    </form>
                    </td>

                    <td>
                    <form method="POST" action="warehouse_packing.php" style="display:inline;">
                        <input type="hidden" name="ship_order_id" value="<?= $order['order_id'] ?>">
                        <button type="submit" class="button">Ship Order</button>
                    </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($orders) === 0): ?>
                <tr><td colspan="7" class="center">No orders ready to ship.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
