<?php
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();

use Models\AbonelikPaketModel;
use Models\KullaniciAbonelikModel;
use Core\Services\MailGonderService;
use Models\UserModel;



$AbonelikPaketModel = new AbonelikPaketModel();
$KullaniciAbonelikModel = new KullaniciAbonelikModel();
$userModel = new UserModel();

$user = $userModel->find($_SESSION['kullanici_id']);



if ($_POST["action"] == "odeme_yap") {
    $paket_id = $_POST["paket_id"];

    // Paket ID kontrolü
    $paket = $AbonelikPaketModel->find($paket_id);

    //Paket yoksa hata mesajı döndür
    if (!$paket) {
        $response = [
            "status" => "error",
            "message" => "Geçersiz paket ID."
        ];
        echo json_encode($response);
        exit;
    }

    try {

        //Kullanıcı var mı kontrol et
        if (!isset($_SESSION['kullanici_id'])) {
            throw new Exception("Kullanıcı oturumu bulunamadı.");
        }

        $kullanici = $userModel->find($_SESSION['kullanici_id']);

        if (!$kullanici) {
            throw new Exception("Kullanıcı bulunamadı.");
        }

        $data = [
            "kullanici_id" => $_SESSION['kullanici_id'],
            "paket_id" => $paket_id,
            "baslangic_tarihi" => date("Y-m-d"),
            "bitis_tarihi" => date("Y-m-d", strtotime("+1 month")), // 1 ay geçerli
            "firma_hakki" => $paket->firma_hakki,

        ];

        //paket id 4 ise bitiş tarihini 31.12.2025 yap ve durumunu aktif yap
        if ($paket_id == 4) {
            $data['bitis_tarihi'] = '2025-12-31';
            $data['durum'] = 1;
        }


        $lastInsertId = $KullaniciAbonelikModel->saveWithAttr($data);


        //referans kodu ile geldiyse 
        //Kullanıcının referral_used alanını converted yap
        $referred_by = $user->referred_by ?? 0;
        if ($referred_by > 0 && $user->referral_used == "pending") {
            //Kullanıcının referral_used alanını converted yap
            $userModel->saveWithAttr([
                "id" => $kullanici->id,
                "referral_used" => "converted"
            ]);

            //Referans olan kullanıcıya 1 ay firma hakkı ekle
            $ref_user = $userModel->find($referred_by);
            if ($ref_user) {
                $ref_user_abonelik = $KullaniciAbonelikModel->getSubscriptionByUserId($ref_user->id);
                if ($ref_user_abonelik) {
                    //Eğer referans kullanıcının aktif aboneliği varsa bitiş tarihine 1 ay ekle
                    $new_bitis_tarihi = date("Y-m-d", strtotime($ref_user_abonelik->bitis_tarihi . " +1 month"));
                    $KullaniciAbonelikModel->saveWithAttr([
                        "id" => $ref_user_abonelik->id,
                        "bitis_tarihi" => $new_bitis_tarihi,
                        "aciklama" =>  $ref_user_abonelik->aciklama . "\n" . $user->id . " id'li kullanıcının referans ile 1 ay uzatıldı - " . date("d-m-Y H:i:s")
                    ]);
                } else {    
                    //Eğer referans kullanıcının aktif aboneliği yoksa yeni bir abonelik oluştur
                    $KullaniciAbonelikModel->saveWithAttr([
                        "kullanici_id" => $ref_user->id,
                        "paket_id" => 4, //Ücretsiz paket
                        "baslangic_tarihi" => date("Y-m-d"),
                        "bitis_tarihi" => date("Y-m-d", strtotime("+1 month")),
                        "firma_hakki" => 1,
                        "durum" => "aktif",
                        "aciklama" => "Referans ile 1 ay hediye - " . date("d-m-Y H:i:s")

                    ]);
                }
            }
        }


        //Sessiondaki hata mesajını temizle
        if (isset($_SESSION['hata'])) {
            unset($_SESSION['hata']);
        }

        $css_content = file_get_contents('../../assets/css/email-styles.css');

        $mail_icerik = '       <!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Abonelik Bildirimi</title>
    <style>
     
        @media screen and (max-width: 650px) {
            .container {
                width: 100% !important;
                border-radius: 0 !important;
            }
            
            .content {
                padding: 20px 15px !important;
            }
            
            .header {
                padding: 25px 15px !important;
            }
            
            .detail-row {
                display: block !important;
                margin-bottom: 15px !important;
            }
            
            .detail-label {
                width: 100% !important;
                margin-bottom: 5px !important;
            }
        }
    </style>
</head> 
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, sans-serif; background-color: #f7f9fc; color: #333344; line-height: 1.6; padding: 20px 0;">
    <table class="container" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);">
        <tr>
            <td class="header" style="background: #6366F1; color: white; padding: 40px 30px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, sans-serif;">
                <div class="logo" style="font-size: 24px; font-weight: 700; margin-bottom: 15px; letter-spacing: -0.5px;">Vizit-e</div>
                <h1 style="font-size: 28px; font-weight: 600; margin: 10px 0; letter-spacing: -0.5px;">Yeni Abonelik Bildirimi</h1>
                <p style="font-size: 16px; opacity: 0.9; font-weight: 300; margin-top: 8px;">Onay bekleyen yeni bir abonelik işlemi</p>
            </td>
        </tr>
        
        <tr>
            <td class="content" style="padding: 40px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, sans-serif;">
                <div class="greeting" style="font-size: 18px; font-weight: 500; margin-bottom: 24px; color: #1F2937;">Sayın Yönetici,</div>
                
                <p class="message" style="font-size: 16px; color: #4B5563; margin-bottom: 32px; line-height: 1.7;">Sistemimize yeni bir abonelik kaydı yapıldı. Aboneliğin aktif olabilmesi için yönetici onayı gerekiyor. Abonelik detayları aşağıda yer almaktadır.</p>
                
                <table class="info-card" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background: #F9FAFB; border-radius: 12px; padding: 24px; margin: 28px 0; border-left: 4px solid #6366F1;">
                    <tr>
                        <td style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, sans-serif;">
                            <div class="info-title" style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 16px; display: flex; align-items: center;">
                                <!-- Informasyon ikonu base64 olarak eklendi -->
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDIyQzE3LjUyMjggMjIgMjIgMTcuNTIyOCAyMiAxMkMyMiA2LjQ3NzE1IDE3LjUyMjggMiAxMiAyQzYuNDc3MTUgMiAyIDYuNDc3MTUgMiAxMkMyIDE3LjUyMjggNi40NzcxNSAyMiAxMiAyMloiIHN0cm9rZT0iIzYzNjZGMSIgc3Ryb2tlLXdpZHRoPSIyIi8+CjxwYXRoIGQ9Ik0xMiAxNlYxMiIgc3Ryb2tlPSIjNjM2NkYxIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8cGF0aCBkPSJNMTIgOEgxMi4wMSIgc3Ryb2tlPSIjNjM2NkYxIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4=" alt="info" style="margin-right: 10px; width: 18px; height: 18px;">
                                Abonelik Detayları
                            </div>
                            
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr class="detail-row" style="display: block; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #EEF2FF;">
                                    <td class="detail-label" style="font-weight: 500; color: #6B7280; font-size: 14px; width: 140px; display: inline-block;">Kullanıcı Adı:</td>
                                    <td class="detail-value" style="font-weight: 500; color: #1F2937; font-size: 14px; display: inline-block;">' . $kullanici->adi_soyadi . '</td>
                                </tr>
                                
                                <tr class="detail-row" style="display: block; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #EEF2FF;">
                                    <td class="detail-label" style="font-weight: 500; color: #6B7280; font-size: 14px; width: 140px; display: inline-block;">E-posta:</td>
                                    <td class="detail-value" style="font-weight: 500; color: #1F2937; font-size: 14px; display: inline-block;">' . $kullanici->email . '</td>
                                </tr>
                                
                                <tr class="detail-row" style="display: block; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #EEF2FF;">
                                    <td class="detail-label" style="font-weight: 500; color: #6B7280; font-size: 14px; width: 140px; display: inline-block;">Paket:</td>
                                    <td class="detail-value" style="font-weight: 500; color: #1F2937; font-size: 14px; display: inline-block;">' . $paket->ad . '</td>
                                </tr>
                                
                                <tr class="detail-row" style="display: block; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #EEF2FF;">
                                    <td class="detail-label" style="font-weight: 500; color: #6B7280; font-size: 14px; width: 140px; display: inline-block;">Fiyat:</td>
                                    <td class="detail-value" style="font-weight: 500; color: #1F2937; font-size: 14px; display: inline-block;">' . $paket->fiyat . '</td>
                                </tr>
                                
                                <tr class="detail-row" style="display: block; margin-bottom: 0; padding-bottom: 0; border-bottom: none;">
                                    <td class="detail-label" style="font-weight: 500; color: #6B7280; font-size: 14px; width: 140px; display: inline-block;">Satın Alma Tarihi:</td>
                                    <td class="detail-value" style="font-weight: 500; color: #1F2937; font-size: 14px; display: inline-block;">' . date("d-m-Y") . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <div class="cta-container" style="text-align: center; margin: 36px 0 28px;">
                    <a href="#" class="cta-button" style="display: inline-block; background: #6366F1; color: white; text-decoration: none; padding: 16px 32px; border-radius: 12px; font-weight: 600; font-size: 16px; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2);">Aboneliği İncele</a>
                </div>
                
                <div class="divider" style="height: 1px; background: #E5E7EB; margin: 30px 0;"></div>
                
                <p class="message" style="font-size: 16px; color: #4B5563; margin-bottom: 32px; line-height: 1.7;">Bu işlemi en kısa sürede tamamlamanız, kullanıcı deneyimi açısından önem taşımaktadır. Aboneliği onaylamak veya reddetmek için yukarıdaki butona tıklayarak yönetim panelinize gidebilirsiniz.</p>
                
                <p class="message" style="font-size: 16px; color: #4B5563; margin-bottom: 32px; line-height: 1.7;">Saygılarımızla,<br><strong>Vizit-e Ekibi</strong></p>
            </td>
        </tr>
        
        <tr>
            <td class="footer" style="text-align: center; padding: 30px; background: #F9FAFB; color: #6B7280; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, sans-serif;">
                <p>© "' . date("Y") . '" Vizit-e. Tüm hakları saklıdır.</p>

                
                <div class="footer-links" style="margin-top: 10px;">
                    <a href="#" style="color: #6366F1; text-decoration: none; margin: 0 10px; font-size: 13px;">Yardım Merkezi</a>
                    <a href="#" style="color: #6366F1; text-decoration: none; margin: 0 10px; font-size: 13px;">Gizlilik Politikası</a>
                    <a href="#" style="color: #6366F1; text-decoration: none; margin: 0 10px; font-size: 13px;">Şartlar ve Koşullar</a>
                </div>
                
                <p class="footer-notice" style="margin-top: 20px; font-size: 12px; color: #9CA3AF;">Bu e-posta otomatik olarak gönderilmiştir. Lütfen cevaplamayınız.</p>
            </td>
        </tr>
    </table>
</body>
</html>';

        if ($paket_id != 4) {

            MailGonderService::gonder(
                "beyzade83@gmail.com",
                "Abonelik Başarılı",
                $mail_icerik
            );
        }

        ob_clean();


        $status = "success";
        $message = "Abonelik başarıyla oluşturuldu.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }


    $response = [
        "status" => $status,
        "message" => $message,
        "lastInsertId" => $lastInsertId ?? null
    ];

    echo json_encode($response);
    exit;
}
