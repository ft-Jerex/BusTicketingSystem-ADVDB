<?php
require_once 'database.php'; // Import the Database class
$db = new Database();
$conn = $db->connect(); // Establish the database connection using the Database class

session_start();
if (!isset($_SESSION['customer']) || !$_SESSION['customer']['isAdmin']) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

if (!$conn) {
    die("Database connection failed.");
}

function getFullName() {
    if (isset($_SESSION['customer'])) {
        return $_SESSION['customer']['first_name'] . ' ' . $_SESSION['customer']['last_name'];
    }
    return '';
}

// Function to sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Handle Create
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $routeName = sanitize($_POST['route_name']);
    $busNo = sanitize($_POST['bus_no']);
    $departureTime = sanitize($_POST['departure_time']);
    $cost = filter_var($_POST['cost'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Get bus_id based on bus_no
    $sql = "SELECT bus_id FROM bus WHERE bus_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$busNo]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bus) {
        $busId = $bus['bus_id'];
        $sql = "INSERT INTO route (route_name, fk_bus_id, departure_time, cost) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$routeName, $busId, $departureTime, $cost]);
    } else {
        echo "Bus number not found.";
    }
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = filter_var($_POST['route_id'], FILTER_SANITIZE_NUMBER_INT);
    $routeName = sanitize($_POST['route_name']);
    $busNo = sanitize($_POST['bus_no']);
    $departureTime = sanitize($_POST['departure_time']);
    $cost = filter_var($_POST['cost'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Get bus_id based on bus_no
    $sql = "SELECT bus_id FROM bus WHERE bus_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$busNo]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bus) {
        $busId = $bus['bus_id'];
        $sql = "UPDATE route SET route_name=?, fk_bus_id=?, departure_time=?, cost=? WHERE route_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$routeName, $busId, $departureTime, $cost, $id]);
    } else {
        echo "Bus number not found.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    $sql = "DELETE FROM route WHERE route_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Read Routes
$sql = "SELECT r.*, b.bus_no FROM route r JOIN bus b ON r.fk_bus_id = b.bus_id";
$stmt = $conn->prepare($sql);
$stmt->execute();
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Management</title>
    <link rel="stylesheet" href="./adminStyle.css">
</head>
<body>
<header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
        <div class="admin-img"></div>
    </header>
    <section class="sidebar">
    <!-- <div class="IBT-admin">Admin</div> -->
    <div class="admin-name">Admin: <?php echo getFullName()?></div>
        <ul>
            <li><a href="dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="bus.php" class="menu-item">Bus</a></li>
            <li><a class="active_link" href="route.php" class="menu-item">Route</a></li>
            <li><a href="customer.php" class="menu-item">Customer</a></li>
            <li><a href="booking.php" class="menu-item">Bookings</a></li>
            <hr class="menu-itemHR">
            <li><a href="logout.php" class="logoutBtn">Logout</a></li>
        </ul>
    </section>
    <main class="main">
        <div id="main-content">
        <h1>Route Management</h1>
    <div class="AddEdit">
    <form method="POST" action="">
    <h2>Add Route</h2>
        <input type="hidden" name="action" value="add">
        <label for="route_name">Route Name:</label>
        <input type="text" name="route_name" required><br>
        
        <label for="bus_no">Bus No:</label>
        <input type="text" name="bus_no" required><br>
        
        <label for="departure_time">Departure Time:</label>
        <input type="time" name="departure_time" required><br>
        
        <label for="cost">Cost:</label>
        <input type="number" step="0.01" name="cost" required><br>
        
        <input type="submit" value="Add Route">
    </form>

    <?php if (isset($_GET['edit'])): 
        $id = filter_var($_GET['edit'], FILTER_SANITIZE_NUMBER_INT);
        $sql = "SELECT r.*, b.bus_no FROM route r JOIN bus b ON r.fk_bus_id = b.bus_id WHERE route_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $route = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    
    <form method="POST" action="">
    <h2>Edit Route</h2>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="route_id" value="<?php echo htmlspecialchars($route['route_id'], ENT_QUOTES, 'UTF-8'); ?>">
        <label for="route_name">Route Name:</label>
        <input type="text" name="route_name" value="<?php echo htmlspecialchars($route['route_name'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <label for="bus_no">Bus No:</label>
        <input type="text" name="bus_no" value="<?php echo htmlspecialchars($route['bus_no'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <label for="departure_time">Departure Time:</label>
        <input type="time" name="departure_time" value="<?php echo htmlspecialchars($route['departure_time'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <label for="cost">Cost:</label>
        <input type="number" step="0.01" name="cost" value="<?php echo htmlspecialchars($route['cost'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <input type="submit" value="Update Route">
    </form>
    <?php endif; ?>
    </div>

    <h2>View Routes</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Route Name</th>
            <th>Bus No</th>
            <th>Departure Time</th>
            <th>Cost</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($routes as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['route_id'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['route_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['bus_no'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['departure_time'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['cost'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a class="editBtn" href="?edit=<?php echo urlencode($row['route_id']); ?>">Edit</a>
                <a class="deleteBtn" href="?delete=<?php echo urlencode($row['route_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>


        </div>
    </main>

</body>
</html>

<?php
$conn = null; // Close the connection
?>