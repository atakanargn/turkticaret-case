<?php
require_once ("db.conn.php");
class Customer
{
    private $_pdo;

    public function __construct()
    {
        global $pdo;
        $this->_pdo = $pdo;
    }

    public function create($first_name, $last_name, $email, $password, $phone_number, $address)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO customers (first_name, last_name, email, password, phone_number, address) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->_pdo->prepare($sql);
        try {
            $stmt->execute([$first_name, $last_name, $email, $hashedPassword, $phone_number, $address]);
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function read($id)
    {
        $sql = "SELECT id, first_name, last_name, email, phone_number, address FROM customers WHERE id = ?";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email)
    {
        $sql = "SELECT * FROM customers WHERE email = ?";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>