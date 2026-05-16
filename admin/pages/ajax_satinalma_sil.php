<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

// Admin yetki kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID eksik.']);
    exit;
}

try {
    $db = \Core\Database::getInstance()->getConnection();
    
    // İşlemi bul
    $stmt = $db->prepare("SELECT ka.*, k.adi_soyadi FROM kullanici_abonelikleri ka LEFT JOIN kullanicilar k ON ka.kullanici_id = k.id WHERE ka.id = :id");
    $stmt->execute([':id' => $id]);
    $islem = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$islem) {
        echo json_encode(['success' => false, 'message' => 'İşlem bulunamadı.']);
        exit;
    }

    // Silme işlemi (Hard delete since no silinme_tarihi logic seen in satinalmalar.php)
    $stmt = $db->prepare("DELETE FROM kullanici_abonelikleri WHERE id = :id");
    $result = $stmt->execute([':id' => $id]);

    if ($result) {
        $logger = new \Core\Services\DatabaseLogger('subscription');
        $userName = !empty($islem->adi_soyadi) ? $islem->adi_soyadi : 'Kullanıcı';
        $logger->warning("Abonelik/Satın alma kaydı silindi. ID: $id, Kullanıcı: $userName");
        
        echo json_encode(['success' => true, 'message' => 'Satın alma kaydı başarıyla silindi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Silme işlemi başarısız oldu.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
