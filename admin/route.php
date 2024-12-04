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

// Pagination setup
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// Search functionality
$searchTerm = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Handle Create
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $routeName = clean_input($_POST['route_name']);
    $busNo = clean_input($_POST['bus_no']);
    $departureTime = clean_input($_POST['departure_time']);
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
        
        // Redirect to the same page without query parameters to reset view
        header("Location: route.php");
        exit();
    } else {
        $errorMessage = "Bus number not found.";
    }
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = clean_input($_POST['route_id']);
    $routeName = clean_input($_POST['route_name']);
    $busNo = clean_input($_POST['bus_no']);
    $departureTime = clean_input($_POST['departure_time']);
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
        
        // Redirect to the same page without query parameters to reset view
        header("Location: route.php");
        exit();
    } else {
        $errorMessage = "Bus number not found.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    $sql = "DELETE FROM route WHERE route_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    
    // Redirect to the same page without query parameters to reset view
    header("Location: route.php");
    exit();
}

// Prepare search and pagination query
$searchCondition = $searchTerm ? "WHERE r.route_name LIKE ? OR b.bus_no LIKE ?" : "";
$countSql = "SELECT COUNT(*) FROM route r JOIN bus b ON r.fk_bus_id = b.bus_id $searchCondition";
$sql = "SELECT r.*, b.bus_no FROM route r JOIN bus b ON r.fk_bus_id = b.bus_id $searchCondition LIMIT ? OFFSET ?";

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
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Management</title>
    <?php include_once 'includes/header.php'; ?>
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
        .form-controls input{
            width: 300px;
        }
        .form-controls .search-Btn, .form-controls .refresh-Btn {
            height: 40px;   
            padding: 10px;
            font-size: 16px;
            color: #000000;
            background-color: #D3D3D3;
            border:none;
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
            <li><a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bus.php" class="menu-item"><i class="fas fa-bus"></i> Bus</a></li>
            <li><a class="active_link" href="route.php" class="menu-item"><i class="fas fa-route"></i> Route</a></li>
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
        <h1>Route Management</h1>
        <div class="AddEdit">
        <?php if (!isset($_GET['edit'])): ?>
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
            <?php endif; ?>

            <?php if (isset($_GET['edit'])): 
                $id = clean_input($_GET['edit']);
                $sql = "SELECT r.*, b.bus_no FROM route r JOIN bus b ON r.fk_bus_id = b.bus_id WHERE route_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id]);
                $route = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <form method="POST" action="">
            <h2>Edit Route</h2>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="route_id" value="<?php echo htmlspecialchars($route['route_id']); ?>">
                <label for="route_name">Route Name:</label>
                <input type="text" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>" required><br>
                
                <label for="bus_no">Bus No:</label>
                <input type="text" name="bus_no" value="<?php echo htmlspecialchars($route['bus_no']); ?>" required><br>
                
                <label for="departure_time">Departure Time:</label>
                <input type="time" name="departure_time" value="<?php echo htmlspecialchars($route['departure_time']); ?>" required><br>
                
                <label for="cost">Cost:</label>
                <input type="number" step="0.01" name="cost" value="<?php echo htmlspecialchars($route['cost']); ?>" required><br>
                
                <input type="submit" value="Update Route">
            </form>
            <?php endif; ?>
        </div>

        <h2>View Routes</h2>
        <div class="table-controls">
            <form class="form-controls" method="GET" action="route.php">
                <a href="route.php" class="refresh-Btn">
                    Refresh
                </a>
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit" class="search-Btn">Search</button>
            </form>
        </div>

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
                <td><?php echo htmlspecialchars($row['route_id']); ?></td>
                <td><?php echo htmlspecialchars($row['route_name']); ?></td>
                <td><?php echo htmlspecialchars($row['bus_no']); ?></td>
                <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
                <td><?php echo htmlspecialchars($row['cost']); ?></td>
                <td>
                    <a class="editBtn" href="?edit=<?php echo htmlspecialchars($row['route_id']); ?>">Edit</a>
                    <a class="deleteBtn" href="?delete=<?php echo htmlspecialchars($row['route_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
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