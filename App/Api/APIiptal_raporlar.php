<?php
header('Content-Type: application/json');
require_once '../../vendor/autoload.php';
require_once '../../Core/Services/SgkViziteService.php';

use App\Helper\Security;
use Models\RaporModel;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Güvenlik kontrolleri
Security::checkLogin();
Security::checkFirma();

$response = [
    'status' => 'error',
    'message' => 'Geçersiz işlem.'
];

try {
    $action = $_REQUEST['action'] ?? '';
    if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $inputData = json_decode(file_get_contents('php://input'), true);
        $action = $inputData['action'] ?? '';
    }

    $db = \Core\Database::getInstance()->getConnection();

    if ($action === 'get_iptal_raporlar') {
        $isyeri_id = $_SESSION['isyeri_id'] ?? 0;
        
        $stmt = $db->prepare("SELECT * FROM onaylanan_raporlar WHERE isyeri_id = ? AND onay_durumu = 'iptal' ORDER BY updated_at DESC");
        $stmt->execute([$isyeri_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Encrypt the IDs for security in HTML attributes
        foreach ($records as &$record) {
            $record['enc_id'] = Security::encrypt($record['id']);
        }

        echo json_encode([
            'status' => 'success',
            'data' => $records
        ]);
        exit;
    }

    if ($action === 'rapor_tekrar_onayla') {
        $id = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $inputData = json_decode(file_get_contents('php://input'), true);
            $enc_id = $inputData['id'] ?? '';
            $nitelikDurumu = $inputData['nitelikDurumu'] ?? '0'; // Default 0: Çalışmamıştır
        } else {
            $enc_id = $_REQUEST['id'] ?? '';
            $nitelikDurumu = $_REQUEST['nitelikDurumu'] ?? '0';
        }

        if (!empty($enc_id)) {
            $id = Security::decrypt($enc_id);
        }

        if (empty($id)) {
            throw new Exception('Geçersiz veya eksik Rapor ID.');
        }

        // Rapor bilgisini veritabanından çekelim
        $stmt = $db->prepare("SELECT * FROM onaylanan_raporlar WHERE id = ? AND onay_durumu = 'iptal' LIMIT 1");
        $stmt->execute([$id]);
        $rapor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rapor) {
            throw new Exception('Onay kaydı bulunamadı veya bu rapor zaten onaylanmış.');
        }

        // SGK Web Servisini başlatalım
        $sgkClient = new SgkViziteService();

        // Rapor Bitiş Tarihini (Ayaktan bitiş tarihi) belirle
        $raporBitisTarihiStr = $rapor['ABITTAR'] ?? $rapor['ISBASKONTTAR'] ?? null;
        if (empty($raporBitisTarihiStr)) {
            throw new Exception('Rapor bitiş tarihi bulunamadı.');
        }
        $raporBitisTarihi = new DateTime($raporBitisTarihiStr);

        // SGK Rapor Onaylama Servisini Çağır
        $onayResponse = $sgkClient->raporuOnayla(
            $rapor['MEDULARAPORID'],
            $rapor['TCKIMLIKNO'],
            $rapor['VAKA'],
            $nitelikDurumu,
            $raporBitisTarihi
        );

        if (isset($onayResponse->sonucKod) && $onayResponse->sonucKod == '0') {
            // Başarılıysa SGK kuyruğundan düş
            $sgkClient->raporuKapat($rapor['MEDULARAPORID']);

            // Veritabanı kaydını başarı statüsüne geri getir
            $sgk_bildirim_id = $onayResponse->bildirimId ?? null;
            $updateStmt = $db->prepare("UPDATE onaylanan_raporlar SET onay_durumu = 'basarili', sgk_bildirim_id = ?, onay_tarihi = NOW(), updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$sgk_bildirim_id, $id]);

            $response = [
                'status' => 'success',
                'message' => 'Rapor başarıyla tekrar onaylandı ve işleme alındı.'
            ];
        } else {
            $hataMesaji = $onayResponse->sonucAciklama ?? 'SGK onaylama servisi hata döndürdü.';
            throw new Exception($hataMesaji);
        }
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
exit;
