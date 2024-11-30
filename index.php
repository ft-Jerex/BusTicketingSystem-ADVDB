<?php
session_start();

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
    <hr class="featurette-divider" />

    <div class="featurette">
        <div class="featurette-text">
            <h2 class="featurette-h2">Centralized Transportation Hub</h2>
            <p>The Zamboanga Integrated Bus Terminal connects passengers to key provinces in the Zamboanga Peninsula or beyond, making it a convenient transit point for regional travel.</p>
        </div>
        <div class="featurette-image1"></div>
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

    <h2 class="about">About</h2>
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