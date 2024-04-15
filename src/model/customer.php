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
        global $JWTSecret;
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO customers (first_name, last_name, email, password, phone_number, address, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->_pdo->prepare($sql);
        try {
            $stmt->execute([$first_name, $last_name, $email, $hashedPassword, $phone_number, $address, md5($email)]);
            $sql2 = "INSERT INTO mail_list (mail_to, fullname, subject, content, success) VALUES (?, ?, ?, ?, 0)";
            $stmt2 = $this->_pdo->prepare($sql2);

            $content = file_get_contents("mail_template/registration.html");
            $render_list = array(
                array("{{isim}}", $first_name . " " . $last_name),
                array("{{aktivasyon_linki}}", "http://localhost:8080/api/v1/activation?code=" . md5($email)),
                array("{{destek_eposta}}", "argin.atakan@gmail.com"),
            );

            foreach ($render_list as $alt_liste) {
                $content = str_replace($alt_liste[0], $alt_liste[1], $content);
            }

            $stmt2->execute([$email, $first_name . " " . $last_name, "TT Coffee Kayıt", $content]);
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

    public function activation($code)
    {
        $sql = "SELECT email FROM customers WHERE status = ?";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([$code]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $sql = "UPDATE customers SET status = ? WHERE status = ?";
            $stmt = $this->_pdo->prepare($sql);
            try {
                $stmt->execute(["0", $code]);
                return true;
            } catch (PDOException $e) {
                return $e->getMessage();
            }
        } else {
            return "Token geçersiz";
        }
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