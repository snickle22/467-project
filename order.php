<?php
ini_set('display_errors',1);
ini_set('displat_startup_errors',1);
error_reporting(E_ALL);

include 'db_connect.php';
 $orders = [];

if($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)){
    $query = "SELECT * FROM orders WHERE 1=1";
    $params = [];


    if(!empty($_GET['date_from'])){
        $query.=" AND order_date >= :date_from";
        $params['date_from'] = $_GET['date_from'];
    }

    if(!empty($_GET['date_to'])){
        $query.=" AND order_date <= :date_to";
        $params['date_to'] = $_GET['date_to'];
    }

    if(!empty($_GET['min_price'])){
        $query.=" AND total_price >= :min_price";
        $params['min_price'] = $_GET['min_price'];
    }

    if(!empty($_GET['max_price'])){
        $query.=" AND total_price <= :max_price";
        $params['max_price'] = $_GET['max_price'];
    }

    if(!empty($_GET['order_status'])){

        $query.=" AND status = :status";
        $params['order_status'] = $_GET['order_status'];
    }

    $getOrders = $new_pdo->prepare($query);
    $getOrders->execute($params);
    $orders = $getOrders->fetchAll(PDO::FETCH_ASSOC);
   
   }


?>


<!DOCTYPE><html>
<head></head>
<body>

<a href="admin.php">Admin Home Page</a> &nbsp; &nbsp; &nbsp;
<a href="index.php">Home</a>
<h2>Order Search</h2>
<h4> Look up order by:</h4>

<form method="GET">
    <label for="dateFrom">Date from:</label>
    <input type="date" name="date_from"> &nbsp;&nbsp;&nbsp;&nbsp;

    <label for="dateTo">To:</label>
    <input type="date" name="date_to"> 

</br>

<label for="status">Status:</label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<select name="order_status" id="statuses">
    <option value="pre">Select One..</option>
    <option value="all">All</option>
    <option value="pending">Pending</option>
    <option value="shipped">Shipped</option>
    <option value="completed">Completed</option>
    <option value="cancelled">Cancelled</option>
</select>

</br>

<label for="priceMin">Price from:</label>
    <input type="number" name="min_price" step="0.01" value="0">&nbsp;&nbsp;&nbsp;&nbsp;
<label for="priceMax">To:</label>
    <input type="number" name="max_price" step="0.01"  value="0">

</br>

<input type="submit" value="search">

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
        <th>Confirmation Email Sent</th>
        <th>Shipment Confirmation Sent</th>
    </tr>

<?php
    if(empty($orders)){
        echo "No orders found."; 
    }

    else{

    foreach ($orders as $order){
       echo "<tr>";
       echo "<td>".htmlspecialchars($order['order_id'])."</td>";
       echo "<td>".htmlspecialchars($order['customer_name'])."</td>";
       echo "<td>".htmlspecialchars($order['order_date'])."</td>";
       echo "<td>".htmlspecialchars($order['total_price'])."</td>";
       echo "<td>".htmlspecialchars($order['shipping_address'])."</td>";
       echo "<td>".htmlspecialchars($order['order_status'])."</td>";
       echo "<td>".htmlspecialchars($order['shipping_handling_charge'])."</td>";
       echo "<td>";
        if($order['tracking_number'] === NULL) {
           echo "Not yet shipped";
        }
        else{
            echo htmlspecialchars($order['tracking_number'])."</td>";
        }
       echo "<td>";
        if($order['email_confirmation_sent'] === "TRUE"){
            echo "Yes";
        }
        else{
            echo "No";
        }
       echo "</td>"; 
       echo "<td>";
        if($order['shipment_confirmation_sent'] === "TRUE"){
            echo "Yes";
        }
        else{
            echo "No";
        }
       echo "</td>";
       echo "</tr>";
       }
    }
?>

    
</table>

</body>
</html>
