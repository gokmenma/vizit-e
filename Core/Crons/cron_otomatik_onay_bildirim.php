<?php
// Hata loglama ve zaman dilimi ayarları
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cron_error.log');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Istanbul');

session_start();

// Gerekli tüm sınıfları yükle
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';



/* BUGÜN ONAYLANMASI GEREKEN RAPORLARI KULLANICI BAŞINA TEK E-POSTA OLARAK GÖNDERİR
 * Kullanıcıya ait isyerlerinin tüm raporlarını sorgular
 * Rapor bitiş tarihi bugünden önce olan ve rapor başlangıç tarihi ile rapor bitiş tarihi arasında 
 * 3 günden fazla olan raporları filtreler
 * Her kullanıcı için tek bir e-posta gönderir
 * Eğer saat 09:00 ise ve Kullanıcının saat_9_mail_bildirimi = 0 ise cron'u çalıştırmaz
 */


use Core\Services\FileLogger; 

use Core\Services\MailGonderService;
use Models\UserModel;
use Models\IsyeriModel;
use App\Helper\Security;
use Models\KullaniciIsyeriModel;
use Models\KullaniciAyarModel;
use Core\Services\CronReporter;
use Models\RaporModel;


$logger = new FileLogger(__DIR__ . '/../../logs/crons', 'otomatik_onay_bildirim');
// Kullanıcı ayar modelini başlat
$KullaniciAyarModel = new KullaniciAyarModel();



$reporter = new CronReporter();



// --- GÜN VE SAAT KONTROLÜ ---
$gunNo = date('N'); // 1 (Pazartesi) - 7 (Pazar)
$saat = date('H'); // Saat (00-23)

// Sadece hafta içi ve 08,09 saatlerde çalış
if ($gunNo > 5 && in_array($saat, [8, 9])) { // Örnek saatler
    $reporter->log("Çalışma günü dışında. Cron işlemi atlanıyor. Tarih: " . date('Y-m-d H:i:s'));
    exit("Çalışma günü veya saati dışında. Cron işlemi atlanıyor. Tarih: " . date('Y-m-d H:i:s'));
}

$reporter->log("Cron Job Başlatıldı (Onaylanacak raporlar bildirimi): " . date('Y-m-d H:i:s') . "\n");

