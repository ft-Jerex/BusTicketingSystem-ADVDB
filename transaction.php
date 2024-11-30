<?php
require_once 'database.php';
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['customer']);

// Ensure the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customerId = $_SESSION['customer_id'];
$db = new Database();

// Function to get the full name of the logged-in user
function getFullName() {
    if (isset($_SESSION['customer'])) {
        return $_SESSION['customer']['first_name'] . ' ' . $_SESSION['customer']['last_name'];
    }
    return '';
}

try {
    // Fetch bookings with additional details
    $query = $db->connect()->prepare("
        SELECT 
            b.booking_id,
            b.date_book,
            bs.bus_no,
            r.route_name,
            b.seat_taken,
            r.cost,
            CASE
                WHEN b.date_book > CURDATE() THEN 'Upcoming'
                ELSE 'Past'
            END AS status
        FROM 
            booking b
        JOIN 
            bus bs ON b.fk_bus_id = bs.bus_id
        JOIN 
            route r ON b.fk_route_id = r.route_id
        WHERE 
            b.fk_customer_id = :customer_id
        ORDER BY 
            b.date_book DESC
    ");
    $query->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
    $query->execute();
    $bookings = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching transaction history: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="userStyle1.css">
</head>
<body>


<div class="container-header">
    <header>
        <a href="./index.php" class="logo"><img src="./img/arrow-left-solid.svg" alt=""></a>

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
            <a href="login.php" class="nav-link">Sign In</a>
        <?php endif; ?>
    </header>
</div>

<div class="container mt-5">
    <h2 class="mb-4">Transaction History</h2>
    <?php if (count($bookings) > 0): ?>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Booking ID</th>
                    <th>Route</th>
                    <th>Bus No</th>
                    <th>Seat</th>
                    <th>Price</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                        <td><?= htmlspecialchars($booking['route_name']) ?></td>
                        <td><?= htmlspecialchars($booking['bus_no']) ?></td>
                        <td><?= htmlspecialchars($booking['seat_taken']) ?></td>
                        <td>₱<?= htmlspecialchars(number_format($booking['cost'], 2)) ?></td>
                        <td><?= htmlspecialchars($booking['date_book']) ?></td>
                        <td>
                            <span class="badge bg-<?= $booking['status'] === 'Upcoming' ? 'success' : 'secondary' ?>">
                                <?= htmlspecialchars($booking['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No transaction history available.</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
