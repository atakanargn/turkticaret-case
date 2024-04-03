<?php
require ("../config.php");
require ("../db.conn.php");

$json_data = file_get_contents('products.json');
$data = json_decode($json_data, true);

// Hataları listelemek için bir liste oluşturdum
$allErrors = array();

// Verileri döndürüyoruz
foreach ($data as $row) {
    try {
        // flavor_notes alanının kontrollerini sağladık
        // flavor_notes içermiyorsa NULL verdik, içeriyorsa da Postgresql TEXT[] yani text array tipine uygun hale getirdik
        $flavor_notes = isset($row['flavor_notes']) ? '{' . implode(",", $row['flavor_notes']) . '}' : null;
        // Insert sorgusu
        $stmt = $pdo->prepare("INSERT INTO products (title, category_id, category_title, description, price, stock_quantity, origin, roast_level, flavor_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Sorgu boşlukları dolduruldu ve çalıştırıldı
        $stmt->execute([$row['title'], $row['category_id'], $row['category_title'], $row['description'], $row['price'], $row['stock_quantity'], $row['origin'], $row['roast_level'], $flavor_notes]);
    } catch (PDOException $e) {
        $allErrors[] = $e->getMessage();
    }
}

// Hata var ise
if (count($allErrors) > 0) {
    // Bad request ver
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(["error" => $allErrors]);
    // Devam eden işlemleri çalıştırma
    exit();
}

// recursive şekilde klasör içeriğini ve kendisini siliyoruz
function temizleVeSil($klasor)
{
    // Klasörü kontrol et ve içeriği al
    if (!is_dir($klasor)) {
        header("HTTP/1.1 400 Bad Request");
        return json_encode(["error" => "Klasör bulunamadı!"]);
    }

    $icerik = scandir($klasor);
    foreach ($icerik as $dosya) {
        if ($dosya != '.' && $dosya != '..') {
            $dosyaYolu = $klasor . DIRECTORY_SEPARATOR . $dosya;
            if (is_dir($dosyaYolu)) {
                // Klasör ise tekrar bu metodu çağır
                temizleVeSil($dosyaYolu);
            } else {
                // Dosya ise sil
                unlink($dosyaYolu);
            }
        }
    }

    // Klasörü sil
    if (!rmdir($klasor)) {
        return ["error" => "Klasör silenemedi!"];
    } else {
        return true;
    }
}

// Mevcut çalışma klasörünü al
$klasor = getcwd();
// Klasör temizleme metodunu çalıştırdık
$islem = temizleVeSil($klasor);

if ($islem) {
    echo "1";
} else {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json');
    echo json_encode(["error" => $islem]);
}

?>