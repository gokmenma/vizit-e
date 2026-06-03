<?php


require __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';
session_start();


use Models\KullaniciIsyeriModel;
use App\Helper\Security;
use Random\Engine\Secure;

$IsyeriModel = new KullaniciIsyeriModel();


//İşyeri Kaydet
if ($_POST["action"] == "isyeri_kaydet") {

    $id = Security::decrypt($_POST["isyeri_id"]) ?? null;

    try {


        $kullanici_kodu = Security::escape($_POST["kullanici_adi"]);
        $isyeri_kodu = Security::escape($_POST["isyeri_kodu"]);
        $wsSifre = $_POST["ws_sifre"];

        if ($id  == 0) {
            $sgkClient = new SgkViziteService($kullanici_kodu, $isyeri_kodu, $wsSifre);
            //Bilgilerin doğruluğunu SGK'ya sor
            $dogrulama = $sgkClient->bilgileriDogrula($kullanici_kodu, $isyeri_kodu, $wsSifre);

            if (!isset($dogrulama->sonucKod) || $dogrulama->sonucKod != '0') {
                // sonucKod 105 genellikle "hatalı bilgi" anlamına gelir.
                $res = [
                    "status" => "error",
                    "message" => 'SGK bilgileri doğrulanamadı. Lütfen kontrol edip tekrar deneyin.',
                ];
                echo json_encode($res);
                return; // İşlemi durdur
            }
        };


        $varsayilan_mi = isset($_POST["varsayilan_mi"]) ? 1 : 0;

        if ($varsayilan_mi == 1) {
            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE kullanici_isyerleri SET varsayilan_mi = 0 WHERE kullanici_id = ?");
            $stmt->execute([$_SESSION["kullanici_id"]]);
        }

        $data = [
            "id" => $id, // Eğer id varsa decrypt et, yoksa null
            "kullanici_id"          => $_SESSION["kullanici_id"],
            "firma_adi"             => htmlspecialchars($_POST["firma_adi"]),
            "kullanici_kodu"        => Security::escape($_POST["kullanici_adi"]),
            "isyeri_kodu"           => Security::escape($_POST["isyeri_kodu"]),
            "otomatik_rapor_onay"   => isset($_POST["otomatik_rapor_onay"]) ? 1 : 0,
            "otomatik_onay_eposta"  => Security::escape($_POST["otomatik_onay_eposta"]),
            "varsayilan_mi"         => $varsayilan_mi,
            "aktif_mi"              => 1,

        ];
        if (isset($_POST["ws_sifre"]) && !empty($_POST["ws_sifre"])) {
            $data["isyeri_sifre"] = Security::encrypt($_POST["ws_sifre"]);
        }

        $lastInsertId = $IsyeriModel->saveWithAttr($data);

        $logger = new \Core\Services\DatabaseLogger('workplace-management');
        $logger->info(($id == 0 ? "Yeni işyeri eklendi: " : "İşyeri güncellendi: ") . $_POST["firma_adi"]);

        $status = "success";
        $message = "İşyeri başarıyla kaydedildi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $response = [
        "status" => $status,
        "message" => $message,
        "lastInsertId" => $lastInsertId ?? 0

    ];

    echo json_encode($response);
}

