<?php
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz işlem.']);
    exit;
}

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

$paketModel = new \Models\AbonelikPaketModel();
$data = [
    'ad' => $ad,
    'fiyat' => $fiyat,
    'sure' => $sure,
    'firma_hakki' => $firma_hakki,
    'alt_kullanici_hakki' => $alt_kullanici_hakki
];

try {
    if ($id) {
        $paketModel->updateSingle($id, $data);
        echo json_encode(['success' => true, 'message' => 'Paket başarıyla güncellendi.']);
    } else {
        $paketModel->saveWithAttr($data);
        echo json_encode(['success' => true, 'message' => 'Yeni paket başarıyla oluşturuldu.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
