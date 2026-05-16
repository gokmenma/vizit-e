<?php

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . '/../../Core/Services/MailGonderService.php';


//Sistem tarihini İstanbul olarak ayarla
date_default_timezone_set('Europe/Istanbul');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




use App\Helper\Security;
use App\Helper\Helper;
use Models\UserModel;
use Models\KullaniciIsyeriModel;
use Models\KullaniciAyarModel;
use Core\Services\MailGonderService;
use Models\KvkkRizaModel;
use Models\KullaniciAbonelikModel;




$UserModel = new UserModel();
$IsyeriModel = new KullaniciIsyeriModel();
$KullaniciAyarModel = new KullaniciAyarModel();
$KvkkRizaModel = new KvkkRizaModel();

function validateUserInput($userInput, $fieldName)
{
    if (empty($userInput)) {
        $status = "error";
        $response = [
            "status" => $status,
            "message" => $fieldName . " alanı zorunludur."
        ];
        echo json_encode($response);
        exit;
    }
}

if ($_POST['action'] == "register") {
    $kullanici_adi = $_POST["kullanici_adi"];
    $email = $_POST["email"];



    //Kullanici adı, email ve şifre validasyonları
    if (empty($kullanici_adi)) {
        validateUserInput($kullanici_adi, "Kullanıcı adı");
    }

    if (empty($email)) {
        validateUserInput($email, "Email");
    }

    //Email format kontrolü
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status = "error";
        $response = [
            "status" => $status,
            "message" => "Geçersiz email formatı."
        ];
        echo json_encode($response);
        exit;
    }


    if (empty($_POST["sifre"])) {
        validateUserInput($_POST["sifre"], "Şifre");
    }



    $aydinlatma_metni_id = Security::decrypt($_POST["aydinlatma_metni_id"]);
    $gizlilik_sozlesmesi_id = Security::decrypt($_POST["gizlilik_sozlesmesi_id"]);
    $acik_riza_beyani_id = Security::decrypt($_POST["acik_riza_beyani_id"]);



    $aydinlatma_onay =  isset($_POST["aydinlatma_onay"]) ? ($_POST["aydinlatma_onay"] == "on" ? 1 : 0) : 0;
    $acik_riza_onay = isset($_POST["acik_riza_onay"]) ? ($_POST["acik_riza_onay"] == "on" ? 1 : 0) : 0;



    //kullanıcı adı ve email kontrolü
    $user = $UserModel->findByUserName($kullanici_adi);

    //Kullanıcı adı kontrolü
    if ($user) {
        $status = "error";
        $message = "Bu kullanıcı adı zaten alınmış.";
        $response = [
            "status" => $status,
            "message" => $message
        ];
        echo json_encode($response);
        exit;
    }

    // Email kontrolü
    $emailUser = $UserModel->findByEmail($email);
    if ($emailUser) {
        $status = "error";
        $message = "Bu email zaten alınmış.";
        $response = [
            "status" => $status,
            "message" => $message
        ];
        echo json_encode($response);
        exit;
    }


    try {

        $data = [
            "kullanici_adi" => $_POST["kullanici_adi"],
            "sifre" => password_hash($_POST["sifre"], PASSWORD_DEFAULT),
            "role" => "admin",
            "email" => $_POST["email"],
            "referral_code" => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10),
            "referred_by" => isset($_POST["referred_by"]) ? Security::decrypt($_POST["referred_by"]) : null,
        ];

        // Kullanıcıyı kaydet
        $lastInsertId = $UserModel->saveWithAttr($data);
        
        $logger = new \Core\Services\DatabaseLogger('auth');
        $logger->info("Yeni kullanıcı kayıt oldu: " . $_POST["kullanici_adi"]);

        $data = [
            "id" => 0,
            "kullanici_id" => Security::decrypt($lastInsertId),
            "kvkk_type" => "aydinlatma_metni",
            "kvkk_bilgi_id" => $aydinlatma_metni_id,
            "onay_durumu" => $aydinlatma_onay,

            "onay_tarihi" => date('Y-m-d H:i:s'),
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "user_agent" => $_SERVER['HTTP_USER_AGENT'],
        ];
        //Aydınlatma metni onayı kaydet
        $KvkkRizaModel->saveWithAttr($data);


        $data = [
            "id" => 0,
            "kullanici_id" => Security::decrypt($lastInsertId),
            "kvkk_type" => "gizlilik_sozlesmesi",
            "kvkk_bilgi_id" => $gizlilik_sozlesmesi_id,
            "onay_durumu" => 1,
            "onay_tarihi" => date('Y-m-d H:i:s'),
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "user_agent" => $_SERVER['HTTP_USER_AGENT'],
        ];
        //Gizlilik sözleşmesi onayı kaydet
        $KvkkRizaModel->saveWithAttr($data);


        $data = [
            "id" => 0,
            "kullanici_id" => Security::decrypt($lastInsertId),
            "kvkk_type" => "acik_riza_beyani",
            "kvkk_bilgi_id" => $acik_riza_beyani_id,
            "onay_durumu" => $acik_riza_onay,
            "onay_tarihi" => date('Y-m-d H:i:s'),
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "user_agent" => $_SERVER['HTTP_USER_AGENT'],
        ];
        //Açık Rıza Beyanı onayı kaydet
        $KvkkRizaModel->saveWithAttr($data);

        //Kullanıcı ayarlarını oluştur
        $data = [
            "saat_9_mail_bildirimi" => 1,
            "rapor_otomatik_onay_bildirim" => 1,


        ];
        $KullaniciAyarModel->updateSettings($lastInsertId, $data);

        //Kayıt başarılı, admin'e bildirim maili gönder        
        $mail_icerik = '<!DOCTYPE html>
                <html lang="tr">
                <head>
                <meta charset="UTF-8">
                <title>Yeni Kullanıcı Kaydı</title>
                </head>
                <body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f4;">
                <table align="center" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                    <td style="background:#007bff; color:#fff; padding:20px; border-radius:8px 8px 0 0; text-align:center;">
                        <h2 style="margin:0;">Yeni Kullanıcı Kaydı</h2>
                    </td>
                    </tr>
                    <tr>
                    <td style="padding:20px; color:#333;">
                        <p>Merhaba <strong>Admin</strong>,</p>
                        <p>Sisteme yeni bir kullanıcı kayıt oldu. Kullanıcı bilgileri aşağıdadır:</p>
                        <table cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <tr>
                            <td style="border-bottom:1px solid #ddd; width:150px;"><strong>Ad Soyad</strong></td>
                            <td style="border-bottom:1px solid #ddd;">' . $kullanici_adi . '</td>

                        </tr>
                        <tr>
                            <td style="border-bottom:1px solid #ddd;"><strong>E-posta</strong></td>
                            <td style="border-bottom:1px solid #ddd;">' . $email . '</td>

                        </tr>
                        <tr>
                            <td style="border-bottom:1px solid #ddd;"><strong>Kayıt Tarihi</strong></td>
                            <td style="border-bottom:1px solid #ddd;">' . date('d-m-Y H:i:s') . '</td>

                        </tr>
                        </table>
                        <p style="margin-top:20px;">Kullanıcıyı incelemek için yönetim paneline giriş yapabilirsiniz.</p>
                        <p style="margin-top:30px;">Saygılarımızla,<br><strong>Sistem Bildirim Botu</strong></p>
                    </td>
                    </tr>
                    <tr>
                    <td style="background:#f4f4f4; text-align:center; padding:10px; font-size:12px; color:#777; border-radius:0 0 8px 8px;">
                        Bu mesaj otomatik olarak gönderilmiştir, lütfen yanıtlamayınız.
                    </td>
                    </tr>
                </table>
                </body>
                </html>
                ';


        //admine bildirim gönder
        MailGonderService::gonder(

            "beyzade83@gmail.com",
            "Yeni Kullanıcı Kaydı",
            $mail_icerik,

        );

