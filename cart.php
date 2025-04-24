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
                     (customer_name, total_price, shipping_address, shipping_handling_charge, customer_email)
                 VALUES (?, ?, ?, ?, ?)");
            $insertOrder->execute([$name, $actualTotal, $shipping_address, $shipping_charge, $customer_email]);

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

            echo <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>Order Confirmation</title>
                <style>
                    html, body {
                        height: 100%;
                        margin: 0;
                        font-family: Arial, sans-serif;
                        background: url('https://wallpapers.com/images/featured/light-blue-2iuzyh711jo9bmgo.jpg') no-repeat center center fixed;
                        background-size: cover;
                        color: white;
                    }
                    .container {
                        width: 80%;
                        margin: auto;
                        padding: 30px;
                        background-color: rgba(0, 0, 0, 0.5);
                        border-radius: 12px;
                        margin-top: 40px;
                        text-align: center;
                    }
                    h2 {
                        margin-bottom: 20px;
                        text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.5);
                    }
                    p {
                        font-size: 18px;
                        margin: 10px 0;
                    }
                    button {
                        padding: 10px 20px;
                        font-size: 16px;
                        background-color: rgb(12, 132, 252);
                        color: white;
                        border: none;
                        border-radius: 8px;
                        cursor: pointer;
                        margin-top: 20px;
                    }
                    button:hover {
                        background-color: rgb(0, 109, 218);
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Order Success!</h2>
                    <p><strong>Your order ID is:</strong> {$order_id}</p>
                    <p><strong>Your payment authorization is:</strong> {$responseData['authorization']}</p>
                    <form action='parts list.php' method='get'>
                        <button type='submit'>Return to Parts List</button>
                    </form>
                    <form action='menu.php' method='get'>
                        <button type='submit'>Back to Menu</button>
                    </form>
                </div>
            </body>
            </html>
            HTML;


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

        a {
            color: #add8ff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.5);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            color: black;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 10px 15px;
            border: 1px solid #ccc;
            text-align: center;
        }

        input[type='number'], input[type='text'], input[type='email'] {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
        }

        .submit-container {
            text-align: center;
            margin-top: 20px;
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
    </style>
</head>
<body>
<div class="container">
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
            <div class="submit-container">
                <input type="submit" value="Update Cart">
            </div>
        </form>

        <h2>Checkout</h2>
        <form method="post" action="cart.php">
            <label>Name:</label>
            <input type="text" name="name" required>

            <label>Email:</label>
            <input type="email" name="customer_email" required>

            <label>Shipping Address:</label>
            <input type="text" name="shipping_address" required>

            <label>Credit Card #:</label>
            <input type="text" name="cc" placeholder="6011 1234 4321 1234" required>

            <label>Exp Date:</label>
            <input type="text" name="exp" placeholder="MM/YYYY" required>

            <input type="hidden" name="action" value="checkout">
            <div class="submit-container">
                <input type="submit" value="Place Order">
            </div>
        </form>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>
</div>
</body>
</html>
