<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$hataMesaji = '';
$arsivlenmisRaporlar = [];

$tarih1Str = !empty($_REQUEST['tarih1']) ? $_REQUEST['tarih1'] : date('Y-m-01');
$tarih2Str = !empty($_REQUEST['tarih2']) ? $_REQUEST['tarih2'] : date('Y-m-d');

$tarih1 = new DateTime($tarih1Str);
$tarih2 = new DateTime($tarih2Str);

$isQueried = !empty($_REQUEST['tarih1']) && !empty($_REQUEST['tarih2']);

if ($isQueried) {
    try {
        $sgkClient = new SgkViziteService(); 
        $arsivlenmisRaporlar = $sgkClient->arsivlenmisRaporlariGetir($tarih1, $tarih2);
    } catch (Exception $e) {
        $hataMesaji = "Mevcut arşiv kayıtları çekilirken hata oluştu: " . $e->getMessage();
    }
}
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Arşivlenmiş Raporlar</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">3 günden kısa süreli (arşivlenmiş) raporların takibi ve listelenmesi.</p>
    </div>

    <!-- Informational Alert Card -->
    <div class="p-3.5 bg-blue-50/50 dark:bg-blue-950/10 border border-blue-200 dark:border-blue-900/30 text-blue-800 dark:text-blue-300 rounded-xl flex gap-2.5 text-[10px] leading-relaxed text-left">
        <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
        <div>
            <span class="font-bold">Bilgilendirme!</span>
            <p class="mt-0.5 opacity-90">SGK'dan alınan ve yerel arşive kaydedilen kısa raporlar birlikte gösterilir. Yeni kayıtlar sonraki sorgularda da listelenir; özellik eklenmeden önce SGK'da kapatılmış eski kayıtlar tekrar alınamayabilir.</p>
        </div>
    </div>

    <!-- Hata Mesajı -->
    <?php if ($hataMesaji): ?>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-xl flex gap-2 text-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <span><?php echo htmlspecialchars($hataMesaji); ?></span>
        </div>
    <?php endif; ?>

    <!-- Query Box -->
    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
        <form action="arsivlenmis-raporlar" method="POST" class="flex flex-col gap-3.5 w-full">
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="tarih1">Başlangıç</label>
                    <input type="text" id="tarih1" name="tarih1" value="<?php echo htmlspecialchars($tarih1Str); ?>" class="h-9 w-full px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold text-center">
                </div>
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="tarih2">Bitiş</label>
                    <input type="text" id="tarih2" name="tarih2" value="<?php echo htmlspecialchars($tarih2Str); ?>" class="h-9 w-full px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold text-center">
                </div>
            </div>
            <button type="submit" class="h-9 w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span>Arşivi Sorgula</span>
            </button>
        </form>
    </div>

    <!-- Results Info Bar -->
    <?php if ($isQueried): ?>
        <div class="text-left font-bold text-xs text-zinc-700 dark:text-zinc-300 mt-1">
            <?php echo $tarih1->format('d.m.Y'); ?> - <?php echo $tarih2->format('d.m.Y'); ?> Arşiv Listesi
        </div>
    <?php endif; ?>

    <!-- Results List -->
    <div class="flex flex-col gap-3">
        <?php if ($isQueried): ?>
            <?php if (!empty($arsivlenmisRaporlar)): ?>
                <?php $i = 0; foreach ($arsivlenmisRaporlar as $rapor): $i++; ?>
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl flex flex-col gap-3 shadow-xs text-left">
                        
                        <!-- Header with index and case status -->
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 font-mono">No: <?php echo $i; ?></span>
                            <?php
                            $vakaBadgeClass = 'border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300';
                            if (stripos($rapor['VAKAADI'], 'ANALIK') !== false) {
                                $vakaBadgeClass = 'border-pink-200 dark:border-pink-900/30 bg-pink-50 dark:bg-pink-950/20 text-pink-700 dark:text-pink-300';
                            } elseif (stripos($rapor['VAKAADI'], 'HASTALIK') !== false) {
                                $vakaBadgeClass = 'border-blue-200 dark:border-blue-900/30 bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300';
                            } elseif (stripos($rapor['VAKAADI'], 'KAZASI') !== false) {
                                $vakaBadgeClass = 'border-amber-200 dark:border-amber-900/30 bg-amber-50 dark:bg-amber-955/20 text-amber-700 dark:text-amber-300';
                            }
                            ?>
                            <span class="inline-flex items-center rounded border px-2 py-0.5 text-[9px] font-bold transition-all <?php echo $vakaBadgeClass; ?>">
                                <?php echo htmlspecialchars($rapor['VAKAADI']); ?>
                            </span>
                        </div>

                        <!-- Patient details -->
                        <div class="flex flex-col">
                            <span class="text-[9px] text-zinc-400 uppercase tracking-wider font-semibold">Sigortalı Bilgileri</span>
                            <span class="font-bold text-zinc-900 dark:text-zinc-55 truncate mt-0.5"><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-mono mt-0.5"><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></span>
                        </div>

                        <!-- Date parameters -->
                        <div class="grid grid-cols-2 gap-3 text-xs leading-normal">
                            <div class="flex flex-col">
                                <span class="text-[9px] text-zinc-400 uppercase tracking-wider font-semibold">Poliklinik Tarihi</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] text-zinc-400 uppercase tracking-wider font-semibold">İşbaşı / Kontrol</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5"><?php echo htmlspecialchars($rapor['ISBASKONTTAR']); ?></span>
                            </div>
                        </div>

                        <!-- Duration Warning -->
                        <div class="p-2.5 bg-zinc-50 dark:bg-zinc-800/80 rounded-xl flex items-center gap-2 border border-zinc-200 dark:border-zinc-850">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-blue-500 flex-shrink-0"></i>
                            <span class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400">2 gün ve daha kısa süreli hastalık raporu</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 text-center border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-white dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">Belirtilen tarih aralığında arşive kaldırılmış rapor bulunamadı.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="p-6 text-center border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-white dark:bg-zinc-900 flex flex-col items-center justify-center gap-2">
                <i data-lucide="calendar-search" style="width: 20px; height: 20px;" class="text-zinc-450 animate-pulse"></i>
                <p class="text-xs text-zinc-500">Lütfen arşivlenmiş raporları görüntülemek için tarih seçip sorgulama yapın.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    if (window.flatpickr) {
        const fp1 = flatpickr("#tarih1", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true,
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    let date1 = selectedDates[0];
                    let year = date1.getFullYear();
                    let month = date1.getMonth() + 1;
                    let lastDay = new Date(year, month, 0).getDate();
                    
                    let monthStr = String(month).padStart(2, '0');
                    let dayStr = String(lastDay).padStart(2, '0');
                    let date2Str = `${year}-${monthStr}-${dayStr}`;
                    
                    if (window.arsivFp2Instance) {
                        window.arsivFp2Instance.setDate(date2Str, true);
                    }
                }
            }
        });

        const fp2 = flatpickr("#tarih2", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });

        window.arsivFp2Instance = fp2;
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
</script>