try {
    // Veritabanı modellerini başlat
    $userModel = new UserModel();
    $isyeriModel = new KullaniciIsyeriModel();
    $raporModel = new RaporModel();

    // 1. Sistemdeki tüm aktif kullanıcıları al
    $kullanicilar = $userModel->all();
    if (empty($kullanicilar)) {
        echo "Sistemde aktif kullanıcı bulunamadı. Cron sonlandırılıyor.\n";
        exit;
    }

    $reporter->log(count($kullanicilar) . " adet kullanıcı için işlem başlatılıyor...\n");
    $reporter->log("--------------------------------------------------\n\n");

    // 2. Her bir kullanıcı için işlemleri tekrarla
    foreach ($kullanicilar as $kullanici) {
        $kullaniciId = $kullanici->id;
        $kullaniciEmail =  $kullanici->email;
        $isyeriYetkiliId = $kullanici->admin_id;

    
        // Eğer saat 09:00 ise  ve Kullanıcının saat_9_mail_bildirimi = 0 ise cron'u çalıştırma
        $saat = date('H:i');
        if ($saat == '09:00') {
            $saat_9_mail_bildirimi  = $KullaniciAyarModel->getSetting("saat_9_mail_bildirimi", $kullaniciId);

            if ($saat_9_mail_bildirimi == 0) {

                $reporter->log("Saat 09:00 bildirimi kapalı olduğundan mail gönderilmedi : saat" . $saat . "\n");
                exit;
            }
        }

        $reporter->log("İşlenen Kullanıcı: {$kullaniciEmail} (ID: {$kullaniciId})");

        //Eğer kullanıcı alt kullanıcı ise yetkilisinin id bilgisi ile sorgulama yap
        // if($isyeriYetkiliId != 0){
        //     // 3. Kullanıcıya ait tüm işyerlerini al
        //     $isyerleri = $isyeriModel->findByUserId($isyeriYetkiliId);
        // }else{
            $isyerleri = $isyeriModel->findByUserId($kullaniciId);
        // }
       
        //Eğer kullanıcı alt kullanıcı ise atla
        if($isyeriYetkiliId != 0){
            $reporter->log(" - Alt kullanıcı olduğundan atlanıyor.\n\n");
            continue; // Döngünün bir sonraki adımına geç
        }


        if (empty($isyerleri)) {
            $reporter->log(" - Bu kullanıcıya ait işyeri bulunamadı. Sonraki kullanıcıya geçiliyor.\n\n");
            continue; // Döngünün bir sonraki adımına geç
        }

        // 4. Bu kullanıcıya ait TÜM raporları toplayacağımız ana dizi
        $kullaniciRaporlariToplami = [];

        // 5. Kullanıcının her bir işyeri için raporları sorgula
        foreach ($isyerleri as $isyeri) {
            // Modelinizin nesne mi yoksa dizi mi döndürdüğüne bağlı olarak doğru erişimi sağlayın
            $isyeriAdi =  $isyeri->firma_adi;
            $kullaniciKodu = $isyeri->kullanici_kodu;
            $isyeriKodu = $isyeri->isyeri_kodu;
            $sifreliWsSifre = $isyeri->isyeri_sifre;
            $wsSifre = Security::decrypt($sifreliWsSifre);
            $otomatik_onay_aktif_mi = $isyeri->otomatik_rapor_onay;

           
            

            $reporter->log(" - İşyeri sorgulanıyor: {$isyeriAdi}\n");
            $logger->info(" - İşyeri sorgulanıyor: {$isyeriAdi}");

            try {
                // Her işyeri için yeni bir servis nesnesi oluştur
                $sgkClient = new SgkViziteService($kullaniciKodu, $isyeriKodu, $wsSifre);


                //Kullanıcı kodu işyeri ve şifre ve kullanıcı email adresini logla
                // $logger->info("Kullanıcı Kodu: {$kullaniciKodu}, 
                //                İşyeri Kodu: {$isyeriKodu}, 
                //                Şifre: {$wsSifre}, 
                //                Kullanıcı Email: {$kullaniciEmail}");

                $raporlar = $sgkClient->raporlariGetir(new DateTime('tomorrow'));

                // Gerekli raporları (3+ gün) filtrele ve ana toplama dizisine ekle
                foreach ($raporlar as $rapor) {
                    // Eğer ARSIV durumu = 1 ise bu raporu atla
                    if ($rapor['ARSIV'] == 1) {
                        continue;
                    }

                    // Eğer rapor durumu "ONAYLI" veya "ONAYLANDI" içeriyorsa bu raporu atla (SGK'dan gelen veri)
                    if ((isset($rapor['RAPORDURUMADI']) && stripos($rapor['RAPORDURUMADI'], 'ONAY') !== false) ||
                        (isset($rapor['ONAYLI']) && ($rapor['ONAYLI'] == '1' || $rapor['ONAYLI'] == 'E')) ||
                        (isset($rapor['ONAYDURUMU']) && ($rapor['ONAYDURUMU'] == '1' || $rapor['ONAYDURUMU'] == 'E'))) {
                        continue;
                    }

                    // Takip numarasi birden fazla rapor sirasinda ortak olabilir; tekil
                    // Medula rapor kimligi daha once kaydedilmisse bu satiri atla.
                    if ($raporModel->findReportByMedulaRaporId($rapor['MEDULARAPORID'] ?? null)) {
                        continue;
                    }

                    // Tarih kontrolü
                    $baslangic = new DateTime($rapor['POLIKLINIKTAR']);
                    $iseBasi = new DateTime($rapor['ISBASKONTTAR']);


                    if($rapor['ISBASKONTTAR'] == "0001-01-01") {
                        // İşe başlama tarihi boş veya geçersizse raporu atla
                        continue;
                    }

                    if ($baslangic->diff($iseBasi)->days >= 3 
                            && $iseBasi < new DateTime('tomorrow')) {
                        $rapor['ISYERI_ADI'] = $isyeriAdi; // Hangi işyerine ait olduğunu ekle
                        $rapor['OTOMATIK_ONAY'] = $otomatik_onay_aktif_mi;
                     
            
                        $kullaniciRaporlariToplami[] = $rapor;
                    }
                }
            } catch (Exception $e) {
                $reporter->log("   - HATA: {$isyeriAdi} işyeri için raporlar sorgulanırken hata oluştu: " . $e->getMessage() . "\n");
            }
        }

        // 6. TÜM işyerlerinin sorgusu bittikten sonra, eğer rapor bulunduysa TEK bir e-posta gönder
        $toplamRaporSayisi = count($kullaniciRaporlariToplami);
        if ($toplamRaporSayisi > 0) {
                $reporter->log(" - Toplam {$toplamRaporSayisi} adet rapor bulundu. E-posta oluşturuluyor...\n");

            // --- E-posta İçeriğini Oluşturma ---
            $konu = "SGK Onay Bekleyen Rapor Bildirimi - " . date('d.m.Y H:i');
            $icerik = "<h1>SGK Onay Bekleyen Raporlar</h1>"
                . "<p>ÖNEMLİ BİLGİLENDİRME : ÖNCEKİ DÖNEMLERDEN ONAYLI RAPORLARINIZ GELİRSE PANELDEN RAPORU KAPAT BUTONU İLE TEKRAR GELMESİNİ ENGELLEYEBİLİRSİNİZ,</p>"
                . "<p>Merhaba,</p>"
                . "<p>Sistemde, size ait işyerleri için aşağıda listelenen toplam <strong>{$toplamRaporSayisi} adet</strong> onay bekleyen rapor bulunmaktadır.</p>"
                . "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; font-family: sans-serif; font-size: 12px;'>"
                . "<thead>
                        <tr style='background-color: #f2f2f2;'>
                            <th>İşyeri Adı</th>
                            <th>TC Kimlik No</th>
                            <th>Ad Soyad</th>
                            <th>Vaka</th>
                            <th>Rapor Dönemi</th>
                            <th>Otomatik Onay Durumu</th>
                        </tr>
                    </thead>
                    <tbody>";

            foreach ($kullaniciRaporlariToplami as $rapor) {
                $icerik .= "<tr>"
                    . "<td>" . htmlspecialchars($rapor['ISYERI_ADI']) . "</td>"
                    . "<td>" . htmlspecialchars($rapor['TCKIMLIKNO']) . "</td>"
                    . "<td>" . htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']) . "</td>"
                    . "<td>" . htmlspecialchars($rapor['VAKAADI']) . "</td>"
                    . "<td>" . htmlspecialchars($rapor['POLIKLINIKTAR'] . ' - ' . $rapor['ABITTAR']) . "</td>";
                    
                    if($rapor['OTOMATIK_ONAY'] == 1){
                        $icerik .= "<td style='color: green; font-weight: bold;'>Otomatik onay Aktif.Saat 16:00'da onaylanacak</td>";
                    } else {
                        $icerik .= "<td style='color: red; font-weight: bold;'>Otomatik Onay Kapalı.Onaylamayı unutmayın</td>";
                    }

                    $icerik .= "</tr>";
            }

            $icerik .= "</tbody></table>"
                . "<p>Kontrol paneline gitmek için <a href='http://vizit-e.com'>buraya tıklayın</a>.</p>";

            // --- E-postayı Gönderme ---
            if (MailGonderService::gonder($kullaniciEmail, $konu, $icerik)) {
                $reporter->log(" - E-posta başarıyla gönderildi: {$kullaniciEmail}\n" . "**Gönderilen E-posta İçeriği:\n" . $icerik);
             } else {
                 $reporter->log(" - E-posta GÖNDERİLEMEDİ: {$kullaniciEmail}\n");
             }
        } else {
            $reporter->log(" - Bu kullanıcı için e-posta gönderilecek rapor bulunmadı.\n");
        }
        $reporter->log("--------------------------------------------------\n");
    }
} catch (Exception $e) {
    $reporter->log("!!! KRİTİK GENEL HATA !!!\n");
    $reporter->log("Hata: " . $e->getMessage() . "\n");
    MailGonderService::gonder('beyzade83@gmail.com', 'SGK CRON JOB GENEL HATASI', 'SGK Cron Job çalışırken genel bir hata oluştu: ' . $e->getMessage());
}




$reporter->log("Cron Job Tamamlandı: " . date('Y-m-d H:i:s') . "\n");

// Mail gönder
$mailContent = "
<h2>SGK Onay Bekleyen Raporlar Cron Job Raporu</h2>
<p><strong>Tamamlanma:</strong> " . date('d.m.Y H:i:s') . "</p>
" . $reporter->getHtmlOutput();

//reporter-getHtmlOutput fonksiyonu çağrısını log dosyasına yazdır
$logger->info($reporter->getHtmlOutput());

if (MailGonderService::gonder(
    "beyzade83@gmail.com",
    "SGK Onay Bekleyen Raporlar Cron Job Tamamlandı",
    $mailContent

)) {
    $reporter->log("Bildirim e-postası gönderildi.\n");
}
