<?php
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit();
}

$id = $_POST['id'] ?? 0;
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$criteria = $_POST['criteria'] ?? [];

if (empty($title) || empty($content)) {
    echo json_encode(['status' => 'error', 'message' => 'Başlık ve içerik alanları zorunludur.']);
    exit();
}

$campaignModel = new \Models\CampaignModel();

try {
    if ($id) {
        $campaignModel->updateCampaign($id, [
            'title' => $title,
            'content' => $content,
            'criteria' => $criteria,
            'status' => 'draft' // Düzenlendiğinde tekrar taslak moduna çek
        ]);
        $campaignId = $id;
        $campaignModel->clearLogs($campaignId);
    } else {
        $campaignId = $campaignModel->createCampaign([
            'title' => $title,
            'content' => $content,
            'criteria' => $criteria
        ]);
    }

    if ($campaignId) {
        // Hedef kullanıcıları bul ve logları oluştur
        $users = $campaignModel->getFilteredUsers($criteria);
        if (!empty($users)) {
            $campaignModel->createLogs($campaignId, $users);
        }

        $msg = $id ? 'Kampanya başarıyla güncellendi' : 'Kampanya başarıyla oluşturuldu';
        echo json_encode(['status' => 'success', 'message' => $msg . ' ve ' . count($users) . ' alıcı hazırlandı.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kampanya kaydedilemedi.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
