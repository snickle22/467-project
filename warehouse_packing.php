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

// Step 2: Organize orders
$orders = [];

foreach ($rows as $row) {
    $orderId = $row['order_id'];

    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'order_id' => $orderId,
            'customer_email' => $row['customer_email'],
            'order_status' => $row['order_status'],
            'items' => []
        ];
    }

    // Step 3: Lookup description from legacy DB
    $descStmt = $legacy_pdo->prepare("SELECT description FROM parts WHERE number = ?");
    $descStmt->execute([$row['product_id']]);
    $description = $descStmt->fetchColumn() ?: 'Unknown Part';

    $orders[$orderId]['items'][] = "{$row['quantity']} x $description";
}

// Step 4: Convert items to string
foreach ($orders as &$order) {
    $order['items'] = implode(', ', $order['items']);
}
unset($order);

// Step 5: Handle shipping form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ship_order_id'])) {
    $orderId = intval($_POST['ship_order_id']);

    // Mark order as shipped
    $update = $new_pdo->prepare("UPDATE orders SET order_status = 'shipped' WHERE order_id = :id");
    $update->execute([':id' => $orderId]);

    // Fetch customer email
    $emailQuery = $new_pdo->prepare("SELECT customer_email FROM orders WHERE order_id = :id");
    $emailQuery->execute([':id' => $orderId]);
    $email = $emailQuery->fetchColumn();

    // Log email confirmation
    file_put_contents('emails.log', "Order $orderId shipped email sent to $email\n", FILE_APPEND);

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
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 90%; margin: auto; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1 class="center">Warehouse Packing Interface</h1>
    <?php if (isset($_GET['shipped'])): ?>
        <p class="center" style="color:green;">Order marked as shipped and email sent!</p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Items</th>
                <th>Customer Email</th>
                <th>Status</th>
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
                <td><a href="print_documents.php?order_id=<?= $order['order_id'] ?>" target="_blank">Print</a></td>
                <td>
                    <form method="POST" action="warehouse_packing.php">
                        <input type="hidden" name="ship_order_id" value="<?= $order['order_id'] ?>">
                        <button type="submit">Ship Order</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($orders) === 0): ?>
            <tr><td colspan="6" class="center">No orders ready to ship.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
