<?php
require_once '../../vendor/autoload.php';
require_once '../../Core/Services/SgkViziteService.php';




if ($_POST["action"] == "manuel_rapor_sil") {

    $bildirimID = $_POST["bildirimId"];
    $tcKimlikNo = $_POST["tcKimlikNo"];


    //bildirim ID ve TC Kimlik No'su boş ise hata ver
    if (empty($bildirimID) || empty($tcKimlikNo)) {
        $response = ["status" => "error", "message" => "Bildirim ID veya TC Kimlik No boş olamaz."];
        echo json_encode($response);
        exit;
    }



    try {

        $sgkClient = new SgkViziteService();

        $silResponse = $sgkClient->manuelBildirimSil($bildirimID, $tcKimlikNo);

        if ($silResponse->sonucKod == '0') {
            $status = "success";
            $message = "Bildirim başarıyla silindi.";
        } else {
            $status = "error";
            $message = $silResponse->sonucAciklama ?? 'Bildirim silinirken bir hata oluştu.';
        }

        $status = "success";
        $message = "Bildirim başarıyla silindi.";

    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }


    $response = ["status" => $status, "message" => $message];
    echo json_encode($response);
    exit;
}