// mail_templates/user_welcome.html dosyası
$mail_icerik = file_get_contents (dirname(__DIR__,2) . '/user_welcome.php');

// Variables'ı replace et
$mail_icerik = str_replace(
    ['{{kullanici_adi}}'], 
    [$kullanici_adi], 
    $mail_icerik
);
        // $mail_icerik = '
        // <!DOCTYPE html>
        //         <html lang="tr">
        //         <head>
        //         <meta charset="UTF-8">
        //         <title>Hoş Geldiniz</title>
        //         <meta name="viewport" content="width=device-width, initial-scale=1.0">
        //         </head>
        //         <body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f4;">
        //         <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f4f4f4">
        //             <tr>
        //             <td align="center">
        //                 <table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="margin:20px; border-radius:10px; overflow:hidden;">
        //                 <tr>
        //                     <td align="center" bgcolor="#007bff" style="padding:20px; color:#fff; font-size:24px; font-weight:bold;">
        //                     🎉 Vizit-e.com’a Hoş Geldiniz!
        //                     </td>
        //                 </tr>
        //                 <tr>
        //                     <td style="padding:30px; color:#333; font-size:16px; line-height:1.6;">
        //                     Merhaba <b>' . $kullanici_adi . '</b>, <br><br>
        //                     Vizit-e.com ailesine katıldığınız için teşekkür ederiz. Artık sağlık raporlarını, bildirimleri ve onay süreçlerini çok daha hızlı ve zahmetsiz şekilde yönetebilirsiniz. 🚀
        //                     <br><br>
        //                     <b>Sizi bekleyen avantajlar:</b><br>
        //                     ✅ Zaman kaybı olmadan otomatik onay<br>
        //                     ✅ İnsan hatasını en aza indiren akıllı sistem<br>
        //                     ✅ SMS doğrulamasına gerek kalmadan kolay kullanım<br>
        //                     ✅ Bekleyen raporları otomatik onaylama<br>

        //                     </td>
        //                 </tr>
        //                 <tr>
        //                     <td align="center" style="padding:20px;">
        //                     <a href="https://vizit-e.com" 
        //                         style="background-color:#007bff; color:#ffffff; text-decoration:none; padding:15px 30px; border-radius:5px; font-size:16px; display:inline-block;">
        //                         Hesabınıza Giriş Yapın
        //                     </a>
        //                     </td>
        //                 </tr>
        //                 <tr>
        //                     <td style="padding:20px; font-size:14px; color:#666; text-align:center; border-top:1px solid #ddd;">
        //                     Herhangi bir sorunuz olursa <a href="mailto:bilgi@vizit-e.com" style="color:#007bff; text-decoration:none;">destek ekibimizle</a> iletişime geçebilirsiniz. <br><br>
        //                     Sevgiler,<br>
        //                     <b>Vizit-e.com Ekibi</b>
        //                     </td>
        //                 </tr>
        //                 </table>
        //             </td>
        //             </tr>
        //         </table>
        //         </body>
        //         </html>
        //         ';

        //kullanıcıya hoş geldin maili gönder
        MailGonderService::gonder(

            $email,
            "Vizit-e.com'a Hoş Geldiniz!",
            $mail_icerik,

        );

        $status = "success";
        $message = "Kayıt işlemi başarılı.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $response = [
        "status" => $status,
        "message" => $message

    ];
    echo json_encode($response);
    exit;
}

