<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php'; // Uses $legacy_pdo and $new_pdo

function getShippingCost($totalWeight) {
    if ($totalWeight <= 5) return 7.00;
    elseif ($totalWeight <= 15) return 12.50;
    else return 20.00;
}

$totalPrice = 0;
$totalWeight = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $totalPrice += $item['price'] * $item['quantity'];
        $totalWeight += $item['weight'] * $item['quantity'];
    }
}
$shipping_charge = getShippingCost($totalWeight);
$actualTotal = $totalPrice + $shipping_charge;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'bulk_add') {
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $part_number => $quantity) {
                $part_number = intval($part_number);
                $quantity = intval($quantity);
                if ($quantity > 0) {
                    $stmt = $legacy_pdo->prepare("SELECT number, description, price, weight, pictureURL FROM parts WHERE number = :number");
                    $stmt->execute(['number' => $part_number]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        if (isset($_SESSION['cart'][$part_number])) {
                            $_SESSION['cart'][$part_number]['quantity'] += $quantity;
                        } else {
                            $_SESSION['cart'][$part_number] = [
                                'number'      => $row['number'],
                                'description' => $row['description'],
                                'price'       => $row['price'],
                                'weight'      => $row['weight'],
                                'pictureURL'  => $row['pictureURL'],
                                'quantity'    => $quantity
                            ];
                        }
                    }
                }
            }
        }
        header("Location: cart.php");
        exit;

    } elseif ($action === 'update') {
        if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $part_number => $quantity) {
                $part_number = intval($part_number);
                $quantity = intval($quantity);
                if ($quantity > 0) {
                    $_SESSION['cart'][$part_number]['quantity'] = $quantity;
                } else {
                    unset($_SESSION['cart'][$part_number]);
                }
            }
        }
        header("Location: cart.php");
        exit;

    } elseif ($action === 'checkout') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $shipping_address = trim($_POST['address']);
        $cc = trim($_POST['cc']);
        $exp = trim($_POST['exp']);

        $vendor = 'VE001-99';
        $trans = uniqid('cart-');
        $amount = number_format($actualTotal, 2, '.', '');

        $data = [
            'vendor' => $vendor,
            'trans'  => $trans,
            'cc'     => $cc,
            'name'   => $name,
            'exp'    => $exp,
            'amount' => $amount
        ];

        $url = 'http://blitz.cs.niu.edu/CreditCard/';
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\nAccept: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            header("Location: cart.php?error=authorization");
            exit;
        }
        $responseData = json_decode($response, true);

        if (!isset($responseData['authorization'])) {
            header("Location: cart.php?error=authorization");
            exit;
        }

        try {
            $new_pdo->beginTransaction();

            $insertOrder = $new_pdo->prepare("INSERT INTO orders (total_price, shipping_address, shipping_handling_charge, customer_email) VALUES (?, ?, ?, ?)");
            $insertOrder->execute([$actualTotal, $shipping_address, $shipping_charge, $email]);

            $order_id = $new_pdo->lastInsertId();

            foreach ($_SESSION['cart'] as $item) {
                $check = $new_pdo->prepare("SELECT quantity FROM inventory WHERE part_number = ? FOR UPDATE");
                $check->execute([$item['number']]);
                $stock = $check->fetchColumn();

                if ($stock === false || $stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for part #{$item['number']}");
                }

                $updateStock = $new_pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE part_number = ?");
                $updateStock->execute([$item['quantity'], $item['number']]);
            }

            $new_pdo->commit();
            $_SESSION['cart'] = [];

            echo "<!DOCTYPE html><html><head><title>Order Confirmation</title></head><body>";
            echo "<h2>Order Success!</h2>";
            echo "<p><strong>Your order ID is:</strong> {$order_id}</p>";
            echo "<p><strong>Your payment authorization is:</strong> {$responseData['authorization']}</p>";
            echo "<form action='parts list.php' method='get'>";
            echo "<button type='submit'>Return to Parts List</button>";
            echo "</form>";
            echo "</body></html>";

        } catch (Exception $e) {
            $new_pdo->rollBack();
            die("Checkout failed: " . $e->getMessage());
        }
        exit;
    }
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; text-align: center; }
        input[type='number'] { width: 60px; }
        form { margin-top: 20px; }
    </style>
</head>
<body>
<a href="parts list.php">&larr; Back to Parts List</a>
<h1>Your Shopping Cart</h1>
<?php if (!empty($_SESSION['cart'])): ?>
    <form method="post" action="cart.php">
        <table>
            <thead>
                <tr><th>Part #</th><th>Description</th><th>Price</th><th>Weight</th><th>Quantity</th></tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['number']); ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                        <td><?php echo htmlspecialchars($item['weight']); ?> lbs</td>
                        <td><input type="number" name="quantities[<?php echo htmlspecialchars($item['number']); ?>]" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Total Price:</strong> $<?php echo number_format($totalPrice, 2); ?></p>
        <p><strong>Shipping:</strong> $<?php echo number_format($shipping_charge, 2); ?></p>
        <p><strong>Final Total:</strong> $<?php echo number_format($actualTotal, 2); ?></p>
        <input type="hidden" name="action" value="update">
        <input type="submit" value="Update Cart">
    </form>
    <h2>Checkout</h2>
    <form method="post" action="cart.php">
        <label>Name: <input type="text" name="name" required></label><br><br>
        <label>Email: <input type="email" name="email" required></label><br><br>
        <label>Address: <input type="text" name="address" required></label><br><br>
        <label>Credit Card #: <input type="text" name="cc" placeholder="6011 1234 4321 1234" required></label><br><br>
        <label>Exp Date: <input type="text" name="exp" placeholder="MM/YYYY" required></label><br><br>
        <input type="hidden" name="action" value="checkout">
        <input type="submit" value="Place Order">
    </form>
<?php else: ?>
    <p>Your cart is empty.</p>
<?php endif; ?>
</body>
</html>
<?php } ?>
