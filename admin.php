

<html>
<head>
<style>
.button{
    border: none;
    color: white;
    padding: 16px 32px;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    transition-duration: 0.4s;
    cursor: pointer;
}

.button1{
    background-color: white;
    color: black;
    border 2px solid #04AA6D;
}

.button1:hover {
background-color: #04AA6D;
color: white;
}

.button2 {
background-color: white;
color: black;
border 2px solid #008CBA;
}

.button2:hover {
background-color: #008CBA;
color: white;
}

</style>
</head>
<body>
<a href="index.php">Home</a>
<h2> Administration Page </h2>

<form action="order.php" method="GET">
<button type="submit" class="button button1">Orders</button>
</form>

<form action="shipping.php" method="GET">
<button type="submit" class="button button2">Shipping</button>
</form>


</body>
</html>
