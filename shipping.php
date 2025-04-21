<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])){

    $deleted_id = intval($_POST['delete']);
    $removeBracket = $new_pdo->prepare("DELETE FROM shipping_costs WHERE cost_id = ?");
    $removeBracket->execute([$deleted_id]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
    
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $start = isset($_POST['start_weight']) && $_POST['start_weight'] !== '' ? floatval($_POST['start_weight']) : null;
    $end = isset($_POST['end_weight']) && $_POST['end_weight'] !== '' ? floatval($_POST['end_weight']) : null;
    $cost = isset($_POST['cost']) && $_POST['cost'] !== '' ? floatval($_POST['cost']) : null;

    $cost_id = $new_pdo->lastInsertId();

if($start !== null && $end !== null && $cost !== null){
    $insertBracket = $new_pdo->prepare("INSERT INTO shipping_costs (start_weight, end_weight, shipping_cost) VALUES (?, ?, ?)");
    $insertBracket->execute([$start, $end, $cost]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
}

$result = $new_pdo->query("SELECT * FROM shipping_costs ORDER BY start_weight ASC");

$rows = $result->fetchAll();
      

?>

<!DOCTYPE html><html>
<head></head>
<body>
    <a href="admin.php">Admin Home Page</a> &nbsp; &nbsp; &nbsp;
    <a href="index.php">Main Home</a></br>
    <h2>Shipping Page</h2>
    <h4>Weight brackets for shipping costs:</h4>
    <table border="1">
        <tr>
            <th>Weight from:</th>
            <th>Weight to:</th>
            <th>Cost</th>
            <th>Remove</th>
        </tr>
        <?php foreach ($rows as $row){
            
        echo "<tr>";
           echo"<td>".htmlspecialchars($row['start_weight'])."</td>";
           echo"<td>".htmlspecialchars($row['end_weight'])."</td>";
           echo"<td>".htmlspecialchars($row['shipping_cost'])."</td>";?>
            
        <td>
           <form method="POST" style="display:inline;">
                <input type="hidden" name="delete" value="<?php echo $row['cost_id']; ?>">
                <button type="submit">Remove</button> 
            </form>
        </td>
        <?php
        echo "</tr>";
       }?>

    </table>




<h4>Adding new shipping cost bracket:</h4>
<form action="" method="POST">
    <label>Weight from:
        <input type="number" name="start_weight"> lbs &nbsp;
    </label>
    <label> to:
        <input type"number" name="end_weight"> lbs &nbsp;
    </label>
    <label>Shipping Cost: $
        <input type="number" name="cost" step="0.01">
        <button type="submit" name="submit">Add new bracket</button>
    </label>
</form>



</br>
</br>



</body></html>
