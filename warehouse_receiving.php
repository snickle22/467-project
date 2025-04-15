<?php
// Any PHP logic you need

// End PHP block before HTML
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Warehouse Inventory</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .search-form { margin-bottom: 15px; text-align: center; }
        .scroll-box {
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .scroll-box table {
            width: 80%;
            margin: auto;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table, th, td { border: 1px solid #ccc; }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th:nth-child(1), td:nth-child(1) { width: 8%; }
        th:nth-child(2), td:nth-child(2) { width: 64%; word-wrap: break-word; }
        th:nth-child(3), td:nth-child(3),
        th:nth-child(4), td:nth-child(4) { width: 14%; }

        input[type="number"] {
            width: 50px;
            text-align: center;
            -moz-appearance: textfield;
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .success { color: green; text-align: center; }
        .centered-button { text-align: center; margin-top: 10px; }
        .reset-link { margin-left: 10px; font-size: 0.9em; }

        .qty-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .qty-button {
            width: 30px;
            height: 30px;
            font-size: 18px;
            font-weight: bold;
            border: 1px solid #ccc;
            background: #f4f4f4;
            cursor: pointer;
        }
    </style>
    <script>
        function changeQty(inputId, delta) {
            const input = document.getElementById(inputId);
            let value = parseInt(input.value) || 0;
            value = Math.max(0, value + delta);
            input.value = value;
        }
    </script>
</head>
<body>
    <h1>Warehouse Receiving Page</h1>
</body>
</html>
<?php