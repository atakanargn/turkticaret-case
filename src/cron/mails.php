<?php
include ("lib/mailer.php");
require_once ("config.php");
include ("db.conn.php");

try {
    $sql = "SELECT * FROM mail_list WHERE success=?;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([0]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $isSent = send_mail(
            $row["mail_to"],
            $row["fullname"],
            $row["subject"],
            $row["content"]
        );
        if ($isSent === true) {
            $sql = "UPDATE mail_list SET success = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute(["1", $row["id"]]);
                return true;
            } catch (PDOException $e) {
                return $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    return $e->getMessage();
}

echo "Mail sent success.";

?>