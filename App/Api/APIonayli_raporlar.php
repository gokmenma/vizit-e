<?php
require_once __DIR__ . '/../../vendor/autoload.php';
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
        //$onayResponse= $sgkClient->raporOnayIptalEt($MEDULARAPORID);


        //Test kullanım için aşağıdaki satırları açın ve SGK API'sine uygun şekilde kullanın
        $onayResponse = new stdClass(); // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
        $onayResponse->sonucKod = '0'; // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
        $onayResponse->sonucAciklama = "Rapor onayı iptal edildi."; // Örnek açıklama


        if(isset($onayResponse->sonucKod) && $onayResponse->sonucKod == '0') {
            // Onay iptali başarılıysa, raporu kuyruktan da düşelim.
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