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
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }
        if (empty($missingFields)) {
            $success = $customer->create($data['first_name'], $data['last_name'], $data['email'], $data['password'], $data['phone_number'], $data['address']);

            if ($success === true) {
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
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }
        if (empty($missingFields)) {
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
} else {
    header("HTTP/1.0 404 Not Found");
    echo json_encode(["error" => "Not found!"]);
}