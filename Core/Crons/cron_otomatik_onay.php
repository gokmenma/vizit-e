<?php
// Sadece komut satırından çalıştır
if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit();
}
// Hata loglama ve zaman dilimi ayarları
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cron_otomatik_onay_error.log');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Istanbul');

// Gerekli tüm sınıfları yükle
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';



use Models\UserModel;
use Models\KullaniciIsyeriModel;
use Models\KullaniciAyarModel;
use Models\RaporModel;
use App\Helper\Security;
use App\Helper\Date;
use Core\Services\MailGonderService;
use Core\Services\FileLogger as Logger;
use Core\Services\CronReporter;


$logger = new Logger(__DIR__ . '/../../logs/crons', 'otomatik_onay');
$reporter = new CronReporter();
$raporModel = new RaporModel();

// Rapor Durum Kodları
const RAPORDURUMU = [
    1 => 'ÇALIŞIR',
    2 => 'KONTROL',
    3 => 'DEVAMI VERİLDİ',
    4 => 'SEVKLİ',
    5 => 'HASTANE KAPATTI',
    6 => 'ÇALIŞIR OLUP ÇAKIŞMA VAR',
    7 => 'KONTROL OLUP ÇAKIŞMA VAR',
    8 => 'MALULİYET AZALTILABİLİR ÇALIŞIR',
    9 => 'MALULİYET SEVK ÇALIŞIR',
    10 => 'ANALIK DOĞUM ÖNCESİ ÇALIŞIR',
    11 => 'ANALIK DOĞUM ÖNCESİ ÇALIŞAMAZ',
    12 => 'ANALIK DOĞUM SONRASI',
    13 => 'MALULİYET AZALTILIR KONTROL',
    14 => 'MALULİYET SEVK KONTROL',
];


/* BUGÜN ONAYLANMASI GEREKİP DE ONAYLANAN RAPORLARI KULLANICI BAŞINA TEK E-POSTA OLARAK GÖNDERİR
 * Kullanıcıya ait isyerlerinin tüm raporlarını sorgular
 * Rapor bitiş tarihi bugünden önce olan ve rapor başlangıç tarihi ile rapor bitiş tarihi arasında 
 * 3 günden fazla olan raporları filtreler
 * Her kullanıcı için tek bir e-posta gönderir
 */





// --- GÜN VE SAAT KONTROLÜ ---
$gunNo = date('N'); // 1 (Pazartesi) - 7 (Pazar)
$saat = date('H'); // Saat (00-23)

// Sadece hafta içi ve belirtilen saatlerde çalış
if ($gunNo > 5 || !in_array($saat, ['16','20'])) { // Örnek saatler
    exit("Çalışma günü veya saati dışında. Cron işlemi atlanıyor. Tarih: " . date('Y-m-d H:i:s'));
}

// TODO: Resmi tatilleri bir veritabanından veya API'den çekip kontrol et
// $resmiTatiller = ['2025-01-01', ...];
// if (in_array(date('Y-m-d'), $resmiTatiller)) { exit; }

$logger->info("Otomatik Onay Cron Job Başlatıldı: " . date('Y-m-d H:i:s'). "\n");
$reporter->log("Otomatik Onay Cron Job Başlatıldı: " . date('Y-m-d H:i:s') . "\n");

