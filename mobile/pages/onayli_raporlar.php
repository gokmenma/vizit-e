<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
use Models\RaporModel;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';

$title = "Onaylı Raporlar";
$RaporModel = new RaporModel();
$onayliRaporlar = []; 
$hataMesaji = '';

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
        $onayliRaporlar = $sgkClient->onayliRaporlariGetir($tarih1, $tarih2);
        foreach ($onayliRaporlar as $rapor) {
            $medulaRaporId = (string)($rapor['MEDULARAPORID'] ?? '');
            if ($medulaRaporId !== '') {
                $_SESSION['rapor_fisleri'][$medulaRaporId] = $rapor;
            }
        }
    } catch (Exception $e) {
        $hataMesaji = $e->getMessage();
    }
}
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Onaylı Raporlar</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Onaylanmış geçmiş vizite raporlarının listesi.</p>
    </div>

    <!-- Mobile Date Search Form -->
    <form id="mobile-search-form" method="POST" class="p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-3 shadow-xs">
        <div class="grid grid-cols-2 gap-2">
            <div class="relative flex items-center">
                <i data-lucide="calendar" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                <input type="text" id="tarih1" name="tarih1" value="<?php echo htmlspecialchars($tarih1Str); ?>" class="h-9 w-full pl-9 pr-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-xs font-semibold">
            </div>
            <div class="relative flex items-center">
                <i data-lucide="calendar" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                <input type="text" id="tarih2" name="tarih2" value="<?php echo htmlspecialchars($tarih2Str); ?>" class="h-9 w-full pl-9 pr-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-xs font-semibold">
            </div>
        </div>
        <button type="submit" class="h-9 w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
            <i data-lucide="search" class="w-3.5 h-3.5"></i>
            <span>Tarih Aralığını Sorgula</span>
        </button>
    </form>

    <!-- Error Alerts -->
    <?php if ($hataMesaji): ?>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-xl flex gap-2.5 text-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold">SGK Bağlantı Hatası</h4>
                <p class="mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mobile Touch Cards List -->
    <div class="flex flex-col gap-3">
        <?php if ($isQueried): ?>
            <?php if (!empty($onayliRaporlar)): ?>
                <?php
                $i = 0; 
                foreach ($onayliRaporlar as &$rapor):
                    $i++;
                    $onay_turu = $RaporModel->onaylanmaTuru($rapor['MEDULARAPORID'] ?? 0);
                    $rapor['ONAYTURU'] = $onay_turu ?? 'Belirtilmemiş';

                    $initials = '';
                    $nameParts = explode(' ', ($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? ''));
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
                ?>
                <div class="rapor-row p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-3.5 shadow-xs">
                    
                    <!-- Header -->
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                            <?php echo $initials; ?>
                        </div>
                        <div class="flex flex-col text-left min-w-0">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100 leading-none truncate"><?php echo htmlspecialchars(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')); ?></span>
                            <span class="text-[9px] text-zinc-500 font-mono mt-1"><?php echo htmlspecialchars($rapor['TCKIMLIKNO'] ?? ''); ?></span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-y-2.5 gap-x-2 border-t border-b border-zinc-100 dark:border-zinc-800/80 py-3 text-[11px] text-zinc-600 dark:text-zinc-400">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">Vaka Türü</span>
                            <span class="font-bold px-1.5 py-0.5 rounded self-start <?php echo $vakaColor; ?>" style="background: <?php echo $vakaBg; ?>; font-size: 9px;">
                                <?php echo htmlspecialchars($rapor['VAKAADI'] ?? ''); ?>
                            </span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">Onaylama Türü</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-100" style="font-size: 10px;"><?php echo htmlspecialchars($rapor['ONAYTURU'] ?? ''); ?></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">Poliklinik Tarihi</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR'] ?? ''); ?></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">İşbaşı / Kontrol</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars($rapor['ISBASKONTTAR'] ?? ''); ?></span>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="flex items-center gap-2">
                        <a href="rapor-onay-goster.php?id=<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>" target="_blank" class="flex-1 h-9 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-850 dark:text-zinc-200 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-sky-600"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span>Detayı Göster</span>
                        </a>
                        <a href="#" data-id="<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>" class="onay-iptal h-9 px-4 border border-rose-200 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-950/10 text-rose-650 hover:bg-rose-100 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-rose-600"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <span>İptal Et</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; unset($rapor); ?>
            <?php else: ?>
                <div class="p-8 text-center text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs flex flex-col items-center justify-center gap-2">
                    <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
                    <span class="text-xs font-semibold">Tarih aralığında onaylı rapor bulunamadı.</span>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="p-8 text-center text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs flex flex-col items-center justify-center gap-2">
                <i data-lucide="calendar-search" class="w-8 h-8 opacity-40"></i>
                <span class="text-xs font-semibold">Lütfen tarih aralığı seçip sorgulama yapın.</span>
            </div>
        <?php endif; ?>
    </div>
</div>

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
<script src="App/Src/onayli_raporlar.js?v=<?php echo time(); ?>"></script>
