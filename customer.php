<?php
require_once 'database.php';
$db = new Database();
$conn = $db->connect();

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Handle Create
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $contactNo = sanitize($_POST['contact_no']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sql = "INSERT INTO customer (first_name, last_name, contact_no, email) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$firstName, $lastName, $contactNo, $email]);
    } else {
        echo "Invalid email format";
    }
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = filter_var($_POST['customer_id'], FILTER_SANITIZE_NUMBER_INT);
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $contactNo = sanitize($_POST['contact_no']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sql = "UPDATE customer SET first_name=?, last_name=?, contact_no=?, email=? WHERE customer_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$firstName, $lastName, $contactNo, $email, $id]);
    } else {
        echo "Invalid email format";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    $sql = "DELETE FROM customer WHERE customer_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Read Customers
$sql = "SELECT * FROM customer";
$stmt = $conn->prepare($sql);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="header">Header</header>
    <section class="sidebar">
        <ul>
            <li><a href="dashboard.php" class="menu-item">Dashboard</a></li>
            <li><a href="bus.php" class="menu-item">Bus</a></li>
            <li><a href="route.php" class="menu-item" data-page="route">Route</a></li>
            <li><a href="customer.php" class="menu-item" data-page="customer">Customer</a></li>
            <li><a href="booking.php" class="menu-item" data-page="bookings">Bookings</a></li>
        </ul>
    </section>
    <main class="main">
        <div id="main-content">
        <h1>Customer Management</h1>
    
    <h2>Add Customer</h2>
    <form method="POST" action="">
        <input type="hidden" name="action" value="add">
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" required><br>
        
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" required><br>
        
        <label for="contact_no">Contact No:</label>
        <input type="text" name="contact_no" required><br>
        
        <label for="email">Email:</label>
        <input type="email" name="email" required><br>
        
        <input type="submit" value="Add Customer">
    </form>

    <h2>View Customers</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Contact No</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($customers as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['customer_id'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['contact_no'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="?edit=<?php echo urlencode($row['customer_id']); ?>">Edit</a>
                <a href="?delete=<?php echo urlencode($row['customer_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['edit'])): 
        $id = filter_var($_GET['edit'], FILTER_SANITIZE_NUMBER_INT);
        $sql = "SELECT * FROM customer WHERE customer_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <h2>Edit Customer</h2>
    <form method="POST" action="">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer['customer_id'], ENT_QUOTES, 'UTF-8'); ?>">
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($customer['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($customer['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <label for="contact_no">Contact No:</label>
        <input type="text" name="contact_no" value="<?php echo htmlspecialchars($customer['contact_no'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8'); ?>" required><br>
        
        <input type="submit" value="Update Customer">
    </form>
    <?php endif; ?>
        </div>
    </main>

</body>
</html>

<?php
$conn = null; // Close the connection
?>