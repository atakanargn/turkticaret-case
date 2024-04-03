<?php
require_once ("db.conn.php");
class Coupon
{
    private $_pdo;

    public function __construct()
    {
        global $pdo;
        $this->_pdo = $pdo;
    }

    public function controlCouponCode($coupon_code)
    {
        // Verilen string içindeki tüm sayıları ve aralarındaki metinleri bul
        preg_match_all('/(\d+)([^0-9]+)/', $coupon_code, $matches, PREG_SET_ORDER);

        // Bulunan her sayı-metin çifti için kontrol yap
        foreach ($matches as $match) {
            $number = $match[1]; // Sayı
            $textBetween = $match[2]; // Aradaki metin

            // Aradaki metinde 'T' harfinin sayısını say
            $tCount = substr_count($textBetween, 'T');

            // Eğer aradaki metinde 3 'T' varsa, geçerli say
            if ($tCount >= 3) {
                return true;
            }
        }

        // 3 'T' bulunamadı
        return false;
    }

    public function create($coupon_code, $discount_amount, $expiration_date)
    {
        if (!$this->controlCouponCode($coupon_code)) {
            return ['error' => 'Geçersiz kupon kodu'];
        }

        if ($discount_amount <= 0 || $discount_amount > 100) {
            return ['error' => 'İndirim miktarı 0 ile 100 arasında olmalıdır'];
        }

        $sql = "INSERT INTO coupons (coupon_code, discount_amount, expiration_date) VALUES (?, ?, ?)";
        $stmt = $this->_pdo->prepare($sql);
        try {
            $stmt->execute([$coupon_code, $discount_amount, $expiration_date]);
            return true;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function usageCountInc($coupon_code)
    {
        try {
            $sql = "UPDATE coupons SET usage_count = usage_count + 1 WHERE coupon_code = ?";
            $stmt = $this->_pdo->prepare($sql);
            $stmt->execute([$coupon_code]);
            return true;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function read($coupon_code)
    {
        try {
            $sql = "SELECT * FROM coupons WHERE coupon_code = ?";
            $stmt = $this->_pdo->prepare($sql);
            $coupon = $stmt->execute([$coupon_code]);
            if (!$coupon) {
                return ['error' => 'Geçersiz kupon kodu'];
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}

?>