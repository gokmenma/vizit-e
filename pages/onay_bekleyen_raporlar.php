<?php

use App\Helper\Security;
use Core\Services\FileLogger;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once __DIR__ . '/../Core/Services/SgkViziteService.php';
use Models\RaporModel;

$userRole = $_SESSION["role"] ?? "user";
$title = 'Onay Bekleyen Raporlar';
$raporlar = [];
$hataMesaji = '';
$basariMesaji = '';
$toplamBulunanRaporSayisi = 0; // Filtrelemeden önceki toplam sayıyı tutmak için
$kisaRaporlariGoster = isset($_POST['kisa_raporlari_goster']); // JavaScript için bu satır gerekmeyecek
$uzunSureliRaporlar = []; // 3 günden uzun raporları tutmak için
$tarih = !empty($_REQUEST["rapor_tarihi"]) ? $_REQUEST["rapor_tarihi"] : date('Y-m-d');
try {
    $sgkClient = new SgkViziteService();
    $raporModel = new RaporModel();

    // 1. SGK'dan onay bekleyen raporları çek (arşivlenmiş olanları otomatik kapatma
    // ve onay/yerel-DB filtrelemesi dahil). Bu mantık SgkViziteService::bekleyenRaporlariGetir()
    // içinde merkezi tutulur; otomatik onay cron'u da aynı metodu kullanır.
    $sonuc = $sgkClient->bekleyenRaporlariGetir(new DateTime($tarih), $raporModel);
    $tumRaporlar = $sonuc['raporlar'];
    $arsivKapatSonucu = $sonuc['arsiv_kapatma'];
    if ($arsivKapatSonucu['kapatilan'] > 0 || !empty($arsivKapatSonucu['hatalar'])) {
        $arsivLogger = new FileLogger(__DIR__ . '/../logs', 'arsiv_otomatik_kapat');
        $arsivLogger->info('Arşivlenmiş raporlar otomatik kapatıldı.', [
            'isyeriKodu' => $_SESSION['isyeriKodu'] ?? null,
            'kullanici_id' => $_SESSION['kullanici_id'] ?? null,
            'kapatilan' => $arsivKapatSonucu['kapatilan'],
            'hatalar' => $arsivKapatSonucu['hatalar'],
        ]);
    }

    // 2. Kalan raporlar için ekrana özgü işlemleri yap (süre hesabı, sekme dağılımı)
    if (!empty($tumRaporlar)) {

        $islenmisRaporlar = []; // İşlenmiş raporları koyacağımız yeni dizi

        foreach ($tumRaporlar as $rapor) {
            // Rapor süresini hesapla
            $gunFarki = 0;
            if (!empty($rapor['POLIKLINIKTAR']) && !empty($rapor['ISBASKONTTAR'])) {
                try {
                    $gunFarki = (new DateTime($rapor['POLIKLINIKTAR']))->diff(new DateTime($rapor['ISBASKONTTAR']))->days;
                } catch (Exception $e) { /* Hatalı tarihi atla */
                }
            }

            // HER RAPORA SÜRESİNİ EKLEYELİM. Bu, JavaScript için gerekli.
            $rapor['gun_farki'] = $gunFarki;

            // 3 günden uzun raporları ayrı tut
            if ($gunFarki >= 3) {
                $uzunSureliRaporlar[] = $rapor;
            }


            // İşlenmiş raporu listeye ekle
            $islenmisRaporlar[] = $rapor;
            $toplamBulunanRaporSayisi++; 
        }
        $raporlar = $islenmisRaporlar;
    }
} catch (Exception $e) {
    $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
}

/**
 * SGK hata mesajlarını kullanıcı dostu ve anlaşılır kılmak için parse eder
 */
