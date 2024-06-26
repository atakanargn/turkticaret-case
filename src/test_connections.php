<?php

require_once 'config.php';

// Postgresql connection
try {
    $pdo = new PDO("pgsql:host=$pgHost;dbname=$pgDbname", $pgUser, $pgPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PostgreSQL connection failed");
}

// Redis connection
$redisConn = new Redis();
$redisConn->connect($redisHost, 6379);
$redisConn->auth($redisPassword);
if (!$redisConn) {
    die("Redis connection failed");
}

echo "All connections are successful!";
?>