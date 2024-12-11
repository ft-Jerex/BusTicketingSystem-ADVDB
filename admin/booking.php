<?php
require_once '../database.php';
require '../sanitize.php';

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

// Pagination setup
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// Search functionality
$searchTerm = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$searchDate = isset($_GET['date']) ? clean_input($_GET['date']) : '';


    class BookingSystem extends Database
{
    private $db;

    public function __construct()
    {
        $this->db = $this->connect();
    }

    public function getCustomerDetails($customer_id)
    {
        $sql = "SELECT customer_id, CONCAT(first_name, ' ', last_name) AS full_name, contact_no 
                FROM customer WHERE customer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRouteDetails()
    {
        $sql = "SELECT r.route_id, r.route_name, b.bus_no, r.cost, r.departure_time, b.bus_seat, b.bus_id 
                FROM route r 
                JOIN bus b ON r.fk_bus_id = b.bus_id";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOccupiedSeats($route_id, $date_book)
    {
        $sql = "SELECT seat_taken FROM booking WHERE fk_route_id = ? AND date_book = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$route_id, $date_book]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function addBooking($customer_id, $bus_id, $route_id, $seat_taken, $date_book)
    {
        $sql = "INSERT INTO booking (fk_customer_id, fk_bus_id, fk_route_id, seat_taken, date_book) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customer_id, $bus_id, $route_id, $seat_taken, $date_book]);
        return $stmt->rowCount() > 0;
    }

    public function updateBooking($booking_id, $bus_id, $route_id, $seat_taken, $date_book)
    {
        $sql = "UPDATE booking 
                SET fk_bus_id = ?, fk_route_id = ?, seat_taken = ?, date_book = ? 
                WHERE booking_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bus_id, $route_id, $seat_taken, $date_book, $booking_id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteBooking($booking_id)
    {
        $sql = "DELETE FROM booking WHERE booking_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$booking_id]);
        return $stmt->rowCount() > 0;
    }

    public function getExistingBookings($searchTerm = '', $searchDate = '', $limit = null, $offset = null)
    {
        $sql = "SELECT b.booking_id, c.customer_id, CONCAT(c.first_name, ' ', c.last_name) AS full_name, 
                c.contact_no, r.route_name, bu.bus_no, b.seat_taken, b.date_book, r.route_id, bu.bus_id 
                FROM booking b 
                JOIN customer c ON b.fk_customer_id = c.customer_id 
                JOIN route r ON b.fk_route_id = r.route_id 
                JOIN bus bu ON b.fk_bus_id = bu.bus_id";
        
        $conditions = [];
        if ($searchTerm) {
            $conditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                           OR c.contact_no LIKE :search 
                           OR r.route_name LIKE :search 
                           OR bu.bus_no LIKE :search)";
        }
        if ($searchDate) {
            $conditions[] = "b.date_book = :date";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add ORDER BY clause for descending booking_id
        $sql .= " ORDER BY b.booking_id DESC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($searchTerm) {
            $searchTerm = "%$searchTerm%";
            $stmt->bindParam(':search', $searchTerm);
        }
        if ($searchDate) {
            $stmt->bindParam(':date', $searchDate);
        }
        
        if ($limit !== null && $offset !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalBookings($searchTerm = '', $searchDate = '')
    {
        $sql = "SELECT COUNT(*) FROM booking b 
                JOIN customer c ON b.fk_customer_id = c.customer_id 
                JOIN route r ON b.fk_route_id = r.route_id 
                JOIN bus bu ON b.fk_bus_id = bu.bus_id";
        
        $conditions = [];
        if ($searchTerm) {
            $conditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                           OR c.contact_no LIKE :search 
                           OR r.route_name LIKE :search 
                           OR bu.bus_no LIKE :search)";
        }
        if ($searchDate) {
            $conditions[] = "b.date_book = :date";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($searchTerm) {
            $searchTerm = "%$searchTerm%";
            $stmt->bindParam(':search', $searchTerm);
        }
        if ($searchDate) {
            $stmt->bindParam(':date', $searchDate);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
}

$bookingSystem = new BookingSystem();

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $action = isset($_POST['action']) ? clean_input($_POST['action']) : '';
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
    $date_book = isset($_POST['date_book']) ? clean_input($_POST['date_book']) : '';
    $seat_taken = isset($_POST['seat_taken']) ? (int)$_POST['seat_taken'] : null;
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

    // Validation checks
    $errors = [];

    if ($action != 'delete') {
        if (!$customer_id) {
            $errors[] = "Customer ID is required.";
        }

        if (!$route_id) {
            $errors[] = "Route selection is required.";
        }

        if (!$date_book) {
            $errors[] = "Booking date is required.";
        }

        if ($seat_taken === null) {
            $errors[] = "Seat selection is required.";
        }

        // Get route details to fetch bus_id
        $sql = "SELECT fk_bus_id AS bus_id FROM route WHERE route_id = ?";
        $stmt = $bookingSystem->connect()->prepare($sql);
        $stmt->execute([$route_id]);
        $routeDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$routeDetails) {
            $errors[] = "Invalid route selected.";
        }

        // Check if seat is already booked (except for the current booking when editing)
        $occupiedSeats = $bookingSystem->getOccupiedSeats($route_id, $date_book);
        if ($action == 'edit') {
            $occupiedSeats = array_filter($occupiedSeats, function($seat) use ($booking_id) {
                return $seat != $booking_id;
            });
        }
        if (in_array($seat_taken, $occupiedSeats)) {
            $errors[] = "Selected seat is already booked.";
        }
    }

    // Process action based on validation
    if (empty($errors)) {
        try {
            switch ($action) {
                case 'add':
                    $result = $bookingSystem->addBooking(
                        $customer_id, 
                        $routeDetails['bus_id'], 
                        $route_id, 
                        $seat_taken, 
                        $date_book
                    );
                    $_SESSION['message'] = $result ? "Booking added successfully." : "Failed to add booking.";
                    break;

                case 'edit':
                    $result = $bookingSystem->updateBooking(
                        $booking_id,
                        $routeDetails['bus_id'], 
                        $route_id, 
                        $seat_taken, 
                        $date_book
                    );
                    $_SESSION['message'] = $result ? "Booking updated successfully." : "Failed to update booking.";
                    break;

                case 'delete':
                    $result = $bookingSystem->deleteBooking($booking_id);
                    $_SESSION['message'] = $result ? "Booking deleted successfully." : "Failed to delete booking.";
                    break;

                default:
                    $_SESSION['error'] = "Invalid action specified.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode(', ', $errors);
    }

    // Redirect to prevent form resubmission
    header('Location: booking.php');
    exit();
}

// Fetch route details
$routes = $bookingSystem->getRouteDetails();

// Fetch existing bookings (similar to previous implementation)
$searchTerm = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$searchDate = isset($_GET['date']) ? clean_input($_GET['date']) : '';
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalResults = $bookingSystem->getTotalBookings($searchTerm, $searchDate);
$totalPages = ceil($totalResults / $resultsPerPage);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $resultsPerPage;

$existingBookings = $bookingSystem->getExistingBookings($searchTerm, $searchDate, $resultsPerPage, $offset);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management</title>
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
            <li><a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bus.php" class="menu-item"><i class="fas fa-bus"></i> Bus</a></li>
            <li><a href="route.php" class="menu-item"><i class="fas fa-route"></i> Route</a></li>
            <li><a href="customer.php" class="menu-item"><i class="fas fa-users"></i> Customer</a></li>
            <li><a class="active_link" href="booking.php" class="menu-item"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
            
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
        <div id="main-content">
            <h1>Booking Management</h1>
            <!-- Booking Form -->
            <div class="AddEditBooking">
                <form method="post" action="booking.php" class="booking-form-container">
                    <div class="booking-details">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="booking_id" id="booking_id">
                        <input type="hidden" name="bus_id" id="bus_id">

                        <label for="customer_id">Customer ID:</label>
                        <input type="number" name="customer_id" id="customer_id" required><br>

                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" readonly><br>

                        <label for="contact_no">Contact No:</label>
                        <input type="text" id="contact_no" readonly><br>

                        <label for="route_id">Route:</label>
                        <select name="route_id" id="route_id" required>
                            <option value="">Select a route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?= $route['route_id'] ?>" 
                                        data-cost="<?= $route['cost'] ?>" 
                                        data-seats="<?= $route['bus_seat'] ?>" 
                                        data-bus-id="<?= $route['bus_id'] ?>" 
                                        data-departure-time="<?= $route['departure_time'] ?>">
                                    <?= $route['route_name'] ?> - <?= $route['bus_no'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select><br>

                        <label for="date_book">Booking Date:</label>
                        <input type="date" name="date_book" id="date_book" required><br>

                        <div class="seat-selection">
                            <section class="seat">
                                <label>Select Seat:</label>
                                <div id="seat_grid"></div>
                                <input type="hidden" name="seat_taken" id="seat_taken">
                            </section>
                        </div>

                        <input type="submit" value="Submit Booking">
                    </div>
                </form>
            </div>

            <h2>Existing Bookings</h2>
<!-- Add the search and refresh controls -->
<div class="table-controls">
                <form class="form-controls" method="GET" action="booking.php">
                <input type="date" name="date" value="<?php echo htmlspecialchars($searchDate); ?>">
                    <a href="booking.php" class="refresh-Btn">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="search-Btn"><i class="fas fa-search"></i></button>
                </form>
            </div>
<table border="1">
    <tr>
        <th>Booking ID</th>
        <th>Customer Name</th>
        <th>Contact No</th>
        <th>Route</th>
        <th>Bus No</th>
        <th>Seat</th>
        <th>Date</th>
        <th>Action</th>
    </tr>
    <?php foreach ($existingBookings as $booking): ?>
        <tr>
            <td><?= $booking['booking_id'] ?></td>
            <td><?= $booking['full_name'] ?></td>
            <td><?= $booking['contact_no'] ?></td>
            <td><?= $booking['route_name'] ?></td>
            <td><?= $booking['bus_no'] ?></td>
            <td><?= $booking['seat_taken'] ?></td>
            <td><?= $booking['date_book'] ?></td>
            <td>
                <button class="editBtn" style="color:#ffffff; border: none;" onclick='editBooking(<?= json_encode($booking) ?>)'><i class="fas fa-edit"></i></button>
                <form method="post" action="booking.php" style="display:inline; background-color: transparent; box-shadow: none; padding: 0; margin: 0;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                    <button type="submit" class="deleteBtn" style="color:#ffffff; border: none;" onclick="return confirm('Are you sure you want to delete this booking?')"><i class="fas fa-trash-alt"></i></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
    </div>

            <!-- Add pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($searchTerm); ?>&date=<?php echo urlencode($searchDate); ?>">Previous</a>
                <?php endif; ?>

                <?php
                for ($i = 1; $i <= $totalPages; $i++) {
                    $activeClass = ($i == $page) ? 'active' : '';
                    echo "<a href='?page=$i&search=" . urlencode($searchTerm) . "&date=" . urlencode($searchDate) . "' class='$activeClass'>$i</a>";
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($searchTerm); ?>&date=<?php echo urlencode($searchDate); ?>">Next</a>
                <?php endif; ?>
            </div>

            <!-- ... [Previous JavaScript content remains unchanged] ... -->
            <script>
    document.addEventListener('DOMContentLoaded', function() {
        const customerIdInput = document.getElementById('customer_id');
        const fullNameInput = document.getElementById('full_name');
        const contactNoInput = document.getElementById('contact_no');
        const routeSelect = document.getElementById('route_id');
        const dateBookInput = document.getElementById('date_book');
        const seatGrid = document.getElementById('seat_grid');
        const busIdInput = document.getElementById('bus_id');
        const seatTakenInput = document.getElementById('seat_taken');

        // Fetch customer details when customer ID changes
        customerIdInput.addEventListener('change', function() {
            fetch(`get_customer_details.php?customer_id=${this.value}`)
                .then(response => response.json())
                .then(data => {
                    fullNameInput.value = data.full_name;
                    contactNoInput.value = data.contact_no;
                });
        });

        // Update route details and seat grid when route is selected
        routeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            busIdInput.value = selectedOption.dataset.busId;
            updateSeatGrid();
        });

        // Update seat grid when booking date changes
        dateBookInput.addEventListener('change', updateSeatGrid);

        function updateSeatGrid() {
            const routeId = routeSelect.value;
            const dateBook = dateBookInput.value;
            const totalSeats = parseInt(routeSelect.selectedOptions[0].dataset.seats);

            if (routeId && dateBook && totalSeats) {
                fetch(`get_occupied_seats.php?route_id=${routeId}&date_book=${dateBook}`)
                    .then(response => response.json())
                    .then(occupiedSeats => {
                        seatGrid.innerHTML = '';
                        for (let i = 1; i <= totalSeats; i++) {
                            const input = document.createElement('input');
                            input.type = 'radio';
                            input.name = 'seat_radio';
                            input.value = i;
                            input.id = `seat_${i}`;
                            
                            const label = document.createElement('label');
                            label.htmlFor = `seat_${i}`;
                            label.textContent = i;

                            // Mark occupied seats
                            if (occupiedSeats.includes(i)) {
                                label.classList.add('occupied');
                                input.disabled = true;
                            }

                            // Add click event to handle seat selection
                            input.addEventListener('change', function() {
                                if (this.checked) {
                                    seatTakenInput.value = this.value;
                                    // Remove previous selections
                                    seatGrid.querySelectorAll('label').forEach(l => {
                                        l.classList.remove('selected');
                                    });
                                    // Mark current seat as selected
                                    label.classList.add('selected');
                                }
                            });

                            seatGrid.appendChild(input);
                            seatGrid.appendChild(label);
                        }
                    });
            }
        }

        // Set minimum date for booking
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateBookInput.min = tomorrow.toISOString().split('T')[0];
    });

    function editBooking(booking) {
        document.getElementById('action').value = 'edit';
        document.getElementById('booking_id').value = booking.booking_id;
        document.getElementById('customer_id').value = booking.customer_id;
        document.getElementById('full_name').value = booking.full_name;
        document.getElementById('contact_no').value = booking.contact_no;
        document.getElementById('route_id').value = booking.route_id;
        document.getElementById('date_book').value = booking.date_book;
        document.getElementById('bus_id').value = booking.bus_id;
        document.getElementById('seat_taken').value = booking.seat_taken;

        // Trigger the seat grid update
        const routeSelect = document.getElementById('route_id');
        const event = new Event('change');
        routeSelect.dispatchEvent(event);

        // Scroll to the form
        document.querySelector('.AddEditBooking').scrollIntoView({behavior: 'smooth'});
    }
    </script>
</body>
</html>