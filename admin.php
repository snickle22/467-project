<!DOCTYPE html>
<html>
<head>
    <title>Administration Page</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: url('https://wallpapers.com/images/featured/light-blue-2iuzyh711jo9bmgo.jpg') no-repeat center center fixed;
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

        .nav-links {
            margin-bottom: 20px;
        }

        a {
            color: #cce6ff;
            font-weight: bold;
            margin-right: 20px;
            text-decoration: none;
        }

        h2 {
            text-align: center;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        form {
            text-align: center;
            margin: 20px 0;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: rgb(12, 132, 252);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        button:hover {
            background-color: rgb(0, 109, 218);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="menu.php">Home</a>
        </div>

        <h2>Administration Page</h2>

        <form action="order.php" method="GET">
            <button type="submit">Orders</button>
        </form>

        <form action="shipping.php" method="GET">
            <button type="submit">Shipping</button>
        </form>
    </div>
</body>
</html>