try {
    // Veritabanı modellerini başlat
    $userModel = new UserModel();
    $isyeriModel = new KullaniciIsyeriModel();
    $kullaniciAyarModel = new KullaniciAyarModel();
    $raporModel = new RaporModel();

    // 1. ADIM: Otomatik onayı aktif olan TÜM BENZERSİZ işyerlerini al
    $aktifIsyerleri = $isyeriModel->findAllActiveForAutoOnay();
    if (empty($aktifIsyerleri)) {
        $reporter->log("Otomatik onayı aktif işyeri bulunamadı. Cron sonlandırılıyor.\n");
        $logger->info("Otomatik onayı aktif işyeri bulunamadı. Cron sonlandırılıyor.\n");
        exit;
    }

    $reporter->log(count($aktifIsyerleri) . " adet aktif ve benzersiz işyeri için işlem başlatılıyor...\n");
    $logger->info(count($aktifIsyerleri) . " adet aktif ve benzersiz işyeri için işlem başlatılıyor...\n");



    $reporter->log("--------------------------------------------------\n");
    $logger->info("--------------------------------------------------\n");

    // İşlem yapılan tüm raporları ve ilgili kullanıcıları toplayacağımız ana yapı
    $islemYapilanRaporlarVeKullanicilari = [];
    $i = 0;

    // 2. ADIM: Her bir İŞYERİ için raporları sorgula ve onayla
    foreach ($aktifIsyerleri as $isyeri ) {
        $i++;
        $isyeriId = $isyeri->id;
        $isyeriAdi = $isyeri->firma_adi;

        $kullaniciAdi = $isyeri->kullanici_kodu;
        $isyeriKodu = $isyeri->isyeri_kodu;
        $wsSifre = Security::decrypt($isyeri->isyeri_sifre);

        $reporter->log("{$i}. İşyeri sorgulanıyor: {$isyeriAdi} (SGK Kodu: {$isyeriKodu})\n");
        $logger->info("{$i}. İşyeri sorgulanıyor: {$isyeriAdi} (SGK Kodu: {$isyeriKodu})\n");

        try {
            $sgkClient = new SgkViziteService($kullaniciAdi, $isyeriKodu, $wsSifre);
            $raporlar = $sgkClient->raporlariGetir(new DateTime('tomorrow'));

            if (empty($raporlar)) {
                $reporter->log(" - Bu işyeri için onay bekleyen rapor bulunamadı.\n");
                $logger->info(" - Bu işyeri için onay bekleyen rapor bulunamadı.\n");
                continue;
            }

            // Onaylanacak raporları filtrele
            foreach ($raporlar as $rapor) {
                if ($rapor['ARSIV'] == 1) continue; // Arşivlenmişleri atla

                // Eğer rapor durumu "ONAYLI" veya "ONAYLANDI" içeriyorsa bu raporu atla (SGK'dan gelen veri)
                if ((isset($rapor['RAPORDURUMADI']) && stripos($rapor['RAPORDURUMADI'], 'ONAY') !== false) ||
                    (isset($rapor['ONAYLI']) && ($rapor['ONAYLI'] == '1' || $rapor['ONAYLI'] == 'E')) ||
                    (isset($rapor['ONAYDURUMU']) && ($rapor['ONAYDURUMU'] == '1' || $rapor['ONAYDURUMU'] == 'E'))) {
                    continue;
                }

                // Eğer bu rapor bizim veritabanımızda zaten onaylanmış görünüyorsa atla
                if ($raporModel->findReportByRaporTakipNo($rapor['RAPORTAKIPNO'])) {
                    continue;
                }

                $baslangic = new DateTime($rapor['POLIKLINIKTAR']);
                $iseBasi = new DateTime($rapor['ISBASKONTTAR']);

                $kontrolSonucu = raporOnayKontrol($baslangic, $iseBasi);
                if ($kontrolSonucu['durum'] !== true) {
                    $reporter->log("   - [ATLANDI] Rapor ID: {$rapor['MEDULARAPORID']}. Sebep: {$kontrolSonucu['mesaj']}\n");
                    $logger->info("   - [ATLANDI] Rapor ID: {$rapor['MEDULARAPORID']}. Sebep: {$kontrolSonucu['mesaj']}\n");
                    continue;
                }

                // --- Onaylama ve Loglama İşlemi ---
                $logData = [
                    'isyeri_id' => $isyeriId,
                    'MEDULARAPORID' => $rapor['MEDULARAPORID'],
                    'RAPORTAKIPNO' => $rapor['RAPORTAKIPNO'],
                    'TCKIMLIKNO' => $rapor['TCKIMLIKNO'],
                    'SIGORTALIADSOYAD' => rtrim($rapor['AD'], ' ') . ' ' . rtrim($rapor['SOYAD'], ' '),
                    'POLIKLINIKTAR' => (new DateTime($rapor['POLIKLINIKTAR']))->format('Y-m-d'),
                    'ISBASKONTTAR' => (new DateTime($rapor['ISBASKONTTAR']))->format('Y-m-d'),
                    'rapor_gun_sayisi' => (new DateTime($rapor['POLIKLINIKTAR']))->diff(new DateTime($rapor['ISBASKONTTAR']))->days,
                    'VAKAADI' => $rapor['VAKAADI'],
                    'VAKA' => $rapor['VAKA'],
                    'RAPORDURUMU' => RAPORDURUMU[$rapor['RAPORDURUMU']],
                    // Başlangıçta durumu başarısız olarak ayarlayalım
                    "onay_turu" => "Otomatik Onay",
                    'onay_durumu' => 'basarisiz',
                    //'hata_mesaji' => null,
                    'sgk_bildirim_id' => null
                ];

               //SGK'ya onayı gönder
                $onayResponse = $sgkClient->raporuOnayla(
                    $rapor['MEDULARAPORID'], 
                    $rapor['TCKIMLIKNO'], 
                    $rapor['VAKA'], 
                    '0', //0 ÇALIŞMAMIŞTIR , 1 ÇALIŞMIŞTIR
                    new DateTime($rapor['ABITTAR']) // AYAKTAN BİTİŞ TARİHİ

                );

               // Test için nesne oluştur
                // $onayResponse = new stdClass();
                // $onayResponse->sonucKod = '0';
                // $onayResponse->sonucAciklama = 'Test Onay';

                if (isset($onayResponse->sonucKod) && $onayResponse->sonucKod == '0') {
                    $sgkClient->raporuKapat($rapor['MEDULARAPORID']);
                    $rapor['ONAY_SONUC'] = 'Başarılı';
                    $reporter->log("   - [ONAYLANDI] Rapor ID: {$rapor['MEDULARAPORID']} onaylandı. Rapor bitiş Tarihi {$rapor['ABITTAR']}.\n");
                    $logger->info("   - [ONAYLANDI] Rapor ID: {$rapor['MEDULARAPORID']} onaylandı. Rapor bitiş Tarihi {$rapor['ABITTAR']}.\n");


                    // Log verisini başarı durumuyla güncelle
                    $logData['onay_durumu'] = 'basarili';
                    $logData['sgk_bildirim_id'] = $onayResponse->sonucAciklama[1] ?? null;

                    $raporModel->saveWithAttr($logData);
                    
                } else {
                    $rapor['ONAY_SONUC'] = 'BAŞARISIZ: ' . ($onayResponse->sonucAciklama ?? 'Bilinmeyen hata');
                    $reporter->log("   - [BAŞARISIZ] Rapor ID: {$rapor['MEDULARAPORID']} ONAYLANAMADI: {$rapor['ONAY_SONUC']}\n");
                    $logger->info("   - [BAŞARISIZ] Rapor ID: {$rapor['MEDULARAPORID']} ONAYLANAMADI: {$rapor['ONAY_SONUC']}\n");


                    $logData['onay_durumu'] = 'basarisiz';
                    $logData['hata_mesaji'] = $rapor['ONAY_SONUC'];
                    $raporModel->saveWithAttr($logData);
                }

                // 3. ADIM: İşlem yapılan raporu ve ilgili TÜM kullanıcıları ana yapıya ekle
                $raporId = $rapor['MEDULARAPORID'];
                $ilgiliKullaniciIdleri = $isyeriModel->findUserIdsByIsyeriId($isyeriId);

                if (!isset($islemYapilanRaporlarVeKullanicilari[$raporId])) {
                    $rapor['ISYERI_ADI'] = $isyeriAdi;
                    $islemYapilanRaporlarVeKullanicilari[$raporId] = [
                        'rapor_data' => $rapor,
                        'kullanici_idler' => []
                    ];
                }
                $islemYapilanRaporlarVeKullanicilari[$raporId]['kullanici_idler'] = array_unique(
                    array_merge($islemYapilanRaporlarVeKullanicilari[$raporId]['kullanici_idler'], $ilgiliKullaniciIdleri)
                );
            }
        } catch (Exception $e) {
            $logger->error(" - HATA: {$isyeriAdi} işyeri işlenirken hata oluştu: " . $e->getMessage() . "\n");
            $reporter->log(" - HATA: {$isyeriAdi} işyeri işlenirken hata oluştu: " . $e->getMessage() . "\n");
        }
    }

    // 4. ADIM: Raporları KULLANICILARA GÖRE GRUPLA
    $kullaniciyaGoreRaporlar = [];
    foreach ($islemYapilanRaporlarVeKullanicilari as $raporId => $data) {
        $rapor = $data['rapor_data'];
        foreach ($data['kullanici_idler'] as $kullaniciId) {
            $kullaniciyaGoreRaporlar[$kullaniciId][] = $rapor;
        }
    }

    // 5. ADIM: Her KULLANICI için TEK bir özet e-posta gönder
    if (empty($kullaniciyaGoreRaporlar)) {
        $reporter->log("Tüm işyerleri tarandı, e-posta gönderilecek yeni rapor bulunmadı.\n");
        $logger->info("Tüm işyerleri tarandı, e-posta gönderilecek yeni rapor bulunmadı.\n");
    } else {
        echo "--------------------------------------------------\n";
        echo "E-posta gönderim süreci başlatılıyor...\n";

        $reporter->log("--------------------------------------------------\n");
        $logger->info("--------------------------------------------------\n");

        $reporter->log("E-posta gönderim süreci başlatılıyor...\n");
        $logger->info("E-posta gönderim süreci başlatılıyor...\n");


        foreach ($kullaniciyaGoreRaporlar as $kullaniciId => $raporListesi) {
            $kullanici = $userModel->find($kullaniciId);
            if (!$kullanici || empty($kullanici->email)) continue;

            // Kullanıcının e-posta bildirim ayarını kontrol et
            $epostaBildirimiAyar = $kullaniciAyarModel->getSetting('rapor_otomatik_onay_bildirim', $kullaniciId);
            if ($epostaBildirimiAyar != '1') {
                $reporter->log(" - Kullanıcı: {$kullanici->email} için e-posta bildirimi kapalı, atlanıyor.\n");
                $logger->info(" - Kullanıcı: {$kullanici->email} için e-posta bildirimi kapalı, atlanıyor.\n");
                continue;
            }

            $kullaniciEmail = $kullanici->email;
            $toplamRaporSayisi = count($raporListesi);

            $reporter->log(" - Kullanıcı: {$kullaniciEmail}, Rapor Sayısı: {$toplamRaporSayisi}. E-posta oluşturuluyor...\n");
            $logger->info(" - Kullanıcı: {$kullaniciEmail}, Rapor Sayısı: {$toplamRaporSayisi}. E-posta oluşturuluyor...\n");


            // --- E-posta İçeriğini Oluşturma ---
            $konu = "Otomatik SGK Rapor Onaylama Sonuçları - " . date('d.m.Y H:i');
            $icerik = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
            $icerik .= "<h1>Otomatik SGK Rapor Onaylama Sonuçları</h1>"
                . "<p>Merhaba,</p>"
                . "<p>Sistem, <strong>" . date('d.m.Y H:i') . "</strong> itibarıyla size ait işyerleri için aşağıdaki işlemleri gerçekleştirmiştir.</p>"
                . "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; font-family: sans-serif; font-size: 13px;'>"
                . "<thead>
                            <tr style='background-color: #f2f2f2;'>
                                <th>İşyeri Adı</th>
                                <th>TC Kimlik No</th>
                                <th>Ad Soyad</th>
                                <th>Rapor Dönemi</th>
                                <th>İşlem Sonucu</th>
                                <th>Raporu Görüntüle</th>
                            </tr>
                        </thead>
                     <tbody>";


            foreach ($raporListesi as $rapor) {
                $style = ($rapor['ONAY_SONUC'] !== 'Başarılı') ? "style='background-color: #f8d7da;'" : "";
                $icerik .= "<tr {$style}>"
                    . "<td>" . htmlspecialchars($rapor['ISYERI_ADI'], ENT_QUOTES, 'UTF-8') . "</td>"
                    . "<td>" . htmlspecialchars($rapor['TCKIMLIKNO'], ENT_QUOTES, 'UTF-8') . "</td>"
                    . "<td>" . htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD'], ENT_QUOTES, 'UTF-8') . "</td>"
                    . "<td>" . htmlspecialchars(Date::dmY($rapor['POLIKLINIKTAR']) . ' - ' . Date::dmY($rapor['ABITTAR']), ENT_QUOTES, 'UTF-8') . "</td>"
                    . "<td>" . htmlspecialchars($rapor['ONAY_SONUC'], ENT_QUOTES, 'UTF-8') . "</td>"
                    . "<td><a href='https://www.vizit-e.com/onayli-rapor-goster?rapor_id=" . Security::encrypt($rapor['RAPORTAKIPNO']) . "' target='_blank'>Raporu Görüntüle</a></td>"
                    . "</tr>";
            }
            $icerik .= "</tbody></table><p>Detayları kontrol etmek için <a href='http://www.vizit-e.com/'>Kontrol Paneli</a> giriş yapabilirsiniz.</p></body></html>";



           // $uniqueEpostalar = $isyeriModel->findUniqueEmailsForAutoOnay();
            $uniqueEpostalar = $isyeriModel->findUniqueEmailsForAutoOnay();

            foreach ($uniqueEpostalar as $email) {
                $emails = explode(',', $email->otomatik_onay_eposta);
                foreach ($emails as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        if (MailGonderService::gonder($email, $konu, $icerik)) {
                            $reporter->log("   - E-posta başarıyla gönderildi.\n" . " Eposta: " . $email);
                            $logger->info("   - E-posta başarıyla gönderildi.\n" . " Eposta: " . $email);
                        } else {
                            $reporter->log("   - E-posta GÖNDERİLEMEDİ.\n" . " Eposta: " . $email); ;
                            $logger->info("   - E-posta GÖNDERİLEMEDİ.\n" . " Eposta: " . $email); ;
                        }
                    }
                }

              
            }
        }
    }
} catch (Exception $e) {
    $reporter->log("!!! KRİTİK GENEL HATA !!!\n");
    $logger->info("!!! KRİTİK GENEL HATA !!!\n");
    $reporter->log("Hata: " . $e->getMessage() . "\n");
    $logger->info("Hata: " . $e->getMessage() . "\n");
    MailGonderService::gonder('bilgi@vizit-e.com', 'SGK OTOMATİK ONAY CRON HATASI', 'Otomatik Onay Cron Job çalışırken genel bir hata oluştu: ' . $e->getMessage());
}

