<?php
require_once '../database.php';
require_once '../customer.class.php';

$customer = new Customer();
$message = '';

session_start();
// Modify the condition to allow either admin OR staff
if (!isset($_SESSION['customer']) || (!$_SESSION['customer']['isAdmin'] && !$_SESSION['customer']['isStaff'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $contact_no = $_POST['contact_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($contact_no) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif ($customer->emailExist($email)) {
        $message = "Email already exists.";
    } else {
        $customer->first_name = $first_name;
        $customer->last_name = $last_name;
        $customer->contact_no = $contact_no;
        $customer->email = $email;
        $customer->password = $password;

        // Always call addStaff() since this is specifically for adding staff
        $result = $customer->addStaff();

        if ($result) {
            $message = "Staff registration successful!";
        } else {
            $message = "Registration failed. Please try again.";
        }
    }
}

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
    <title>Add Staff</title>
    <link rel="stylesheet" href="adminStyle.css">
</head>
<body>
    <header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
        <button class="admin-img"></button>
    </header>
    <section class="sidebar">
        <div class="admin-name">Admin: <?php echo getFullName()?></div>
        <ul>
            <li><a href="dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="bus.php" class="menu-item">Bus</a></li>
            <li><a href="route.php" class="menu-item">Route</a></li>
            <li><a class="active_link" href="customer.php" class="menu-item">Customer</a></li>
            <li><a href="booking.php" class="menu-item">Bookings</a></li>
            <hr class="menu-itemHR">
            <li><a href="../logout.php" class="logoutBtn">Logout</a></li>
        </ul>
    </section>
    <main class="main">
        <div id="main-content">
            <h1>Staff Management</h1>
            <div class="AddEdit">
                <form method="POST" action="">
                    <h2>Add Staff</h2>
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="tel" name="contact_no" placeholder="Contact Number" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <input type="submit" value="Register Staff">
                </form>
                <?php if (!empty($message)): ?>
                    <div class="message"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>