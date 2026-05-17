<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin yetki kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

use Admin\Models\CampaignModel;

$campaignModel = new CampaignModel();

// Hem GET hem de POST için action'ı al
$action = $_REQUEST['action'] ?? '';

// 1. KAMPANYA DETAYI (GET)
if ($action === 'detail' || $action === 'detay') {
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz kampanya ID.']);
        exit;
    }

    $campaign = $campaignModel->getCampaign($id);
    if (!$campaign) {
        echo json_encode(['status' => 'error', 'message' => 'Kampanya bulunamadı.']);
        exit;
    }

    $campaign->criteria = json_decode($campaign->criteria, true);

    // Eğer seçili kullanıcılar varsa isimlerini de getir (UI için)
    if (!empty($campaign->criteria['user_ids'])) {
        $userModel = new \Models\UserModel();
        $ids = is_array($campaign->criteria['user_ids']) ? $campaign->criteria['user_ids'] : explode(',', $campaign->criteria['user_ids']);
        $users = [];
        foreach ($ids as $uid) {
            $u = $userModel->findById($uid);
            if ($u) {
                $users[] = ['id' => $u->id, 'name' => $u->adi_soyadi ?: $u->kullanici_adi];
            }
        }
        $campaign->selected_users_data = $users;
    }

    echo json_encode(['status' => 'success', 'data' => $campaign]);
    exit;
}

// 2. GÖNDERİM LOGLARI (GET)
if ($action === 'logs') {
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz kampanya ID.']);
        exit;
    }

    try {
        $logs = $campaignModel->getCampaignLogs($id);
        echo json_encode(['status' => 'success', 'logs' => $logs]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}

// 3. KAMPANYA KAYDET / DÜZENLE (POST)
if ($action === 'save') {
    $id = $_POST['id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $criteria = $_POST['criteria'] ?? [];

    // Akıllı Yeniden Gönderim (Resend) Desteği
    if ($id && empty($title) && empty($content)) {
        try {
            $existing = $campaignModel->getCampaign($id);
            if ($existing) {
                $campaignModel->updateCampaign($id, [
                    'title' => $existing->title,
                    'content' => $existing->content,
                    'criteria' => json_decode($existing->criteria, true),
                    'status' => 'draft'
                ]);
                $campaignModel->clearLogs($id);
                
                $users = $campaignModel->getFilteredUsers(json_decode($existing->criteria, true));
                if (!empty($users)) {
                    $campaignModel->createLogs($id, $users);
                }
                echo json_encode(['status' => 'success', 'message' => 'Kampanya alıcı listesi yeniden hazırlandı.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Kampanya bulunamadı.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    if (empty($title) || empty($content)) {
        echo json_encode(['status' => 'error', 'message' => 'Başlık ve içerik alanları zorunludur.']);
        exit;
    }

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
    exit;
}

// 4. KAMPANYA GÖNDER (POST)
if ($action === 'send') {
    $id = $_POST['id'] ?? 0;
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz kampanya ID.']);
        exit;
    }

    $campaign = $campaignModel->getCampaign($id);
    if (!$campaign) {
        echo json_encode(['status' => 'error', 'message' => 'Kampanya bulunamadı.']);
        exit;
    }

    if ($campaign->status === 'completed') {
        echo json_encode(['status' => 'error', 'message' => 'Bu kampanya zaten tamamlanmış.']);
        exit;
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
        exit;
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

    $stillPending = $campaignModel->getPendingLogs($id, 1);
    if (empty($stillPending)) {
        $campaignModel->finishCampaign($id);
        echo json_encode(['status' => 'success', 'message' => "Gönderim tamamlandı. Başarılı: $sentCount, Hata: $failedCount"]);
    } else {
        echo json_encode(['status' => 'success', 'message' => "$sentCount adet e-posta gönderildi. Kalanlar için lütfen tekrar gönder düğmesine basın veya bu işlemi otomatiğe bağlayın.", 'continue' => true]);
    }
    exit;
}

// 5. KAMPANYA SİL (POST)
if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz kampanya ID.']);
        exit;
    }

    if ($campaignModel->deleteCampaign($id)) {
        echo json_encode(['status' => 'success', 'message' => 'Kampanya başarıyla silindi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kampanya silinirken bir hata oluştu.']);
    }
    exit;
}
