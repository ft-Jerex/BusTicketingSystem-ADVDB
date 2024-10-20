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
                    <a href="?logout=1">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="nav-link">Sign In</a>
        <?php endif; ?>
    </header>
</div>

<div class="container-banner">
    <h1>BIENVENIDOS ZAMBOANGUEÑOS</h1>
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
    <a href="?buy=1" class="button">Buy Ticket Now</a>
</div>

<main class="container">
    <hr class="featurette-divider" />

    <div class="featurette">
        <div class="featurette-text">
            <h2>First featurette heading.</h2>
            <p>Another featurette? Of course. More placeholder content here to give you an idea of how this layout would work with some actual real-world content in place.</p>
        </div>
        <div class="featurette-image1"></div>
    </div>

    <hr class="featurette-divider" />

    <div class="featurette">
        <div class="featurette-image2"></div>
        <div class="featurette-text">
            <h2>Oh yeah, it's that good.</h2>
            <p>Another featurette? Of course. More placeholder content here to give you an idea of how this layout would work with some actual real-world content in place.</p>
        </div>
    </div>

    <hr class="featurette-divider" id="aboutDivider"/>

    <h2 class="about">About</h2>
    <div class="about-section">
        <div class="about-item">
            <h3>Featured title</h3>
            <p>Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.</p>
        </div>
        <div class="about-item">
            <h3>Featured title</h3>
            <p>Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.</p>
        </div>
        <div class="about-item">
            <h3>Featured title</h3>
            <p>Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.</p>
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
        <p>&copy; 2024 Your Company. All rights reserved.</p>
    </div>
</footer>

</body>
</html>