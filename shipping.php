<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $deleted_id = intval($_POST['delete']);
    $removeBracket = $new_pdo->prepare("DELETE FROM shipping_costs WHERE cost_id = ?");
    $removeBracket->execute([$deleted_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = isset($_POST['start_weight']) && $_POST['start_weight'] !== '' ? floatval($_POST['start_weight']) : null;
    $end = isset($_POST['end_weight']) && $_POST['end_weight'] !== '' ? floatval($_POST['end_weight']) : null;
    $cost = isset($_POST['cost']) && $_POST['cost'] !== '' ? floatval($_POST['cost']) : null;

    if ($start !== null && $end !== null && $cost !== null) {
        $insertBracket = $new_pdo->prepare("INSERT INTO shipping_costs (start_weight, end_weight, shipping_cost) VALUES (?, ?, ?)");
        $insertBracket->execute([$start, $end, $cost]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$result = $new_pdo->query("SELECT * FROM shipping_costs ORDER BY start_weight ASC");
$rows = $result->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shipping Cost Brackets</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
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
        }

        h2, h4 {
            text-align: center;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        a {
            color: #cce6ff;
            font-weight: bold;
            margin-right: 20px;
            text-decoration: none;
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

        form {
            display: inline;
        }

        button {
            padding: 6px 12px;
            background-color: rgb(12, 132, 252);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        button:hover {
            background-color: rgb(0, 109, 218);
        }

        input[type="number"] {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }

        label {
            display: inline-block;
            margin-bottom: 10px;
            color: white;
        }

        form[action=""] {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php">Admin Home Page</a>
        <a href="menu.php">Home</a>

        <h2>Shipping Page</h2>
        <h4>Weight brackets for shipping costs:</h4>
        <table>
            <tr>
                <th>Weight from</th>
                <th>Weight to</th>
                <th>Cost</th>
                <th>Remove</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['start_weight']) ?></td>
                    <td><?= htmlspecialchars($row['end_weight']) ?></td>
                    <td>$<?= htmlspecialchars($row['shipping_cost']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="delete" value="<?= $row['cost_id'] ?>">
                            <button type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h4>Adding new shipping cost bracket:</h4>
        <form action="" method="POST">
            <label>Weight from:
                <input type="number" name="start_weight"> lbs
            </label>
            <label> to:
                <input type="number" name="end_weight"> lbs
            </label>
            <label>Shipping Cost: $
                <input type="number" name="cost" step="0.01">
            </label>
            <button type="submit" name="submit">Add new bracket</button>
        </form>
    </div>
</body>
</html>
