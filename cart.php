<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php'; // This file exposes the $pdo object

// Function to calculate shipping cost based on total weight
function getShippingCost($totalWeight) {
    if ($totalWeight <= 5) return 7.00;
    elseif ($totalWeight <= 15) return 12.50;
    else return 20.00;
}

// Calculate totals if cart is not empty
$totalPrice = 0;
$totalWeight = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $totalPrice += $item['price'] * $item['quantity'];
        $totalWeight += $item['weight'] * $item['quantity'];
    }
}
$shippingCost = getShippingCost($totalWeight);
$actualTotal = $totalPrice + $shippingCost;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine action from POST
    $action = isset($_POST['action']) ? $_POST['action'] : null;

    if ($action === 'bulk_add') {
        // Bulk add: process multiple items from parts list
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
    } elseif ($action === 'add') {
        // Single add from parts list
        if (isset($_POST['part_number']) && isset($_POST['quantity'])) {
            $part_number = intval($_POST['part_number']);
            $quantity = intval($_POST['quantity']);
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
        header("Location: cart.php");
        exit;
    } elseif ($action === 'update') {
        // Update cart quantities
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
        // Checkout process: billing details and API call for credit card authorization
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $cc = trim($_POST['cc']);
        $exp = trim($_POST['exp']);
        $testPrice = trim($_POST['test_price']);
        $useTest = isset($_POST['use_test']) && $_POST['use_test'] == "1";

        $vendor = 'VE001-99';
        $trans = uniqid('cart-');
        $amount = ($useTest && is_numeric($testPrice)) 
                    ? number_format((float)$testPrice, 2, '.', '') 
                    : number_format($actualTotal, 2, '.', '');

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
            echo "<!-- Debug: API call failed: " . print_r($error, true) . " -->";
            die("API connection error. Please check your network settings.");
        }
        $responseData = json_decode($response, true);

        if (isset($responseData['errors']) && is_array($responseData['errors']) && count($responseData['errors']) > 0) {
            echo "<!DOCTYPE html><html><head><title>Order Error</title>
                  <style>body { font-family: Arial; }
                  .container { width: 80%; margin: auto; text-align: center; }
                  pre { background: #f4f4f4; padding: 10px; border: 1px solid #ccc; text-align: left; display: inline-block; }
                  ul { list-style-type: disc; margin: 10px auto; text-align: left; display: inline-block; }
                  </style></head><body>
                  <div class='container'><h1>Final Checkout</h1><h2>Authorization Response:</h2>
                  <pre>" . htmlspecialchars($response) . "</pre>
                  <h2>Order Error</h2><p>The following error(s) occurred:</p><ul>";
            foreach ($responseData['errors'] as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul><p>Please go back and correct the information.</p>
                  <p>You will be redirected to the checkout page shortly.</p></div>
                  <script>setTimeout(function() { window.location.href = 'cart.php'; }, 10000);</script>
                  </body></html>";
            exit;
        } else {
            // Success: Send confirmation email and clear cart
            $to = $email;
            $subject = "Order Confirmation – " . $responseData['trans'];
            $message = "Thank you for your order, " . $responseData['name'] . "!\n\n";
            $message .= "Transaction ID: " . $responseData['trans'] . "\n";
            $message .= "Authorization #: " . $responseData['authorization'] . "\n";
            $message .= "Amount Charged: $" . $responseData['amount'] . "\n";
            $message .= "Card Brand: " . $responseData['brand'] . "\n";
            $message .= "Order Time: " . date('Y-m-d H:i:s', $responseData['timeStamp'] / 1000) . "\n\n";
            $message .= "Your order will be processed and shipped shortly.\nThank you,\nYour Parts Team";
            $headers = "From: orders@yourstore.com";
            mail($to, $subject, $message, $headers);

            $_SESSION['cart'] = [];  // Clear cart

            echo "<!DOCTYPE html><html><head><title>Order Confirmation</title>
                  <style>body { font-family: Arial; }
                  .container { width: 80%; margin: auto; text-align: center; }
                  pre { background: #f4f4f4; padding: 10px; border: 1px solid #ccc; text-align: left; display: inline-block; }
                  </style></head><body>
                  <div class='container'><h1>Final Checkout</h1>
                  <h2>Authorization Response:</h2><pre>" . htmlspecialchars($response) . "</pre>
                  <h2>Order Confirmed</h2>
                  <p>Authorization #: <strong>" . htmlspecialchars($responseData['authorization']) . "</strong></p>
                  <p>Transaction: <strong>" . htmlspecialchars($responseData['trans']) . "</strong></p>
                  <p>Charged: $<strong>" . htmlspecialchars($responseData['amount']) . "</strong></p>
                  <p>A confirmation email was sent to: <strong>" . htmlspecialchars($email) . "</strong></p>
                  <p>You will be redirected to the parts list shortly.</p></div>
                  <script>setTimeout(function() { window.location.href = 'parts list.php'; }, 10000);</script>
                  </body></html>";
            exit;
        }
    } else {
        // Unknown action: simply redirect back
        header("Location: cart.php");
        exit;
    }
} else {  // GET Request Handling: Display cart, update form, and checkout form
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
<a href="parts list.php" style="text-decoration:none; font-size:16px;">&larr; Back to Parts List</a>
<br />
    <h1>Your Shopping Cart</h1>
    <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
        <!-- Update Cart Form -->
        <form method="post" action="cart.php">
            <table>
                <thead>
                    <tr>
                        <th>Part Number</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Weight</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['number']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                            <td><?php echo htmlspecialchars($item['weight']); ?> lbs</td>
                            <td>
                                <input type="number" name="quantities[<?php echo htmlspecialchars($item['number']); ?>]" 
                                       value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Total Price:</strong> $<?php echo number_format($totalPrice, 2); ?></p>
            <p><strong>Shipping Cost:</strong> $<?php echo number_format($shippingCost, 2); ?></p>
            <p><strong>Final Total:</strong> $<?php echo number_format($actualTotal, 2); ?></p>
            <input type="hidden" name="action" value="update">
            <input type="submit" value="Update Cart">
        </form>

        <!-- Checkout Form -->
        <h2>Checkout</h2>
        <form method="post" action="cart.php">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required><br><br>
            
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required><br><br>
            
            <label for="address">Address:</label>
            <input type="text" name="address" id="address" required><br><br>
            
            <label for="cc">Credit Card Number:</label>
            <input type="text" name="cc" id="cc" required><br><br>
            
            <label for="exp">Expiration Date:</label>
            <input type="text" name="exp" id="exp" placeholder="MM/YY" required><br><br>
            
            <!-- Optional Test Price Section -->
            <label for="test_price">Test Price (if applicable):</label>
            <input type="text" name="test_price" id="test_price"><br><br>
            
            <label for="use_test">Use Test Price:</label>
            <input type="checkbox" name="use_test" id="use_test" value="1"><br><br>
            
            <input type="hidden" name="action" value="checkout">
            <input type="submit" value="Place Order">
        </form>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>
</body>
</html>
<?php
}
?>
