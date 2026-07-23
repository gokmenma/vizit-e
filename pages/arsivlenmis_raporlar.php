<?php
use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once __DIR__ . '/../Core/Services/SgkViziteService.php';

$title = 'Arşivlenmiş Raporlar';
$hataMesaji = '';
$arsivlenmisRaporlar = [];

$tarih1Str = !empty($_REQUEST['tarih1']) ? $_REQUEST['tarih1'] : date('01.m.Y');
$tarih2Str = !empty($_REQUEST['tarih2']) ? $_REQUEST['tarih2'] : date('d.m.Y');

$tarih1 = DateTime::createFromFormat('d.m.Y', $tarih1Str);
if (!$tarih1) {
    $tarih1 = new DateTime($tarih1Str);
}

$tarih2 = DateTime::createFromFormat('d.m.Y', $tarih2Str);
if (!$tarih2) {
    $tarih2 = new DateTime($tarih2Str);
}

$isQueried = !empty($_REQUEST['tarih1']) && !empty($_REQUEST['tarih2']);

if ($isQueried) {
    try {
        $sgkClient = new SgkViziteService();
        $sgkHatasi = null;
        $arsivlenmisRaporlar = $sgkClient->arsivlenmisRaporlariGetir($tarih1, $tarih2, $sgkHatasi);
        if ($sgkHatasi !== null) {
            $hataMesaji = "SGK canlı sorgusu tamamlanamadı: " . $sgkHatasi;
        }
    } catch (Exception $e) {
        $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
    }
} else {
    $arsivlenmisRaporlar = [];
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

<div class="flex flex-col gap-6 w-full py-2 px-1">
    <!-- Header Bölümü -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Arşivlenmiş Raporlar</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                3 günden kısa süreli (arşivlenmiş) raporların takibi ve listelenmesi.
            </p>
        </div>
        <div class="flex items-center gap-2 text-nowrap self-start md:self-auto flex-shrink-0">
            <!-- Tarih Seçim Formu (Header İçinde) -->
            <form action="arsivlenmis-raporlar" method="POST" class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <i data-lucide="calendar" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" id="tarih1" name="tarih1" value="<?php echo htmlspecialchars($tarih1->format('d.m.Y')); ?>" class="h-9 w-[120px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <span class="text-zinc-400 dark:text-zinc-600 text-xs font-semibold">-</span>
                <div class="relative flex items-center">
                    <i data-lucide="calendar" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" id="tarih2" name="tarih2" value="<?php echo htmlspecialchars($tarih2->format('d.m.Y')); ?>" class="h-9 w-[120px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <button type="submit" class="h-9 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 shadow cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Sorgula</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Bilgilendirme Alerter -->
    <div class="border border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/30 dark:bg-blue-950/20 dark:text-blue-300 rounded-xl p-4 flex gap-3 shadow-sm">
        <i data-lucide="info" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
        <div>
            <h4 class="font-bold text-sm">Bilgilendirme!</h4>
            <p class="text-xs mt-1 leading-relaxed opacity-95">
                Bu liste, SGK web servisinden alınan arşivlenmiş raporlarla sistemin yerel arşivine kaydedilen raporları birlikte göstermektedir. Sistem tarafından kapatılan yeni arşiv kayıtları sonraki sorgularda da listelenmeye devam eder. Yerel arşiv özelliği eklenmeden önce SGK'da kapatılmış eski kayıtlar web servisinden tekrar alınamayabilir.
            </p>
        </div>
    </div>

    <!-- Hata Mesajları -->
    <?php if ($hataMesaji): ?>
        <div class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Hata Oluştu</h4>
                <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtreler ve Kontroller -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        <div>
            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                <?php if ($isQueried): ?>
                    <strong><?php echo $tarih1->format('d.m.Y'); ?></strong> ile <strong><?php echo $tarih2->format('d.m.Y'); ?></strong> tarihleri arası listelenmektedir.
                <?php else: ?>
                    Arşivlenmiş Rapor Listesi
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- Tablo Alanı -->
    <form method="post" class="w-full">
        <div class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full border-collapse text-left" id="arsivlenmis-rapor-table">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[60px] text-center">Sıra</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Sigortalı Bilgileri</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">Vaka Türü</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">Poliklinik Tarihi</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">İşbaşı / Kontrol</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center pr-6">Rapor Süresi Bilgisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php if ($isQueried): ?>
                        <?php if (!empty($arsivlenmisRaporlar)): ?>
                            <?php $i = 0; foreach ($arsivlenmisRaporlar as $rapor): $i++; ?>
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="py-3.5 px-4 text-sm text-center font-medium text-zinc-500 dark:text-zinc-400"><?php echo $i; ?></td>
                                    <td class="py-3.5 px-4 text-sm">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-zinc-900 dark:text-zinc-100 leading-tight"><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5"><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-sm text-center">
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
                                    <td class="py-3.5 px-4 text-sm text-center text-zinc-600 dark:text-zinc-400 font-medium"><?php echo htmlspecialchars($rapor['ISBASKONTTAR']); ?></td>
                                    <td class="py-3.5 px-4 text-sm text-center text-zinc-500 dark:text-zinc-400 font-medium pr-6">
                                        <span class="inline-flex items-center gap-1 text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800/80 px-2 py-1 rounded text-xs border border-zinc-200 dark:border-zinc-850">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 text-blue-500"></i>
                                            <span>2 gün ve daha kısa süreli hastalık raporu</span>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
                                        <span>
                                            <?php echo $hataMesaji
                                                ? 'SGK sorgusu tamamlanamadığı için yeni arşiv kayıtları kontrol edilemedi.'
                                                : 'Belirtilen tarih aralığında arşive kaldırılmış (3 günden kısa) rapor bulunamadı.'; ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2 animate-pulse">
                                    <i data-lucide="calendar-search" class="w-8 h-8 opacity-45"></i>
                                    <span>Lütfen arşivlenmiş raporları görüntülemek için tarih seçip sorgulama yapın.</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Script include'ları -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script>
(function() {
    if (window.flatpickr) {
        const fp1 = flatpickr("#tarih1", {
            locale: "tr",
            dateFormat: "d.m.Y",
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    let tarih1 = selectedDates[0];
                    let yil = tarih1.getFullYear();
                    let ay = tarih1.getMonth() + 1;
                    let sonGun = new Date(yil, ay, 0).getDate();

                    let ayStr = String(ay).padStart(2, '0');
                    let gunStr = String(sonGun).padStart(2, '0');
                    let newDate2 = `${gunStr}.${ayStr}.${yil}`;

                    if (window.fp2Instance) {
                        window.fp2Instance.setDate(newDate2, true);
                    }
                }
            }
        });

        const fp2 = flatpickr("#tarih2", {
            locale: "tr",
            dateFormat: "d.m.Y",
            allowInput: true
        });

        window.fp2Instance = fp2;
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
</script>

<?php include 'layouts/foot.php'; ?>
