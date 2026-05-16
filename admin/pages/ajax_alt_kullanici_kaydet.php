<?php
require_once __DIR__ . '/../../autoload.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Oturum kontrolü
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(["status" => "error", "message" => "Yetkisiz erişim."]);
    exit;
}

$db = \Core\Database::getInstance()->getConnection();

$id = $_POST['id'] ?? 0;
$adi_soyadi = $_POST['adi_soyadi'] ?? '';
$kullanici_adi = $_POST['kullanici_adi'] ?? '';
$email = $_POST['email'] ?? '';
$sifre = $_POST['sifre'] ?? '';
$admin_id = $_POST['admin_id'] ?? 0;
$ekleyen_id = $_SESSION['user_id'] ?? 0;

if (empty($adi_soyadi) || empty($kullanici_adi) || empty($email) || empty($admin_id)) {
    echo json_encode(["status" => "error", "message" => "Lütfen zorunlu alanları doldurun."]);
    exit;
}

if ($id == 0 && empty($sifre)) {
    echo json_encode(["status" => "error", "message" => "Yeni kullanıcı için şifre zorunludur."]);
    exit;
}

// Kullanıcı adı ve email kontrolü
$stmt = $db->prepare("SELECT id FROM kullanicilar WHERE (kullanici_adi = ? OR email = ?) AND id != ? AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
$stmt->execute([$kullanici_adi, $email, $id]);
if ($stmt->fetch()) {
    echo json_encode(["status" => "error", "message" => "Bu kullanıcı adı veya e-posta zaten kullanılıyor."]);
    exit;
}

try {
    if ($id > 0) {
        // Güncelleme
        $sql = "UPDATE kullanicilar SET adi_soyadi = ?, kullanici_adi = ?, email = ?, admin_id = ? WHERE id = ?";
        $params = [$adi_soyadi, $kullanici_adi, $email, $admin_id, $id];
        
        if (!empty($sifre)) {
            $sql = "UPDATE kullanicilar SET adi_soyadi = ?, kullanici_adi = ?, email = ?, admin_id = ?, sifre = ? WHERE id = ?";
            $params = [$adi_soyadi, $kullanici_adi, $email, $admin_id, password_hash($sifre, PASSWORD_DEFAULT), $id];
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $message = "Alt kullanıcı başarıyla güncellendi.";
    } else {
        // Yeni Kayıt
        $sql = "INSERT INTO kullanicilar (adi_soyadi, kullanici_adi, email, sifre, role, admin_id, ekleyen_id, kayit_tarihi, durum) 
                VALUES (?, ?, ?, ?, 'user', ?, ?, ?, 'Aktif')";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $adi_soyadi,
            $kullanici_adi,
            $email,
            password_hash($sifre, PASSWORD_DEFAULT),
            $admin_id,
            $ekleyen_id,
            date('Y-m-d H:i:s')
        ]);
        $message = "Alt kullanıcı başarıyla eklendi.";
    }

    echo json_encode(["status" => "success", "message" => $message]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Hata: " . $e->getMessage()]);
}
