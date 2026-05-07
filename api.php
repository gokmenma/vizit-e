<?php
// Bu dosyanın bir JSON API'si olduğunu tarayıcıya bildiriyoruz.
header('Content-Type: application/json');

require_once 'Core/Services/SgkViziteService.php';

// Güvenliğiniz için bu bilgileri daha güvenli bir yerden okuyun.
$kullaniciAdi = '32450401908';
$isyeriKodu = '3';
$wsSifre = '87174585';

// Çıktı olarak göndereceğimiz standart cevap yapısı
$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'kapatilan' => 0,
        'kapatilamayan' => 0
    ]
];

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Geçersiz istek metodu.';
    echo json_encode($response);
    exit;
}

// JavaScript'ten gönderilen JSON verisini al ve PHP dizisine çevir
$inputData = json_decode(file_get_contents('php://input'), true);

if (empty($inputData) || !isset($inputData['reportIds']) || !is_array($inputData['reportIds'])) {
    $response['message'] = 'Geçersiz veya eksik veri gönderildi. "reportIds" adında bir dizi bekleniyor.';
    echo json_encode($response);
    exit;
}

$secilenRaporlar = $inputData['reportIds'];

if (empty($secilenRaporlar)) {
    $response['message'] = 'Lütfen kapatmak için en az bir rapor seçin.';
    echo json_encode($response);
    exit;
}


try {
    $sgkClient = new SgkViziteService();
    
    foreach ($secilenRaporlar as $raporId) {
        $kapatResponse = $sgkClient->raporuKapat($raporId);
        if ($kapatResponse->sonucKod == '0') {
            $response['data']['kapatilan']++;
        } else {
            $response['data']['kapatilamayan']++;
        }
    }

    $response['success'] = true;
    $response['message'] = "İşlem tamamlandı. {$response['data']['kapatilan']} rapor başarıyla kapatıldı.";
    if ($response['data']['kapatilamayan'] > 0) {
        $response['message'] .= " {$response['data']['kapatilamayan']} rapor kapatılamadı.";
    }

} catch (Exception $e) {
    $response['message'] = 'Servis tarafında bir hata oluştu: ' . $e->getMessage();
}

// Sonucu JSON formatında ekrana bas ve işlemi bitir.
echo json_encode($response);
exit;