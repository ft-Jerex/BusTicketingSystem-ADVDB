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

$db = new Database();
$conn = $db->connect();

$errorBusNo = "";

// Pagination setup
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// Search functionality
$searchTerm = isset($_GET['search']) ? clean_input($_GET['search']) : '';

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
        
        // Redirect to the same page without query parameters to reset view
        header("Location: bus.php");
        exit();
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
        
        // Redirect to the same page without query parameters to reset view
        header("Location: bus.php");
        exit();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    
    // First, delete related records in the route table
    $deleteRouteSql = "DELETE FROM route WHERE fk_bus_id=?";
    $routeStmt = $conn->prepare($deleteRouteSql);
    $routeStmt->execute([$id]);
    
    // Then, delete the bus record
    $sql = "DELETE FROM bus WHERE bus_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    
    // Redirect to the same page without query parameters to reset view
    header("Location: bus.php");
    exit();
}

// Prepare search and pagination query
$searchCondition = $searchTerm ? "WHERE bus_no LIKE ? OR bus_type LIKE ?" : "";
$countSql = "SELECT COUNT(*) FROM bus $searchCondition";
$sql = "SELECT * FROM bus $searchCondition LIMIT ? OFFSET ?";

$stmt = $conn->prepare($countSql);
if ($searchTerm) {
    $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
} else {
    $stmt->execute();
}
$totalResults = $stmt->fetchColumn();
$totalPages = ceil($totalResults / $resultsPerPage);

// Adjust page number if it's out of bounds
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $resultsPerPage;

$stmt = $conn->prepare($sql);
if ($searchTerm) {
    $stmt->bindValue(1, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(3, $resultsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt->bindValue(1, $resultsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Management</title>
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
            <li><a class="active_link" href="bus.php" class="menu-item"><i class="fas fa-bus"></i> Bus</a></li>
            <li><a href="route.php" class="menu-item"><i class="fas fa-route"></i> Route</a></li>
            <li><a href="customer.php" class="menu-item"><i class="fas fa-users"></i> Customer</a></li>
            <li><a href="booking.php" class="menu-item"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
            
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
        <h1>Bus Management</h1>
        <div class="AddEditBus"> 
        <?php if (!isset($_GET['edit'])): ?>
            <form method="POST" action="">
            <h2>Add Bus</h2>
                <input type="hidden" name="action" value="add">
                <label for="bus_no">Bus No:</label>
                <input type="text" name="bus_no" required><br>
                <span style="color: red;"><?php echo htmlspecialchars($errorBusNo); ?></span><br>
                
                <label for="bus_type">Bus Type:</label>
                <select name="bus_type" required>
                    <option value="aircon">Aircon</option>
                    <option value="non-aircon">Non-Aircon</option>
                </select><br>
                
                <label for="bus_seat">Number of Seats:</label>
                <input type="number" name="bus_seat" required><br>
                
                <input type="submit" value="Add Bus">
            </form>
            <?php endif; ?>

            <?php if (isset($_GET['edit'])): 
                $id = clean_input($_GET['edit']);
                $sql = "SELECT * FROM bus WHERE bus_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id]);
                $bus = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $errorBusNo = "";
            ?>
            
            <form method="POST" action="">
            <h2>Edit Bus</h2>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="bus_id" value="<?php echo htmlspecialchars($bus['bus_id']); ?>">
                <label for="bus_no">Bus No:</label>
                <input type="text" name="bus_no" value="<?php echo htmlspecialchars($bus['bus_no']); ?>" required><br>
                <span style="color: red;"><?php echo htmlspecialchars($errorBusNo); ?></span><br>
                
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
        <div class="table-controls">
    <form class="form-controls" method="GET" action="bus.php">
        <a href="bus.php" class="refresh-Btn">
                Refresh
            </a>
        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit" class="search-Btn">Search</button>
    </form>
</div>

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

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($searchTerm); ?>">Previous</a>
            <?php endif; ?>

            <?php
            // Show page numbers
            for ($i = 1; $i <= $totalPages; $i++) {
                $activeClass = ($i == $page) ? 'active' : '';
                echo "<a href='?page=$i&search=" . urlencode($searchTerm) . "' class='$activeClass'>$i</a>";
            }
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($searchTerm); ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>

<?php
$conn = null; // Close the connection
?>