<?php
require_once 'database.php';

class CustomerDetails extends Database
{
    public function getDetails($customer_id)
    {
        $db = $this->connect();
        $sql = "SELECT CONCAT(first_name, ' ', last_name) AS full_name, contact_no FROM customer WHERE customer_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$customer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$customerDetails = new CustomerDetails();
$customer = $customerDetails->getDetails($_GET['customer_id']);

header('Content-Type: application/json');
echo json_encode($customer);