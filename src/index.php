<?php
require "db.conn.php";
$request = $_SERVER['REQUEST_URI'];
$viewDir = '/views/';

header('Content-Type: application/json');
switch ($request) {
    case '':
    case '/':
        echo json_encode(["message" => "TurkTicaret.net Test Case"]);
        break;
    default:
        header("HTTP/1.0 404 Not Found");
        echo json_encode(["error" => "Not found!"]);
        break;
}


