<?php

/** @var Router $router */

$router->get('sign-in', 'login.php')
    ->post('sign-in', 'login.php')
    ->get('index', 'index.php')
    ->get('dashboard', 'pages/dashboard.php')
    ->get('kullanicilar', 'pages/kullanicilar.php')
    ->get('alt-kullanicilar', 'pages/alt_kullanicilar.php')
    ->get('paketler', 'pages/paketler.php')
    ->get('satinalmalar', 'pages/satinalmalar.php')
    ->get('ayarlar', 'pages/ayarlar.php')
    ->get('aktiviteler', 'pages/aktiviteler.php')
    // AJAX Routes
    ->post('kullanici-sil', 'pages/ajax_kullanici_sil.php')
    ->post('admin-kullanici-ekle', 'pages/ajax_kullanici_ekle.php')
    ->post('admin-kullanici-satin-al', 'pages/ajax_kullanici_satin_al.php')

    ->get('logout', function () {
        $config = require __DIR__ . '/../config.php';
        $basePath = rtrim($config['base_path'], '/');
        $is_admin_host = ($_SERVER['HTTP_HOST'] === 'admin.vizite.com' || $_SERVER['HTTP_HOST'] === 'admin.vizit-e.com');
        $redirectUrl = $is_admin_host ? '/sign-in' : $basePath . '/admin/sign-in';
        
        session_destroy();
        header("Location: " . $redirectUrl);
        exit();
    })
    ->resetPrefix();


