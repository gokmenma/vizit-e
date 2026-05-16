<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

// Log call for debugging
file_put_contents(__DIR__ . '/../ajax_debug.txt', "AJAX SIL CALL: " . date('H:i:s') . " - " . print_r($_POST, true), FILE_APPEND);

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
    $userModel = new \Models\UserModel();
    
    // Kullanıcının varlığını kontrol et
    $user = $userModel->findById($id);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
        exit;
    }

    $result = $userModel->softDeleteUser($id);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Kullanıcı ve bağlı tüm veriler (alt kullanıcılar, işyerleri) başarıyla silindi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Silme işlemi veritabanı seviyesinde başarısız oldu.']);
    }
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/../ajax_debug.txt', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
