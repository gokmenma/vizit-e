<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================================================================
// 1. GEREKLİ BİLGİLER
// ==================================================================
// Lütfen bu alanları size ait GERÇEK bilgilerle doldurun.
$kullaniciAdi = '32450401908';
$isyeriKodu = '3';
$wsSifre = '87174585';

// ==================================================================
// 2. İSTEĞİ GÖNDERECEĞİMİZ URL (WSDL DEĞİL, SERVİSİN KENDİSİ)
// ==================================================================
$serviceUrl = 'https://uyg.sgk.gov.tr/Ws_Vizite/services/ViziteGonder';

// ==================================================================
// 3. GÖNDERECEĞİMİZ XML'İ ELLE OLUŞTURMA
// ==================================================================
// Değişkenleri doğrudan XML'in içine yerleştiriyoruz.
$xml_request = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://service.com">
<SOAP-ENV:Body>
    <ns1:wsLogin>
        <kullaniciAdi>{$kullaniciAdi}</kullaniciAdi>
        <isyeriKodu>{$isyeriKodu}</isyeriKodu>
        <isyeriSifresi>{$wsSifre}</isyeriSifresi>
    </ns1:wsLogin>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

// ==================================================================
// 4. cURL İLE İSTEĞİ GÖNDERME
// ==================================================================
echo "cURL ile istek gönderiliyor...\n\n";

// cURL oturumunu başlat
$ch = curl_init();

// cURL seçeneklerini ayarla
curl_setopt($ch, CURLOPT_URL, $serviceUrl); // İstek yapılacak URL
curl_setopt($ch, CURLOPT_POST, true); // İstek türü POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_request); // Gönderilecek XML verisi
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Cevabın doğrudan ekrana basılması yerine bir değişkene atanmasını sağla

// Gerekli HTTP başlık (header) bilgilerini ayarla
// SOAP istekleri için bu başlıklar önemlidir.
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: text/xml; charset=utf-8',
    'Content-Length: ' . strlen($xml_request),
    'SOAPAction: "wsLogin"' // Hangi metodun çağrıldığını belirtir
]);

// Localhost'taki SSL sorunlarını aşmak için
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// İsteği gönder ve cevabı al
$response = curl_exec($ch);

// Hata olup olmadığını kontrol et
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    echo "!!! cURL HATASI OLUŞTU !!!\n";
    echo "Hata: " . $error_msg;
} else {
    echo "============================================\n";
    echo "SUNUCUDAN GELEN HAM CEVAP:\n";
    echo "============================================\n";
    // Gelen XML'i daha okunaklı hale getirelim
    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($response);
    echo htmlspecialchars($dom->saveXML());
}

// cURL oturumunu kapat
curl_close($ch);

?>