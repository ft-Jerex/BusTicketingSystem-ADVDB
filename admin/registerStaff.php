<?php
require_once '../database.php';
require_once '../customer.class.php';

$customer = new Customer();
$message = '';

session_start();
// Modify the condition to allow either admin OR staff
// Check if user is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user is admin
if ($_SESSION['customer']['role'] !== 'admin' && 
    $_SESSION['customer']['isAdmin'] != 1) {
    // Redirect to dashboard or show an access denied message
    header('Location: dashboard.php');
    exit();
}

// Fetch all staff accounts
$staffAccounts = $customer->getAllStaff();

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
            // Refresh staff accounts after adding
            $staffAccounts = $customer->getAllStaff();
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
    <title>Staff Management</title>
    <link rel="stylesheet" href="adminStyle.css">
    <style>
        .staff-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .staff-table th, .staff-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .staff-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
    </header>
    <section class="sidebar">
        <div class="admin-name">Admin: <?php echo getFullName()?></div>
        <ul>
            <li><a href="dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="bus.php" class="menu-item">Bus</a></li>
            <li><a href="route.php" class="menu-item">Route</a></li>
            <li><a href="customer.php" class="menu-item">Customer</a></li>
            <li><a href="booking.php" class="menu-item">Bookings</a></li>
            
            <?php 
            // Only show Staff Management for admin users
            if (isset($_SESSION['customer']) && 
                ($_SESSION['customer']['role'] === 'admin' || 
                 $_SESSION['customer']['isAdmin'] == 1)) : ?>
                <li><a href="registerStaff.php" class="active_link menu-item">Staff Management</a></li>
            <?php endif; ?>
            
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

            <div class="staff-list">
                <h2>Current Staff Accounts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staffAccounts)): ?>
                            <?php foreach ($staffAccounts as $staff): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($staff['customer_id']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['contact_no']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No staff accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>