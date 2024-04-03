<?php
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$controllerDir = '/controller/';

header('Content-Type: application/json');

require_once ("config.php");

if ($request == '/api/v1/') {
    echo json_encode(["message" => "TurkTicaret.net Test Case"]);
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
                http_response_code(500);
                echo json_encode(['message' => 'Müşteri oluşturulamadı.', 'error' => $success], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Gerekli alanlarda eksik var!', 'fields' => $missingFields], JSON_UNESCAPED_UNICODE);
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
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Gerekli alanlar eksik', 'missing_fields' => $missingFields]);
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
                        listCart($redisConn, $id, $product);
                    } else {
                        if ($products["stock_quantity"] >= $data['quantity']) {
                            $newData = array($data['product_id'] => array('quantity' => $data['quantity']));
                        } else {
                            http_response_code(400);
                            echo json_encode(['error' => 'Bu üründen stokta \'' . $products["stock_quantity"] . '\' adet bulunmaktadır, daha fazla ekleyemezsiniz!']);
                            exit();
                        }

                        $redisConn->setex($id, 60 * 60, json_encode($newData));
                        listCart($redisConn, $id, $product);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Böyle bir ürün yok!']);
                }

            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Gerekli alanlar eksik', 'missing_fields' => $missingFields]);
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
                            listCart($redisConn, $id, $product);
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
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Gerekli alanlar eksik', 'missing_fields' => $missingFields]);
            }
        } else if ($method === "GET") {
            listCart($redisConn, $id, $product);
        } else if ($method == "DELETE") {
            $redisConn->del($id);
            echo json_encode(["message" => "Sepetiniz temizlendi!"]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Desteklenmeyen HTTP metodu']);
        }
    }
} else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode(["error" => "Not found!"]);
}