<?php


require __DIR__ . "/../../vendor/autoload.php";
require_once '../../Core/Services/SgkViziteService.php'; // Yolun doğru olduğundan emin olun

session_start();

if ($_POST["action"] == "iletisimGuncelle") {

        $sgkClient = new SgkViziteService();

    try {
        $eposta = $_POST['eposta'];
        $cepTel = $_POST['cepTel'];

        $sgkClient->iletisimBilgileriniGuncelle($eposta, $cepTel);

        $status = "success";
        $message = "İletişim bilgileri başarıyla güncellendi.";

    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();

    }

    // JSON yanıtı oluştur ve gönder
    $response = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($response);
}
