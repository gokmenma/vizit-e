<?php

require_once '../../Core/Services/SgkViziteService.php';
require_once __DIR__ . "/../../vendor/autoload.php";

use App\Helper\Security;

$sgkClient = new SgkViziteService();


if ($_POST["action"] == "mahsuplastiOnayla") {


    $raporData = json_decode($_POST['raporData'], true);

    if (empty($raporData)) {

        throw new Exception("Mahsuplaştırma için rapor verisi gönderilmedi.");
    }

    $onayResponse = $sgkClient->mahsuplasmayiOnayla($raporData);

    if (isset($onayResponse->sonucKod) && $onayResponse->sonucKod == '0') {
        $status = "success";
        $message = "Rapor başarıyla mahsuplaştırıldı.";
    } else {
        $status = "error";
        $message = $onayResponse->sonucAciklama ?? 'Mahsuplaştırma işlemi başarısız.';
    
    }


    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $raporData
    ];
    echo json_encode($response);
}

if ($_POST["action"] == "mahsuplastiOnaylaToplu") {
    $raporlar = json_decode($_POST['raporData'], true);

    if (empty($raporlar)) {
        echo json_encode(['status' => 'error', 'message' => 'Mahsuplaştırma için rapor verisi gönderilmedi.']);
        exit;
    }

    $successCount = 0;
    $errors = [];

    foreach ($raporlar as $raporData) {
        try {
            $onayResponse = $sgkClient->mahsuplasmayiOnayla($raporData);
            if (isset($onayResponse->sonucKod) && $onayResponse->sonucKod == '0') {
                $successCount++;
            } else {
                $errors[] = $raporData['adiSoyadi'] . ": " . ($onayResponse->sonucAciklama ?? 'İşlem başarısız.');
            }
        } catch (Exception $e) {
            $errors[] = $raporData['adiSoyadi'] . ": " . $e->getMessage();
        }
    }

    if ($successCount > 0 && empty($errors)) {
        echo json_encode(['status' => 'success', 'message' => "$successCount adet rapor başarıyla mahsuplaştırıldı."]);
    } elseif ($successCount > 0 && !empty($errors)) {
        echo json_encode([
            'status' => 'success', 
            'message' => "$successCount adet rapor başarıyla mahsuplaştırıldı, ancak bazı hatalar oluştu:\n" . implode("\n", $errors)
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Hiçbir rapor mahsuplaştırılamadı.\n" . implode("\n", $errors)]);
    }
}
