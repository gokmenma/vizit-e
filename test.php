<?php

require_once __DIR__ . '/vendor/autoload.php';  
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';


use Core\Services\MailGonderService;
use Dotenv\Dotenv;
use App\Helper\Date;

// .env dosyasını yükle
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$sgkClient = new SgkViziteService(

    "3245040108","2","87174585"
);

$loginResponse = $sgkClient->wsLogin(

);

$mail_icerik = "<h2>SGK Vizit-e Rapor Sistemi Test E-Postası</h2>";

$gonder = MailGonderService::gonder(
    "mehmetaligokmen@duzce.edu.tr",
    "Sgk Vizit-e Rapor Sistemi",
    $mail_icerik

);

if($gonder){
    echo "E-posta gönderildi.";
} else {
    echo "E-posta gönderilemedi.";
}

?>