<?php
require_once 'database.php';
require_once 'customer.class.php';

$customer = new Customer();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $contact_no = $_POST['contact_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? true : false;

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

        if ($is_admin) {
            $result = $customer->addAdmin();
        } else {
            $result = $customer->addCustomer();
        }

        if ($result) {
            $message = "Registration successful!";
        } else {
            $message = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="./userStyle.css">
</head>
<body>
    <h2>Admin Registration</h2>
    <form method="post" action="">
    <h2>Admin Registration</h2>
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="tel" name="contact_no" placeholder="Contact Number" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <input type="hidden" name="is_admin" value="1">
        <input type="submit" value="Register as Admin">
    </form>

    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
</body>
</html>