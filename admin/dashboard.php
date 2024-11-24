<?php
require_once '../database.php';
$db = new Database();
$conn = $db->connect();

session_start();


if (!isset($_SESSION['customer']) || !$_SESSION['customer']['isAdmin'] || !$_SESSION['customer']['isStaff']) {
    header('Location: login.php');
    exit();
}

function getFullName() {
    if (isset($_SESSION['customer'])) {
        return $_SESSION['customer']['first_name'] . ' ' . $_SESSION['customer']['last_name'];
    }
    return '';
}

function getCount($conn, $table) {
    $sql = "SELECT COUNT(*) as count FROM `$table`";
    echo "Executing query: $sql<br>";  // Debug output
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "Count for $table: " . $result['count'] . "<br>";  // Debug output
            return $result['count'];
        } else {
            echo "No rows found for $table<br>";  // Debug output
            return 0;
        }
    } catch (PDOException $e) {
        echo "Query failed: " . $e->getMessage() . "<br>";  // Debug output
        return 0;
    }
}

$busCount = getCount($conn, "bus");
$routeCount = getCount($conn, "route");
$customerCount = getCount($conn, "customer");
$bookingCount = getCount($conn, "booking");

// Array of items to display
$items = [
    "Bus" => $busCount,
    "Route" => $routeCount,
    "Customer" => $customerCount,
    "Bookings" => $bookingCount
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="./adminStyle.css">
    <style>
        .dash {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .dash-count {
            font-size: 2em;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>  
<body>
    <header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
        <button class="admin-img"></button>
    </header>
    <section class="sidebar">
    <!-- <div class="IBT-admin">Admin</div> -->
    <div class="admin-name">Admin: <?php echo getFullName()?></div>
        <ul>
            <li><a class="active_link" href="dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="bus.php" class="menu-item">Bus</a></li>
            <li><a href="route.php" class="menu-item">Route</a></li>
            <li><a href="customer.php" class="menu-item">Customer</a></li>
            <li><a href="booking.php" class="menu-item">Bookings</a></li>
            <li><a href="registerStaff.php" class="menu-item">Staff Management</a></li>
            <hr class="menu-itemHR">
            <li><a href="../logout.php" class="logoutBtn">Logout</a></li>
        </ul>
    </section>
    <main class="main">
        <div id="main-content" class="main-content">
            <h1>Welcome to the Dashboard</h1>
        <div class="dashboard-box">
            <?php foreach ($items as $name => $count): ?>
                <div class="dash">
                    <span><?php echo $name; ?></span>
                    <span class="dash-count"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
    </main>
</body>
</html>