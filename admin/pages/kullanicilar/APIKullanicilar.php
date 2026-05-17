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
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'admin-kullanici-ekle' || $action === 'admin-kullanici-guncelle') {
    // Forward to parent system's API controller to run extensive system logic (e.g. KVKK, email, registration logic)
    if (file_exists(__DIR__ . '/../../../App/Api/APIuser.php')) {
        require_once __DIR__ . '/../../../App/Api/APIuser.php';
    } else {
        // Fallback local model implementation if ported
        try {
            $userModel = new \Models\UserModel();
            $id = isset($_POST['id']) ? \App\Helper\Security::decrypt($_POST['id']) : null;
            $adi_soyadi = $_POST['adi_soyadi'] ?? '';
            $kullanici_adi = $_POST['kullanici_adi'] ?? '';
            $email = $_POST['email'] ?? '';
            $paket_id = $_POST['paket_id'] ?? '';
            $role = $_POST['role'] ?? 'admin';

            if (empty($adi_soyadi) || empty($email) || empty($kullanici_adi)) {
                echo json_encode(["status" => "error", "message" => "Lütfen zorunlu alanları doldurun."]);
                exit;
            }

            $data = [
                'id' => $id,
                'adi_soyadi' => $adi_soyadi,
                'kullanici_adi' => $kullanici_adi,
                'email' => $email,
                'role' => $role
            ];
            
            $userModel->saveWithAttr($data);
            echo json_encode(["status" => "success", "message" => "Kullanıcı başarıyla kaydedildi."]);
        } catch (Exception $ex) {
            echo json_encode(["status" => "error", "message" => $ex->getMessage()]);
        }
    }
    exit;
}

if ($action === 'delete' || $action === 'kullanici-sil') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID eksik.']);
        exit;
    }

    try {
        $userModel = new \Models\UserModel();
        $user = $userModel->findById($id);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
            exit;
        }

        $result = $userModel->softDeleteUser($id);

        if ($result) {
            if (class_exists('\\Core\\Services\\DatabaseLogger')) {
                $logger = new \Core\Services\DatabaseLogger('user-management');
                $logger->warning("Kullanıcı silindi: " . $user->adi_soyadi . " (ID: $id)");
            }
            echo json_encode(['success' => true, 'message' => 'Kullanıcı ve bağlı tüm veriler (alt kullanıcılar, işyerleri) başarıyla silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Silme işlemi veritabanı seviyesinde başarısız oldu.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}
