<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum ve Yetki Kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

// Sadece Süper Admin veya Admin paket düzenleyebilir
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
if ($userRole !== 'superadmin' && $userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem.']);
    exit;
}

use Admin\Models\PackageModel;

$paketModel = new PackageModel();
$action = $_POST['action'] ?? '';
if (empty($action) && strpos($_SERVER['REQUEST_URI'], 'admin-paket-sil') !== false) {
    $action = 'delete';
}
if (empty($action)) {
    $action = 'save';
}

if ($action === 'save') {
    $id = $_POST['id'] ?? null;
    $ad = $_POST['ad'] ?? '';
    $fiyat = $_POST['fiyat'] ?? 0;
    $sure = $_POST['sure'] ?? 1;
    $firma_hakki = $_POST['firma_hakki'] ?? 1;
    $alt_kullanici_hakki = $_POST['alt_kullanici_hakki'] ?? 0;

    if (empty($ad)) {
        echo json_encode(['success' => false, 'message' => 'Paket adı gereklidir.']);
        exit;
    }

    $data = [
        'ad' => $ad,
        'fiyat' => $fiyat,
        'sure' => $sure,
        'firma_hakki' => $firma_hakki,
        'alt_kullanici_hakki' => $alt_kullanici_hakki
    ];

    if ($id) {
        $data['id'] = $id;
    }

    try {
        $paketModel->saveWithAttr($data);
        $message = $id ? 'Paket başarıyla güncellendi.' : 'Yeni paket başarıyla oluşturuldu.';
        
        // Log
        if (class_exists('\\Core\\Services\\DatabaseLogger')) {
            $logger = new \Core\Services\DatabaseLogger('package-management');
            $logger->info("Paket kaydedildi: $ad" . ($id ? " (ID: $id)" : ""));
        }

        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Paket ID eksik.']);
        exit;
    }

    try {
        $paket = $paketModel->find($id);
        if (!$paket) {
            echo json_encode(['success' => false, 'message' => 'Paket bulunamadı.']);
            exit;
        }

        $result = $paketModel->softDelete($id);

        if ($result) {
            // Log
            if (class_exists('\\Core\\Services\\DatabaseLogger')) {
                $logger = new \Core\Services\DatabaseLogger('package-management');
                $logger->warning("Paket silindi (Soft Delete): " . $paket->ad . " (ID: $id)");
            }
            echo json_encode(['success' => true, 'message' => 'Paket başarıyla silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Silme işlemi veritabanı seviyesinde başarısız oldu.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'toggle-status') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Paket ID eksik.']);
        exit;
    }

    try {
        $paket = $paketModel->find($id);
        if (!$paket) {
            echo json_encode(['success' => false, 'message' => 'Paket bulunamadı.']);
            exit;
        }

        $yeniDurum = $paket->aktif_mi ? 0 : 1;
        $result = $paketModel->updateSingle($id, ['aktif_mi' => $yeniDurum]);

        if ($result) {
            // Log
            if (class_exists('\\Core\\Services\\DatabaseLogger')) {
                $logger = new \Core\Services\DatabaseLogger('package-management');
                $logger->info("Paket durumu güncellendi: " . $paket->ad . " (" . ($yeniDurum ? 'Aktif' : 'Pasif') . ", ID: $id)");
            }
            $durumMesaj = $yeniDurum ? 'Paket başarıyla aktifleştirildi.' : 'Paket başarıyla pasifleştirildi.';
            echo json_encode(['success' => true, 'message' => $durumMesaj]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Silme işlemi veritabanı seviyesinde başarısız oldu.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}


