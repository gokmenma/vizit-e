<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin yetki kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

use Admin\Models\PurchaseModel;

$purchaseModel = new PurchaseModel();
$action = $_POST['action'] ?? '';

// 1. Yeni Abonelik Ekleme / Satın Alma Tanımlama
if ($action === 'create' || $action === 'admin-kullanici-satin-al') {
    $kullanici_id = $_POST["kullanici_id"] ?? '';
    $paket_id = $_POST["paket_id"] ?? '';
    $baslangic_tarihi = $_POST["baslangic_tarihi"] ?? '';
    $bitis_tarihi = $_POST["bitis_tarihi"] ?? '';
    $firma_hakki = $_POST["firma_hakki"] ?? 30;
    $alt_kullanici_hakki = $_POST["alt_kullanici_hakki"] ?? 3;

    if (empty($kullanici_id) || empty($paket_id) || empty($baslangic_tarihi) || empty($bitis_tarihi)) {
        echo json_encode(["status" => "error", "message" => "Lütfen tüm alanları doldurun."]);
        exit;
    }

    try {
        // Kullanıcının diğer aktif aboneliklerini iptal et (Model üzerinden)
        $purchaseModel->db->prepare("UPDATE kullanici_abonelikleri SET durum = 'iptal' WHERE kullanici_id = ? AND durum = 'aktif'")
                          ->execute([$kullanici_id]);

        // Yeni aboneliği kaydet (Dynamic ORM)
        $purchaseModel->saveWithAttr([
            'kullanici_id' => $kullanici_id,
            'paket_id' => $paket_id,
            'durum' => 'aktif',
            'baslangic_tarihi' => $baslangic_tarihi,
            'bitis_tarihi' => $bitis_tarihi,
            'firma_hakki' => $firma_hakki,
            'alt_kullanici_hakki' => $alt_kullanici_hakki,
            'olusturma_tarihi' => date('Y-m-d H:i:s')
        ]);

        if (class_exists('\\Core\\Services\\DatabaseLogger')) {
            $logger = new \Core\Services\DatabaseLogger('subscription');
            $logger->success("Kullanıcıya paket tanımlandı. Kullanıcı ID: $kullanici_id, Paket ID: $paket_id");
        }

        echo json_encode(["status" => "success", "message" => "Satın alma işlemi başarıyla kaydedildi."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Hata: " . $e->getMessage()]);
    }
    exit;
}

// 2. Abonelik Silme
if ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID eksik.']);
        exit;
    }

    try {
        $islem = $purchaseModel->getPurchaseById($id);
        if (!$islem) {
            echo json_encode(['success' => false, 'message' => 'İşlem bulunamadı.']);
            exit;
        }

        $result = $purchaseModel->delete($id);

        if ($result) {
            if (class_exists('\\Core\\Services\\DatabaseLogger')) {
                $logger = new \Core\Services\DatabaseLogger('subscription');
                $userName = !empty($islem->adi_soyadi) ? $islem->adi_soyadi : 'Kullanıcı';
                $logger->warning("Abonelik/Satın alma kaydı silindi. ID: $id, Kullanıcı: $userName");
            }
            
            echo json_encode(['success' => true, 'message' => 'Satın alma kaydı başarıyla silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Silme işlemi başarısız oldu.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}

// 3. Abonelik Onaylama
if ($action === 'approve') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID eksik.']);
        exit;
    }

    try {
        $islem = $purchaseModel->getPurchaseById($id);
        if (!$islem) {
            echo json_encode(['success' => false, 'message' => 'İşlem bulunamadı.']);
            exit;
        }

        // Kullanıcının diğer aktif aboneliklerini iptal et
        $purchaseModel->db->prepare("UPDATE kullanici_abonelikleri SET durum = 'iptal' WHERE kullanici_id = ? AND durum = 'aktif'")
                          ->execute([$islem->kullanici_id]);

        // Bu aboneliği aktif yap
        $purchaseModel->saveWithAttr([
            'id' => $id,
            'durum' => 'aktif'
        ]);

        if (class_exists('\\Core\\Services\\DatabaseLogger')) {
            $logger = new \Core\Services\DatabaseLogger('subscription');
            $userName = !empty($islem->adi_soyadi) ? $islem->adi_soyadi : 'Kullanıcı';
            $logger->success("Abonelik onaylandı. ID: $id, Kullanıcı: $userName");
        }

        echo json_encode(['success' => true, 'message' => 'Abonelik başarıyla onaylandı.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}
