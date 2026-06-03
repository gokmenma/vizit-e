<?php
// Hata loglama ve zaman dilimi ayarları
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cron_gunluk_bildirim_error.log');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Istanbul');

// Gerekli sınıfları yükle
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';



/**
 * GÜN İÇİNDE RAPOR ALAN PERSONELLERİN RAPOR BİLDİRİM CRON DOSYASI.
 * 
 * Bu dosya, gün içinde rapor alan personelleri bildirmek amacıyla
 * gün içinde yarım saat aralıklarla çalışan cron job ile
 * işyeri bazında, kullanıcılara mail gönderir.
 * 
 */



use Models\UserModel;
use Models\KullaniciIsyeriModel as IsyeriModel;
use Models\RaporBildirimLogModel; // YENİ: Log modeliniz
use App\Helper\Security;
use App\Helper\Helper;
use Core\Services\MailGonderService;
use Core\Services\DatabaseLogger;
use Core\Services\CronReporter;
use Core\Services\FileLogger;

$reporter = new CronReporter();
$logger = new FileLogger(__DIR__ . '/../../logs/crons', 'cron_gunluk_bildirim');


/** RAPOR ALAN PERSONELLERİ GÜN İÇİNDE KULLANICILARA BİLDİRİR */


// --- GÜN VE SAAT KONTROLÜ ---
$gunNo = date('N'); // 1 (Pazartesi) - 7 (Pazar)
$saat = date('H'); // Saat (00-23)

// Sadece hafta içi ve belirtilen saatlerde çalış
if ($gunNo > 7) { // Örnek saatler
    exit("Çalışma günü veya saati dışında. Cron işlemi atlanıyor. Tarih: " . date('Y-m-d H:i:s'));
}

// --- SAAT KONTROLÜ ---
$saat = (int)date('G');
//saatler 08-23 arasında bildirim yapılır
if (!in_array($saat, array_merge(range(8, 23), [0]))) {
    $reporter->log("Çalışma saatleri (" . implode(',', array_merge(range(8, 23), [0])) . ") dışında. Cron işlemi atlanıyor. Saat: " . date('H:i'));
    exit;
}
$bildirimSaatiStr = date('H:00');

$reporter->log("GÜN İÇİNDE RAPOR ALAN PERSONELLERİN RAPOR BİLDİRİM CRON JOB BAŞLATILDI: " . date('Y-m-d H:i:s') . "\n");
$bugun = new DateTime('today');

