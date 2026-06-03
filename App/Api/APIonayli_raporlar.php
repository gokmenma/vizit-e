<?php
require_once __DIR__ . "/../../vendor/autoload.php";
require_once '../../Core/Services/SgkViziteService.php'; // Yolun doğru olduğundan emin olun



use App\Helper\Security;

//Security::checkLogin();

header('Content-Type: application/json');


if($_POST["action"] == "rapor_onay_iptal"){

    $MEDULARAPORID = Security::decrypt($_POST["MEDULARAPORID"]);

    //rapor id boş ise hata mesajı döndür
    if(empty($MEDULARAPORID)){
        $response = [
            "status" => "error",
            "message" => "Rapor ID boş olamaz."
        ];
        echo json_encode($response);
        exit;
    }

    try {
        $sgkClient = new SgkViziteService();
        $onayResponse = $sgkClient->raporOnayIptalEt($MEDULARAPORID);

        if(isset($onayResponse->sonucKod) && $onayResponse->sonucKod == '0') {
            // Onay iptali başarılıysa, yerel veritabanını güncelleyelim.
            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE onaylanan_raporlar SET onay_durumu = 'iptal', updated_at = NOW() WHERE MEDULARAPORID = ?");
            $stmt->execute([$MEDULARAPORID]);

            $status = "success";
            $message = "Rapor onayı başarıyla iptal edildi.";
        } else {
            $status = "error";
            $message = $onayResponse->sonucAciklama ?? 'Rapor onayı iptal edilirken bir hata oluştu.';
        }

    } catch (Exception $e) {
        // Hata durumunda JSON yanıtı döndür
        $response = [
            "status" => "error",
            "message" => $e->getMessage()
        ];
        echo json_encode($response);
        exit;
    }

    $response = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($response);

}