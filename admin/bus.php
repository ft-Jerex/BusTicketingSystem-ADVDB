<?php
require_once '../database.php';
require '../sanitize.php'; // Include the sanitize.php file

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

$db = new Database();
$conn = $db->connect();

$errorBusNo = ""; // Variable to hold the error message

// Handle Create
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $busNo = clean_input($_POST['bus_no']);
    $busType = clean_input($_POST['bus_type']);
    $busSeat = clean_input($_POST['bus_seat']);

    // Check if bus_no already exists
    $sql = "SELECT * FROM bus WHERE bus_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$busNo]);
    $existingBus = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingBus) {
        $errorBusNo = "Error: Bus number already exists.";
    } else {
        $sql = "INSERT INTO bus (bus_no, bus_type, bus_seat) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$busNo, $busType, $busSeat]);
        echo "Bus added successfully!";
    }
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = clean_input($_POST['bus_id']);
    $busNo = clean_input($_POST['bus_no']);
    $busType = clean_input($_POST['bus_type']);
    $busSeat = clean_input($_POST['bus_seat']);

    // Check if bus_no already exists for a different bus
    $sql = "SELECT * FROM bus WHERE bus_no = ? AND bus_id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$busNo, $id]);
    $existingBus = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingBus) {
        $errorBusNo = "Error: Bus number already exists.";
    } else {
        $sql = "UPDATE bus SET bus_no=?, bus_type=?, bus_seat=? WHERE bus_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$busNo, $busType, $busSeat, $id]);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    $sql = "DELETE FROM bus WHERE bus_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Read Buses
$sql = "SELECT * FROM bus";
$stmt = $conn->prepare($sql);
$stmt->execute();
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Management</title>
    <link rel="stylesheet" href="./adminStyle.css">
</head>
<body>
<header class="header">
        <p class="header-p">IBT TICKETING SYSTEM</p>
    </header>
    <section class="sidebar">
    <div class="admin-name">Admin: <?php echo getFullName()?></div>
    <ul>
        <li><a href="dashboard.php" class="menu-item">Dashboard</a></li>
        <li><a class="active_link" href="bus.php" class="menu-item">Bus</a></li>
        <li><a href="route.php" class="menu-item">Route</a></li>
        <li><a href="customer.php" class="menu-item">Customer</a></li>
        <li><a href="booking.php" class="menu-item">Bookings</a></li>
        
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
           
            <h1>Bus Management</h1>
            <div class="AddEditBus"> 

            <form method="POST" action="">
            <h2>Add Bus</h2>
                <input type="hidden" name="action" value="add">
                <label for="bus_no">Bus No:</label>
                <input type="text" name="bus_no" required><br>
                <span style="color: red;"><?php echo htmlspecialchars($errorBusNo); ?></span><br> <!-- Error message for bus_no -->
                
                <label for="bus_type">Bus Type:</label>
                <select name="bus_type" required>
                    <option value="aircon">Aircon</option>
                    <option value="non-aircon">Non-Aircon</option>
                </select><br>
                
                <label for="bus_seat">Number of Seats:</label>
                <input type="number" name="bus_seat" required><br>
                
                <input type="submit" value="Add Bus">
            </form>

            <?php if (isset($_GET['edit'])): 
                $id = clean_input($_GET['edit']);
                $sql = "SELECT * FROM bus WHERE bus_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id]);
                $bus = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Reset error message for edit
                $errorBusNo = "";
            ?>
            
            <form method="POST" action="">
            <h2>Edit Bus</h2>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="bus_id" value="<?php echo htmlspecialchars($bus['bus_id']); ?>">
                <label for="bus_no">Bus No:</label>
                <input type="text" name="bus_no" value="<?php echo htmlspecialchars($bus['bus_no']); ?>" required><br>
                <span style="color: red;"><?php echo htmlspecialchars($errorBusNo); ?></span><br> <!-- Error message for bus_no -->
                
                <label for="bus_type">Bus Type:</label>
                <select name="bus_type" required>
                    <option value="aircon" <?php echo $bus['bus_type'] == 'aircon' ? 'selected' : ''; ?>>Aircon</option>
                    <option value="non-aircon" <?php echo $bus['bus_type'] == 'non-aircon' ? 'selected' : ''; ?>>Non-Aircon</option>
                </select><br>
                
                <label for="bus_seat">Number of Seats:</label>
                <input type="number" name="bus_seat" value="<?php echo htmlspecialchars($bus['bus_seat']); ?>" required><br>
                
                <input type="submit" value="Update Bus">
            </form>
            <?php endif; ?>
            </div> 

            <h2>View Buses</h2>
            <table border="1">
                <tr>
                    <th>ID</th>
                    <th>Bus No</th>
                    <th>Bus Type</th>
                    <th>Number of Seats</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($buses as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['bus_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['bus_no']); ?></td>
                    <td><?php echo htmlspecialchars($row['bus_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['bus_seat']); ?></td>
                    <td>
                        <a class="editBtn" href="?edit=<?php echo htmlspecialchars($row['bus_id']); ?>">Edit</a>
                        <a class="deleteBtn" href="?delete=<?php echo htmlspecialchars($row['bus_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
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