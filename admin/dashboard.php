<?php
require_once '../database.php';
$db = new Database();
$conn = $db->connect();

session_start();

if (!isset($_SESSION['customer'])) {
    header('Location: ../login.php');
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
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['count'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getWeeklyRevenue($conn) {
    $sql = "SELECT 
                DAYNAME(booking.date_book) AS day_name,
                SUM(route.cost) AS total_revenue
            FROM 
                booking
            JOIN 
                route ON booking.fk_route_id = route.route_id
            WHERE 
                booking.date_book >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                AND booking.date_book < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)
            GROUP BY 
                day_name
            ORDER BY 
                FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$busCount = getCount($conn, "bus");
$routeCount = getCount($conn, "route");
$customerCount = getCount($conn, "customer");
$bookingCount = getCount($conn, "booking");

$weeklyRevenue = getWeeklyRevenue($conn);

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
    <?php include_once 'includes/header.php'; ?>
</head>  
<body>
    <header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
    </header>
    <section class="sidebar">
        <div class="admin-name">Admin: <?php echo getFullName()?></div>
        <hr class="menu-itemHR">
        <ul>
            <li><a class="active_link" href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bus.php" class="menu-item"><i class="fas fa-bus"></i> Bus</a></li>
            <li><a href="route.php" class="menu-item"><i class="fas fa-route"></i> Route</a></li>
            <li><a href="customer.php" class="menu-item"><i class="fas fa-users"></i> Customer</a></li>
            <li><a href="booking.php" class="menu-item"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
            
            <?php 
            // Only show Staff Management for admin users
            if (isset($_SESSION['customer']) && 
                ($_SESSION['customer']['role'] === 'admin' || 
                 $_SESSION['customer']['isAdmin'] == 1)) : ?>
                <li><a href="registerStaff.php" class="menu-item"><i class="fas fa-user-cog"></i> Staff Management</a></li>
            <?php endif; ?>
            
            <hr class="menu-itemHR">
            <li><a href="../logout.php" class="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
            
            <div class="revenue-chart-container">
                <h2>Weekly Revenue</h2>
                <canvas id="weeklyRevenueChart"></canvas>
            </div>
        </div>
    </main>
    <?php include_once 'includes/footer.php'; ?>
    <script>  
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('weeklyRevenueChart').getContext('2d');
            var weeklyRevenueData = <?php echo json_encode($weeklyRevenue); ?>;

            var labels = weeklyRevenueData.map(item => item.day_name);
            var revenues = weeklyRevenueData.map(item => parseFloat(item.total_revenue));

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: revenues,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue (PHP)'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>