<?php
/**
 * Admin Panel Satın Alma İşlemi AJAX Handler
 */
require_once __DIR__ . '/../../autoload.php';

// Oturum kontrolü (Sadece adminler)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(["status" => "error", "message" => "Yetkisiz erişim."]);
    exit;
}

// API dosyasını çağır (Oradaki logic'i kullanmak için)
// Not: APIuser.php POST['action'] kontrolü yapar
$_POST['action'] = 'admin-abone-satin-al';
require_once __DIR__ . '/../../App/Api/APIuser.php';
