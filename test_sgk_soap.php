<?php
/**
 * SGK SoapClient Test Dosyası
 * Bu dosya cURL yerine PHP'nin dahili SoapClient sınıfı ile SGK'ya bağlanmayı dener.
 */

header('Content-Type: text/plain; charset=utf-8');

// 1. SoapClient kontrolü
if (!extension_loaded('soap')) {
    die("HATA: PHP SoapClient eklentisi yüklü değil! Lütfen php.ini dosyasından 'extension=soap' satırını aktif edin.\n");
}

echo "SoapClient eklentisi yüklü, test başlıyor...\n";

// TEST BİLGİLERİ (Lütfen burayı kendi bilgilerinizle doldurun veya bir session'dan çekin)
// Şimdilik hata mesajını görmek için rastgele bilgilerle de denenebilir, 
// çünkü amacımız 'Connection closed abruptly' hatasını alıp almadığımızı görmek.
$kullaniciAdi = 'TEST'; 
$isyeriKodu   = 'TEST';
$wsSifre      = 'TEST';

$wsdlUrl = 'https://uyg.sgk.gov.tr/Ws_Vizite/services/ViziteGonder?wsdl';

try {
    $options = [
        'trace' => 1,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $client = new SoapClient($wsdlUrl, $options);

    echo "WSDL başarıyla yüklendi. wsLogin deneniyor...\n";

    $params = [
        'kullaniciAdi'  => $kullaniciAdi,
        'isyeriKodu'    => $isyeriKodu,
        'isyeriSifresi' => $wsSifre
    ];

    // SoapClient üzerinden wsLogin çağrısı
    $result = $client->wsLogin($params);

    echo "İstek gönderildi! SGK'dan gelen cevap:\n";
    print_r($result);

} catch (SoapFault $e) {
    echo "SOAP HATASI YAKALANDI:\n";
    echo "Hata Kodu: " . $e->faultcode . "\n";
    echo "Hata Mesajı: " . $e->getMessage() . "\n";
    
    // Ham XML'leri görmek için (Hata detayını anlamak için kritik)
    if (isset($client)) {
        echo "\n--- GÖNDERİLEN İSTEK (Request) ---\n";
        echo $client->__getLastRequest() . "\n";
        echo "\n--- GELEN CEVAP (Response) ---\n";
        echo $client->__getLastResponse() . "\n";
    }
} catch (Exception $e) {
    echo "GENEL HATA: " . $e->getMessage() . "\n";
}