function parseSgkError($errorMessage) {
    $result = [
        'type' => 'unknown',
        'title' => 'Sistemsel Bir İşlem Hatası',
        'description' => 'SGK sistemleri ile iletişim kurulurken bir sorun meydana geldi.',
        'severity' => 'danger',
        'solutions' => [],
        'technical_details' => $errorMessage
    ];

    if (empty($errorMessage)) {
        return null;
    }

    if (stripos($errorMessage, 'aktif bir seans mevcuttur') !== false || stripos($errorMessage, 'Oturum Hatası') !== false) {
        $result['type'] = 'session_conflict';
        $result['title'] = 'SGK Sunucusunda Aktif Oturum Var';
        $result['description'] = 'SGK sistemi güvenlik ve veri güvenliği politikaları gereği aynı işveren için aynı anda yalnızca tek bir oturuma izin vermektedir. Şu anda başka bir IP adresi veya cihaz üzerinden aktif bir seans bulunmaktadır.';
        $result['severity'] = 'warning';
        $result['solutions'] = [
            '**Bekleyin (En Kolay Çözüm):** SGK sunucusundaki eski oturumun otomatik olarak sonlanması için **10 - 15 dakika** bekleyip tekrar aramayı deneyin.',
            '**Yeni IP Alın:** İnternet bağlantınızı kesip tekrar bağlanarak (modem kapat-aç vb.) yeni bir IP adresi almayı deneyin.',
            '**Diğer Tarayıcıları Kapatın:** Eğer SGK Vizite sistemine başka bir tarayıcı sekmesinde veya başka bir bilgisayarda girdiyseniz, oradaki oturumları kapatın.'
        ];
    } elseif (stripos($errorMessage, 'Login başarısız') !== false || stripos($errorMessage, 'şifre hatalı') !== false || stripos($errorMessage, 'Kullanıcı adı') !== false) {
        $result['type'] = 'auth_failed';
        $result['title'] = 'SGK Giriş Bilgileri Hatası';
        $result['description'] = 'SGK web servislerine giriş yetkilendirmesi başarısız oldu. Tanımlı olan SGK kullanıcı adı, şifre veya işyeri kodunuz hatalı veya süresi dolmuş olabilir.';
        $result['severity'] = 'danger';
        $result['solutions'] = [
            '**Ayarları Kontrol Edin:** Yönetim panelinden **"SGK Ayarları"** sayfasına giderek bilgilerinizi kontrol edin ve eksiksiz/doğru yazıldığından emin olun.',
            '**Şifre Güncelliği:** SGK işveren şifrenizin veya sistem şifrenizin süresinin dolup dolmadığını SGK resmi sitesine direkt giriş yaparak teyit edin.'
        ];
    } elseif (stripos($errorMessage, 'timeout') !== false || stripos($errorMessage, 'connect') !== false || stripos($errorMessage, 'ulaşılamadı') !== false || stripos($errorMessage, 'SoapFault') !== false) {
        $result['type'] = 'connection';
        $result['title'] = 'SGK Servis Bağlantı Sorunu';
        $result['description'] = 'SGK web servislerine erişim sağlanırken zaman aşımı veya bağlantı hatası oluştu. Bu durum genellikle SGK sunucularının geçici olarak yoğun veya bakımda olmasından kaynaklanır.';
        $result['severity'] = 'info';
        $result['solutions'] = [
            '**Tekrar Deneyin:** Birkaç dakika bekledikten sonra sayfayı yenileyerek aramayı veya işlemi tekrarlayın.',
            '**SGK Genel Durumu:** SGK sistemlerinin genel bir kesinti yaşayıp yaşamadığını kontrol edin.'
        ];
    } else {
        $cleanMessage = preg_replace('/^(KRİTİK HATA:\s*|HATA:\s*)/i', '', $errorMessage);
        $result['title'] = 'SGK Servis Hatası';
        $result['description'] = $cleanMessage;
        $result['solutions'] = [
            'Sayfayı yenileyerek işlemi tekrar deneyin.',
            'Sorun devam ederse sistem yöneticinizle iletişime geçerek teknik detay kodunu paylaşın.'
        ];
    }

    return $result;
}

?>

<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>

<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>

<?php
// Ekstra metrikleri hesapla
$enUzunRaporSuresi = 0;
$kisaRaporSayisi = 0;
foreach ($raporlar as $r) {
    if ($r['gun_farki'] > $enUzunRaporSuresi) {
        $enUzunRaporSuresi = $r['gun_farki'];
    }
    if ($r['gun_farki'] < 3) {
        $kisaRaporSayisi++;
    }
}
$uzunRaporSayisi = count($uzunSureliRaporlar);
$toplamRaporSayisi = count($raporlar);
?>

