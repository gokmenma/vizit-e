<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
use App\Helper\Date;
use Core\Services\FileLogger;
use Models\RaporModel;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';

$userRole = $_SESSION["role"] ?? "user";
$raporlar = [];
$hataMesaji = '';
$basariMesaji = '';
$toplamBulunanRaporSayisi = 0;
$tarih = !empty($_REQUEST["rapor_tarihi"]) ? $_REQUEST["rapor_tarihi"] : date('Y-m-d');
$uzunSureliRaporlar = [];

try {
    $sgkClient = new SgkViziteService();
    $raporModel = new RaporModel();

    // SGK'dan onay bekleyen raporları çek (arşivlenmiş olanları otomatik kapatma ve
    // onay/yerel-DB filtrelemesi dahil). Bu mantık SgkViziteService::bekleyenRaporlariGetir()
    // içinde merkezi tutulur; masaüstü panel ve otomatik onay cron'u da aynı metodu kullanır.
    $sonuc = $sgkClient->bekleyenRaporlariGetir(new DateTime($tarih), $raporModel);
    $tumRaporlar = $sonuc['raporlar'];
    $arsivKapatSonucu = $sonuc['arsiv_kapatma'];
    if ($arsivKapatSonucu['kapatilan'] > 0 || !empty($arsivKapatSonucu['hatalar'])) {
        $arsivLogger = new FileLogger(__DIR__ . '/../../logs', 'arsiv_otomatik_kapat');
        $arsivLogger->info('Arşivlenmiş raporlar otomatik kapatıldı (mobil).', [
            'isyeriKodu' => $_SESSION['isyeriKodu'] ?? null,
            'kullanici_id' => $_SESSION['kullanici_id'] ?? null,
            'kapatilan' => $arsivKapatSonucu['kapatilan'],
            'hatalar' => $arsivKapatSonucu['hatalar'],
        ]);
    }

    if (!empty($tumRaporlar)) {
        $islenmisRaporlar = [];
        foreach ($tumRaporlar as $rapor) {
            $gunFarki = 0;
            if (!empty($rapor['POLIKLINIKTAR']) && !empty($rapor['ISBASKONTTAR'])) {
                try {
                    $gunFarki = (new DateTime($rapor['POLIKLINIKTAR']))->diff(new DateTime($rapor['ISBASKONTTAR']))->days;
                } catch (Exception $e) {}
            }

            $rapor['gun_farki'] = $gunFarki;

            if ($gunFarki >= 3) {
                $uzunSureliRaporlar[] = $rapor;
            }

            $islenmisRaporlar[] = $rapor;
            $toplamBulunanRaporSayisi++; 
        }
        $raporlar = $islenmisRaporlar;
    }
} catch (Exception $e) {
    $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
}

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

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Onay Bekleyen Raporlar</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">SGK onay bekleyen vizite raporlarının takibi.</p>
    </div>

    <!-- Date Search Form -->
    <form id="mobile-search-form" method="POST" class="flex items-center gap-2">
        <div class="relative flex items-center flex-1">
            <i data-lucide="calendar" class="absolute left-3 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
            <input type="text" id="rapor_tarihi" name="rapor_tarihi" value="<?php echo htmlspecialchars($tarih); ?>" class="h-9 w-full pl-9 pr-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold shadow-xs">
        </div>
        <button type="submit" name="rapor_ara_buton" class="h-9 px-4 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs cursor-pointer">
            <i data-lucide="search" class="w-3.5 h-3.5"></i>
            <span>Sorgula</span>
        </button>
    </form>

    <!-- Error Logs / Alerts -->
    <?php if ($hataMesaji): ?>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-xl flex gap-2.5 text-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold">Sistem Bağlantı Sorunu</h4>
                <p class="mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Horizontal Category Selector Chips -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
        <button type="button" id="tab-uzun" onclick="setFilterTab('uzun')" class="mobile-tab-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1 bg-zinc-900 text-white dark:bg-zinc-50 dark:text-zinc-950 shadow-sm border border-transparent">
            <span>3 Gün ve Üzeri</span>
            <span class="bg-zinc-700 text-white dark:bg-zinc-200 dark:text-zinc-800 rounded px-1.5 py-0.5 text-[9px] font-bold" id="badge-count-uzun"><?php echo $uzunRaporSayisi; ?></span>
        </button>
        <button type="button" id="tab-kisa" onclick="setFilterTab('kisa')" class="mobile-tab-chip px-3 py-1.5 rounded-full text-xs font-semibold text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:text-zinc-950 transition-all flex items-center gap-1">
            <span>3 Gün Altı</span>
            <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded px-1.5 py-0.5 text-[9px] font-bold" id="badge-count-kisa"><?php echo $kisaRaporSayisi; ?></span>
        </button>
        <button type="button" id="tab-hepsi" onclick="setFilterTab('hepsi')" class="mobile-tab-chip px-3 py-1.5 rounded-full text-xs font-semibold text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:text-zinc-950 transition-all flex items-center gap-1">
            <span>Tümü</span>
            <span class="bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded px-1.5 py-0.5 text-[9px] font-bold" id="badge-count-hepsi"><?php echo $toplamRaporSayisi; ?></span>
        </button>
    </div>

    <!-- Hidden native elements for backward compatibility compatibility with rapor_onay.js -->
    <div style="display: none;">
        <input id="kisa-rapor-goster-cb" type="checkbox">
        <span id="rapor-sayi-bilgisi"></span>
        <input type="hidden" id="toplam-rapor-sayisi" value="<?php echo $toplamRaporSayisi; ?>">
    </div>

    <!-- Mobile Touch Cards List -->
    <div class="flex flex-col gap-3" id="mobile-reports-container">
        <?php if (!empty($raporlar)): ?>
            <?php $i = 0; foreach ($raporlar as $rapor): $i++; 
                $initials = '';
                $nameParts = explode(' ', $rapor['AD'] . ' ' . $rapor['SOYAD']);
                foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');

                $vakaBg = 'rgba(0,0,0,0.03)';
                $vakaColor = 'text-zinc-700 dark:text-zinc-300';
                if (stripos($rapor['VAKAADI'], 'HASTALIK') !== false) {
                    $vakaBg = 'rgba(37, 99, 235, 0.08)';
                    $vakaColor = 'text-blue-600 dark:text-blue-400';
                } elseif (stripos($rapor['VAKAADI'], 'KAZASI') !== false) {
                    $vakaBg = 'rgba(245, 158, 11, 0.08)';
                    $vakaColor = 'text-amber-600 dark:text-amber-400';
                } elseif (stripos($rapor['VAKAADI'], 'ANALIK') !== false) {
                    $vakaBg = 'rgba(236, 72, 153, 0.08)';
                    $vakaColor = 'text-pink-600 dark:text-pink-400';
                }

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
                $can_approve = ($userRole == "admin" || $userRole == "superadmin" || strpos($_SESSION["yetkiler"] ?? "", "rapor_onay") !== false);
            ?>
            <div class="rapor-row p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-3.5 shadow-xs" data-rapor='<?php echo htmlspecialchars(json_encode($rapor)); ?>' data-gun-farki="<?php echo $rapor['gun_farki']; ?>">
                
                <!-- Personel Header -->
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center font-bold text-xs">
                        <?php echo $initials; ?>
                    </div>
                    <div class="flex flex-col text-left">
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100 leading-none"><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></span>
                        <span class="text-[9px] text-zinc-500 font-mono mt-1"><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></span>
                    </div>
                </div>

                <!-- Report Details Grid -->
                <div class="grid grid-cols-2 gap-y-2.5 gap-x-2 border-t border-b border-zinc-100 dark:border-zinc-800/80 py-3 text-[11px] text-zinc-600 dark:text-zinc-400">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[9px] text-zinc-400 font-medium">Vaka Türü</span>
                        <span class="font-bold px-1.5 py-0.5 rounded self-start <?php echo $vakaColor; ?>" style="background: <?php echo $vakaBg; ?>; font-size: 9px;">
                            <?php echo htmlspecialchars($rapor['VAKAADI']); ?>
                        </span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[9px] text-zinc-400 font-medium">Süre</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-100"><?php echo $rapor['gun_farki']; ?> Gün</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[9px] text-zinc-400 font-medium">Poliklinik Tarihi</span>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[9px] text-zinc-400 font-medium">Bitiş / İşbaşı</span>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                            <?php echo ($rapor['ABITTAR'] == '0001-01-01' || empty($rapor['ABITTAR'])) ? '<span class="text-zinc-400 italic">Belirtilmemiş</span>' : htmlspecialchars($rapor['ABITTAR']); ?>
                        </span>
                    </div>
                </div>

                <!-- Action: Nitelik Durumu Selector -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] text-zinc-400 font-medium">Nitelik Durumu</label>
                    <select class="nitelik-durumu w-full p-2 text-xs font-semibold rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200 cursor-pointer">
                        <option value="0">ÇALIŞMAMIŞTIR</option>
                        <option value="1">ÇALIŞMIŞTIR</option>
                    </select>
                </div>

                <!-- Card Actions -->
                <?php if ($can_approve): ?>
                <div class="flex items-center gap-2 mt-1">
                    <button type="button" class="btn-onayla flex-1 h-9 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1 shadow-xs cursor-pointer <?php echo $is_future ? 'opacity-40 cursor-not-allowed' : ''; ?>" <?php echo $disabled_attr; ?>>
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Onayla</span>
                    </button>
                    <button type="button" class="btn-personel-degil h-9 px-3 border border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1 cursor-pointer">
                        <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                        <span>Değil</span>
                    </button>
                    <button type="button" class="btn-kapat h-9 px-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1 shadow-xs cursor-pointer <?php echo $is_future ? 'opacity-40 cursor-not-allowed' : ''; ?>" <?php echo $disabled_attr; ?> data-id="<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>">
                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                        <span>Kapat</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Empty State -->
        <div id="rapor-yok-mesaji" class="p-8 text-center text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs" style="display: <?php echo empty($raporlar) ? 'flex' : 'none'; ?>; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem;">
            <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
            <span class="text-xs font-semibold">Listelenecek onay bekleyen rapor bulunamadı.</span>
        </div>
    </div>
