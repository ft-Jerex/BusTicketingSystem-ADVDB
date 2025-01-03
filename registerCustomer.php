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
    $is_admin = isset($_POST['isAdmin']) ? true : false;

    // Enhanced email validation
    function isValidEmail($email) {
        // Check if email is properly formatted
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Optional: Check for disposable email domains (you can expand this list)
        $disposableDomains = [
            'mailinator.com', 'temp-mail.org', 'guerrillamail.com', 
            'throwawaymail.com', 'sharklasers.com'
        ];
        $emailDomain = strtolower(substr(strrchr($email, "@"), 1));
        if (in_array($emailDomain, $disposableDomains)) {
            return false;
        }

        // Optional: DNS MX record check to verify email domain exists
        return checkdnsrr($emailDomain, 'MX');
    }

    // Validation checks
    if (empty($first_name) || empty($last_name) || empty($contact_no) || 
        empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
    } elseif (!isValidEmail($email)) {
        $message = "Invalid email address. Please provide a valid email.";
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
            $result = $customer->addStaff();
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
    <link rel="stylesheet" href="./login.css">
</head>
<body>
    <form method="post" action="">
        <h2>Customer Registration</h2>
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="tel" name="contact_no" placeholder="Contact Number" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <input type="submit" value="Register as Customer">
        <a class="return-btn" href="./login.php">Return Login</a>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
</body>
</html>