try {
    $userModel = new UserModel();
    $isyeriModel = new IsyeriModel();
    $logModel = new RaporBildirimLogModel(); // Yeni log modelini başlat

    // 1. Sistemdeki tüm aktif kullanıcıları al
    $kullanicilar = $userModel->AktifKullanicilarAltKullanici();
    if (empty($kullanicilar)) {
        $reporter->log("Sistemde aktif kullanıcı bulunamadı." . "\n");
        exit;
    }

    // 2. Her bir kullanıcı için işlemleri tekrarla
    foreach ($kullanicilar as $kullanici) {
        $kullaniciId = $kullanici->id;
        $kullaniciEmail = "beyzade83@hotmail.com"; // $kullanici->email;
        $kullanici_adi = $kullanici->kullanici_adi;
        $isyeriYetkiliId = $kullanici->admin_id;

         //Eğer işyeri_ids içinde virgül yoksa, direkt dizi yap
        $yetkili_isyeri_ids = $kullanici->yetkili_oldugu_isyeri_ids ?? '';

        // İşyeri ID'lerini güvenli şekilde normalize et (trim, int'e çevir, geçersizleri at)
        $isyeri_ids = [];
        if (!empty($yetkili_isyeri_ids)) {
            $parcalar = (strpos($yetkili_isyeri_ids, ',') === false)
                ? [$yetkili_isyeri_ids]
                : explode(',', $yetkili_isyeri_ids);
            $isyeri_ids = array_values(array_filter(array_map(function ($v) {
                return (int) trim($v);
            }, $parcalar), function ($v) {
                return $v > 0;
            }));
        }

        $reporter->log("--------------------------------------------------\n");

        $reporter->log("İşlenen Kullanıcı: {$kullaniciEmail}" . "\n" . "Kullanıcı adı ve ID: {$kullanici_adi} ({$kullaniciId})" . "\n");



        //Eğer kullanıcı alt kullanıcı ise, yetkilinin işyerlerini al
        if ($isyeriYetkiliId > 0) {
            
            // Alt kullanıcının yetkili olduğu işyerlerini al
            $isyerleri = $isyeriModel->altKullanicininYetkiliOlduguIsyerleri($isyeri_ids);
            $reporter->log("Alt kullanıcının yetkili olduğu işyeri sayısı: " . count($isyerleri));
        } else {
            $isyerleri = $isyeriModel->findByUserId($kullaniciId);
        }

        if (empty($isyerleri)) {
            continue;
        }


        $kullaniciyaGonderilecekRaporlar = [];

        // 3. Kullanıcının her bir işyeri için raporları sorgula
        foreach ($isyerleri as $isyeri) {
            $isyeriId = $isyeri->id;
            $isyeriAdi = $isyeri->firma_adi;
            $isyeriKullaniciKodu = $isyeri->kullanici_kodu;
            $isyeriKodu = $isyeri->isyeri_kodu;
            $isyeriSifre = Security::decrypt($isyeri->isyeri_sifre);


            $kontrol_edilecek_isyerleri = array("31252682942", "32450401908");


            if (!in_array($isyeri->kullanici_kodu, $kontrol_edilecek_isyerleri)) {
                $reporter->log(" - {$isyeriAdi} işyeri, kontrol edilecek listede değil, atlanıyor." . "\n");
                continue;
            }

            $reporter->log(" ---- {$isyeriAdi}  işyeri için raporlar kontrol ediliyor..." . "\n" .
                "kullanıcı kodu: {$isyeriKullaniciKodu}, işyeri kodu: {$isyeriKodu}" . "\n");

            try {
                $sgkClient = new SgkViziteService(
                    $isyeriKullaniciKodu,
                    $isyeriKodu,
                    $isyeriSifre
                );

                // Onay bekleyen TÜM raporları çek
                $raporlar = $sgkClient->raporlariGetir(new DateTime('tomorrow'));

                foreach ($raporlar as $rapor) {
                    $raporBaslangic = new DateTime($rapor['POLIKLINIKTAR']);


                    // ŞART 1: Rapor başlangıç tarihi BUGÜN mü?
                    if ($raporBaslangic->format('Y-m-d') === $bugun->format('Y-m-d')) {

                        // ŞART 2: Bu rapor bu kullanıcıya BUGÜN daha önce bildirildi mi?
                        if (!$logModel->isReportNotifiedToday($rapor['MEDULARAPORID'], $kullaniciId)) {
                            // Bildirilmediyse, listeye ekle ve veritabanına logla
                            $rapor['ISYERI_ADI'] = $isyeriAdi;
                            $rapor['ISYERI_ID'] = $isyeriId; // İşyeri ID'sini de ekle

                            $kullaniciyaGonderilecekRaporlar[] = $rapor;

                            $logModel->saveWithAttr([
                                'medula_rapor_id' => $rapor['MEDULARAPORID'],
                                'isyeri_id' => $isyeriId,
                                'kullanici_id' => $kullaniciId,
                                'bildirim_tarihi' => $bugun->format('Y-m-d'),
                                'bildirim_saati' => $bildirimSaatiStr
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                $reporter->log(" - HATA: {$isyeriAdi} işyeri sorgulanırken hata: " . $e->getMessage());
            }
        }

        // 4. Kullanıcı için yeni raporlar bulunduysa, TEK bir özet e-posta gönder
        $yeniRaporSayisi = count($kullaniciyaGonderilecekRaporlar);
        if ($yeniRaporSayisi > 0) {
            $reporter->log(" - {$yeniRaporSayisi} adet YENİ rapor bulundu. E-posta gönderiliyor..." . "\n");

            $konu = "Yeni İstirahat Raporu Bildirimi ({$bildirimSaatiStr}) - " . date('d.m.Y');
            $icerik = '<!DOCTYPE html>
                        <html lang="tr">
                        <head>
                            <meta charset="UTF-8">
                            <title>Yeni İstirahat Raporları</title>
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        </head>
                        <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #2C2C2F; color: #E4DEFF; line-height: 1.6;">

                            <!-- Ana Konteyner Tablosu -->
                            <table width="100%"  style="background-color: #2C2C2F; padding: 20px 20px;">
                                <tr>
                                    <td align="center">
                                        <table >

                                    <!-- Ana İçerik Kartı -->
                                            <tr>
                                                <td style="background-color: #2c2c2f; border-radius: 16px; border: 1px solid #444;">
                                                    <table width="100%" >
                                                        <tr>
                                                            <td style="padding: 40px; color: #E4DEFF; font-size: 16px;">
                                                                
                                                                <h1 style="font-size: 28px; font-weight: bold; margin: 0 0 20px 0; line-height: 1.2; color: #FFFFFF;">Yeni İstirahat Raporları</h1>
                                                                <p style="margin: 0 0 20px 0;">Merhaba,</p>
                                                                <p style="margin: 0 0 30px 0;">Sistemde, <strong style="color: #FFFFFF;">bugün başlayan ' . $yeniRaporSayisi . ' adet</strong> yeni istirahat raporu tespit edilmiştir:</p>
                                                                
                                                                <!-- Raporlar Tablosu -->
                                                                <table width="100%" >
                                                                    <thead>
                                                                        <tr style="background-color: #3a3a3d;">
                                                                            <th align="left" style="padding: 12px; color: #FFFFFF; font-weight: bold; border-bottom: 1px solid #444;">İşyeri</th>
                                                                            <th align="left" style="padding: 12px; color: #FFFFFF; font-weight: bold; border-bottom: 1px solid #444;">Personel</th>
                                                                            <th align="left" style="padding: 12px; color: #FFFFFF; font-weight: bold; border-bottom: 1px solid #444;">Rapor Dönemi</th>
                                                                            <th align="left" style="padding: 12px; color: #FFFFFF; font-weight: bold; border-bottom: 1px solid #444;">Notlar</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>';

            foreach ($kullaniciyaGonderilecekRaporlar as $rapor) {
                $gunFarki = (new DateTime($rapor['POLIKLINIKTAR']))->diff(new DateTime($rapor['ISBASKONTTAR']))->days;
                $notlar = "";
                $isyeriAdi = htmlspecialchars($rapor['ISYERI_ADI'], ENT_QUOTES, 'UTF-8');
                $personelBilgisi = htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD'] . ' (' . $rapor['TCKIMLIKNO'] . ')', ENT_QUOTES, 'UTF-8');
                $raporDonemi = htmlspecialchars($rapor['POLIKLINIKTAR'] . ' - ' . $rapor['ISBASKONTTAR'], ENT_QUOTES, 'UTF-8');

                if ($gunFarki < 3) {
                    $notlar = "<span style='color: #6c757d;'>3 günden kısa süreli olduğu için SGK tarafından otomatik arşivlenir, onay gerekmez.</span>";
                } else {

                    // İşyeri ID'sini rapor verisinden al
                    $raporIsyeriId = $rapor['ISYERI_ID']; // veya hangi alan adı kullanılıyorsa
                    $isyeriAyariAktifMi = $isyeriModel->isAutoOnayActive($raporIsyeriId);

                    if ($isyeriAyariAktifMi) {
                        $notlar = "<span style='color: green;'>Otomatik onaylama aktif, rapor bitiminde sistem tarafından onaylanacaktır.</span>";
                    } else {
                        $notlar = "<span style='color: orange;'>Otomatik onaylama aktif değil, rapor bitiminde manuel onaylamayı unutmayın!</span>";
                    }
                }


                $icerik .= "<tr >
                                <td style='border: 1px solid #444;'>{$isyeriAdi}</td>
                                <td style='border: 1px solid #444;'>{$personelBilgisi}</td>
                                <td style='border: 1px solid #444;'>{$raporDonemi}</td>
                                <td style='border: 1px solid #444;'>{$notlar}</td>
                        </tr>";
            }

            $icerik .= '</tbody></table>
                                    <p style="margin: 30px 0 20px 0;">Detayları kontrol etmek için aşağıdaki butona tıklayabilirsiniz.</p>

                                            <!-- Buton -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                <tr>
                                                    <td align="center">
                                                        <a href="http://vizit-e.com/" target="_blank" style="background-color: #8a2be2; color: #FFFFFF !important; padding: 16px 32px; border-radius: 30px; font-weight: bold; font-size: 18px; text-decoration: none; display: inline-block;">Kontrol Paneline Git</a>
                                                    </td>
                                                </tr>
                                            </table>

                                            <p style="margin: 30px 0 0 0;">İyi çalışmalar dileriz.</p>
                                        </td>
                                    </tr>
                                </table>
                                </td>
                                </tr>

                                        <!-- Footer -->
                                        <tr>
                                            <td align="center" style="padding: 30px 0; color: #777; font-size: 14px;">
                                                © 2025 Vizit-e. Tüm hakları saklıdır.
                                            </td>
                                        </tr>

                                    </table>
                                </td>
                            </tr>
                        </table>

                    </body>
                    </html>';

           if (MailGonderService::gonder($kullaniciEmail, $konu, $icerik)) {
                $reporter->log($kullaniciEmail . " adresine E-posta gönderildi." . "\n");
            } else {
                //$reporter->log($kullaniciEmail . " adresine E-posta gönderilemedi." . "\n");
            }
        } else {
            $reporter->log(" - Bu kullanıcı için bildirilecek YENİ rapor bulunmadı." . "\n" . "\n");
        }
    }
} catch (Exception $e) {
    $reporter->log("!!! KRİTİK GENEL HATA !!!");
    $reporter->log("Hata: " . $e->getMessage());
    MailGonderService::gonder('beyzade83@gmail.com', 'SGK GÜNLÜK BİLDİRİM CRON HATASI', 'Günlük bildirim cronu çalışırken hata oluştu: ' . $e->getMessage());
}

$reporter->log("Cron Job Tamamlandı: " . date('Y-m-d H:i:s') . "\n");

// Mail gönder
$mailContent = "
<h2>GÜN İÇİNDE RAPOR ALAN PERSONELLERİN RAPOR BİLDİRİM CRON JOB RAPORU</h2>
<p><strong>Tamamlanma:</strong> " . date('d.m.Y H:i:s') . "</p>
" . $reporter->getHtmlOutput();

if (MailGonderService::gonder(
    "bilgi@vizit-e.com",
    "SGK Gün İçinde Yeni Rapor Bildirim Cron Job Tamamlandı",
    $mailContent

)) {
    $reporter->log("Bildirim e-postası gönderildi.\n");
}
