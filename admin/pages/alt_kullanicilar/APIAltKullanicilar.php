<?php
header('Content-Type: application/json');

if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Oturum kontrolü
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(["status" => "error", "message" => "Yetkisiz erişim."]);
    exit;
}

use Admin\Models\UserModel;

$userModel = new UserModel();
$action = $_POST['action'] ?? 'save';

if ($action === 'save') {
    $id = $_POST['id'] ?? 0;
    $adi_soyadi = $_POST['adi_soyadi'] ?? '';
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $email = $_POST['email'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    $admin_id = $_POST['admin_id'] ?? 0;
    $ekleyen_id = $_SESSION['user_id'] ?? 0;

    if (empty($adi_soyadi) || empty($kullanici_adi) || empty($email) || ($id == 0 && empty($admin_id))) {
        echo json_encode(["status" => "error", "message" => "Lütfen zorunlu alanları doldurun."]);
        exit;
    }

    if ($id == 0 && empty($sifre)) {
        echo json_encode(["status" => "error", "message" => "Yeni kullanıcı için şifre zorunludur."]);
        exit;
    }

    // Kullanıcı adı ve email kontrolü (Model üzerinden)
    if ($userModel->checkUserExists($kullanici_adi, $email, $id)) {
        echo json_encode(["status" => "error", "message" => "Bu kullanıcı adı veya e-posta zaten kullanılıyor."]);
        exit;
    }

    $yetkiler = isset($_POST['yetkiler']) ? (is_array($_POST['yetkiler']) ? implode(',', $_POST['yetkiler']) : $_POST['yetkiler']) : '';

    try {
        $success = $userModel->saveAltUser([
            'id' => $id,
            'adi_soyadi' => $adi_soyadi,
            'kullanici_adi' => $kullanici_adi,
            'email' => $email,
            'sifre' => $sifre,
            'admin_id' => $admin_id,
            'ekleyen_id' => $ekleyen_id,
            'yetkiler' => $yetkiler
        ]);

        if ($success) {
            $message = $id > 0 ? "Alt kullanıcı başarıyla güncellendi." : "Alt kullanıcı başarıyla eklendi.";
            echo json_encode(["status" => "success", "message" => $message]);
        } else {
            echo json_encode(["status" => "error", "message" => "İşlem sırasında bir hata oluştu."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Hata: " . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "Geçersiz kullanıcı ID."]);
        exit;
    }

    try {
        $success = $userModel->delete($id);
        if ($success) {
            echo json_encode(["status" => "success", "message" => "Alt kullanıcı başarıyla silindi."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Silme işlemi başarısız oldu."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Hata: " . $e->getMessage()]);
    }
    exit;
}