<div class="flex flex-col gap-6 w-full py-2 px-1">
    <!-- Header Bölümü -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Onay Bekleyen Raporlar</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                SGK sistemi üzerinden gelen, onay bekleyen vizite raporlarının takibi ve bildirimi.
            </p>
        </div>
        <div class="flex items-center gap-2 text-nowrap self-start md:self-auto flex-shrink-0">
            <!-- Tarih Seçim Formu -->
            <form action="onay-bekleyen-raporlar" method="POST" class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <i data-lucide="calendar" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" id="rapor_tarihi" name="rapor_tarihi" value="<?php echo htmlspecialchars($tarih); ?>" class="h-9 w-[180px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <button type="submit" name="rapor_ara_buton" class="h-9 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 shadow cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Sorgula</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Hata Mesajları -->
    <?php if ($hataMesaji): 
        $parsedError = parseSgkError($hataMesaji);
        $severityClass = 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300';
        $iconClass = 'alert-triangle';
        if ($parsedError['severity'] === 'warning') {
            $severityClass = 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-300';
            $iconClass = 'alert-circle';
        } elseif ($parsedError['severity'] === 'info') {
            $severityClass = 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/30 dark:bg-blue-950/20 dark:text-blue-300';
            $iconClass = 'info';
        }
    ?>
        <div class="border rounded-xl p-4 flex gap-3 <?php echo $severityClass; ?>">
            <i data-lucide="<?php echo $iconClass; ?>" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <h4 class="font-bold text-sm"><?php echo htmlspecialchars($parsedError['title']); ?></h4>
                <p class="text-xs mt-1 leading-relaxed opacity-90"><?php echo htmlspecialchars($parsedError['description']); ?></p>
                
                <?php if (!empty($parsedError['solutions'])): ?>
                    <div class="mt-3 bg-white/50 dark:bg-black/20 rounded-lg p-3 border border-current/10">
                        <h5 class="text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="wrench" class="w-3.5 h-3.5"></i> Önerilen Çözüm Adımları
                        </h5>
                        <ul class="list-none p-0 m-0 mt-2 flex flex-col gap-1.5 text-xs">
                            <?php foreach ($parsedError['solutions'] as $sol): 
                                $solFormatted = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', htmlspecialchars($sol));
                            ?>
                                <li class="flex items-start gap-1.5">
                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                    <span><?php echo $solFormatted; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <details class="mt-2 text-[11px] font-mono cursor-pointer opacity-85">
                    <summary class="hover:underline">Teknik Detaylar ve Hata Kodu</summary>
                    <div class="mt-1.5 bg-black/5 dark:bg-black/35 rounded p-2 overflow-x-auto max-h-[120px] select-all">
                        <?php echo htmlspecialchars($parsedError['technical_details']); ?>
                    </div>
                </details>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($basariMesaji): ?>
        <div class="border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/30 dark:bg-emerald-950/20 dark:text-emerald-300 rounded-xl p-4 flex gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">İşlem Başarılı</h4>
                <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($basariMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtreler ve Kontroller -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        
        <!-- Sol Taraf: Shadcn Tarzı Filtre Sekmeleri -->
        <div class="inline-flex items-center p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg select-none self-start md:self-auto overflow-x-auto max-w-full">
            <button type="button" id="tab-uzun" onclick="setFilterTab('uzun')" class="tab-btn px-3 py-1.5 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 shadow-sm">
                <span>3 Gün ve Üzeri</span>
                <span class="bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 rounded px-1.5 py-0.5 text-[10px] font-bold" id="badge-count-uzun"><?php echo $uzunRaporSayisi; ?></span>
            </button>
            <button type="button" id="tab-kisa" onclick="setFilterTab('kisa')" class="tab-btn px-3 py-1.5 rounded-md text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-all flex items-center gap-1.5">
                <span>3 Günden Kısa</span>
                <span class="bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded px-1.5 py-0.5 text-[10px] font-semibold" id="badge-count-kisa"><?php echo $kisaRaporSayisi; ?></span>
            </button>
            <button type="button" id="tab-hepsi" onclick="setFilterTab('hepsi')" class="tab-btn px-3 py-1.5 rounded-md text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-all flex items-center gap-1.5">
                <span>Hepsi</span>
                <span class="bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded px-1.5 py-0.5 text-[10px] font-semibold" id="badge-count-hepsi"><?php echo $toplamRaporSayisi; ?></span>
            </button>
        </div>

        <!-- Gizli checkbox ve toplam sayı inputu (rapor_onay.js için geriye dönük uyumluluk) -->
        <div style="display: none;">
            <input id="kisa-rapor-goster-cb" type="checkbox">
            <span id="rapor-sayi-bilgisi"></span>
            <input type="hidden" id="toplam-rapor-sayisi" value="<?php echo $toplamRaporSayisi; ?>">
        </div>

        <!-- Sağ Taraf: Excel / PDF Aktar Butonları -->
        <?php if (!empty($raporlar)): ?>
            <div class="flex items-center gap-2 self-end md:self-auto">
                <button type="button" id="export-excel" class="inline-flex items-center gap-1.5 h-9 px-3 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 rounded-md text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                    <span>Excel'e Aktar</span>
                </button>
                <button type="button" id="export-pdf" class="inline-flex items-center gap-1.5 h-9 px-3 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 rounded-md text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer">
                    <i data-lucide="file-down" class="w-4 h-4 text-rose-600"></i>
                    <span>PDF'e Aktar</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tablo Alanı -->
    <form method="post" class="w-full">
        <div class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full border-collapse text-left" id="onay-bekleyen-table">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[60px]">Sıra</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Sigortalı Bilgileri</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Vaka Türü</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">Başlangıç</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">İşbaşı / Bitiş</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center w-[80px]">Süre (Gün)</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[160px]">Nitelik Durumu</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-right pr-6 w-[280px]">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php if (!empty($raporlar)): ?>
                        <?php $i = 0; foreach ($raporlar as $rapor): $i++; ?>
                            <tr class="rapor-row hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors" data-rapor='<?php echo htmlspecialchars(json_encode($rapor)); ?>' data-gun-farki="<?php echo $rapor['gun_farki']; ?>">
                                <td class="py-3.5 px-4 text-sm font-medium text-zinc-500 dark:text-zinc-400"><?php echo $i; ?></td>
                                <td class="py-3.5 px-4 text-sm">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100 leading-tight"><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5"><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-sm">
                                    <?php
                                    $vakaBadgeClass = 'border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300';
                                    if (stripos($rapor['VAKAADI'], 'ANALIK') !== false) {
                                        $vakaBadgeClass = 'border-pink-200 dark:border-pink-900/30 bg-pink-50 dark:bg-pink-950/20 text-pink-700 dark:text-pink-300';
                                    } elseif (stripos($rapor['VAKAADI'], 'HASTALIK') !== false) {
                                        $vakaBadgeClass = 'border-blue-200 dark:border-blue-900/30 bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300';
                                    } elseif (stripos($rapor['VAKAADI'], 'KAZASI') !== false) {
                                        $vakaBadgeClass = 'border-amber-200 dark:border-amber-900/30 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300';
                                    }
                                    ?>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-all <?php echo $vakaBadgeClass; ?>">
                                        <?php echo htmlspecialchars($rapor['VAKAADI']); ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-sm text-center text-zinc-600 dark:text-zinc-400 font-medium"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></td>
                                <td class="py-3.5 px-4 text-sm text-center text-zinc-600 dark:text-zinc-400 font-medium">
                                    <?php 
                                    $bitis = htmlspecialchars($rapor['ABITTAR']); 
                                    if ($bitis == '0001-01-01' || empty($bitis)) {
                                        echo '<span class="text-zinc-400 dark:text-zinc-600 italic">Belirtilmemiş</span>';
                                    } else {
                                        echo $bitis;
                                    }
                                    ?>
                                </td>
                                <td class="py-3.5 px-4 text-sm text-center font-bold text-zinc-900 dark:text-zinc-50"><?php echo $rapor['gun_farki']; ?></td>
                                
                                <td class="py-3.5 px-4 text-sm">
                                    <select class="nitelik-durumu select2 w-full cursor-pointer">
                                        <option value="0">ÇALIŞMAMIŞTIR</option>
                                        <option value="1">ÇALIŞMIŞTIR</option>
                                    </select>
                                </td>

                                <td class="py-3.5 px-4 text-sm text-right pr-6">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <?php 
                                        $is_future = false;
                                        $is_invalid_date = ($rapor['ABITTAR'] == '0001-01-01' || empty($rapor['ABITTAR']));
                                        try {
                                            $raporBitis = new DateTime($rapor['ABITTAR']);
                                            $raporBitis->setTime(0, 0, 0);
                                            $bugun = new DateTime();
                                            $bugun->setTime(0, 0, 0);
                                            if ($raporBitis >= $bugun || $is_invalid_date) {
                                                $is_future = true;
                                            }
                                        } catch (Exception $e) {
                                            $is_future = true;
                                        }
                                        $disabled_attr = $is_future ? 'disabled title="Rapor süresi henüz dolmadı, bugün bitiyor veya bitiş tarihi belirsiz"' : '';
                                        
                                        $yetkiler = $_SESSION["yetkiler"] ?? "";
                                        $can_approve = ($userRole == "admin" || $userRole == "superadmin" || strpos($yetkiler, "rapor_onay") !== false);
                                        ?>
                                        <?php if ($can_approve): ?>
                                            <button type="button" class="btn-onayla inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-600 dark:hover:bg-emerald-500 rounded-md px-2.5 py-1.5 text-xs font-semibold shadow-sm transition-all cursor-pointer <?php echo $is_future ? 'opacity-40 cursor-not-allowed' : ''; ?>" <?php echo $disabled_attr; ?>>
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Onayla</span>
                                            </button>
                                            <button type="button" class="btn-personel-degil inline-flex items-center gap-1 border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded-md px-2.5 py-1.5 text-xs font-medium transition-all cursor-pointer">
                                                <i data-lucide="user-x" class="w-3.5 h-3.5"></i>    
                                                <span>Değil</span>
                                            </button>
                                            <button type="button" class="btn-kapat inline-flex items-center gap-1 bg-amber-600 hover:bg-amber-700 text-white dark:bg-amber-600 dark:hover:bg-amber-500 rounded-md px-2.5 py-1.5 text-xs font-semibold shadow-sm transition-all cursor-pointer <?php echo $is_future ? 'opacity-40 cursor-not-allowed' : ''; ?>" <?php echo $disabled_attr; ?> data-id="<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>">
                                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                                <span>Kapat</span>
                                            </button>
                                        <?php endif ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <tr id="rapor-yok-mesaji" style="display: <?php echo empty($raporlar) ? 'table-row' : 'none'; ?>;">
                        <td colspan="8" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
                                <span>Listelenecek onay bekleyen rapor bulunamadı.</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages/onaysiz_raporlar/export.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- Custom Script for Shadcn Tab Filtering & PDF/Excel Export -->
<script>
(function () {
let activeTab = 'uzun'; // Varsayılan sekme '3 Gün ve Üzeri'

window.setFilterTab = function setFilterTab(tabName) {
    activeTab = tabName;
    
    // Sekme buton stillerini güncelle (Shadcn style)
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.className = "tab-btn px-3 py-1.5 rounded-md text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-all flex items-center gap-1.5";
    });
    
    const activeBtn = document.getElementById('tab-' + tabName);
    activeBtn.className = "tab-btn px-3 py-1.5 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 shadow-sm";
    
    // Tablo satırlarını filtrele
    let visibleCount = 0;
    const rows = document.querySelectorAll('.rapor-row');
    
    rows.forEach(row => {
        const gunFarki = parseInt(row.getAttribute('data-gun-farki'), 10) || 0;
        let visible = false;
        
        if (tabName === 'hepsi') {
            visible = true;
        } else if (tabName === 'uzun') {
            visible = (gunFarki >= 3);
        } else if (tabName === 'kisa') {
            visible = (gunFarki < 3);
        }
        
        if (visible) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Rapor yok mesajını yönet
    const noReportRow = document.getElementById('rapor-yok-mesaji');
    if (visibleCount === 0) {
        noReportRow.style.display = 'table-row';
    } else {
        noReportRow.style.display = 'none';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Flatpickr initialization
    if (window.flatpickr) {
        flatpickr("#rapor_tarihi", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }

    // Lucide ikonlarını yeniden oluştur
    if (window.lucide) {
        window.lucide.createIcons();
    }

    // Excel & PDF aktarımı tetikleyicileri
    const btnExcel = document.getElementById('export-excel');
    const btnPdf = document.getElementById('export-pdf');
    const raporlarData = <?php echo json_encode($raporlar); ?>;

    if (btnExcel) {
        btnExcel.addEventListener('click', function() {
            exportData('excel');
        });
    }

    if (btnPdf) {
        btnPdf.addEventListener('click', function() {
            exportData('pdf');
        });
    }

    function exportData(format) {
        document.getElementById('export-format').value = format;
        document.getElementById('export-data').value = JSON.stringify(raporlarData);
        document.getElementById('export-form').submit();
    }
    
    // Başlangıç filtrelemesini çalıştır (Varsayılan: uzun)
    setFilterTab('uzun');
});
})();
</script>

<script src="App/Src/rapor_onay.js?v=<?php echo filemtime('App/Src/rapor_onay.js'); ?>"></script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>
