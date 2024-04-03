<?php
try {
    $pdo = new PDO("pgsql:host=$pgHost;dbname=$pgDbname", $pgUser, $pgPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PostgreSQL connection failed");
}
?>