if ($_POST['action'] == "admin-kullanici-ekle") {
    $adi_soyadi = $_POST["adi_soyadi"] ?? '';
    $email = $_POST["email"] ?? '';
    $paket_id = $_POST["paket_id"] ?? '';

    // Validasyonlar
    if (empty($adi_soyadi) || empty($email)) {
        echo json_encode(["status" => "error", "message" => "Ad Soyad ve Email alanları zorunludur."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Geçersiz email formatı."]);
        exit;
    }

    // Email kontrolü
    if ($UserModel->checkEmailExists($email)) {
        echo json_encode(["status" => "error", "message" => "Bu e-posta adresi zaten başka bir abone tarafından kullanılıyor."]);
        exit;
    }

    try {
        $kullanici_adi = strtolower(str_replace(' ', '', $adi_soyadi)) . rand(10, 99);
        $sifre = '123456'; // Varsayılan şifre

        $data = [
            "adi_soyadi" => $adi_soyadi,
            "kullanici_adi" => $kullanici_adi,
            "email" => $email,
            "sifre" => password_hash($sifre, PASSWORD_DEFAULT),
            "role" => $_POST["role"] ?? "admin",
            "admin_id" => 0,
            "durum" => "Aktif",
            "referral_code" => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10),
            "kayit_tarihi" => date('Y-m-d H:i:s')
        ];

        $lastInsertId = $UserModel->saveWithAttr($data);
        
        $logger = new \Core\Services\DatabaseLogger('user-management');
        $logger->info("Yeni kullanıcı eklendi (Admin tarafından): " . $adi_soyadi);

        if (ob_get_length()) ob_clean();
        echo json_encode(["status" => "success", "message" => "Yeni kullanıcı başarıyla eklendi."]);
    } catch (Exception $e) {
        if (ob_get_length()) ob_clean();
        echo json_encode(["status" => "error", "message" => "Bir hata oluştu: " . $e->getMessage()]);
    }
    exit;
}

if ($_POST['action'] == "admin-kullanici-guncelle") {
    $id = Security::decrypt($_POST["id"]);
    $adi_soyadi = $_POST["adi_soyadi"] ?? '';
    $email = $_POST["email"] ?? '';
    $paket_id = $_POST["paket_id"] ?? '';

    if (!$id) {
        echo json_encode(["status" => "error", "message" => "Geçersiz kullanıcı ID."]);
        exit;
    }

    if (empty($adi_soyadi) || empty($email)) {
        echo json_encode(["status" => "error", "message" => "Ad Soyad ve Email alanları zorunludur."]);
        exit;
    }

    try {
        // Kullanıcı temel bilgilerini güncelle
        $UserModel->saveWithAttr([
            "id" => $id,
            "adi_soyadi" => $adi_soyadi,
            "email" => $email,
            "role" => $_POST["role"] ?? "admin"
        ]);

        // Paket aboneliğini güncelle (Varsa güncelle, yoksa ekle)
        if (!empty($paket_id)) {
            // Önce aktif aboneliklerini kapat
            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE kullanici_abonelikleri SET durum = 'iptal' WHERE kullanici_id = ?");
            $stmt->execute([$id]);

            // Yeni abonelik ekle
            $stmt = $db->prepare("INSERT INTO kullanici_abonelikleri (kullanici_id, paket_id, durum, baslangic_tarihi) VALUES (?, ?, 'aktif', ?)");
            $stmt->execute([$id, $paket_id, date('Y-m-d')]);
        }

        echo json_encode(["status" => "success", "message" => "Kullanıcı bilgileri başarıyla güncellendi."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Hata: " . $e->getMessage()]);
    }
    exit;
}
if ($_POST['action'] == "admin-kullanici-satin-al") {
    $kullanici_id = $_POST["kullanici_id"] ?? '';
    $paket_id = $_POST["paket_id"] ?? '';
    $baslangic_tarihi = $_POST["baslangic_tarihi"] ?? '';
    $bitis_tarihi = $_POST["bitis_tarihi"] ?? '';

    if (empty($kullanici_id) || empty($paket_id) || empty($baslangic_tarihi) || empty($bitis_tarihi)) {
        echo json_encode(["status" => "error", "message" => "Lütfen tüm alanları doldurun."]);
        exit;
    }

    try {
        $db = \Core\Database::getInstance()->getConnection();
        
        // Kullanıcının diğer aktif aboneliklerini kapat
        $stmt = $db->prepare("UPDATE kullanici_abonelikleri SET durum = 'iptal' WHERE kullanici_id = ? AND durum = 'aktif'");
        $stmt->execute([$kullanici_id]);

        // Yeni abonelik ekle
        $firma_hakki = $_POST["firma_hakki"] ?? 30;
        $alt_kullanici_hakki = $_POST["alt_kullanici_hakki"] ?? 3;

        $stmt = $db->prepare("INSERT INTO kullanici_abonelikleri (kullanici_id, paket_id, durum, baslangic_tarihi, bitis_tarihi, firma_hakki, alt_kullanici_hakki, olusturma_tarihi) VALUES (?, ?, 'aktif', ?, ?, ?, ?, ?)");
        $stmt->execute([$kullanici_id, $paket_id, $baslangic_tarihi, $bitis_tarihi, $firma_hakki, $alt_kullanici_hakki, date('Y-m-d H:i:s')]);

        $logger = new \Core\Services\DatabaseLogger('subscription');
        $logger->success("Kullanıcıya paket tanımlandı. Kullanıcı ID: $kullanici_id, Paket ID: $paket_id");

        echo json_encode(["status" => "success", "message" => "Satın alma işlemi başarıyla kaydedildi."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Hata: " . $e->getMessage()]);
    }
    exit;
}
if ($_POST['action'] == "profil-guncelle") {
    $kullanici_adi = $_POST["kullanici_adi"];
    $adi_soyadi = $_POST["adi_soyadi"];
    $telefon = $_POST["telefon"];
    $sifre = $_POST["mevcut_sifre"];
    $yeni_sifre = $_POST["yeni_sifre"];


    //Mevcut şifre kontrolü
    $user = $UserModel->findByUserName($kullanici_adi);

    // Kullanıcı adı kontrolü
    if (!$user) {
        $response = [
            "status" => "error",
            "message" => "Kullanıcı bulunamadı."
        ];
        echo json_encode($response);
        exit;
    }


    if (!$user || !password_verify($sifre, $user->sifre)) {
        $response = [
            "status" => "error",
            "message" => "Mevcut şifre yanlış."
        ];
        echo json_encode($response);
        exit;
    }

    try {
        $data = [
            "id" => $user->id,
            "adi_soyadi" => $adi_soyadi,
            "telefon" => $telefon,
        ];

        //Eğer kullanıcı şifresini değiştirmek istiyorsa
        if (!empty($yeni_sifre)) {
            // Yeni şifre boş değilse, yeni şifreyi de ekle
            $data["sifre"] = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        }

        // Kullanıcıyı güncelle
        $UserModel->saveWithAttr($data);

        $logger = new \Core\Services\DatabaseLogger('user-management');
        $logger->info("Profil güncellendi: " . $adi_soyadi);

        $status = "success";
        $message = "Profil güncelleme işlemi başarılı.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }


    $response = [
        "status" => $status,
        "message" => $message,

    ];

    echo json_encode($response);
}


if ($_POST['action'] == "bildirim-guncelle") {
    $saat_9_mail_bildirimi = isset($_POST["saat_9_mail_bildirimi"]) ? ($_POST["saat_9_mail_bildirimi"] == "on" ? 1 : 0) : 0;
    $rapor_otomatik_onay_bildirim = isset($_POST["rapor_otomatik_onay_bildirim"]) ? ($_POST["rapor_otomatik_onay_bildirim"] == "on" ? 1 : 0) : 0;

    try {
        $data = [
            "saat_9_mail_bildirimi" => $saat_9_mail_bildirimi,
            "rapor_otomatik_onay_bildirim" => $rapor_otomatik_onay_bildirim,


        ];
        $KullaniciAyarModel->updateSettings($_SESSION["kullanici_id"], $data);

        $status = "success";
        $message = "Bildirim ayarları güncellendi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }


    $response = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($response);
}

//Hesabımı sil
if ($_POST['action'] == "hesabimi-sil") {
    $kullanici_id = $_SESSION['kullanici_id'];

    //Mevcut şifre kontrolü
    $user = $UserModel->findById($kullanici_id);


    // Kullanıcı adı kontrolü
    if (!$user) {
        $response = [
            "status" => "error",
            "message" => "Kullanıcı bulunamadı."
        ];
        echo json_encode($response);
        exit;
    }

    $sifre = $_POST["mevcut_sifre"];
    if (!$user || !password_verify($sifre, $user->sifre)) {
        $response = [
            "status" => "error",
            "message" => "Mevcut şifre yanlış."

        ];
        echo json_encode($response);
        exit;
    }




    try {

        //Kullanıcının işyerlerini sil
        $IsyeriModel->softDeleteByUserId($kullanici_id);

        //Kullanıcının ayarlarını sil
        $KullaniciAyarModel->softDeleteByUserId($kullanici_id);

        // Kullanıcıyı sil
        $UserModel->softDeleteUser($kullanici_id);

        $logger = new \Core\Services\DatabaseLogger('auth');
        $logger->warning("Kullanıcı hesabını sildi: " . $user->kullanici_adi);

        // Oturumu sonlandır
        session_unset();
        session_destroy();

        $status = "success";
        $message = "Hesabınız başarıyla silindi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $response = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($response);
}



//Alt kullanıcı oluştur
if ($_POST['action'] == "alt-kullanici-olustur") {
    $kullanici_id = $_POST['kullanici_id'] != 0 ? Security::decrypt($_POST["kullanici_id"]) : 0;
    $kullanici_adi = $_POST["kullanici_adi"];
    $email = $_POST["email"];
    $sifre = $_POST["sifre"];
    $ust_kullanici_id = $_SESSION["kullanici_id"];

    // Debug çıktısı
    $isyerleri_ids =  isset($_POST["isyerleri_ids"]) ? 
        (is_array($_POST["isyerleri_ids"]) ? implode(",", $_POST["isyerleri_ids"]) : $_POST["isyerleri_ids"]) 
        : "";


      // Dinamik kullanıcı ekleme yetkisi kontrolü
    $AbonelikModel = new KullaniciAbonelikModel();
    $abonelik = $AbonelikModel->getSubscriptionByUserId($ust_kullanici_id);
    $limit = $abonelik ? ($abonelik->alt_kullanici_hakki ?? 3) : 3;

    $altKullaniciSayisi = $UserModel->altKullaniciSayisi($ust_kullanici_id);
    if ($altKullaniciSayisi >= $limit && $kullanici_id == 0) {
        $status = "error";
        $response = [
            "status" => $status,
            "message" => "$limit adet kullanıcı ekleme yetkiniz bulunmaktadır. Limitiniz dolmuştur."
        ];
        echo json_encode($response);
        exit;
    }

    //Kullanici adı, email ve şifre validasyonları
    if (empty($kullanici_adi)) {
        validateUserInput($kullanici_adi, "Kullanıcı adı");
    }

    if (empty($email)) {
        validateUserInput($email, "Email");
    }

    //Email format kontrolü
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status = "error";
        $response = [
            "status" => $status,
            "message" => "Geçersiz email formatı."
        ];
        echo json_encode($response);
        exit;
    }
    if (empty($sifre) && $kullanici_id == 0) {
        validateUserInput($sifre, "Şifre");
    }
    //kullanıcı adı ve email kontrolü
    $kullaniciAdiVarMi = $UserModel->findByUsername($kullanici_adi);
    if ($kullaniciAdiVarMi && $kullanici_id == 0) {
        $status = "error";
        $response = [
            "status" => $status,
            "message" => "Bu kullanıcı adı zaten mevcut."
        ];
        echo json_encode($response);
        exit;
    }
    $emailVarMi = $UserModel->checkEmailExists($email, $kullanici_id);
    if ($emailVarMi) {
        $status = "error";
        $response = [
            "status" => $status,
            "message" => "Bu email zaten mevcut."
        ];
        echo json_encode($response);
        exit;
    }

    try {
        $data = [
            "id" => $kullanici_id,
            "kullanici_adi" => $kullanici_adi,
            "adi_soyadi" => $_POST["adi_soyadi"],
            "sifre" => password_hash($sifre, PASSWORD_DEFAULT),
            "role" => "user",
            "email" => $email,
            "admin_id" => $ust_kullanici_id,
            "yetkili_oldugu_isyeri_ids" => $isyerleri_ids,
        ];
        if (empty($sifre)) {
            unset($data["sifre"]); //şifre boş ise güncelleme yapma
        }

        

        // Kullanıcıyı kaydet
        $lastInsertId = $UserModel->saveWithAttr($data);

        $logger = new \Core\Services\DatabaseLogger('user-management');
        $logger->info("Alt kullanıcı oluşturuldu: " . $kullanici_adi . " (Üst Kullanıcı ID: $ust_kullanici_id)");

        // //Kullanıcı ayarlarını oluştur
        // $data = [
        //     "saat_9_mail_bildirimi" => 1,
        //     "rapor_otomatik_onay_bildirim" => 1,


        // ];
        // $KullaniciAyarModel->updateSettings($lastInsertId, $data);

        $status = "success";
        $message = "Alt kullanıcı oluşturma işlemi başarılı.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $response = [
        "status" => $status,
        "message" => $message,

    ];
    echo json_encode($response);
}


//Alt Kullanıcı bilgilerini getir
if ($_POST['action'] == "kullanici-bilgilerini-getir") {
    $kullanici_id = Security::decrypt($_POST["kullanici_id"]);

    //Kullanıcı var mı kontrol et
    $user = $UserModel->findById($kullanici_id);
    if (!$user) {
        $response = [
            "status" => "error",
            "message" => "Kullanıcı bulunamadı."
        ];
        echo json_encode($response);
        exit;
    }

    $user->id = Security::encrypt($user->id); //id şifrele

    $response = [
        "status" => "success",
        "user" => $user
    ];
    echo json_encode($response);
}



//alt-kullanici-sil
if ($_POST['action'] == "alt-kullanici-sil") {
    $kullanici_id = Security::decrypt($_POST["kullanici_id"]);

    //Kullanıcı var mı kontrol et
    $user = $UserModel->findById($kullanici_id);
    if (!$user) {
        $response = [
            "status" => "error",
            "message" => "Kullanıcı bulunamadı."
        ];
        echo json_encode($response);
        exit;
    }


    try {
        // Kullanıcıyı sil
        $UserModel->softDeleteUser($kullanici_id);

        $logger = new \Core\Services\DatabaseLogger('user-management');
        $logger->warning("Alt kullanıcı silindi: " . $user->kullanici_adi);

        $status = "success";
        $message = "Alt kullanıcı silme işlemi başarılı.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $response = [
        "status" => $status,
        "message" => $message,

    ];
    echo json_encode($response);
}


//alt Kulanıcıyı Aktif/Pasif yap
if ($_POST['action'] == "kullanici-durum-guncelle") {
    //gelen veriyi decode yap
    
    $kullanici_id = Security::decrypt($_POST["kullanici_id"]);
    $durum = $_POST["durum"] == 1 ? "Aktif" : "Pasif";
    try {
        $data = [
            "id" => $kullanici_id,
            "durum" => $durum
        ];

        // Kullanıcıyı güncelle
        $UserModel->saveWithAttr($data);

        $logger = new \Core\Services\DatabaseLogger('user-management');
        $logger->warning("Kullanıcı durumu güncellendi: $durum (ID: $kullanici_id)");

        $status = "success";
        $message = "Kullanıcı durumu güncelleme işlemi başarılı.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $response = [
        "status" => $status,
        "message" => $message,

    ];
    echo json_encode($response);
}
