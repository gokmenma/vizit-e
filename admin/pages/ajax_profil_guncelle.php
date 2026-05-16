<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../Core/Database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$userId = $_SESSION['user_id'];
$adi_soyadi = $_POST['adi_soyadi'] ?? '';
$kullanici_adi = $_POST['kullanici_adi'] ?? '';
$email = $_POST['email'] ?? '';
$new_password = $_POST['new_password'] ?? '';

try {
    $db = \Core\Database::getInstance()->getConnection();

    // Kullanıcı adı veya email başkası tarafından kullanılıyor mu?
    $stmt = $db->prepare("SELECT id FROM kullanicilar WHERE (kullanici_adi = ? OR email = ?) AND id != ? AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
    $stmt->execute([$kullanici_adi, $email, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı veya e-posta adresi zaten kullanımda.']);
        exit;
    }

    $sql = "UPDATE kullanicilar SET adi_soyadi = ?, kullanici_adi = ?, email = ? WHERE id = ?";
    $params = [$adi_soyadi, $kullanici_adi, $email, $userId];

    if (!empty($new_password)) {
        $sql = "UPDATE kullanicilar SET adi_soyadi = ?, kullanici_adi = ?, email = ?, sifre = ? WHERE id = ?";
        $params = [$adi_soyadi, $kullanici_adi, $email, password_hash($new_password, PASSWORD_DEFAULT), $userId];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Oturum verilerini güncelle
    $_SESSION['user_ad'] = $adi_soyadi;
    $_SESSION['user_email'] = $email;

    // Logla
    require_once __DIR__ . '/../../Core/Services/DatabaseLogger.php';
    $logger = new \Core\Services\DatabaseLogger('user-management');
    $logger->info("Profil güncellendi: $adi_soyadi (#$userId)");

    echo json_encode(['success' => true, 'message' => 'Profiliniz başarıyla güncellendi.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
