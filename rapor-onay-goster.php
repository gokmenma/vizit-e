<?php
// Bu dosya, onay belgesini SPA kabuğu (sidebar/topbar) olmadan, sadece
// yazdırılabilir belge olarak göstermek için index.php'nin SPA sarmalayıcısını
// bilinçli olarak atlar (.htaccess mevcut dosyaları yeniden yazmaz).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/pages/rapor_onay_goster.php';
