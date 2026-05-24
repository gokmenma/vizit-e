<?php
require_once __DIR__ . '/../../../Core/Services/SgkViziteService.php';
use App\Helper\Security;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$mahsupKayitlari = [];
$hataMesaji = '';
$formGonderildi = false;

$tarih1Str = !empty($_REQUEST['tarih1']) ? $_REQUEST['tarih1'] : date('Y-m-01');
$tarih2Str = !empty($_REQUEST['tarih2']) ? $_REQUEST['tarih2'] : date('Y-m-d');
$isQueried = !empty($_REQUEST['tarih1']) && !empty($_REQUEST['tarih2']);

if ($isQueried) {
    $formGonderildi = true;
    try {
        $sgkClient = new SgkViziteService();
        $tarih1 = new DateTime($tarih1Str);
        $tarih2 = new DateTime($tarih2Str);
        $mahsupKayitlari = $sgkClient->primBorcunaMahsupEdilenleriGetir($tarih1, $tarih2);
    } catch (Exception $e) {
        $hataMesaji = $e->getMessage();
    }
}
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Prim Borcuna Mahsup Edilenler</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">İşveren prim borcuna mahsup edilen ödemelerin takibi.</p>
    </div>

    <!-- Date Search Form -->
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
        <button type="submit" name="sorgula_buton" class="h-9 w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
            <i data-lucide="search" class="w-3.5 h-3.5"></i>
            <span>Tarih Aralığını Sorgula</span>
        </button>
    </form>

    <!-- Error Alerts -->
    <?php if ($hataMesaji): ?>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-xl flex gap-2.5 text-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold">Bağlantı Sorunu</h4>
                <p class="mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mobile Touch Cards List -->
    <div class="flex flex-col gap-3">
        <?php if ($formGonderildi && !$hataMesaji): ?>
            <?php if (!empty($mahsupKayitlari)): ?>
                <?php foreach ($mahsupKayitlari as $kayit): 
                    $initials = '';
                    $nameParts = explode(' ', $kayit['adiSoyadi'] ?? 'P');
                    foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
                    $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                ?>
                <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-3.5 shadow-xs">
                    
                    <!-- Header -->
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                            <?php echo $initials; ?>
                        </div>
                        <div class="flex flex-col text-left min-w-0">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100 leading-none truncate"><?php echo htmlspecialchars($kayit['adiSoyadi'] ?? ''); ?></span>
                            <span class="text-[9px] text-zinc-500 font-mono mt-1"><?php echo htmlspecialchars($kayit['tcKimlikNo'] ?? ''); ?></span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-y-2.5 gap-x-2 border-t border-b border-zinc-100 dark:border-zinc-800/80 py-3 text-[11px] text-zinc-600 dark:text-zinc-400">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">Ödenen Tutar</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400"><?php echo htmlspecialchars($kayit['odenenTutar'] ?? '0'); ?> TL</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">Tahsilat Tutarı</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-100"><?php echo htmlspecialchars($kayit['tahsilat_tutar'] ?? '0'); ?> TL</span>
                        </div>
                        <div class="flex flex-col gap-0.5 col-span-2">
                            <span class="text-[9px] text-zinc-400 font-medium">Ödenek Dönemi</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars(($kayit['odemeBasTar'] ?? '') . ' - ' . ($kayit['odemeBitTar'] ?? '')); ?></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">Mahsup Tarihi</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars($kayit['mahsuplasmaTar'] ?? ''); ?></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[9px] text-zinc-400 font-medium">Tahsilat Dönemi</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars($kayit['primTahsilatDonem'] ?? ''); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs flex flex-col items-center justify-center gap-2">
                    <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
                    <span class="text-xs font-semibold">Tarih aralığında mahsup kaydı bulunamadı.</span>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="p-8 text-center text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs flex flex-col items-center justify-center gap-2">
                <i data-lucide="calendar-search" class="w-8 h-8 opacity-40"></i>
                <span class="text-xs font-semibold">Lütfen sorgulamak istediğiniz tarih aralığını seçin.</span>
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
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    let tarih1 = selectedDates[0];
                    let yil = tarih1.getFullYear();
                    let ay = tarih1.getMonth() + 1;
                    let sonGun = new Date(yil, ay, 0).getDate();

                    let ayStr = String(ay).padStart(2, '0');
                    let gunStr = String(sonGun).padStart(2, '0');
                    let newDate2 = `${yil}-${ayStr}-${gunStr}`;

                    if (window.fp2Instance) {
                        window.fp2Instance.setDate(newDate2, true);
                    }
                }
            }
        });

        const fp2 = flatpickr("#tarih2", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });

        window.fp2Instance = fp2;
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
</script>
