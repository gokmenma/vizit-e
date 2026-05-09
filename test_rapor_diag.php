<?php
/**
 * SGK raporAramaTarihile Tanılama Dosyası
 * Bu dosya, SADECE "raporAramaTarihile" metodunun neden başarısız olduğunu
 * bulmak için 4 farklı yöntemle bağlantı dener.
 * 
 * Kullanım: Tarayıcıdan çağırın. Session bilgilerini kullanır.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 dakika timeout
header('Content-Type: text/html; charset=utf-8');

echo "<h2>SGK raporAramaTarihile Tanılama Testi</h2><hr>";
echo "<pre>";

// Session ve ayarları yükle
require_once __DIR__ . '/vendor/autoload.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Helper\Security;

$kullaniciAdi = $_SESSION['kullaniciAdi'] ?? null;
$isyeriKodu   = $_SESSION['isyeriKodu'] ?? null;
$wsSifre      = Security::decrypt($_SESSION['wsSifre'] ?? null);

if (!$kullaniciAdi || !$isyeriKodu || !$wsSifre) {
    die("HATA: Session bilgileri bulunamadı. Lütfen önce sisteme giriş yapın.");
}

echo "Kullanıcı: {$kullaniciAdi}\n";
echo "İşyeri: {$isyeriKodu}\n";
echo "Tarih: " . date('d.m.Y H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

$serviceUrl = 'https://uyg.sgk.gov.tr/Ws_Vizite/services/ViziteGonder';
$tarih = date('d.m.Y', strtotime('+1 day')); // Yarın

// ─────────────────────────────────────────────────────
// ADIM 1: Önce wsLogin ile token alalım (cURL ile)
// ─────────────────────────────────────────────────────
echo "═══ ADIM 1: wsLogin ile Token Alma ═══\n";

$loginXml = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://service.com">
<SOAP-ENV:Body>
    <ns1:wsLogin>
        <kullaniciAdi>' . htmlspecialchars($kullaniciAdi, ENT_XML1) . '</kullaniciAdi>
        <isyeriKodu>' . htmlspecialchars($isyeriKodu, ENT_XML1) . '</isyeriKodu>
        <isyeriSifresi>' . htmlspecialchars($wsSifre, ENT_XML1) . '</isyeriSifresi>
    </ns1:wsLogin>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

// Önce cache'den token var mı bak
$currentUserKey = $kullaniciAdi . '|' . $isyeriKodu;
$cacheFile = __DIR__ . '/cache/sgk_token_' . md5($currentUserKey) . '.json';
$wsToken = null;

if (file_exists($cacheFile)) {
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    if ($cacheData && isset($cacheData['wsToken']) && time() < $cacheData['tokenExpiresAt']) {
        $wsToken = $cacheData['wsToken'];
        echo "✓ Token cache'den alındı: " . substr($wsToken, 0, 20) . "...\n\n";
    }
}

if (!$wsToken) {
    $ch = curl_init($serviceUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $loginXml,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ""',
            'Connection: close'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ]);
    $loginResponse = curl_exec($ch);
    $loginErr = curl_error($ch);
    $loginErrNo = curl_errno($ch);
    curl_close($ch);

    if ($loginErrNo) {
        die("✗ wsLogin BAŞARISIZ - cURL Hatası ({$loginErrNo}): {$loginErr}\n");
    }

    // Token'ı parse et
    $cleanXml = preg_replace("/(<\/?)(\\w+):([^>]*>)/", "$1$2$3", $loginResponse);
    $xml = simplexml_load_string($cleanXml);
    if ($xml === false) {
        die("✗ wsLogin cevabı parse edilemedi\n");
    }
    $body = $xml->xpath('//soapenvBody')[0];
    $loginReturn = $body->children()->children();
    
    if (isset($loginReturn->wsLoginReturn->sonucKod) && $loginReturn->wsLoginReturn->sonucKod == '0') {
        $wsToken = (string)$loginReturn->wsLoginReturn->wsToken;
        echo "✓ Token başarıyla alındı: " . substr($wsToken, 0, 20) . "...\n\n";
    } else {
        $hata = (string)($loginReturn->wsLoginReturn->sonucAciklama ?? 'Bilinmeyen');
        die("✗ wsLogin başarısız: {$hata}\n");
    }
}

// ─────────────────────────────────────────────────────
// ADIM 2: raporAramaTarihile XML'ini hazırla
// ─────────────────────────────────────────────────────
$raporXml = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://service.com">
<SOAP-ENV:Body>
    <ns1:raporAramaTarihile>
        <kullaniciAdi>' . htmlspecialchars($kullaniciAdi, ENT_XML1) . '</kullaniciAdi>
        <isyeriKodu>' . htmlspecialchars($isyeriKodu, ENT_XML1) . '</isyeriKodu>
        <wsToken>' . htmlspecialchars($wsToken, ENT_XML1) . '</wsToken>
        <tarih>' . $tarih . '</tarih>
    </ns1:raporAramaTarihile>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

echo "Gönderilecek XML boyutu: " . strlen($raporXml) . " byte\n";
echo "Hedef tarih: {$tarih}\n\n";

// ─────────────────────────────────────────────────────
// YÖNTEM 1: Mevcut cURL ayarlarıyla (Şu anki haliyle)
// ─────────────────────────────────────────────────────
echo "═══ YÖNTEM 1: Mevcut cURL ayarları (HTTP 1.0 + Gzip) ═══\n";
$ch = curl_init($serviceUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $raporXml,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: text/xml; charset=utf-8',
        'Content-Length: ' . mb_strlen($raporXml, '8bit'),
        'SOAPAction: ""',
        'Expect:',
        'Connection: close'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 150,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_FORBID_REUSE => true,
    CURLOPT_FRESH_CONNECT => true,
    CURLOPT_ENCODING => '',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
    CURLOPT_BUFFERSIZE => 64000,
]);
$t1 = microtime(true);
$resp1 = curl_exec($ch);
$t1_end = microtime(true);
$err1 = curl_error($ch);
$errNo1 = curl_errno($ch);
$info1 = curl_getinfo($ch);
curl_close($ch);
echo "Süre: " . round($t1_end - $t1, 2) . "s\n";
echo "HTTP Kodu: " . $info1['http_code'] . "\n";
echo "İndirilen: " . $info1['size_download'] . " byte\n";
if ($errNo1) {
    echo "✗ HATA ({$errNo1}): {$err1}\n\n";
} else {
    echo "✓ BAŞARILI! Cevap boyutu: " . strlen($resp1) . " byte\n\n";
}

// ─────────────────────────────────────────────────────
// YÖNTEM 2: Sade cURL (Minimum ayarlarla)
// ─────────────────────────────────────────────────────
echo "═══ YÖNTEM 2: Sade cURL (Minimum ayarlar) ═══\n";
$ch = curl_init($serviceUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $raporXml,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: ""',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 180,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
]);
$t2 = microtime(true);
$resp2 = curl_exec($ch);
$t2_end = microtime(true);
$err2 = curl_error($ch);
$errNo2 = curl_errno($ch);
$info2 = curl_getinfo($ch);
curl_close($ch);
echo "Süre: " . round($t2_end - $t2, 2) . "s\n";
echo "HTTP Kodu: " . $info2['http_code'] . "\n";
echo "İndirilen: " . $info2['size_download'] . " byte\n";
if ($errNo2) {
    echo "✗ HATA ({$errNo2}): {$err2}\n\n";
} else {
    echo "✓ BAŞARILI! Cevap boyutu: " . strlen($resp2) . " byte\n\n";
}

// ─────────────────────────────────────────────────────
// YÖNTEM 3: PHP SoapClient
// ─────────────────────────────────────────────────────
echo "═══ YÖNTEM 3: PHP SoapClient (WSDL ile) ═══\n";
if (extension_loaded('soap')) {
    try {
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            ],
            'http' => [
                'timeout' => 180,
            ]
        ]);
        
        $client = new SoapClient($serviceUrl . '?wsdl', [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => $ctx,
            'connection_timeout' => 30,
            'keep_alive' => false,
        ]);

        $t3 = microtime(true);
        $result3 = $client->raporAramaTarihile([
            'kullaniciAdi' => $kullaniciAdi,
            'isyeriKodu'   => $isyeriKodu,
            'wsToken'      => $wsToken,
            'tarih'        => $tarih,
        ]);
        $t3_end = microtime(true);
        echo "Süre: " . round($t3_end - $t3, 2) . "s\n";
        echo "✓ BAŞARILI!\n";
        $lastResponse = $client->__getLastResponse();
        echo "Cevap boyutu: " . strlen($lastResponse) . " byte\n\n";
    } catch (SoapFault $e) {
        $t3_end = microtime(true);
        echo "Süre: " . round($t3_end - $t3, 2) . "s\n";
        echo "✗ SOAP HATA: " . $e->getMessage() . "\n\n";
    } catch (Exception $e) {
        echo "✗ GENEL HATA: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "✗ SoapClient eklentisi yüklü değil, atlanıyor.\n\n";
}

// ─────────────────────────────────────────────────────
// YÖNTEM 4: file_get_contents + stream_context
// ─────────────────────────────────────────────────────
echo "═══ YÖNTEM 4: file_get_contents + stream_context ═══\n";
$streamCtx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: text/xml; charset=utf-8\r\n" .
                     "SOAPAction: \"\"\r\n" .
                     "Connection: close\r\n",
        'content' => $raporXml,
        'timeout' => 180,
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
    ]
]);
$t4 = microtime(true);
$resp4 = @file_get_contents($serviceUrl, false, $streamCtx);
$t4_end = microtime(true);
echo "Süre: " . round($t4_end - $t4, 2) . "s\n";
if ($resp4 === false) {
    $lastErr = error_get_last();
    echo "✗ HATA: " . ($lastErr['message'] ?? 'Bilinmeyen hata') . "\n\n";
} else {
    echo "✓ BAŞARILI! Cevap boyutu: " . strlen($resp4) . " byte\n\n";
}

// ─────────────────────────────────────────────────────
// YÖNTEM 5: Karşılaştırma - onayliRaporlarTarihile (Çalışan metot)
// ─────────────────────────────────────────────────────
echo "═══ YÖNTEM 5: Kontrol Testi - onayliRaporlarTarihile (Çalışan metot) ═══\n";
$tarih1 = date('d.m.Y', strtotime('-30 days'));
$tarih2 = date('d.m.Y');
$onayliXml = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://service.com">
<SOAP-ENV:Body>
    <ns1:onayliRaporlarTarihile>
        <kullaniciAdi>' . htmlspecialchars($kullaniciAdi, ENT_XML1) . '</kullaniciAdi>
        <isyeriKodu>' . htmlspecialchars($isyeriKodu, ENT_XML1) . '</isyeriKodu>
        <wsToken>' . htmlspecialchars($wsToken, ENT_XML1) . '</wsToken>
        <tarih1>' . $tarih1 . '</tarih1>
        <tarih2>' . $tarih2 . '</tarih2>
    </ns1:onayliRaporlarTarihile>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

$ch = curl_init($serviceUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $onayliXml,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: ""',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 180,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
]);
$t5 = microtime(true);
$resp5 = curl_exec($ch);
$t5_end = microtime(true);
$err5 = curl_error($ch);
$errNo5 = curl_errno($ch);
$info5 = curl_getinfo($ch);
curl_close($ch);
echo "Süre: " . round($t5_end - $t5, 2) . "s\n";
echo "HTTP Kodu: " . $info5['http_code'] . "\n";
echo "İndirilen: " . $info5['size_download'] . " byte\n";
if ($errNo5) {
    echo "✗ HATA ({$errNo5}): {$err5}\n\n";
} else {
    echo "✓ BAŞARILI! Cevap boyutu: " . strlen($resp5) . " byte\n\n";
}

// ─────────────────────────────────────────────────────
// SONUÇ TABLOSU
// ─────────────────────────────────────────────────────
echo str_repeat("═", 70) . "\n";
echo "SONUÇ TABLOSU\n";
echo str_repeat("═", 70) . "\n";
echo str_pad("Yöntem", 45) . str_pad("Durum", 15) . "Boyut\n";
echo str_repeat("-", 70) . "\n";
echo str_pad("1. cURL (HTTP 1.0 + Gzip)", 45) . str_pad($errNo1 ? "✗ HATA({$errNo1})" : "✓ OK", 15) . ($errNo1 ? "-" : strlen($resp1) . " byte") . "\n";
echo str_pad("2. cURL (Minimum)", 45) . str_pad($errNo2 ? "✗ HATA({$errNo2})" : "✓ OK", 15) . ($errNo2 ? "-" : strlen($resp2) . " byte") . "\n";
echo str_pad("3. SoapClient", 45) . (isset($e) && $e instanceof SoapFault ? "✗ HATA" : "✓ OK") . "\n";
echo str_pad("4. file_get_contents", 45) . str_pad($resp4 === false ? "✗ HATA" : "✓ OK", 15) . ($resp4 === false ? "-" : strlen($resp4) . " byte") . "\n";
echo str_pad("5. onayliRaporlarTarihile (Kontrol)", 45) . str_pad($errNo5 ? "✗ HATA({$errNo5})" : "✓ OK", 15) . ($errNo5 ? "-" : strlen($resp5) . " byte") . "\n";
echo str_repeat("═", 70) . "\n";

// PHP ve cURL bilgileri
echo "\n═══ SUNUCU BİLGİLERİ ═══\n";
echo "PHP Sürümü: " . PHP_VERSION . "\n";
echo "cURL Sürümü: " . curl_version()['version'] . "\n";
echo "OpenSSL Sürümü: " . curl_version()['ssl_version'] . "\n";
echo "OS: " . PHP_OS . "\n";
echo "</pre>";
