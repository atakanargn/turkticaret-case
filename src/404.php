<?php
header('Content-Type: application/json');
$response = array(
    'error' => true,
    'message' => 'Sayfa bulunamadı!'
);
http_response_code(404);
echo json_encode($response);