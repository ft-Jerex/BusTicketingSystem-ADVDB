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


    class BookingSystem extends Database
{
    private $db;

    public function __construct()
    {
        $this->db = $this->connect();
    }

    public function getCustomerDetails($customer_id)
    {
        $sql = "SELECT CONCAT(first_name, ' ', last_name) AS full_name, contact_no FROM customer WHERE customer_id = ?";
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

    public function getExistingBookings($searchTerm = '', $limit = null, $offset = null)
    {
        $sql = "SELECT b.booking_id, c.customer_id, CONCAT(c.first_name, ' ', c.last_name) AS full_name, 
                c.contact_no, r.route_name, bu.bus_no, b.seat_taken, b.date_book 
                FROM booking b 
                JOIN customer c ON b.fk_customer_id = c.customer_id 
                JOIN route r ON b.fk_route_id = r.route_id 
                JOIN bus bu ON b.fk_bus_id = bu.bus_id";
        
        if ($searchTerm) {
            $sql .= " WHERE CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                      OR c.contact_no LIKE :search 
                      OR r.route_name LIKE :search 
                      OR bu.bus_no LIKE :search";
        }
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($searchTerm) {
            $searchTerm = "%$searchTerm%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        if ($limit !== null && $offset !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalBookings($searchTerm = '')
    {
        $sql = "SELECT COUNT(*) FROM booking b 
                JOIN customer c ON b.fk_customer_id = c.customer_id 
                JOIN route r ON b.fk_route_id = r.route_id 
                JOIN bus bu ON b.fk_bus_id = bu.bus_id";
        
        if ($searchTerm) {
            $sql .= " WHERE CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                      OR c.contact_no LIKE :search 
                      OR r.route_name LIKE :search 
                      OR bu.bus_no LIKE :search";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($searchTerm) {
            $searchTerm = "%$searchTerm%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}

$bookingSystem = new BookingSystem();

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... [Previous POST handling code remains unchanged] ...
}

// Get total results and calculate total pages
$totalResults = $bookingSystem->getTotalBookings($searchTerm);
$totalPages = ceil($totalResults / $resultsPerPage);

// Adjust page number if it's out of bounds
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $resultsPerPage;

$routes = $bookingSystem->getRouteDetails();
$existingBookings = $bookingSystem->getExistingBookings($searchTerm, $resultsPerPage, $offset);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management</title>
    <link rel="stylesheet" href="./adminStyle.css">
    <style>
        .table-controls {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: black;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
        }
        .form-controls {
            background-color: transparent;
            box-shadow: none;
            padding: 0px;
        }
        .form-controls input {
            width: 300px;
        }
        .form-controls .search-Btn, .form-controls .refresh-Btn {
            height: 40px;   
            padding: 10px;
            font-size: 16px;
            color: #000000;
            background-color: #D3D3D3;
            border: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
    </header>
    <section class="sidebar">
    <div class="admin-name">Admin: <?php echo getFullName()?></div>
    <hr class="menu-itemHR">
    <ul>
        <li><a href="dashboard.php" class="menu-item">Dashboard</a></li>
        <li><a href="bus.php" class="menu-item">Bus</a></li>
        <li><a href="route.php" class="menu-item">Route</a></li>
        <li><a href="customer.php" class="menu-item">Customer</a></li>
        <li><a class="active_link" href="booking.php" class="menu-item">Bookings</a></li>
        
        <?php 
        // Only show Staff Management for admin users
        if (isset($_SESSION['customer']) && 
            ($_SESSION['customer']['role'] === 'admin' || 
             $_SESSION['customer']['isAdmin'] == 1)) : ?>
            <li><a href="registerStaff.php" class="menu-item">Staff Management</a></li>
        <?php endif; ?>
        
        <hr class="menu-itemHR">
        <li><a href="../logout.php" class="logoutBtn">Logout</a></li>
    </ul>
</section>
    
    <main class="main">
        <div id="main-content">
            <h1>Booking Management</h1>
            <div class="AddEdit">
        <form method="post" action="">
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

    <label for="cost">Cost:</label>
    <input type="text" id="cost" readonly><br>

    <label for="seats">Number of Seats:</label>
    <input type="text" id="seats" readonly><br>

    <label for="departure_time">Departure Time:</label>
    <input type="text" id="departure_time" readonly><br>

    <label for="date_book">Booking Date:</label>
    <input type="date" name="date_book" id="date_book" required><br>

    <input type="submit" value="Submit">
</form>

<section class="seat">
    <label>Select Seat:</label>
    <div id="seat_grid"></div>
</section>
    </div>

            <h2>Existing Bookings</h2>
<!-- Add the search and refresh controls -->
<div class="table-controls">
                <form class="form-controls" method="GET" action="booking.php">
                    <a href="booking.php" class="refresh-Btn">
                        Refresh
                    </a>
                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="search-Btn">Search</button>
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
                <button class="editBtn" onclick='editBooking(<?= json_encode($booking) ?>)'>Edit</button>
                <button class="deleteBtn" onclick='deleteBooking(<?= $booking['booking_id'] ?>)'>Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
    </div>

            <!-- Add pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($searchTerm); ?>">Previous</a>
                <?php endif; ?>

                <?php
                for ($i = 1; $i <= $totalPages; $i++) {
                    $activeClass = ($i == $page) ? 'active' : '';
                    echo "<a href='?page=$i&search=" . urlencode($searchTerm) . "' class='$activeClass'>$i</a>";
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($searchTerm); ?>">Next</a>
                <?php endif; ?>
            </div>

            <!-- ... [Previous JavaScript content remains unchanged] ... -->
            <script>
    document.addEventListener('DOMContentLoaded', function() {
        const customerIdInput = document.getElementById('customer_id');
        const fullNameInput = document.getElementById('full_name');
        const contactNoInput = document.getElementById('contact_no');
        const routeSelect = document.getElementById('route_id');
        const costInput = document.getElementById('cost');
        const seatsInput = document.getElementById('seats');
        const departureTimeInput = document.getElementById('departure_time');
        const dateBookInput = document.getElementById('date_book');
        const seatGrid = document.getElementById('seat_grid');
        const busIdInput = document.getElementById('bus_id');

        customerIdInput.addEventListener('change', function() {
            fetch(`get_customer_details.php?customer_id=${this.value}`)
                .then(response => response.json())
                .then(data => {
                    fullNameInput.value = data.full_name;
                    contactNoInput.value = data.contact_no;
                });
        });

        routeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            costInput.value = selectedOption.dataset.cost;
            seatsInput.value = selectedOption.dataset.seats;
            departureTimeInput.value = selectedOption.dataset.departureTime;
            busIdInput.value = selectedOption.dataset.busId;
            updateSeatGrid();
        });

        dateBookInput.addEventListener('change', updateSeatGrid);

        function updateSeatGrid() {
            const routeId = routeSelect.value;
            const dateBook = dateBookInput.value;
            const totalSeats = parseInt(seatsInput.value);

            if (routeId && dateBook && totalSeats) {
                fetch(`get_occupied_seats.php?route_id=${routeId}&date_book=${dateBook}`)
                    .then(response => response.json())
                    .then(occupiedSeats => {
                        seatGrid.innerHTML = '';
                        for (let i = 1; i <= totalSeats; i++) {
                            const input = document.createElement('input');
                            input.type = 'radio';
                            input.name = 'seat_taken';
                            input.value = i;
                            input.id = `seat_${i}`;
                            if (occupiedSeats.includes(i)) {
                                input.disabled = true;
                            }
                            const label = document.createElement('label');
                            label.htmlFor = `seat_${i}`;
                            label.textContent = i;
                            seatGrid.appendChild(input);
                            seatGrid.appendChild(label);
                        }
                    });
            }
        }

        // Set minimum date for booking
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);a
        dateBookInput.min = tomorrow.toISOString().split('T')[0];
    });

    function editBooking(booking) {
        document.getElementById('action').value = 'edit';
        document.getElementById('booking_id').value = booking.booking_id;
        document.getElementById('customer_id').value = booking.customer_id;
        document.getElementById('full_name').value = booking.full_name;
        document.getElementById('contact_no').value = booking.contact_no;
        document.getElementById('route_id').value = booking.route_name + ' - ' + booking.bus_no;
        document.getElementById('date_book').value = booking.date_book;
        updateSeatGrid();
        document.querySelector(`input[name="seat_taken"][value="${booking.seat_taken}"]`).checked = true;
    }

    function deleteBooking(bookingId) {
        if (confirm('Are you sure you want to delete this booking?')) {
            document.getElementById('action').value = 'delete';
            document.getElementById('booking_id').value = bookingId;
            document.querySelector('form').submit();
        }
    }
</script>
        </div>
    </main>
    
</body>
</html>