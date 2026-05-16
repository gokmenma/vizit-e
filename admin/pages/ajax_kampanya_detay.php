<?php
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit();
}

$id = $_GET['id'] ?? 0;
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

$campaign->criteria = json_decode($campaign->criteria, true);

// Eğer seçili kullanıcılar varsa isimlerini de getir (UI için)
if (!empty($campaign->criteria['user_ids'])) {
    $userModel = new \Models\UserModel();
    $ids = is_array($campaign->criteria['user_ids']) ? $campaign->criteria['user_ids'] : explode(',', $campaign->criteria['user_ids']);
    $users = [];
    foreach($ids as $uid) {
        $u = $userModel->findById($uid);
        if ($u) {
            $users[] = ['id' => $u->id, 'name' => $u->adi_soyadi ?: $u->kullanici_adi];
        }
    }
    $campaign->selected_users_data = $users;
}

echo json_encode(['status' => 'success', 'data' => $campaign]);