</div>

<script>
(function () {
let activeTab = 'uzun';

window.setFilterTab = function setFilterTab(tabName) {
    activeTab = tabName;
    
    // Reset and set active tabs style
    document.querySelectorAll('.mobile-tab-chip').forEach(btn => {
        btn.className = "mobile-tab-chip px-3 py-1.5 rounded-full text-xs font-semibold text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:text-zinc-950 transition-all flex items-center gap-1";
    });
    
    const activeBtn = document.getElementById('tab-' + tabName);
    if (activeBtn) {
        activeBtn.className = "mobile-tab-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1 bg-zinc-900 text-white dark:bg-zinc-50 dark:text-zinc-950 shadow-sm border border-transparent";
    }
    
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
            row.style.display = 'flex';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const noReportRow = document.getElementById('rapor-yok-mesaji');
    if (noReportRow) {
        if (visibleCount === 0) {
            noReportRow.style.display = 'flex';
        } else {
            noReportRow.style.display = 'none';
        }
    }
};

// Mobile page loaded hooks
(function() {
    if (window.flatpickr) {
        flatpickr("#rapor_tarihi", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
    
    // Initial run
    setFilterTab('uzun');
})();
})();
</script>
<script src="App/Src/rapor_onay.js?v=<?php echo time(); ?>"></script>
