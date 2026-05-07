<?php 

// Sadece komut satırından çalıştır
if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit();
}
echo "Rapor otomatik onay cron'u çalıştırıldı: " . date('Y-m-d H:i:s') . "\n";

?>