//İşyerini silme işlemi
if ($_POST["action"] == "isyeri_sil") {
    try {
        $isyeriId = $_POST["isyeri_id"];

        //Session'dan isyeri ID'sini al
        $session_isyeriId = $_SESSION['isyeri_id'] ?? null;

        //Session'daki isyeri ID'si ile post'daki isyeri ID'si aynı mı kontrol et
        if ($session_isyeriId == Security::decrypt($isyeriId)) {

            //Eğer aynı ise session'daki isyeri bilgilerini temizle
            unset($_SESSION['isyeri_id']);
            unset($_SESSION['firma_adi']);
            unset($_SESSION['kullaniciAdi']);
            unset($_SESSION['isyeriKodu']);
            unset($_SESSION['wsSifre']);
        }


        $IsyeriModel->delete($isyeriId);
        
        $logger = new \Core\Services\DatabaseLogger('workplace-management');
        $logger->warning("İşyeri silindi. ID: $isyeriId");

        $status = "success";
        $message = "İşyeri başarıyla silindi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $response = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($response);
}


//İşyeri bilgilerini getir
if ($_POST["action"] == "isyeri_getir") {
    try {
        $isyeriId = Security::decrypt($_POST["isyeri_id"]);

        $isyeri = $IsyeriModel->find($isyeriId);
        $status = "success";
        $message = "İşyeri bilgileri başarıyla getirildi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $response = [
        "status" => $status,
        "message" => $message,
        "isyeri" => $isyeri ?? null
    ];
    echo json_encode($response);
}


//Excelden isyerlerini yukle
if ($_POST["action"] == "excel_upload_isyeri") {
    try {
        $file = $_FILES['excelFile'];
        $filePath = $file['tmp_name'];
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);

        if ($fileType != 'xlsx' && $fileType != 'xls') {
            throw new Exception("Lütfen sadece Excel dosyası yükleyin.");
        }

        $excelData = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $excelData->getActiveSheet();
        $rows = $worksheet->toArray();


        //Sıra	İşyeri Adı(Kolay hatırlamak için)	SGK Kullanıcı Adı	SGK İşyeri Kodu	SGK İşyeri Şifresi	Otomatik Rapor Onayı(E/H)	Otomatik Rapor Onayı Gönderilecek Mail Adresleri

        // Sütunları kontrol et
        $expectedColumns = ["Sıra", "İşyeri Adı(Kolay hatırlamak için)", "SGK Kullanıcı Adı", "SGK İşyeri Kodu", "SGK İşyeri Şifresi", "Otomatik Rapor Onayı(E/H)", "Otomatik Rapor Onayı Gönderilecek Mail Adresleri"];
        if ($rows[0] != $expectedColumns) {
            throw new Exception("Excel dosyasının sütunları doğru değil. Lütfen şablonu kullanarak dosyayı oluşturun.");
        }
        // Başlık satırını kaldır
        array_shift($rows);

        //Veri yoksa hata ver
        if (count($rows) == 0) {
            $response = [
                "status" => "error",
                "message" => "Excel dosyasında yüklenecek veri bulunamadı"
            ];
            echo json_encode($response);
            return;
        }


        //Hataları kontrol et ve bir diziye ekle
        $errors = [];
        $basaliKayitSayisi = 0;

        $kalan_firma_hakki = $IsyeriModel->kalanFirmaHakki($_SESSION["kullanici_id"]);

        if ($kalan_firma_hakki < 1) {
            $errors[] = "Kalan firma hakkınız ' . $kalan_firma_hakki . ' işyeri yükleme işlemi durduruldu. Lütfen firma hakkınızı artırın.";
            #session'a hata mesajını ekle
            $_SESSION['hata'] = "Kalan firma hakkınız ' . $kalan_firma_hakki . ' işyeri yükleme işlemi durduruldu. Lütfen firma hakkınızı artırın.";
            $response = [
                "status" => "error",
                "message" => implode("<br>", $errors)
            ];
            echo json_encode($response);
            return;
        }

        foreach ($rows as $row) {

            //Kalan firma hakkını kontrol et
            //Eğer kalan firma hakkı 1 veya 1'den az ise döngüyü kır
            if ($kalan_firma_hakki <= 0) {
                $errors[] = "Kalan firma hakkınız yetersiz. Lütfen firma hakkınızı artırın.";
                #session'a hata mesajını ekle
                $_SESSION['hata'] = $row[0] . "itibaren kalan firma hakkınız dolduğunda yükleme işlemi durduruldu. 
                ";
                break; // Döngüyü kır
            }


            if (empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
                $errors[] = "İşyeri Adı, SGK Kullanıcı Adı, SGK İşyeri Kodu ve SGK İşyeri Şifresi alanları boş bırakılamaz. Hata satırı: " . $row[0];
                continue; // Bu satırı atla ve sonraki satıra geç
            }

            //İşyeri bilgilerini SGK'ya sor
            $sgkClient = new SgkViziteService($row[2], $row[3], $row[4]);
            $dogrulama = $sgkClient->bilgileriDogrula($row[2], $row[3], $row[4]);
            //Eğer hata varsa hata mesajını ekle
            if ($dogrulama->sonucKod == 105) {
                $errors[] = "Firma kaydedilemedi!. Kullanıcı Adı, Kullanıcı Kodu veya Şifre hatalı." . " Hata satırı: " . $row[0];
                continue; // Bu satırı atla ve sonraki satıra geç
            }

            //Tc Kimlik numarası 11 hane mi kontrol et
            if (strlen($row[2]) != 11 || !ctype_digit($row[2])) {
                $errors[] = "SGK İşyeri Kodu 11 haneli bir sayı olmalıdır. Hata satırı: " . $row[0];
                continue; // Bu satırı atla ve sonraki satıra geç
            }


            $data = [
                "kullanici_id" => $_SESSION["kullanici_id"],
                "firma_adi" => htmlspecialchars($row[1]),
                "kullanici_kodu" => Security::escape($row[2]),
                "isyeri_kodu" => Security::escape($row[3]),
                "isyeri_sifre" => Security::encrypt($row[4]),
                "otomatik_rapor_onay" => $row[5] === "E" ? 1 : 0,
                "otomatik_onay_eposta" => Security::escape($row[6]),
                "aktif_mi" => 1,
            ];


            $lastInsertId = $IsyeriModel->saveWithAttr($data);
            $basaliKayitSayisi++;
            $kalan_firma_hakki--;
        }

        if ($basaliKayitSayisi > 0) {
            $logger = new \Core\Services\DatabaseLogger('workplace-management');
            $logger->info("Excel ile toplu işyeri yüklendi. Sayı: $basaliKayitSayisi");
        }

        //Hataları kontrol et
        if (count($errors) > 0) {
            $_SESSION['hata'] = implode("<br>", $errors);
        }
        if ($basaliKayitSayisi > 0) {
            $status = "success";
            $message = "$basaliKayitSayisi işyeri başarıyla yüklendi.Kalan firma hakkı : " . $kalan_firma_hakki;
        } else {
            $status = "error";
            $message = "İşyeri yüklenemedi. Lütfen hataları kontrol edin.";
        }
    } catch (Exception $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $response = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($response);
}
