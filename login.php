<?php
require_once 'sanitize.php';
require_once 'customer.class.php';
require_once 'database.php';

session_start();

$email = $password = '';
$customerObj = new customer();
$loginErr = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);

    if ($customerObj->login($email, $password)) {
        $data = $customerObj->fetch($email);
        
        $_SESSION['customer_id'] = $data['customer_id'];
        $_SESSION['customer'] = $data;

        // Check role and boolean flags
        if ($data['role'] === 'admin' || $data['role'] === 'staff' || 
            $data['isAdmin'] == 1 || $data['isStaff'] == 1) {
            header('Location: admin/dashboard.php');
            exit();
        } else {
            header('Location: index.php');
            exit();
        }
    } else {
        $loginErr = 'Invalid email/password';
    }
} else {
    if (isset($_SESSION['customer'])) {
        if ($_SESSION['customer']['role'] === 'admin' || 
            $_SESSION['customer']['role'] === 'staff' ||
            $_SESSION['customer']['isAdmin'] == 1 || 
            $_SESSION['customer']['isStaff'] == 1) {
            header('Location: admin/dashboard.php');
            exit();
        } else {
            header('Location: index.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        .error {
            color: red;
        }
    </style>
    <link rel="stylesheet" href="./login.css">
</head>
<body>
    <form action="login.php" method="post">
        <h1>Welcome to <br><b>IBT Online Ticketing</b></h1>
        <h2>Login</h2>
        <label for="email">Email</label>
        <br>
        <input type="text" name="email" id="email" required>
        <br>
        <label for="password">Password</label>
        <br>
        <input type="password" name="password" id="password" required>
        <br>
        <input type="submit" value="Login" name="login">
        <br>
        <a class="return-btn" href="./registerCustomer.php">Create Account</a>
        <?php if (!empty($loginErr)): ?>
            <p class="error"><?= $loginErr ?></p>
        <?php endif; ?>
    </form>
</body>
</html>