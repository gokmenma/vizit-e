<?php

/** @var Router $router */

$router->get('sign-in', 'login.php')
    ->post('sign-in', 'login.php')
    ->get('index', 'index.php')
    ->get('dashboard', 'pages/dashboard.php')
    ->get('kullanicilar', 'pages/kullanicilar/kullanicilar.php')
    ->get('alt-kullanicilar', 'pages/alt_kullanicilar/alt_kullanicilar.php')
    ->get('paketler', 'pages/paketler/paketler.php')
    ->get('satinalmalar', 'pages/satinalmalar/satinalmalar.php')
    ->get('ayarlar', 'pages/ayarlar/ayarlar.php')
    ->get('aktiviteler', 'pages/aktiviteler/aktiviteler.php')
    ->get('profil', 'pages/profil/profil.php')
    ->get('kampanyalar', 'pages/kampanyalar/kampanyalar.php')
    // AJAX Routes
    ->post('profil-guncelle', 'pages/profil/APIProfil.php')
    ->post('kullanici-sil', 'pages/kullanicilar/APIKullanicilar.php')
    ->post('admin-kullanici-ekle', 'pages/kullanicilar/APIKullanicilar.php')
    ->post('admin-kullanici-guncelle', 'pages/kullanicilar/APIKullanicilar.php')
    ->post('admin-kullanici-satin-al', 'pages/satinalmalar/APISatinalmalar.php')
    ->post('admin-paket-kaydet', 'pages/paketler/APIPaketler.php')
    ->post('admin-paket-sil', 'pages/paketler/APIPaketler.php')
    ->post('alt-kullanici-kaydet', 'pages/alt_kullanicilar/APIAltKullanicilar.php')
    ->post('satinalma-sil', 'pages/satinalmalar/APISatinalmalar.php')
    ->post('kampanya-kaydet', 'pages/kampanyalar/APIKampanyalar.php')
    ->post('kampanya-gonder', 'pages/kampanyalar/APIKampanyalar.php')
    ->post('kampanya-sil', 'pages/kampanyalar/APIKampanyalar.php')
    ->get('kampanya-detay', 'pages/kampanyalar/APIKampanyalar.php')
    ->get('kampanya-logs', 'pages/kampanyalar/APIKampanyalar.php')
    // Ayarlar API Routes
    ->post('ayarlar-guncelle', 'pages/ayarlar/APIAyarlar.php')
    ->post('smtp-test', 'pages/ayarlar/APIAyarlar.php')
    ->get('sistem-yedek-indir', 'pages/ayarlar/APIAyarlar.php')

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


