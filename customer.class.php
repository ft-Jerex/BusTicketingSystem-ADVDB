<?php
require_once 'database.php';

class Customer {
    public $customer_id = '';
    public $first_name = '';
    public $last_name = '';
    public $contact_no = '';
    public $email = '';
    public $password = '';
    public $role = '';
    public $isAdmin = 0;
    public $isCustomer = 1;
    public $isStaff = 0;

    protected $db;

    function __construct() {
        $this->db = new Database();
    }

    function addCustomer() {
        $this->role = 'customer';
        $this->isAdmin = 0;
        $this->isCustomer = 1;
        $this->isStaff = 0;
        return $this->insertUser();
    }

    function addAdmin() {
        $this->role = 'admin';
        $this->isAdmin = 1;
        $this->isCustomer = 0;
        $this->isStaff = 0;
        return $this->insertUser();
    }

    function addStaff() {
        $this->role = 'staff';
        $this->isAdmin = 0;
        $this->isCustomer = 0;
        $this->isStaff = 1;
        return $this->insertUser();
    }

    private function insertUser() {
        $sql = "INSERT INTO customer (first_name, last_name, contact_no, email, password, role, isAdmin, isCustomer, isStaff) 
                VALUES (:first_name, :last_name, :contact_no, :email, :password, :role, :isAdmin, :isCustomer, :isStaff)";
        $query = $this->db->connect()->prepare($sql);

        $query->bindParam(':first_name', $this->first_name);
        $query->bindParam(':last_name', $this->last_name);
        $query->bindParam(':contact_no', $this->contact_no);
        $query->bindParam(':email', $this->email);
        $hashpassword = password_hash($this->password, PASSWORD_DEFAULT);
        $query->bindParam(':password', $hashpassword);
        $query->bindParam(':role', $this->role);
        $query->bindParam(':isAdmin', $this->isAdmin, PDO::PARAM_INT);
        $query->bindParam(':isCustomer', $this->isCustomer, PDO::PARAM_INT);
        $query->bindParam(':isStaff', $this->isStaff, PDO::PARAM_INT);

        return $query->execute();
    }

    function emailExist($email, $excludeID = null) {
        $sql = "SELECT COUNT(*) FROM customer WHERE email = :email";
        if ($excludeID) {
            $sql .= " AND customer_id != :excludeID";
        }

        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);

        if ($excludeID) {
            $query->bindParam(':excludeID', $excludeID);
        }

        return $query->execute() ? $query->fetchColumn() > 0 : false;
    }

    function login($email, $password) {
        $sql = "SELECT * FROM customer WHERE email = :email LIMIT 1";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);

        if ($query->execute()) {
            $data = $query->fetch(PDO::FETCH_ASSOC);
            if ($data && password_verify($password, $data['password'])) {
                return true;
            }
        }
        return false;
    }

    function fetch($email) {
        $sql = "SELECT customer_id, first_name, last_name, contact_no, email, role, 
                CAST(isAdmin AS UNSIGNED) as isAdmin, 
                CAST(isCustomer AS UNSIGNED) as isCustomer, 
                CAST(isStaff AS UNSIGNED) as isStaff 
                FROM customer 
                WHERE email = :email 
                LIMIT 1";
        
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);
        
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
}