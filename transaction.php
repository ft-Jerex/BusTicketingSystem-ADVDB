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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Pagination setup
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

try {
    // Build the WHERE clause based on filters
    $where_conditions = ["b.fk_customer_id = :customer_id"];
    $params = [':customer_id' => $customerId];

    if ($status_filter === 'upcoming') {
        $where_conditions[] = "b.date_book > CURDATE()";
    } elseif ($status_filter === 'past') {
        $where_conditions[] = "b.date_book <= CURDATE()";
    }

    if (!empty($search)) {
        $where_conditions[] = "(r.route_name LIKE :search OR bs.bus_no LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($date_from)) {
        $where_conditions[] = "b.date_book >= :date_from";
        $params[':date_from'] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "b.date_book <= :date_to";
        $params[':date_to'] = $date_to;
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get total number of filtered bookings
    $countQuery = $db->connect()->prepare("
        SELECT COUNT(*) 
        FROM booking b
        JOIN bus bs ON b.fk_bus_id = bs.bus_id
        JOIN route r ON b.fk_route_id = r.route_id
        WHERE $where_clause
    ");
    foreach ($params as $key => $value) {
        $countQuery->bindValue($key, $value);
    }
    $countQuery->execute();
    $totalResults = $countQuery->fetchColumn();
    $totalPages = ceil($totalResults / $resultsPerPage);

    // Fetch bookings with filters and pagination
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
            $where_clause
        ORDER BY 
            b.booking_id DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $value) {
        $query->bindValue($key, $value);
    }
    $query->bindValue(':limit', $resultsPerPage, PDO::PARAM_INT);
    $query->bindValue(':offset', $offset, PDO::PARAM_INT);
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
        <a href="./index.php" class="logo"><img src="./img/arrow-left-solid.svg" alt="" style="filter: invert(1);"></a>

        <?php if ($isLoggedIn): ?>
            <div class="dropdown">
                <button class="dropbtn"></button>
                    <div class="dropdown-content">
                    <div class="username"><?php echo getFullName(); ?></div>
                    <!-- <hr> -->
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

    <!-- Filter Controls -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Bookings</option>
                        <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="past" <?= $status_filter === 'past' ? 'selected' : '' ?>>Past</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search route or bus no" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" placeholder="From Date" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" placeholder="To Date" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="transaction.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

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

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php else: ?>
        <div class="alert alert-info">No transaction history available.</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
