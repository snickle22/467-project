<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';   // provides $legacy_pdo and $new_pdo

/* ------------------------------------------------------------------ */
/*                    SHIPPING‑COST LOOK‑UP FUNCTION                  */
/* ------------------------------------------------------------------ */
function getShippingCost(PDO $pdo, float $totalWeight): float
{
    // 1. exact bracket
    $stmt = $pdo->prepare(
        "SELECT shipping_cost
         FROM shipping_costs
         WHERE :w BETWEEN start_weight AND end_weight
         ORDER BY start_weight ASC
         LIMIT 1");
    $stmt->execute(['w' => $totalWeight]);
    $cost = $stmt->fetchColumn();
    if ($cost !== false) return (float)$cost;

    // 2. largest bracket below weight
    $stmt = $pdo->prepare(
        "SELECT shipping_cost
         FROM shipping_costs
         WHERE start_weight < :w
         ORDER BY start_weight DESC
         LIMIT 1");
    $stmt->execute(['w' => $totalWeight]);
    $cost = $stmt->fetchColumn();
    if ($cost !== false) return (float)$cost;

    // 3. smallest bracket above weight (last fallback)
    $stmt = $pdo->query(
        "SELECT shipping_cost
         FROM shipping_costs
         ORDER BY start_weight ASC
         LIMIT 1");
    $cost = $stmt->fetchColumn();
    return $cost !== false ? (float)$cost : 0.00;   // empty table ⇒ free shipping
}

/* ------------------------------------------------------------------ */
/*                    PRICE / WEIGHT FOR CURRENT CART                 */
/* ------------------------------------------------------------------ */
$totalPrice  = 0.00;
$totalWeight = 0.00;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $totalPrice  += $item['price']  * $item['quantity'];
        $totalWeight += $item['weight'] * $item['quantity'];
    }
}

$shipping_charge = getShippingCost($new_pdo, $totalWeight);
$actualTotal     = $totalPrice + $shipping_charge;

/* ------------------------------------------------------------------ */
/*                              POST ACTIONS                          */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    /* --------------------------- bulk_add -------------------------- */
    if ($action === 'bulk_add') {
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $part_number => $quantity) {
                $part_number = intval($part_number);
                $quantity    = intval($quantity);
                if ($quantity > 0) {
                    $stmt = $legacy_pdo->prepare(
                        "SELECT number, description, price, weight, pictureURL
                         FROM parts
                         WHERE number = :number");
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
    }

    /* --------------------------- update ---------------------------- */
    if ($action === 'update') {
        if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $part_number => $quantity) {
                $part_number = intval($part_number);
                $quantity    = intval($quantity);
                if ($quantity > 0) {
                    $_SESSION['cart'][$part_number]['quantity'] = $quantity;
                } else {
                    unset($_SESSION['cart'][$part_number]);
                }
            }
        }
        header("Location: cart.php");
        exit;
    }

    /* --------------------------- checkout -------------------------- */
    if ($action === 'checkout') {
        $name             = trim($_POST['name']);
        $customer_email   = trim($_POST['customer_email']);
        $shipping_address = trim($_POST['shipping_address']);
        $cc               = trim($_POST['cc']);
        $exp              = trim($_POST['exp']);

        $vendor  = 'VE001-99';
        $trans   = uniqid('cart-');
        $amount  = number_format($actualTotal, 2, '.', '');

        $payload = [
            'vendor' => $vendor,
            'trans'  => $trans,
            'cc'     => $cc,
            'name'   => $name,
            'exp'    => $exp,
            'amount' => $amount
        ];

        $context = stream_context_create([
            'http' => [
                'header'  => "Content-type: application/json\r\nAccept: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($payload)
            ]
        ]);

        $response = file_get_contents('http://blitz.cs.niu.edu/CreditCard/', false, $context);
        if ($response === false) {
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

            $insertOrder = $new_pdo->prepare(
                "INSERT INTO orders
                     (total_price, shipping_address, shipping_handling_charge, customer_email)
                 VALUES (?, ?, ?, ?)");
            $insertOrder->execute([$actualTotal, $shipping_address, $shipping_charge, $customer_email]);

            $order_id   = $new_pdo->lastInsertId();
            $insertItem = $new_pdo->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity)
                 VALUES (?, ?, ?)");

            foreach ($_SESSION['cart'] as $item) {
                $check = $new_pdo->prepare(
                    "SELECT quantity
                     FROM inventory
                     WHERE part_number = ? FOR UPDATE");
                $check->execute([$item['number']]);
                $stock = $check->fetchColumn();

                if ($stock === false || $stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for part #{$item['number']}");
                }

                $updateStock = $new_pdo->prepare(
                    "UPDATE inventory
                     SET quantity = quantity - ?
                     WHERE part_number = ?");
                $updateStock->execute([$item['quantity'], $item['number']]);

                $insertItem->execute([$order_id, $item['number'], $item['quantity']]);
            }

            $new_pdo->commit();
            $_SESSION['cart'] = [];

            echo "<!DOCTYPE html><html><head><title>Order Confirmation</title></head><body>";
            echo "<h2>Order Success!</h2>";
            echo "<p><strong>Your order ID is:</strong> {$order_id}</p>";
            echo "<p><strong>Your payment authorization is:</strong> {$responseData['authorization']}</p>";
            echo "<form action='parts list.php' method='get'>";
            echo "<button type='submit'>Return to Parts List</button>";
            echo "</form></body></html>";

        } catch (Exception $e) {
            $new_pdo->rollBack();
            die("Checkout failed: " . $e->getMessage());
        }
        exit;
    }
    /* ------------------------------------------------------------------ */
}
/* ---------------------------------------------------------------------- */
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
            <tr>
                <th>Part #</th>
                <th>Description</th>
                <th>Price</th>
                <th>Weight</th>
                <th>Quantity</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['number']) ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td><?= htmlspecialchars($item['weight']) ?> lbs</td>
                    <td>
                        <input type="number"
                               name="quantities[<?= htmlspecialchars($item['number']) ?>]"
                               value="<?= htmlspecialchars($item['quantity']) ?>"
                               min="0">
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p><strong>Total Price:</strong> $<?= number_format($totalPrice, 2) ?></p>
        <p><strong>Shipping (<?= $totalWeight ?> lbs):</strong> $<?= number_format($shipping_charge, 2) ?></p>
        <p><strong>Final Total:</strong> $<?= number_format($actualTotal, 2) ?></p>

        <input type="hidden" name="action" value="update">
        <input type="submit" value="Update Cart">
    </form>

    <h2>Checkout</h2>
    <form method="post" action="cart.php">
        <label>Name: <input type="text" name="name" required></label><br><br>
        <label>Email: <input type="email" name="customer_email" required></label><br><br>
        <label>Shipping Address: <input type="text" name="shipping_address" required></label><br><br>
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

