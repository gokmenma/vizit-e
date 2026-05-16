<?php
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit();
}

$id = $_POST['id'] ?? 0;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz kampanya ID.']);
    exit();
}

$campaignModel = new \Models\CampaignModel();
$campaign = $campaignModel->getCampaign($id);

if (!$campaign) {
    echo json_encode(['status' => 'error', 'message' => 'Kampanya bulunamadı.']);
    exit();
}

if ($campaign->status === 'completed') {
    echo json_encode(['status' => 'error', 'message' => 'Bu kampanya zaten tamamlanmış.']);
    exit();
}

// Kampanya durumunu gönderiliyor yap
$campaignModel->updateCampaign($id, [
    'title' => $campaign->title,
    'content' => $campaign->content,
    'criteria' => json_decode($campaign->criteria, true),
    'status' => 'sending'
]);

$pendingLogs = $campaignModel->getPendingLogs($id, 100); // 100'erli gönderim

if (empty($pendingLogs)) {
    $campaignModel->finishCampaign($id);
    echo json_encode(['status' => 'success', 'message' => 'Tüm e-postalar gönderildi.']);
    exit();
}

$sentCount = 0;
$failedCount = 0;

foreach ($pendingLogs as $log) {
    $content = str_replace('{adi_soyadi}', $log->name ?: 'Değerli Kullanıcımız', $campaign->content);
    
    $result = \Core\Services\MailGonderService::gonder($log->email, $campaign->title, $content);
    
    if ($result) {
        $campaignModel->updateLogStatus($log->id, 'sent');
        $sentCount++;
    } else {
        $campaignModel->updateLogStatus($log->id, 'failed', 'Mail gönderimi başarısız.');
        $failedCount++;
    }
}

// Eğer hala bekleyen varsa göndermeye devam et uyarısı verilebilir veya otomatiğe bağlanabilir.
// Bu basit örnekte tek seferde hepsini göndermeye çalışacağız ama timeout riskine karşı:
$stillPending = $campaignModel->getPendingLogs($id, 1);
if (empty($stillPending)) {
    $campaignModel->finishCampaign($id);
    echo json_encode(['status' => 'success', 'message' => "Gönderim tamamlandı. Başarılı: $sentCount, Hata: $failedCount"]);
} else {
    echo json_encode(['status' => 'success', 'message' => "$sentCount adet e-posta gönderildi. Kalanlar için lütfen tekrar gönder düğmesine basın veya bu işlemi otomatiğe bağlayın.", 'continue' => true]);
}
