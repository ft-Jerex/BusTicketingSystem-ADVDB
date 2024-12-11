<?php
session_start();
require_once 'Database.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['customer']);

// Function to check if user is logged in and redirect if not
function requireLogin() {
    if (!isset($_SESSION['customer'])) {
        header('Location: login.php');
        exit();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Handle Buy Now button click
if (isset($_GET['buy'])) {
    requireLogin();
    // Redirect to buy page if logged in
    header('Location: buy.php');
    exit();
}


// Function to get the full name of the logged-in user
function getFullName() {
    if (isset($_SESSION['customer'])) {
        return $_SESSION['customer']['first_name'] . ' ' . $_SESSION['customer']['last_name'];
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home</title>
  <link rel="stylesheet" href="userStyle1.css">

</head>
<body>

<div class="container-header">
    <header>
        <a href="#" class="logo">IBT</a>
        <ul class="nav">
            <li class="nav-item"><a href="#" class="nav-link">Home</a></li>
            <li class="nav-item"><a href="?buy=1" class="nav-link">Buy Ticket</a></li>
            <li class="nav-item"><a href="#aboutDivider" class="nav-link">About</a></li>
        </ul>
        <?php if ($isLoggedIn): ?>
            <div class="dropdown">
                <button class="dropbtn"></button>
                <div class="dropdown-content">
                    <div class="username"><?php echo getFullName(); ?></div>
                    <hr>
                    <a href="transaction.php"><img class="icon-dropdown" src="./img/receipt-solid.svg" alt=""> Transactions</a>
                    <a href="?logout=1"><img class="icon-dropdown" src="./img/right-from-bracket-solid.svg" alt=""> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="nav-link signin-btn">Sign In</a>
        <?php endif; ?>
    </header>
</div>

<div class="container-banner">
    <h1>BIENVENIDOS ZAMBOANGUEÑOS</h1>
    <p>Zamboanga Integrated Bus Terminal is the main hub for buses in Zamboanga City, connecting passengers to nearby provinces. Located in Divisoria, it offers essential services like ticketing and waiting areas for a smooth travel experiences.</p>
    <a href="?buy=1" class="button">Buy Ticket Now</a>
</div>

<!-- <main class="container"> -->
    <!-- <hr class="featurette-divider" /> -->

    <div class="featurette">
        <div class="featurette-text">
            <h2 class="featurette-h2">Centralized Transportation Hub</h2>
            <p>The Zamboanga Integrated Bus Terminal connects passengers to key provinces in the Zamboanga Peninsula or beyond, making it a convenient transit point for regional travel.</p>
        </div>
        <div class="featurette-image1"></div>
    </div>

    <!-- <hr class="featurette-divider" /> -->
    
    <div class="available-routes">
        <h2 class="section-title">Available Routes</h2>
        <div class="routes-container">
            <?php
            $db = new Database();
            $conn = $db->connect();
            
            $sql = "SELECT r.route_name, b.bus_no, b.bus_type, r.departure_time, r.cost 
                    FROM route r
                    JOIN bus b ON r.fk_bus_id = b.bus_id
                    ORDER BY r.departure_time ASC";
                    
            $stmt = $conn->query($sql);
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($routes as $route): ?>
                <div class="route-card">
                    <div class="route-header">
                        <h3><?php echo htmlspecialchars($route['route_name']); ?></h3>
                        <span class="bus-badge"><?php echo htmlspecialchars($route['bus_type']); ?></span>
                    </div>
                    <div class="route-details">
                        <p><i class="fas fa-bus"></i> Bus No: <?php echo htmlspecialchars($route['bus_no']); ?></p>
                        <p><i class="far fa-clock"></i> Departure: <?php echo date('h:i A', strtotime($route['departure_time'])); ?></p>
                        <p><i class="fas fa-tag"></i> Price: ₱<?php echo number_format($route['cost'], 2); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <hr class="featurette-divider" />

    <div class="featurette">
        <div class="featurette-image2"></div>
        <div class="featurette-text">
            <h2 class="featurette-h2">Modern Amenities</h2>
            <p>The terminal offers facilities such as waiting areas, food stalls, and restrooms, providing a comfortable experience for travelers.</p>
        </div>
    </div>

    <!-- <hr class="featurette-divider" id="aboutDivider"/> -->
    <div class="About_Section" style="background-color: #283950; padding: 40px 0;">
    <h2 class="about" style="display: flex; justify-content:center; color:#ffff">About</h2>
    <div class="about-section">
        <div class="about-item">
            <h3>Inaugration</h3>
            <p>The Zamboanga Integrated Bus Terminal started its operations in 2015 to improve the city's transportation system and connect various provinces in the region.</p>
        </div>
        <div class="about-item">
            <h3>Purpose</h3>
            <p>The terminal was established to provide a more organized and efficient transportation service, offering modern facilities for the convenience of passengers.</p>
        </div>
        <div class="about-item">
            <h3>Security</h3>
            <p>The Zamboanga Integrated Bus Terminal is equipped with security measures such as CCTV cameras and security personnel to ensure the safety of passengers and staff.</p>
        </div>
    </div>
    </div>

<div class="contact-section" style="background-color: #f5f5f5; padding: 40px 0;">
    <div class="contact-container" style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 40px;">
        <div class="contact-map" style="flex: 1;">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.7046244492387!2d122.07377147486453!3d7.0444999927633825!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32502e821b833da7%3A0x62d2c46c6c6a8c08!2sZamboanga%20City%20Integrated%20Bus%20Terminal!5e0!3m2!1sen!2sph!4v1708697789609!5m2!1sen!2sph"
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
        
        <div class="contact-info" style="flex: 1; padding: 20px;">
            <h2 style="color: #283950; margin-bottom: 30px; font-size: 2.5em;">Contact Us</h2>
            
            <div class="contact-details" style="display: flex; flex-direction: column; gap: 20px;">
                <div class="contact-item" style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-envelope" style="width: 20px; height: 20px; color: #283950;"></i>
                    <p>Email: info@zamboint.com</p>
                </div>
                
                <div class="contact-item" style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-phone" style="width: 20px; height: 20px; color: #283950;"></i>
                    <p>Contact: (062) 991-2121</p>
                </div>
                
                <div class="contact-item" style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-location-dot" style="width: 20px; height: 20px; color: #283950;"></i>
                    <p>Divisoria, Zamboanga City, Philippines</p>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<footer class="footer">
    <div class="footer-content">
        <ul class="footer-links">
            <li><a href="#" class="footer-link">Home</a></li>
            <li><a href="#" class="footer-link">Buy Ticket</a></li>
            <li><a href="#" class="footer-link">About</a></li>
        </ul>
        <p>&copy; 2024 IBT. All rights reserved.</p>
    </div>
</footer>

</body>
</html>