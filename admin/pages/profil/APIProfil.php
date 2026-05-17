<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

use Admin\Models\UserModel;

$userModel = new UserModel();
$action = $_POST['action'] ?? 'update';

if ($action === 'update') {
    $userId = $_SESSION['user_id'];
    $adi_soyadi = $_POST['adi_soyadi'] ?? '';
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $email = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    try {
        // Kullanıcı adı veya email başkası tarafından kullanılıyor mu? (Model üzerinden)
        if ($userModel->checkUserExists($kullanici_adi, $email, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı adı veya e-posta adresi zaten kullanımda.']);
            exit;
        }

        $success = $userModel->updateProfile([
            'user_id' => $userId,
            'adi_soyadi' => $adi_soyadi,
            'kullanici_adi' => $kullanici_adi,
            'email' => $email,
            'new_password' => $new_password
        ]);

        if ($success) {
            // Oturum verilerini güncelle
            $_SESSION['user_ad'] = $adi_soyadi;
            $_SESSION['user_email'] = $email;

            // Logla (DatabaseLogger handles log writing)
            if (class_exists('\\Core\\Services\\DatabaseLogger')) {
                $logger = new \Core\Services\DatabaseLogger('user-management');
                $logger->info("Profil güncellendi: $adi_soyadi (#$userId)");
            }

            echo json_encode(['success' => true, 'message' => 'Profiliniz başarıyla güncellendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Profil güncelleme işlemi başarısız oldu.']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}
