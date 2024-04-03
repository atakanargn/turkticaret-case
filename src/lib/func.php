<?php
function startsWith($haystack, $needle)
{
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

function authControl()
{
    global $JWTSecret;
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authorizationHeader = $headers['Authorization'];
        $jwtToken = str_replace('Bearer ', '', $authorizationHeader);
        $payload = JWT::isValid($jwtToken, $JWTSecret);
        if ($payload == false) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            exit();
        }
        return $payload;
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Forbidden"]);
        exit();
    }
}



?>