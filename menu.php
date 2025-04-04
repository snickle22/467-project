<!DOCTYPE html>
<html>
<head>
    <title>467 Group Project</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: url('https://wallpapers.com/images/featured/light-blue-2iuzyh711jo9bmgo.jpg') no-repeat center center/cover;
            color: white;
        }

        h1 {
            margin-bottom: 30px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }

        .button-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .button {
            display: inline-block;
            padding: 15px 30px;
            font-size: 18px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            outline: none;
            color: #fff;
            background-color:rgb(12, 132, 252);
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease-in-out;
        }

        .button:hover { background-color:rgb(0, 109, 218); } 
        .button:active {
            background-color: #004a99;
            box-shadow: 0 2px rgba(0, 0, 0, 0.2);
            transform: translateY(2px);
        }
    </style>
</head>
<body>
    <h1>Select Your Page</h1>
    <div class="button-container">
        <button class="button" onclick="location.href='parts list.php'">Customer Place Order</button>
        <button class="button" onclick="location.href='warehouse_packing.php'">Warehouse Packing</button>
        <button class="button" onclick="location.href='warehouse_receiving.php'">Warehouse Receiving</button>
        <button class="button" onclick="location.href='admin.php'">Administrator</button>
    </div>
</body>
</html>
