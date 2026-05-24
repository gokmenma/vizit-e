<?php 

use App\Helper\Security;


Security::checkLogin();


require_once __DIR__ . "/../../vendor/autoload.php";


use Models\KullaniciIsyeriModel;

$IsyeriModel = new KullaniciIsyeriModel();

// $page = $_POST["previous_page"] ?? $_ENV['BASE_PATH'];
// $parca = basename(parse_url($page, PHP_URL_PATH));


$page =$_ENV['BASE_PATH'];
$title = 'İş Yeri Seç';

$id =isset($_GET['isyeri_id']) ? Security::decrypt($_GET['isyeri_id']) : $_POST['isyeri_id'] ?? null;


$isyeri = $IsyeriModel->find($id);

$_SESSION['isyeri_id'] = $isyeri->id; // İşyeri ID'si
$_SESSION['firma_adi'] = $isyeri->firma_adi;
$_SESSION['kullaniciAdi'] = $isyeri->kullanici_kodu; // İşyeri kodu 1 için
$_SESSION['isyeriKodu'] = $isyeri->isyeri_kodu; // İşyeri kodu 1 için
$_SESSION['wsSifre'] = $isyeri->isyeri_sifre;


//Hata mesajlarını temizle
unset($_SESSION['hata']);
unset($_SESSION['wsToken']);
unset($_SESSION['tokenExpiresAt']);

// AJAX Kontrolü (Sayfa yenilemeden geçiş için JSON döner)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'İşyeri başarıyla değiştirildi.',
        'firma_adi' => $isyeri->firma_adi
    ]);
    exit();
}

header('Location: ' . $page);

exit();
?>