<?php
include 'db_connect.php'; 

// 1. Get all orders that are marked as 'ready_to_ship'
$stmt = $new_pdo->prepare("SELECT o.id, o.customer_email, o.status, GROUP_CONCAT(CONCAT(oi.quantity, ' x ', p.description) SEPARATOR ', ') AS items
                           FROM orders o
                           JOIN order_items oi ON o.order_id = oi.order_id
                           JOIN parts p ON oi.product_id = p.number
                           WHERE o.order_status = 'pending'
                           GROUP BY o.order_id");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Handle shipping action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ship_order_id'])) {
    $orderId = intval($_POST['ship_order_id']);

    // Update the order status to shipped
    $updateStmt = $new_pdo->prepare("UPDATE orders SET status = 'shipped' WHERE id = :id");
    $updateStmt->execute([':id' => $orderId]);

    // Get customer email to send a confirmation (fake for now)
    $emailStmt = $new_pdo->prepare("SELECT customer_email FROM orders WHERE id = :id");
    $emailStmt->execute([':id' => $orderId]);
    $email = $emailStmt->fetchColumn();

    // Fake email send (log it instead)
    file_put_contents('emails.log', "Order $orderId shipped email sent to $email\n", FILE_APPEND);

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
                <td><?= htmlspecialchars($order['id']) ?></td>
                <td><?= htmlspecialchars($order['items']) ?></td>
                <td><?= htmlspecialchars($order['customer_email']) ?></td>
                <td><?= htmlspecialchars($order['status']) ?></td>
                <td><a href="print_documents.php?order_id=<?= $order['id'] ?>" target="_blank">Print</a></td>
                <td>
                    <form method="POST" action="warehouse_packing.php">
                        <input type="hidden" name="ship_order_id" value="<?= $order['id'] ?>">
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
