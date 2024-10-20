<?php
require_once 'Database.php';
$db = new Database();
$conn = $db->connect();

session_start();

if (!isset($_SESSION['customer']) || !$_SESSION['customer']['isAdmin']) {
    header('Location: login.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="./adminStyle.css">
</head>  
<body>
    <header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
        <div class="admin-img"></div>
    </header>
    <section class="sidebar">
    <div class="IBT-admin">Admin</div>
        <ul>
            <li><a class="active_link" href="dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="bus.php" class="menu-item">Bus</a></li>
            <li><a href="route.php" class="menu-item">Route</a></li>
            <li><a href="customer.php" class="menu-item">Customer</a></li>
            <li><a href="booking.php" class="menu-item">Bookings</a></li>
            <hr class="menu-itemHR">
            <li><a href="logout.php" class="logoutBtn">Logout</a></li>
        </ul>
    </section>
    <main class="main">
        <div id="main-content" class="main-content">
            <h2>Welcome to the Dashboard</h2>
        <div class="dashboard-box">
            <div class="dash">Bus</div>
            <div class="dash">Route</div>
            <div class="dash">Customer</div>
            <div class="dash">Bookings</div>
        </div>
        </div>
    </main>
</body>
</html>