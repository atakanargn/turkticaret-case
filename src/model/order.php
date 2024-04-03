<?php
require_once ("db.conn.php");

class Order
{
    private $_pdo;

    public function __construct()
    {
        global $pdo;
        $this->_pdo = $pdo;
    }

    public function create($customer_id, $coupon_discount_percent, $coupon_code, $product_count, $total_price, $discounted_percent, $discounted_price, $cargo_price, $final_price, $shipping_address, $note)
    {
        $sql = "INSERT INTO orders (customer_id, coupon_discount_percent,coupon_code, product_count, total_price, discounted_percent, discounted_price, cargo_price, final_price, shipping_address, payment_status, shipping_status, note) VALUES (?, ?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->_pdo->prepare($sql);
        try {
            $stmt->execute([$customer_id, $coupon_discount_percent, $coupon_code, $product_count, $total_price, $discounted_percent, $discounted_price, $cargo_price, $final_price, $shipping_address, 'Ödeme bekleniyor', 'Ödeme bekleniyor', $note]);
            return [0 => true, 1 => $this->_pdo->lastInsertId()];
        } catch (PDOException $e) {
            return [0 => false, 1 => $e->getMessage()];
        }
    }

    public function addItem($order_id, $product_id, $quantity, $price)
    {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $this->_pdo->prepare($sql);
        try {
            $stmt->execute([$order_id, $product_id, $quantity, $price]);
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function read($id)
    {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function readItems($id)
    {
        $sql = "SELECT * FROM order_items WHERE order_id = ?";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePaymentShippingStatus($id, $payment_status, $shipping_status)
    {
        $sql = "UPDATE orders SET payment_status = ?, shipping_status = ? WHERE id = ?";
        $stmt = $this->_pdo->prepare($sql);
        try {
            $stmt->execute([$payment_status, $shipping_status, $id]);
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function findByCustomer($customer_id)
    {
        $sql = "SELECT * FROM orders WHERE customer_id = ?";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>