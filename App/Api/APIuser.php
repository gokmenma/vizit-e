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
    $adi_soyadi = $_POST["adi_soyadi"] ?? '';

    //Adı Soyadı, Kullanıcı adı, email ve şifre validasyonları
    if (empty($adi_soyadi)) {
        validateUserInput($adi_soyadi, "Adı Soyadı");
    }

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
            "adi_soyadi" => $adi_soyadi,
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

        // 15 Günlük Deneme Paketi ekleme mantığı
        try {
            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM abonelik_paketleri WHERE id = 7 OR ad LIKE '%Deneme%' ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $trialPackage = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($trialPackage) {
                $trialPackageId = $trialPackage->id;
                $firma_hakki = $trialPackage->firma_hakki;
                $alt_kullanici_hakki = $trialPackage->alt_kullanici_hakki;
                $sure = $trialPackage->sure;
            } else {
                $trialPackageId = 7;
                $firma_hakki = 1;
                $alt_kullanici_hakki = 0;
                $sure = 15;
            }

            $baslangic = date('Y-m-d');
            $bitis = date('Y-m-d', strtotime("+$sure days"));
            $kullanici_id = Security::decrypt($lastInsertId);

            $subStmt = $db->prepare("INSERT INTO kullanici_abonelikleri 
                (kullanici_id, paket_id, durum, baslangic_tarihi, bitis_tarihi, firma_hakki, alt_kullanici_hakki, olusturma_tarihi) 
                VALUES (?, ?, 'aktif', ?, ?, ?, ?, ?)");
            $subStmt->execute([
                $kullanici_id,
                $trialPackageId,
                $baslangic,
                $bitis,
                $firma_hakki,
                $alt_kullanici_hakki,
                date('Y-m-d H:i:s')
            ]);

            $logger->success("Kullanıcıya 15 günlük deneme paketi tanımlandı. Kullanıcı ID: $kullanici_id");
        } catch (\Exception $subEx) {
            $logger->error("Deneme paketi tanımlanırken hata oluştu: " . $subEx->getMessage());
        }

        $data = [
            "id" => 0,
            "kullanici_id" => Security::decrypt($lastInsertId),
            "kvkk_type" => "aydinlatma_metni",
            "kvkk_bilgi_id" => $aydinlatma_metni_id,
            "onay_durumu" => $aydinlatma_onay,

            "onay_tarihi" => date('Y-m-d H:i:s'),
            "ip_address" => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
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
            "ip_address" => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
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
            "ip_address" => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        ];
        //Açık Rıza Beyanı onayı kaydet
        $KvkkRizaModel->saveWithAttr($data);

        //Kullanıcı ayarlarını oluştur
        $data = [
            "saat_9_mail_bildirimi" => 1,
            "rapor_otomatik_onay_bildirim" => 1,
        ];
        $KullaniciAyarModel->updateSettings($lastInsertId, $data);

        $status = "success";
        $message = "Kayıt işlemi başarılı.";

        // Windows and Linux compatible background script execution
        try {
            $scriptPath = dirname(__DIR__, 2) . '/scratch/send_signup_emails.php';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $cmd = "start /B php " . escapeshellarg($scriptPath) . " " . escapeshellarg($email) . " " . escapeshellarg($kullanici_adi);
                pclose(popen($cmd, "r"));
            } else {
                $cmd = "php " . escapeshellarg($scriptPath) . " " . escapeshellarg($email) . " " . escapeshellarg($kullanici_adi) . " > /dev/null 2>&1 &";
                exec($cmd);
            }
        } catch (\Exception $bgEx) {
            // Log background execution failures silently
            if (isset($logger)) {
                $logger->error("Arka plan e-posta tetikleme hatası: " . $bgEx->getMessage());
            }
        }

        $response = [
            "status" => $status,
            "message" => $message
        ];
        echo json_encode($response);
        exit;
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
    $sifre = $_POST["sifre"] ?? '';

    // Validasyonlar
    if (empty($adi_soyadi) || empty($email)) {
        echo json_encode(["status" => "error", "message" => "Ad Soyad ve Email alanları zorunludur."]);
        exit;
    }

    if (empty($sifre)) {
        echo json_encode(["status" => "error", "message" => "Şifre alanı zorunludur."]);
        exit;
    }

    if (strlen($sifre) < 6) {
        echo json_encode(["status" => "error", "message" => "Şifre en az 6 karakter olmalıdır."]);
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

    $kullanici_adi = trim($_POST["kullanici_adi"] ?? '');
    if (empty($kullanici_adi)) {
        $base_username = strtolower(str_replace(' ', '', $adi_soyadi));
        // Remove non-alphanumeric characters for clean username
        $base_username = preg_replace('/[^a-zA-Z0-9]/', '', $base_username);
        if (empty($base_username)) {
            $base_username = 'user';
        }
        $kullanici_adi = $base_username . rand(10, 99);
        
        // Loop to guarantee unique generated username
        $counter = 1;
        $temp_username = $kullanici_adi;
        while ($UserModel->findByUserName($temp_username)) {
            $temp_username = $kullanici_adi . $counter;
            $counter++;
        }
        $kullanici_adi = $temp_username;
    } else {
        // If the user specified a username, check if it's already taken
        if ($UserModel->findByUserName($kullanici_adi)) {
            echo json_encode(["status" => "error", "message" => "Bu kullanıcı adı zaten alınmış."]);
            exit;
        }
    }

    try {

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
    $id = isset($_POST["id"]) ? Security::decrypt($_POST["id"]) : null;
    $adi_soyadi = $_POST["adi_soyadi"] ?? '';
    $email = $_POST["email"] ?? '';
    $paket_id = $_POST["paket_id"] ?? '';
    $sifre = $_POST["sifre"] ?? '';

    if (!$id) {
        echo json_encode(["status" => "error", "message" => "Geçersiz kullanıcı ID."]);
        exit;
    }

    try {
        // Kullanıcı temel bilgilerini güncelle
        if (!empty($adi_soyadi) && !empty($email)) {
            // Email check on update
            if ($UserModel->checkEmailExists($email, $id)) {
                echo json_encode(["status" => "error", "message" => "Bu e-posta adresi zaten başka bir abone tarafından kullanılıyor."]);
                exit;
            }

            // Username check on update
            $kullanici_adi = trim($_POST["kullanici_adi"] ?? '');
            if (empty($kullanici_adi)) {
                echo json_encode(["status" => "error", "message" => "Kullanıcı adı alanı boş bırakılamaz."]);
                exit;
            }

            $existingUser = $UserModel->findByUserName($kullanici_adi);
            if ($existingUser && $existingUser->id != $id) {
                echo json_encode(["status" => "error", "message" => "Bu kullanıcı adı zaten başka bir kullanıcı tarafından kullanılıyor."]);
                exit;
            }

            $updateData = [
                "id" => $id,
                "adi_soyadi" => $adi_soyadi,
                "kullanici_adi" => $kullanici_adi,
                "email" => $email,
                "role" => $_POST["role"] ?? "admin"
            ];

            if (!empty($sifre)) {
                if (strlen($sifre) < 6) {
                    echo json_encode(["status" => "error", "message" => "Şifre en az 6 karakter olmalıdır."]);
                    exit;
                }
                $updateData["sifre"] = password_hash($sifre, PASSWORD_DEFAULT);
            }

            $UserModel->saveWithAttr($updateData);
        }

        // Paket aboneliğini güncelle
        if (!empty($paket_id)) {
            $db = \Core\Database::getInstance()->getConnection();
            $firma_hakki = $_POST["firma_hakki"] ?? 30;
            $alt_kullanici_hakki = $_POST["alt_kullanici_hakki"] ?? 3;
            $subscription_id = isset($_POST["subscription_id"]) ? Security::decrypt($_POST["subscription_id"]) : null;
            $baslangic_tarihi = !empty($_POST["baslangic_tarihi"]) ? $_POST["baslangic_tarihi"] : null;
            $bitis_tarihi = !empty($_POST["bitis_tarihi"]) ? $_POST["bitis_tarihi"] : null;

            if ($subscription_id) {
                // Spesifik kaydı güncelle
                // Eğer tarihler gönderilmemişse mevcut değerleri koruyalım
                if (!$baslangic_tarihi || !$bitis_tarihi) {
                    $curr_stmt = $db->prepare("SELECT baslangic_tarihi, bitis_tarihi FROM kullanici_abonelikleri WHERE id = ?");
                    $curr_stmt->execute([$subscription_id]);
                    $curr_sub = $curr_stmt->fetch(PDO::FETCH_OBJ);
                    if ($curr_sub) {
                        if (!$baslangic_tarihi) $baslangic_tarihi = $curr_sub->baslangic_tarihi;
                        if (!$bitis_tarihi) $bitis_tarihi = $curr_sub->bitis_tarihi;
                    }
                }

                $stmt = $db->prepare("UPDATE kullanici_abonelikleri SET paket_id = ?, firma_hakki = ?, alt_kullanici_hakki = ?, baslangic_tarihi = ?, bitis_tarihi = ? WHERE id = ?");
                $stmt->execute([$paket_id, $firma_hakki, $alt_kullanici_hakki, $baslangic_tarihi, $bitis_tarihi, $subscription_id]);
            } else {
                // Genel güncelleme: Diğerlerini kapat, yenisini aç
                $stmt = $db->prepare("UPDATE kullanici_abonelikleri SET durum = 'iptal' WHERE kullanici_id = ?");
                $stmt->execute([$id]);

                $final_start = $baslangic_tarihi ?: date('Y-m-d');
                $sure = 30;
                if ($paket_id) {
                    $paket_stmt = $db->prepare("SELECT sure FROM abonelik_paketleri WHERE id = ?");
                    $paket_stmt->execute([$paket_id]);
                    $paket_obj = $paket_stmt->fetch(PDO::FETCH_OBJ);
                    if ($paket_obj && !empty($paket_obj->sure)) {
                        $sure = (int)$paket_obj->sure;
                    }
                }
                $final_end = $bitis_tarihi ?: date('Y-m-d', strtotime($final_start . " +$sure days"));

                $stmt = $db->prepare("INSERT INTO kullanici_abonelikleri (kullanici_id, paket_id, durum, baslangic_tarihi, bitis_tarihi, firma_hakki, alt_kullanici_hakki) VALUES (?, ?, 'aktif', ?, ?, ?, ?)");
                $stmt->execute([$id, $paket_id, $final_start, $final_end, $firma_hakki, $alt_kullanici_hakki]);
            }
        }

        echo json_encode(["status" => "success", "message" => "İşlem başarıyla güncellendi."]);
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
    $yetkiler = isset($_POST["yetkiler"]) ? 
        (is_array($_POST["yetkiler"]) ? implode(",", $_POST["yetkiler"]) : $_POST["yetkiler"]) 
        : "";

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
            "yetkiler" => $yetkiler,
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
