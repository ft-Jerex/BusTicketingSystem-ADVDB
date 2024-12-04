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

$errorMessage = "";

// Pagination setup
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// Search functionality
$searchTerm = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Handle Create
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $firstName = clean_input($_POST['first_name']);
    $lastName = clean_input($_POST['last_name']);
    $contactNo = clean_input($_POST['contact_no']);
    $email = clean_input($_POST['email']);
    $password = password_hash(clean_input($_POST['password']), PASSWORD_DEFAULT);
    $role = 'customer'; // Set default role to customer

    // Check if email already exists
    $sql = "SELECT * FROM customer WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $existingCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCustomer) {
        $errorMessage = "Error: Email already exists.";
    } else {
        $sql = "INSERT INTO customer (first_name, last_name, contact_no, email, password, role, isCustomer) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$firstName, $lastName, $contactNo, $email, $password, $role]);
        
        header("Location: customer.php");
        exit();
    }
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = clean_input($_POST['customer_id']);
    $firstName = clean_input($_POST['first_name']);
    $lastName = clean_input($_POST['last_name']);
    $contactNo = clean_input($_POST['contact_no']);
    $email = clean_input($_POST['email']);

    // Check if email already exists for a different customer
    $sql = "SELECT * FROM customer WHERE email = ? AND customer_id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email, $id]);
    $existingCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCustomer) {
        $errorMessage = "Error: Email already exists.";
    } else {
        $sql = "UPDATE customer SET first_name=?, last_name=?, contact_no=?, email=? WHERE customer_id=? AND role='customer'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$firstName, $lastName, $contactNo, $email, $id]);
        
        header("Location: customer.php");
        exit();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    $sql = "DELETE FROM customer WHERE customer_id=? AND role='customer'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    
    header("Location: customer.php");
    exit();
}

// Prepare search and pagination query with role filter
$searchCondition = "WHERE role = 'customer'";
if ($searchTerm) {
    $searchCondition .= " AND (first_name LIKE ? OR last_name LIKE ? OR customer_id LIKE ? OR email LIKE ?)";
}

$countSql = "SELECT COUNT(*) FROM customer $searchCondition";
$sql = "SELECT * FROM customer $searchCondition";

// Get total count first
$stmt = $conn->prepare($countSql);
if ($searchTerm) {
    $stmt->execute(["%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"]);
} else {
    $stmt->execute();
}
$totalResults = $stmt->fetchColumn();
$totalPages = ceil($totalResults / $resultsPerPage);

// Adjust page number if it's out of bounds
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $resultsPerPage;

// Add pagination to the main query
$sql .= " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// Bind parameters in the correct order
if ($searchTerm) {
    $stmt->bindValue(1, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(3, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(4, "%$searchTerm%", PDO::PARAM_STR);
    $stmt->bindValue(5, $resultsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(6, $offset, PDO::PARAM_INT);
} else {
    $stmt->bindValue(1, $resultsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
    <?php include_once 'includes/header.php'; ?>
    <style>
        
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
            <li><a href="route.php" class="menu-item"><i class="fas fa-route"></i> Route</a></li>
            <li><a class="active_link" href="customer.php" class="menu-item"><i class="fas fa-users"></i> Customer</a></li>
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
            <h1>Customer Management</h1>
            <div class="AddEdit"> 
            <?php if (!isset($_GET['edit'])): ?>
                <form method="POST" action="">
                    <h2>Add Customer</h2>
                    <input type="hidden" name="action" value="add">
                    <label for="first_name">First Name:</label>
                    <input type="text" name="first_name" required><br>
                    
                    <label for="last_name">Last Name:</label>
                    <input type="text" name="last_name" required><br>

                    <label for="contact_no">Contact Number:</label>
                    <input type="tel" name="contact_no" required><br>
                    
                    <label for="email">Email:</label>
                    <input type="email" name="email" required><br>
                    <span style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></span><br>
                    
                    <label for="password">Password:</label>
                    <input type="password" name="password" required><br>
                    
                    <input type="submit" value="Add Customer">
                </form>
                <?php endif; ?>

                <?php if (isset($_GET['edit'])): 
                    $id = clean_input($_GET['edit']);
                    $sql = "SELECT * FROM customer WHERE customer_id=? AND role='customer'";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$id]);
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($customer):
                ?>
                
                <form method="POST" action="">
                    <h2>Edit Customer</h2>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer['customer_id']); ?>">
                    <label for="first_name">First Name:</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required><br>
                    
                    <label for="last_name">Last Name:</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required><br>

                    <label for="contact_no">Contact Number:</label>
                    <input type="text" name="contact_no" value="<?php echo htmlspecialchars($customer['contact_no']); ?>"required><br>
                    
                    <label for="email">Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required><br>
                    <span style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></span><br>
                    
                    <input type="submit" value="Update Customer">
                </form>
                <?php endif; endif; ?>
            </div> 

            <h2>View Customers</h2>
            <div class="table-controls">
                <form class="form-controls" method="GET" action="customer.php">
                    <a href="customer.php" class="refresh-Btn">
                        Refresh
                    </a>
                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="search-Btn">Search</button>
                </form>
            </div>

            <table border="1">
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Contact Number</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($customers as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['customer_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <a class="editBtn" href="?edit=<?php echo htmlspecialchars($row['customer_id']); ?>">Edit</a>
                        <a class="deleteBtn" href="?delete=<?php echo htmlspecialchars($row['customer_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
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