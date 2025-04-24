<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';
$orders = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    $query = "SELECT * FROM orders WHERE 1=1";
    $params = [];

    if (!empty($_GET['date_from'])) {
        $query .= " AND order_date >= :date_from";
        $params['date_from'] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $query .= " AND order_date <= :date_to";
        $params['date_to'] = $_GET['date_to'];
    }

    if (!empty($_GET['min_price'])) {
        $query .= " AND total_price >= :min_price";
        $params['min_price'] = $_GET['min_price'];
    }

    if (!empty($_GET['max_price'])) {
        $query .= " AND total_price <= :max_price";
        $params['max_price'] = $_GET['max_price'];
    }

    if (!empty($_GET['order_status']) && $_GET['order_status'] !== 'all') {
        $query .= " AND order_status = :order_status";
        $params['order_status'] = $_GET['order_status'];
    }

    $getOrders = $new_pdo->prepare($query);
    $getOrders->execute($params);
    $orders = $getOrders->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Search</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: url('https://wallpapers.com/images/featured/light-blue-2iuzyh711jo9bmgo.jpg') center center/cover;
            background-size: cover;
            color: white;
        }

        .container {
            width: 95%;
            margin: auto;
            padding: 30px;
            margin-top: 40px;
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 12px;
        }

        a {
            color: #cce6ff;
            font-weight: bold;
            margin-right: 20px;
            text-decoration: none;
        }

        h2, h4 {
            text-align: center;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        form {
            text-align: center;
            margin-bottom: 30px;
        }

        label {
            margin-right: 10px;
            color: white;
        }

        input, select {
            padding: 6px;
            margin: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            background-color: rgb(12, 132, 252);
            color: white;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: rgb(0, 109, 218);
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
        }

        th {
            background-color: rgb(12, 132, 252);
            color: white;
        }

        tr:hover {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php">Admin Home Page</a>
        <a href="menu.php">Home</a>

        <h2>Order Search</h2>
        <h4>Look up order by:</h4>

        <form method="GET">
            <label for="dateFrom">Date from:</label>
            <input type="date" name="date_from">

            <label for="dateTo">To:</label>
            <input type="date" name="date_to">
            <br>

            <label for="status">Status:</label>
            <select name="order_status" id="statuses">
                <option value="all">All</option>
                <option value="pending">Pending</option>
                <option value="shipped">Shipped</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <br>

            <label for="priceMin">Price from:</label>
            <input type="number" name="min_price" step="0.01" value="0">

            <label for="priceMax">To:</label>
            <input type="number" name="max_price" step="0.01" value="0">
            <br>

            <input type="submit" value="Search">
        </form>

        <table>
            <tr>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Order Date</th>
                <th>Order Total</th>
                <th>Shipping Address</th>
                <th>Order Status</th>
                <th>Shipping Charge</th>
                <th>Tracking Number</th>
                <th>Email Sent</th>
                <th>Shipment Sent</th>
            </tr>

            <?php
            if (empty($orders)) {
                echo '<tr><td colspan="10" style="text-align:center;">No orders found.</td></tr>';
            } else {
                foreach ($orders as $order) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['order_date']) . "</td>";
                    echo "<td>$" . htmlspecialchars($order['total_price']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['shipping_address']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['order_status']) . "</td>";
                    echo "<td>$" . htmlspecialchars($order['shipping_handling_charge']) . "</td>";
                    echo "<td>" . ($order['tracking_number'] === NULL ? "Not yet shipped" : htmlspecialchars($order['tracking_number'])) . "</td>";
                    echo "<td>" . ($order['email_confirmation_sent'] === "TRUE" ? "Yes" : "No") . "</td>";
                    echo "<td>" . ($order['shipment_confirmation_sent'] === "TRUE" ? "Yes" : "No") . "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </table>
    </div>
</body>
</html>
