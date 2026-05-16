<?php
/**
 * Admin Panel Kullanıcı Güncelleme AJAX Bridge
 */
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

// Oturum kontrolü (Sadece adminler)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(["status" => "error", "message" => "Yetkisiz erişim."]);
    exit;
}

// Gelen action değerini koru, eğer yoksa varsayılan ata
if (!isset($_POST['action'])) {
    $_POST['action'] = 'admin-kullanici-guncelle';
}

require_once __DIR__ . '/../../App/Api/APIuser.php';