$reporter->log("Cron Job Tamamlandı: " . date('Y-m-d H:i:s') . "\n");
$logger->info("Cron Job Tamamlandı: " . date('Y-m-d H:i:s') . "\n");


// Mail gönder
$mailContent = "
<h2>SGK Otomatik Onay Cron Job Raporu</h2>
<p><strong>Tamamlanma:</strong> " . date('d.m.Y H:i:s') . "</p>
" . $reporter->getHtmlOutput();



if (MailGonderService::gonder(
    "bilgi@vizit-e.com",
    "SGK Otomatik Onay Cron Job Tamamlandı",
    $mailContent

)) {
    $reporter->log("Bildirim e-postası gönderildi.\n");
}


// Rapor onay kontrol fonksiyonu
function raporOnayKontrol(
    DateTime $baslangic,
    DateTime $iseBasi,
    bool $bitisGelecekteOlmali = false
): array {
    // Saatleri sıfırlayarak sadece gün bazında karşılaştırma yap
    $baslangic->setTime(0, 0, 0);
    $iseBasi->setTime(0, 0, 0);

    $now = new DateTime('today'); // today zaten saatleri sıfırlar
    $raporBitis = (clone $iseBasi)->modify('-1 day');

    // Şart 1: Rapor süresi en az 3 gün olmalı
    if ($baslangic->diff($iseBasi)->days < 3) {
        return ['durum' => false, 'mesaj' => 'Rapor süresi 3 günden az. (' . $baslangic->diff($iseBasi)->days . ' gün)'];
    }

    // Şart 2: İSTEĞE BAĞLI — bitiş tarihi geçmişte olmamalı
    if ($bitisGelecekteOlmali && $raporBitis < $now) {
        return ['durum' => false, 'mesaj' => 'Rapor bitiş tarihi geçmişte. (' . $raporBitis->format('Y-m-d') . ')'];
    }

    // Ayın gününü ve ay sınırlarını hesapla
    $gun = $now->format('d');
    $ayBaslangic = (clone $now)->modify('first day of this month');
    $oncekiAySonu = (clone $ayBaslangic)->modify('-1 day');
    $buAyin26si = (clone $ayBaslangic)->setDate($ayBaslangic->format('Y'), $ayBaslangic->format('m'), 26);
    $oncekiAyin26si = (clone $oncekiAySonu)->setDate($oncekiAySonu->format('Y'), $oncekiAySonu->format('m'), 26);


    //Yıl 0000 ise hiç işlem yapma
    if ($raporBitis->format('Y') == '0000') {
        return ['durum' => false, 'mesaj' => 'Rapor bitiş tarihi geçersiz. (' . $raporBitis->format('Y-m-d') . ')'];
    }



    // Ayın 1-26'i arası
    if ($raporBitis->format('Y-m-d') >= $now->format('Y-m-d')) {
        return ['durum' => false, 'mesaj' => 'Rapor bitiş tarihi gelecekte. (' . $raporBitis->format('Y-m-d') . ')'];
    } else if ($gun <= 26) {
        if ($raporBitis >= $oncekiAyin26si && $raporBitis <= $buAyin26si) {
            return ['durum' => true, 'mesaj' => 'Rapor, onay dönemi içinde. (' . $raporBitis->format('Y-m-d') . ')'];
        }
        return ['durum' => false, 'mesaj' => 'Rapor, onay dönemi (Önceki Ay 27 - Bu Ay 26) dışında. (' . $raporBitis->format('Y-m-d') . ')'];
    }
    // Ayın 26'sı ve sonrası
    else {
        $sonrakiAyin26si = (clone $ayBaslangic)->modify('+1 month')->setDate($ayBaslangic->format('Y'), $ayBaslangic->format('m') + 1, 25);
        $buAyin26si = (clone $ayBaslangic)->setDate($ayBaslangic->format('Y'), $ayBaslangic->format('m'), 26);

        if ($raporBitis >= $buAyin26si && $raporBitis <= $sonrakiAyin26si) {
            return ['durum' => true, 'mesaj' => 'Rapor, onay dönemi içinde. (' . $raporBitis->format('Y-m-d') . ')'];
        }
        return ['durum' => false, 'mesaj' => 'Rapor, onay dönemi (Bu Ay 26 - Sonraki Ay 26) dışında. (' . $raporBitis->format('Y-m-d') . ')'];
    }
}



