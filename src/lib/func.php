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

function redisConnection($host, $password)
{
    $redisConn = new Redis();
    $redisConn->connect($host, 6379);
    $redisConn->auth($password);
    if (!$redisConn) {
        die("Redis connection failed");
    }
    return $redisConn;
}

function controlRequiredFields($data, $requiredFields)
{
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $missingFields[] = $field;
        }
    }
    if (empty($missingFields)) {
        return true;
    }
    http_response_code(400);
    echo json_encode(['message' => 'Gerekli alanlarda eksik var!', 'fields' => $missingFields], JSON_UNESCAPED_UNICODE);
    exit();
}

function listCart($redisConn, $id, $product)
{
    $storedData = $redisConn->get($id);
    if ($storedData == null) {
        $storedData = json_encode([]);
    }
    $storedDataArray = json_decode($storedData);
    $cart = [];

    $total_price = 0.0;
    $cargo_price = 54.99;
    $discount = 0;

    foreach ($storedDataArray as $product_id => $item) {
        $quantity = (array) $item;
        $quantity = $quantity['quantity'];
        $productData = $product->read($product_id);

        $cart[] = [
            "product" => $productData,
            "quantity" => $quantity,
            "price" => number_format(((int) $quantity * (float) $productData["price"]), 2, '.', '')
        ];

        $total_price += ((int) $quantity * (float) $productData["price"]);
    }

    if ($total_price > 500 || count($cart) == 0) {
        $cargo_price = 0;
    }

    if ($total_price > 3000) {
        $discount = 25;
        $allProducts = $product->readAll();

        $inStock = array_filter($allProducts, function ($item) {
            return $item['stock_quantity'] != 0;
        });
        $randomIndex = rand(0, count($inStock) - 1);
        $selectedProduct = $inStock[$randomIndex];
        $selectedProduct["price"] = 0;
        $cart[] = [
            "product" => $selectedProduct,
            "quantity" => 1,
            "price" => 0
        ];
    } else if ($total_price > 2000) {
        $discount = 20;
    } else if ($total_price > 1500) {
        $discount = 15;
    } else if ($total_price > 1000) {
        $discount = 10;
    }

    $discounted_price = $total_price * ($discount / 100);

    $final_price = $total_price - $discounted_price + $cargo_price;

    return [
        "cart" => $cart,
        "summary" => [
            "product_count" => count($cart),
            "total_price" => number_format($total_price, 2, '.', ''),
            "discount_percent" => number_format($discount, 2, '.', ''),
            "discounted_price" => number_format($discounted_price, 2, '.', ''),
            "cargo_price" => number_format($cargo_price, 2, '.', ''),
            "final_price" => number_format($final_price, 2, '.', ''),
        ]
    ];
}

?>