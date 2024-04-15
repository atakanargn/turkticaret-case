<?php

// Postgresql ayarları
$pgHost = 'postgres';
$pgDbname = 'coffeedb';
$pgUser = 'postgres';
$pgPassword = 'postgres';

// Redis ayarları
$redisHost = "redis";
$redisPassword = "redis";

// JWT Config
$JWTSecret = "c1b9a5c1-9ba6-4cac-b23c-ad41d3cd284b";

require_once ('lib/JWT.php');
require_once ("lib/func.php");
?>