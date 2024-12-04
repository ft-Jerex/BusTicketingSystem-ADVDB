<?php
require_once '../database.php';
require_once '../customer.class.php';
require '../sanitize.php';

// Initialize database connection
$db = new Database();
$conn = $db->connect();

$customer = new Customer();
$message = '';

session_start();
// Check if user is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user is admin
if ($_SESSION['customer']['role'] !== 'admin' && 
    $_SESSION['customer']['isAdmin'] != 1) {
    header('Location: dashboard.php');
    exit();
}

// Pagination setup
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// Search functionality
$searchTerm = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Get total count for pagination
$totalResults = $customer->getTotalStaffCount($searchTerm);
$totalPages = ceil($totalResults / $resultsPerPage);

// Adjust page number if it's out of bounds
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $resultsPerPage;

// Handle Create
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $contact_no = $_POST['contact_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($contact_no) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif ($customer->emailExist($email)) {
        $message = "Email already exists.";
    } else {
        $customer->first_name = $first_name;
        $customer->last_name = $last_name;
        $customer->contact_no = $contact_no;
        $customer->email = $email;
        $customer->password = $password;

        $result = $customer->addStaff();

        if ($result) {
            $message = "Staff registration successful!";
            header("Location: registerStaff.php");
            exit();
        } else {
            $message = "Registration failed. Please try again.";
        }
    }
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = clean_input($_POST['customer_id']);
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $contact_no = clean_input($_POST['contact_no']);
    $email = clean_input($_POST['email']);

    // Check if email exists for different staff
    $stmt = $conn->prepare("SELECT * FROM customer WHERE email = ? AND customer_id != ?");
    $stmt->execute([$email, $id]);
    $existingStaff = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingStaff) {
        $message = "Error: Email already exists.";
    } else {
        $sql = "UPDATE customer SET first_name=?, last_name=?, contact_no=?, email=? WHERE customer_id=? AND role='staff'";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$first_name, $last_name, $contact_no, $email, $id]);
        
        if ($result) {
            // Redirect to reset view
            header("Location: registerStaff.php");
            exit();
        } else {
            $message = "Update failed. Please try again.";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM customer WHERE customer_id=? AND role='staff'");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        header("Location: registerStaff.php");
        exit();
    }
}

// Fetch paginated staff accounts
$staffAccounts = $customer->getAllStaff($searchTerm, $resultsPerPage, $offset);

function getFullName() {
    if (isset($_SESSION['customer'])) {
        return $_SESSION['customer']['first_name'] . ' ' . $_SESSION['customer']['last_name'];
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
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
            <li><a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bus.php" class="menu-item"><i class="fas fa-bus"></i> Bus</a></li>
            <li><a href="route.php" class="menu-item"><i class="fas fa-route"></i> Route</a></li>
            <li><a href="customer.php" class="menu-item"><i class="fas fa-users"></i> Customer</a></li>
            <li><a href="booking.php" class="menu-item"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
            
            <?php 
            // Only show Staff Management for admin users
            if (isset($_SESSION['customer']) && 
                ($_SESSION['customer']['role'] === 'admin' || 
                 $_SESSION['customer']['isAdmin'] == 1)) : ?>
                <li><a href="registerStaff.php" class="menu-item active_link"><i class="fas fa-user-cog"></i> Staff Management</a></li>
            <?php endif; ?>
            
            <hr class="menu-itemHR">
            <li><a href="../logout.php" class="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </section>
    <main class="main">
        <div id="main-content">
            <h1>Staff Management</h1>
            <div class="AddEdit">
    <?php if (!isset($_GET['edit'])): ?>
    <!-- Add Staff Form -->
    <form method="POST" action="">
        <h2>Add Staff</h2>
        <input type="hidden" name="action" value="add">
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" required><br>
        
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" required><br>
        
        <label for="contact_no">Contact Number:</label>
        <input type="tel" name="contact_no" required><br>
        
        <label for="email">Email:</label>
        <input type="email" name="email" required><br>
        
        <label for="password">Password:</label>
        <input type="password" name="password" required><br>
        
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" required><br>
        
        <input type="submit" value="Register Staff">
    </form>
    <?php endif; ?>

    <?php if (isset($_GET['edit'])): 
        $id = clean_input($_GET['edit']);
        $stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id=? AND role='staff'");
        $stmt->execute([$id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($staff):
    ?>
    <form method="POST" action="">
        <h2>Edit Staff</h2>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($staff['customer_id']); ?>">
        
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($staff['first_name']); ?>" required><br>
        
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($staff['last_name']); ?>" required><br>
        
        <label for="contact_no">Contact Number:</label>
        <input type="tel" name="contact_no" value="<?php echo htmlspecialchars($staff['contact_no']); ?>" required><br>
        
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required><br>
        
        <input type="submit" value="Update Staff">
    </form>
    <?php 
        endif;
    endif; 
    ?>
</div>
                <?php if (!empty($message)): ?>
                    <div class="message"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
            

            <div class="staff-list">
                <h2>Current Staff Accounts</h2>
                <div class="table-controls">
                    <form class="form-controls" method="GET" action="registerStaff.php">
                        <a href="registerStaff.php" class="refresh-Btn">Refresh</a>
                        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit" class="search-Btn">Search</button>
                    </form>
                </div>

                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staffAccounts)): ?>
                            <?php foreach ($staffAccounts as $staff): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($staff['customer_id']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['contact_no']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td>
                                        <a class="editBtn" href="?edit=<?php echo htmlspecialchars($staff['customer_id']); ?>">Edit</a>
                                        <a class="deleteBtn" href="?delete=<?php echo htmlspecialchars($staff['customer_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No staff accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
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
        </div>
    </main>
</body>
</html>

<?php
$conn = null; // Close the connection
?>