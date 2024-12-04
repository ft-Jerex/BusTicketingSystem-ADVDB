<?php
require_once 'database.php';
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
}

$bookingSystem = new BookingSystem();
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    die("User not logged in.");
}

$customer_details = $bookingSystem->getCustomerDetails($customer_id);
$routes = $bookingSystem->getRouteDetails();

function bookingAlert(){
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bookingSystem = new BookingSystem();
    $customer_id = $_SESSION['customer_id'] ?? null;
    $result = $bookingSystem->addBooking(
        $customer_id,
        $_POST['bus_id'],
        $_POST['route_id'],
        $_POST['seat_taken'],
        $_POST['date_book']
    );
    echo $result ? "Booking added successfully." : "Failed to add booking.";
     }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking System</title>
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

<main class="main">
    <div id="main-content">
        <h1 class="booking-title">Add a Booking</h1>
        <?php echo bookingAlert();?>
        <div class="AddEdit">
            <form method="post" action="">
                <input type="hidden" name="bus_id" id="bus_id">
                
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" value="<?= htmlspecialchars($customer_details['full_name']) ?>" readonly><br>

                <label for="contact_no">Contact No:</label>
                <input type="text" id="contact_no" value="<?= htmlspecialchars($customer_details['contact_no']) ?>" readonly><br>

                <label for="route_id">Route:</label>
                <select name="route_id" id="route_id" required>
                    <option value="">Select a route</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['route_id'] ?>" data-cost="<?= $route['cost'] ?>" data-seats="<?= $route['bus_seat'] ?>" data-bus-id="<?= $route['bus_id'] ?>" data-departure-time="<?= $route['departure_time'] ?>">
                            <?= $route['route_name'] ?> - <?= $route['bus_no'] ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>

                <div id="route-details" style="display:none;">
                    <p>Cost: <span id="route-cost"></span></p>
                    <p>Available Seats: <span id="route-seats"></span></p>
                    <p>Departure Time: <span id="route-departure-time"></span></p>
                </div>

                <label for="date_book">Booking Date:</label>
                <input type="date" name="date_book" id="date_book" required><br>

                <label>Select Seat:</label>
                <section class="seat-section">
                <div id="seat_grid"></div>
                </section>

                <input type="submit" value="Submit">
            </form>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const routeSelect = document.getElementById('route_id');
    const dateBookInput = document.getElementById('date_book');
    const seatGrid = document.getElementById('seat_grid');
    const busIdInput = document.getElementById('bus_id');
    const routeDetails = document.getElementById('route-details');
    const routeCost = document.getElementById('route-cost');
    const routeSeats = document.getElementById('route-seats');
    const routeDepartureTime = document.getElementById('route-departure-time');

    routeSelect.addEventListener('change', updateSeatGrid);
    dateBookInput.addEventListener('change', updateSeatGrid);

    function updateSeatGrid() {
    const routeId = routeSelect.value;
    const dateBook = dateBookInput.value;
    const totalSeats = parseInt(routeSelect.selectedOptions[0].dataset.seats);
    busIdInput.value = routeSelect.selectedOptions[0].dataset.busId;

    // Update route details
    if (routeId) {
        routeCost.textContent = routeSelect.selectedOptions[0].dataset.cost;
        routeSeats.textContent = totalSeats;
        routeDepartureTime.textContent = routeSelect.selectedOptions[0].dataset.departureTime;
        routeDetails.style.display = 'block';
    } else {
        routeDetails.style.display = 'none';
    }

    if (routeId && dateBook && totalSeats) {
        fetch(`get_occupied_seats.php?route_id=${routeId}&date_book=${dateBook}`)
            .then(response => response.json())
            .then(occupiedSeats => {
                seatGrid.innerHTML = '';
                for (let i = 1; i <= totalSeats; i++) {
                    // Create a hidden input
                    const input = document.createElement('input');
                    input.type = 'radio';
                    input.name = 'seat_taken';
                    input.value = i;
                    input.id = `seat_${i}`;
                    
                    // Create the label for the seat
                    const label = document.createElement('label');
                    label.htmlFor = `seat_${i}`;
                    label.textContent = i;

                    // Check if the seat is occupied
                    if (occupiedSeats.includes(i)) {
                        label.classList.add('occupied'); // Add the occupied class to the label
                        label.style.cursor = 'not-allowed'; // Change cursor to indicate it's not selectable
                    } else {
                        // Add click event to handle seat selection
                        label.addEventListener('click', function() {
                            // Remove 'selected' class from all labels
                            const allLabels = seatGrid.querySelectorAll('label');
                            allLabels.forEach(lbl => {
                                lbl.classList.remove('selected'); // Remove selected class
                                lbl.style.backgroundColor = 'white'; // Reset background color
                                lbl.style.color = '#007bff'; // Reset text color
                            });
                            
                            // Mark the current seat as selected
                            input.checked = true; // Check the hidden input
                            label.classList.add('selected'); // Add selected class
                            label.style.backgroundColor = '#007bff'; // Change background color
                            label.style.color = 'white'; // Change text color
                        });
                    }

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
</script>
</body>
</html>