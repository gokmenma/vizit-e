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

if ($campaignModel->deleteCampaign($id)) {
    echo json_encode(['status' => 'success', 'message' => 'Kampanya başarıyla silindi.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Kampanya silinirken bir hata oluştu.']);
}
