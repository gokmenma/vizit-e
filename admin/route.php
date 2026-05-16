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
    ->get('profil', 'pages/profil.php')
    ->get('kampanyalar', 'pages/kampanyalar.php')
    // AJAX Routes
    ->post('profil-guncelle', 'pages/ajax_profil_guncelle.php')
    ->post('kullanici-sil', 'pages/ajax_kullanici_sil.php')
    ->post('admin-kullanici-ekle', 'pages/ajax_kullanici_ekle.php')
    ->post('admin-kullanici-guncelle', 'pages/ajax_kullanici_guncelle.php')
    ->post('admin-kullanici-satin-al', 'pages/ajax_kullanici_satin_al.php')
    ->post('admin-paket-kaydet', 'pages/ajax_paket_kaydet.php')
    ->post('alt-kullanici-kaydet', 'pages/ajax_alt_kullanici_kaydet.php')
    ->post('satinalma-sil', 'pages/ajax_satinalma_sil.php')
    ->post('kampanya-kaydet', 'pages/ajax_kampanya_kaydet.php')
    ->post('kampanya-gonder', 'pages/ajax_kampanya_gonder.php')
    ->post('kampanya-sil', 'pages/ajax_kampanya_sil.php')
    ->get('kampanya-detay', 'pages/ajax_kampanya_detay.php')
    ->get('kampanya-logs', 'pages/ajax_kampanya_logs.php')

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


