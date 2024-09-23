<?php
require 'Database.php';
$db = new Database();
$conn = $db->connect();

// Initialize variables
$error = "";
$customerId = $customerName = $contactNo = $routeId = $busNo = $seatTaken = $cost = $dateBook = $departureTime = "";
$busSeat = [];
$seats = [];

// Handle adding or updating booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $customerId = $_POST['customer_id'];
    $seatTaken = $_POST['seat_taken'];
    $routeId = $_POST['route_id'];
    $busNo = $_POST['bus_no'];
    $dateBook = $_POST['date_book'];

    // Validate date_book
    $currentDate = date('Y-m-d');
    if ($dateBook <= $currentDate) {
        $error = "Error: Booking date must be at least one day after today.";
    } else {
        // Check if seat is already taken for the selected bus, route, and date
        $sql = "SELECT * FROM booking WHERE seat_taken = ? AND fk_route_id = ? AND fk_bus_id = (SELECT bus_id FROM bus WHERE bus_no = ?) AND date_book = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$seatTaken, $routeId, $busNo, $dateBook]);
        $existingBooking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingBooking) {
            $error = "Error: This seat is already booked for the selected route, bus, and date.";
        } else {
            // Insert booking
            $sql = "INSERT INTO booking (fk_customer_id, fk_route_id, fk_bus_id, seat_taken, date_book) VALUES (?, ?, (SELECT bus_id FROM bus WHERE bus_no = ?), ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$customerId, $routeId, $busNo, $seatTaken, $dateBook]);
            echo "Booking added successfully!";
        }
    }
}

// Handle customer lookup
if (isset($_GET['customer_id'])) {
    $customerId = $_GET['customer_id'];
    $sql = "SELECT * FROM customer WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        $customerName = $customer['first_name'] . " " . $customer['last_name'];
        $contactNo = $customer['contact_no'];
    }
}

// Handle route and bus lookup
if (isset($_GET['route_id'])) {
    $routeId = $_GET['route_id'];
    $sql = "SELECT r.route_name, r.departure_time, r.cost, b.bus_no, b.bus_seat FROM route r JOIN bus b ON r.fk_bus_id = b.bus_id WHERE r.route_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$routeId]);
    $routeDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($routeDetails) {
        $busNo = $routeDetails['bus_no'];
        $cost = $routeDetails['cost'];
        $busSeat = $routeDetails['bus_seat'];
        $departureTime = $routeDetails['departure_time'];
        $seats = range(1, $busSeat); // Create seat array
    }
}

// Read bookings to show in a table
$sql = "SELECT b.*, c.first_name, c.last_name, r.route_name, bu.bus_no FROM booking b 
        JOIN customer c ON b.fk_customer_id = c.customer_id 
        JOIN route r ON b.fk_route_id = r.route_id 
        JOIN bus bu ON b.fk_bus_id = bu.bus_id";
$stmt = $conn->prepare($sql);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management</title>
    <script>
        function fetchCustomerDetails() {
            const customerId = document.getElementById('customer_id').value;
            if (customerId) {
                window.location.href = `?customer_id=${customerId}&route_id=${document.getElementById('route_id').value}`;
            }
        }

        function fetchRouteDetails() {
            const routeId = document.getElementById('route_id').value;
            if (routeId) {
                window.location.href = `?route_id=${routeId}&customer_id=${document.getElementById('customer_id').value}`;
            }
        }

        function setMinBookingDate() {
            const today = new Date();
            today.setDate(today.getDate() + 1);
            const minDate = today.toISOString().split('T')[0];
            document.getElementById('date_book').setAttribute('min', minDate);
        }
    </script>
</head>
<body onload="setMinBookingDate()">

<h1>Booking Management</h1>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<h2>Add Booking</h2>
<form method="POST" action="">
    <input type="hidden" name="action" value="add">

    <label for="customer_id">Customer ID:</label>
    <input type="number" name="customer_id" id="customer_id" value="<?php echo htmlspecialchars($customerId); ?>" onchange="fetchCustomerDetails()" required><br>

    <label>Customer Name:</label>
    <input type="text" value="<?php echo htmlspecialchars($customerName); ?>" readonly><br>

    <label>Contact No:</label>
    <input type="text" value="<?php echo htmlspecialchars($contactNo); ?>" readonly><br>

    <label for="route_id">Route:</label>
    <select name="route_id" id="route_id" onchange="fetchRouteDetails()" required>
        <option value="">Select Route - Bus</option>
        <?php 
        $routes = $conn->query("SELECT r.route_id, r.route_name, b.bus_no FROM route r JOIN bus b ON r.fk_bus_id = b.bus_id")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($routes as $route) {
            $selected = ($route['route_id'] == $routeId) ? 'selected' : '';
            echo "<option value='{$route['route_id']}' $selected>{$route['route_name']} - {$route['bus_no']}</option>";
        }
        ?>
    </select><br>

    <label>Cost:</label>
    <input type="number" step="0.01" name="cost" value="<?php echo htmlspecialchars($cost); ?>" readonly><br>

    <label>Departure Time:</label>
    <input type="text" value="<?php echo htmlspecialchars($departureTime); ?>" readonly><br>

    <label for="seat_taken">Seat Selection:</label>
    <div>
        <?php 
        if ($busSeat) {
            $bookedSeats = $conn->prepare("SELECT seat_taken FROM booking WHERE fk_route_id = ? AND date_book = ?");
            $bookedSeats->execute([$routeId, $dateBook]);
            $takenSeats = $bookedSeats->fetchAll(PDO::FETCH_COLUMN);

            foreach ($seats as $seat) {
                $disabled = in_array($seat, $takenSeats) ? 'disabled' : '';
                echo "<label><input type='radio' name='seat_taken' value='$seat' $disabled required>$seat</label>";
            }
        }
        ?>
    </div><br>

    <label for="date_book">Booking Date:</label>
    <input type="date" name="date_book" id="date_book" required><br>

    <input type="submit" value="Add Booking">
</form>

<h2>View Bookings</h2>
<table border="1">
    <tr>
        <th>Booking ID</th>
        <th>Customer Name</th>
        <th>Route Name - Bus</th>
        <th>Seats Taken</th>
        <th>Date Booked</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($bookings as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
        <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
        <td><?php echo htmlspecialchars($row['route_name'] . " - " . $row['bus_no']); ?></td>
        <td><?php echo htmlspecialchars($row['seat_taken']); ?></td>
        <td><?php echo htmlspecialchars($row['date_book']); ?></td>
        <td>
            <a href="?delete=<?php echo htmlspecialchars($row['booking_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

<?php
$conn = null; // Close the connection
?>
