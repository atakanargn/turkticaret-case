<?php
error_reporting(0);
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$QUERY_PARAMS = array();
parse_str(parse_url($request, PHP_URL_QUERY), $QUERY_PARAMS);

header('Content-Type: application/json');

require_once ("config.php");

if ($request == '/api/v1/') {
    echo json_encode(["message" => "TurkTicaret.net Test Case"]);
} else if (startsWith($request, '/api/v1/activation')) {
    if (isset($QUERY_PARAMS["code"])) {
        require_once ('model/customer.php');
        $customer = new Customer();
        $process = $customer->activation($QUERY_PARAMS["code"]);
        if ($process === true) {
            http_response_code(200);
            echo json_encode(['message' => 'Aktivasyon başarılı.'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Aktivasyon tamamlanamadı!', 'error' => $process], JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Aktivasyon tamamlanamadı!', 'error' => 'Token geçersiz!'], JSON_UNESCAPED_UNICODE);
    }
} else if ($request == '/api/v1/register') {
    if ($method === "POST") {
        require_once ('model/customer.php');
        $customer = new Customer();

        $data = json_decode(file_get_contents('php://input'), true);
        $requiredFields = ['first_name', 'last_name', 'email', 'password', 'phone_number', 'address'];
        $missingFields = controlRequiredFields($data, $requiredFields);

        if ($missingFields === true) {
            $isSuccess = $customer->create($data['first_name'], $data['last_name'], $data['email'], $data['password'], $data['phone_number'], $data['address']);
            if ($isSuccess === true) {
                http_response_code(201);
                echo json_encode(['message' => 'Müşteri başarıyla oluşturuldu.'], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Müşteri oluşturulamadı.', 'error' => $success], JSON_UNESCAPED_UNICODE);
            }
        }
    } else {
        http_response_code(405);
        echo json_encode(['message' => 'Desteklenmeyen HTTP metodu'], JSON_UNESCAPED_UNICODE);
    }
} else if ($request == '/api/v1/login') {
    if ($method === "POST") {
        require_once ('model/customer.php');
        $customer = new Customer();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'E-posta ve şifre alanları gereklidir']);
            exit;
        }
        $email = $data['email'];
        $password = $data['password'];
        $customerData = $customer->findByEmail($email);
        if (!$customerData) {
            http_response_code(404);
            echo json_encode(['error' => 'Kullanıcı bulunamadı']);
            exit;
        }

        if ($customerData['status'] !== "0") {
            http_response_code(401);
            echo json_encode(['error' => 'Kullanıcı hesabı aktifleştirilmemiş, lütfen e-postanızı kontrol edin!']);
            exit();
        }

        if (password_verify($password, $customerData['password'])) {
            http_response_code(200);
            $expiration = date('Y-m-d H:i:s', time() + 60 * 60 * 24);
            // 3 gün öncesine JWT oluşturmak için aşağıdaki açıklama satırı açılabilir
            // JWT.php dosyasında isValid metodu 1 gün geçerli olacak şekilde kontrolleri yapıyor
            // $expiration = date('Y-m-d H:i:s', time() - 60 * 60 * 24 * 3);
            $payload = array(
                'user' => $customerData['id'],
                'exp' => $expiration
            );

            $jwt = JWT::create($payload, $JWTSecret);
            echo json_encode(['success' => 'Giriş başarılı', 'token' => $jwt]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Şifre yanlış']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['message' => 'Desteklenmeyen HTTP metodu'], JSON_UNESCAPED_UNICODE);
    }
} else if (startsWith($request, '/api/v1/product')) {
    if (authControl()) {
        $urlParts = explode('/', $request);
        require_once ('model/product.php');
        $product = new Product();
        if (count($urlParts) === 5) {
            $id = $urlParts[4];
            if ($id == "") {
                $products = $product->readAll();
            } else {
                $products = $product->read($id);
            }
        } else {
            $products = $product->readAll();
        }
        echo json_encode($products);
    }
} else if ($request == '/api/v1/coupon') {
    if ($method === "POST") {
        require_once ('model/coupon.php');
        $coupon = new Coupon();
        $data = json_decode(file_get_contents('php://input'), true);
        $requiredFields = ['coupon_code', 'discount_amount', 'expiration_date'];
        $missingFields = controlRequiredFields($data, $requiredFields);

        if ($missingFields === true) {
            $result = $coupon->create($data['coupon_code'], $data['discount_amount'], $data['expiration_date']);
            if (isset($result['error'])) {
                http_response_code(400);
                echo json_encode(['error' => $result['error']]);
            } else {
                http_response_code(201);
                echo json_encode(['success' => 'Kupon başarıyla oluşturuldu']);
            }
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Desteklenmeyen HTTP metodu']);
    }
} else if ($request == '/api/v1/cart') {
    $user = authControl();
    if ($user) {
        require_once ('model/product.php');
        $product = new Product();
        $redisConn = redisConnection($redisHost, $redisPassword);
        $id = $user['user'];
        if ($method === "POST") {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);

            $requiredFields = ['product_id', 'quantity'];
            $missingFields = controlRequiredFields($data, $requiredFields);

            if ($missingFields === true) {
                $products = $product->read($data['product_id']);
                if ($products) {
                    $storedData = $redisConn->get($id);
                    if ($storedData) {
                        $storedDataArray = json_decode($storedData, true);
                        if (isset($storedDataArray[$data['product_id']])) {
                            if ($products["stock_quantity"] >= ($data['quantity'] + $storedDataArray[$data['product_id']]['quantity'])) {
                                $storedDataArray[$data['product_id']]['quantity'] += $data['quantity'];
                            } else {
                                http_response_code(400);
                                echo json_encode(['error' => 'Bu üründen stokta \'' . $products["stock_quantity"] . '\' adet bulunmaktadır, daha fazla ekleyemezsiniz!']);
                                exit();
                            }
                        } else {
                            if ($products["stock_quantity"] >= $data['quantity']) {
                                $storedDataArray[$data['product_id']] = ['quantity' => $data['quantity']];
                            } else {
                                http_response_code(400);
                                echo json_encode(['error' => 'Bu üründen stokta \'' . $products["stock_quantity"] . '\' adet bulunmaktadır, daha fazla ekleyemezsiniz!']);
                                exit();
                            }
                        }

                        $redisConn->setex($id, 60 * 60, json_encode($storedDataArray));
                        echo json_encode(listCart($redisConn, $id, $product));
                    } else {
                        if ($products["stock_quantity"] >= $data['quantity']) {
                            $newData = array($data['product_id'] => array('quantity' => $data['quantity']));
                        } else {
                            http_response_code(400);
                            echo json_encode(['error' => 'Bu üründen stokta \'' . $products["stock_quantity"] . '\' adet bulunmaktadır, daha fazla ekleyemezsiniz!']);
                            exit();
                        }

                        $redisConn->setex($id, 60 * 60, json_encode($newData));
                        echo json_encode(listCart($redisConn, $id, $product));
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Böyle bir ürün yok!']);
                }

            }
        } else if ($method == "PUT") {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
            $requiredFields = ['product_id', 'quantity'];
            $missingFields = controlRequiredFields($data, $requiredFields);

            if ($missingFields === true) {
                $storedData = $redisConn->get($id);
                if ($storedData) {
                    $products = $product->read($data['product_id']);
                    if ($products) {
                        $storedDataArray = json_decode($storedData, true);
                        if (isset($storedDataArray[$data['product_id']])) {
                            if ($data['quantity'] == 0) {
                                unset($storedDataArray[$data['product_id']]);
                                $redisConn->setex($id, 60 * 60, json_encode($storedDataArray));
                                if (count($storedDataArray) == 0) {
                                    $redisConn->del($id);
                                    echo json_encode([]);
                                    exit();
                                }
                            } else {
                                if ($products["stock_quantity"] >= $data['quantity']) {
                                    $storedDataArray[$data['product_id']]['quantity'] = $data['quantity'];
                                    $redisConn->setex($id, 60 * 60, json_encode($storedDataArray));
                                } else {
                                    http_response_code(400);
                                    echo json_encode(['error' => 'Bu üründen stokta \'' . $products["stock_quantity"] . '\' adet bulunmaktadır, daha fazla ekleyemezsiniz!']);
                                    exit();
                                }
                            }
                            echo json_encode(listCart($redisConn, $id, $product));
                        } else {
                            echo json_encode(['error' => 'Sepette bu ürün yok!']);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Böyle bir ürün yok!']);
                    }
                } else {
                    echo json_encode(['message' => 'Sepetiniz boş!']);
                }
            }
        } else if ($method === "GET") {
            echo json_encode(listCart($redisConn, $id, $product));
        } else if ($method == "DELETE") {
            $redisConn->del($id);
            echo json_encode(["message" => "Sepetiniz temizlendi!"]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Desteklenmeyen HTTP metodu']);
        }
    }
} else if (startsWith($request, "/api/v1/order")) {
    $user = authControl();
    if ($user) {
        $user_id = $user["user"];
        if ($method == "POST") {
            $redisConn = redisConnection($redisHost, $redisPassword);

            require_once ('model/customer.php');
            require_once ('model/product.php');
            require_once ('model/order.php');
            require_once ('model/coupon.php');

            $_customer = new Customer();
            $product = new Product();
            $order = new Order();
            $coupon = new Coupon();

            $customer = $_customer->read($user_id);
            $cart = listCart($redisConn, $user_id, $product);

            if ($cart["summary"]["product_count"] == 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Sepetiniz boş!']);
                exit();
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $coupon_code = null;
            $couponDiscountPercent = 0;
            if (isset($data['coupon_code'])) {
                $couponDiscount = $coupon->read($data['coupon_code']);
                if (isset($couponDiscount['error'])) {
                    http_response_code(400);
                    echo json_encode(['error' => $couponDiscount['error']]);
                    exit();
                } else {
                    $coupon->usageCountInc($data['coupon_code']);
                }
                $couponDiscountPercent = (float) $couponDiscount["discount_amount"];
                $coupon_code = $data['coupon_code'];
            }

            $product_in_cart = $cart["cart"];
            $isQuantityUpdateRequired = [];
            foreach ($product_in_cart as $_product => $item) {
                $_stock_quantity = $item["product"]["stock_quantity"];
                $_quantity = $item["quantity"];
                if ($_quantity > $_stock_quantity) {
                    $isQuantityUpdateRequired[] = $item["product"];
                }
            }

            if (count($isQuantityUpdateRequired) > 0) {
                http_response_code(400);
                echo json_encode([
                    'error' => "Sepetinde stokta yeterli adette bulunmayan ürünler mevcut! Lütfen listelenen ürünlerin adetlerini güncelle!",
                    'products' => $isQuantityUpdateRequired
                ]);
                exit();
            }

            // Kupon kodu yüzdesini düşüyoruz
            // Zaten kupon kodu yoksa otomatik sıfır gelecektir
            $couponDiscountPrice = (float) $cart["summary"]['total_price'] * ($couponDiscountPercent / 100);
            $finalPrice = (float) $cart["summary"]['final_price'] - $couponDiscountPrice;

            $isSuccess = $order->create(
                $customer['id'],
                $couponDiscountPercent,
                $coupon_code,
                $cart["summary"]["product_count"],
                $cart["summary"]['total_price'],
                $cart["summary"]['discount_percent'],
                $cart["summary"]['discounted_price'],
                $cart["summary"]['cargo_price'],
                number_format($finalPrice, 2, '.', ''),
                $customer['address'],
                isset($data['note']) ? $data['note'] : null
            );

            if ($isSuccess[0] === true) {
                $lastInsertId = $isSuccess[1];
                $tableContent = "";
                foreach ($product_in_cart as $_product => $item) {
                    $item_price = (int) $item["quantity"] * $item["product"]["price"];
                    $tableContent = $tableContent . '<tr>
                        <td>' . $item["product"]["title"] . '</td>
                        <td>' . $item["quantity"] . '</td>
                        <td>' . number_format($item_price, 2, '.', '') . ' TL</td>
                    </tr>';
                    $order->addItem($lastInsertId, $item["product"]["id"], $item["quantity"], $item_price);
                    $product->updateQuantity($item["product"]["id"], (int) $item["product"]["stock_quantity"] - (int) $item["quantity"]);
                }
                $lastOrder = $order->read($lastInsertId);
                $orderItems = $order->readItems($lastInsertId);
                $redisConn->del($user_id);

                $content = file_get_contents("mail_template/order.html");
                $fullname = $customer["firstname"] . " " . $customer["last_name"];
                $render_list = array(
                    array("{{isim}}", $fullname),
                    array("{{item_list}}", $tableContent),
                    array("{{toplam_tutar}}", number_format($cart["summary"]['total_price'], 2, '.', '')),
                    array("{{kupon_indirim_tutari}}", number_format($couponDiscountPercent, 2, '.', '')),
                    array("{{sepette_indirim_tutari}}", number_format($cart["summary"]['discount_percent'], 2, '.', '')),
                    array("{{odenecek_tutar}}", number_format($finalPrice, 2, '.', '')),
                    array("{{kargo_ucreti}}", number_format($cart["summary"]['cargo_price'], 2, '.', '')),
                    array("{{destek_eposta}}", "argin.atakan@gmail.com"),
                );

                foreach ($render_list as $alt_liste) {
                    $content = str_replace($alt_liste[0], $alt_liste[1], $content);
                }
                ;

                $sql = "INSERT INTO mail_list (mail_to, fullname, subject, content, success) VALUES (?, ?, ?, ?, 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$customer["email"], $fullname, "TT Coffee Sipariş", $content]);

                http_response_code(201);
                echo json_encode([
                    'message' => 'Sipariş başarıyla oluşturuldu.',
                    'summary' => [
                        'order' => $lastOrder,
                        'order_items' => $orderItems
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Sipariş oluşturulamadı.', 'error' => $isSuccess[1]], JSON_UNESCAPED_UNICODE);
            }
        } else if ($method == "GET") {
            $urlParts = explode('/', $request);
            require_once ('model/product.php');
            require_once ('model/order.php');

            $product = new Product();
            $order = new Order();

            $new_order = [];

            if (count($urlParts) === 5) {
                $id = $urlParts[4];
                if ($id == "") {
                    $_order = $order->findByCustomer($user_id);
                    $new_order = [];
                    foreach ($_order as $_order_ => $item) {
                        $_orderItems = $order->readItems($item["id"]);
                        $orderItems = [];
                        foreach ($_orderItems as $_product2 => $item2) {
                            $_product = $product->read($item2["product_id"]);
                            $orderItems[] = $_product;
                        }
                        $new_order[] = [
                            "order" => $item,
                            "order_items" => $orderItems
                        ];
                    }
                } else {
                    $_order = $order->read($id);
                    $_orderItems = $order->readItems($_order["id"]);
                    $orderItems = [];
                    foreach ($_orderItems as $_product2 => $item2) {
                        $_product = $product->read($item2["product_id"]);
                        $orderItems[] = $_product;
                    }
                    $new_order[] = [
                        "order" => $_order,
                        "order_items" => $orderItems
                    ];

                }
            } else {
                $_order = $order->findByCustomer($user_id);
                $new_order = [];
                foreach ($_order as $_order_ => $item) {
                    $_orderItems = $order->readItems($item["id"]);
                    $orderItems = [];
                    foreach ($_orderItems as $_product2 => $item2) {
                        $_product = $product->read($item2["product_id"]);
                        $orderItems[] = $_product;
                    }
                    $new_order[] = [
                        "order" => $item,
                        "order_items" => $orderItems
                    ];
                }
            }
            echo json_encode($new_order);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Desteklenmeyen HTTP metodu']);
        }
    }
} else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode(["error" => "Not found!"]);
}