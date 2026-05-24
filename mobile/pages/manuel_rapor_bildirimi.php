<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;

Security::checkUserRole();
Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php';

$hataMesaji = '';
$basariMesaji = '';
$userRole = $_SESSION["role"] ?? "user";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bildirim_kaydet_buton'])) {
    try {
        $tcKimlikNo = $_POST['tc_kimlik_no'];
        $raporBasTarih = new DateTime($_POST['rapor_baslangic_tarihi']);
        $iseBasTarih = new DateTime($_POST['rapor_bitis_tarihi']);
        $nitelik = $_POST['rapor_durumu'];

        $sgkClient = new SgkViziteService();
        // Backend notification insertion logic goes here when needed
    } catch (Exception $e) {
        $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
    }
}
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Manuel Rapor Bildirimi</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Vizite istirahat raporlarını manuel olarak bildirin.</p>
    </div>

    <!-- Mobile Sub-navigation Quick Chips -->
    <div class="grid grid-cols-2 gap-2">
        <a href="#manuel-rapor-goruntule" class="p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center gap-2.5 shadow-xs text-decoration-none">
            <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-sky-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="flex flex-col text-left">
                <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-100 leading-tight">Rapor Listesi</span>
                <span class="text-[8px] text-zinc-400 mt-0.5">Görüntüle</span>
            </div>
        </a>

        <a href="#manuel-rapor-guncelleme" class="p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center gap-2.5 shadow-xs text-decoration-none">
            <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="flex flex-col text-left">
                <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-100 leading-tight">Rapor Güncelle</span>
                <span class="text-[8px] text-zinc-400 mt-0.5">Kayıt Düzenle</span>
            </div>
        </a>
    </div>

    <!-- Form Panel Card -->
    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-4 shadow-xs">
        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-3">
            <h3 class="text-xs font-bold text-zinc-800 dark:text-zinc-100 flex items-center gap-1.5">
                <i data-lucide="plus-circle" style="width:16px;height:16px;" class="text-zinc-600"></i>
                Yeni Manuel Bildirim
            </h3>
        </div>

        <!-- Alert messages -->
        <?php if ($hataMesaji): ?>
            <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-lg flex gap-2 text-xs">
                <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <span><?php echo htmlspecialchars($hataMesaji); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($basariMesaji): ?>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-lg flex gap-2 text-xs">
                <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <span><?php echo htmlspecialchars($basariMesaji); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="flex flex-col gap-4 w-full">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider" for="tc_kimlik_no">TC Kimlik No</label>
                <input type="text" id="tc_kimlik_no" name="tc_kimlik_no" class="h-9 px-3 text-xs font-semibold rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200" placeholder="TC Kimlik No" required maxlength="11" pattern="\d{11}">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider" for="sicil_no">Sigorta Sicil No <span class="text-zinc-400 font-normal">(Opsiyonel)</span></label>
                <input type="text" id="sicil_no" name="sicil_no" class="h-9 px-3 text-xs font-semibold rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200" placeholder="Sigorta Sicil No (Opsiyonel)">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider" for="ad_soyad">Ad Soyad <span class="text-zinc-400 font-normal">(Opsiyonel)</span></label>
                <input type="text" id="ad_soyad" name="ad_soyad" class="h-9 px-3 text-xs font-semibold rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200" placeholder="Ad Soyad (Opsiyonel)">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider" for="rapor_baslangic_tarihi">Başlangıç Tarihi</label>
                    <input type="text" id="rapor_baslangic_tarihi" name="rapor_baslangic_tarihi" class="h-9 px-3 text-xs font-semibold rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider" for="rapor_bitis_tarihi">Bitiş Tarihi</label>
                    <input type="text" id="rapor_bitis_tarihi" name="rapor_bitis_tarihi" class="h-9 px-3 text-xs font-semibold rounded-lg bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-200" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <!-- Rapor Durumu -->
            <div class="flex flex-col gap-1.5 mt-1">
                <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Rapor Süresinde Durumu</span>
                <div class="flex items-center gap-6 mt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="radio" name="rapor_durumu" id="rapor_durumuE" value="H" checked class="w-4 h-4 text-primary bg-zinc-100 border-zinc-300">
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Çalışmadı</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="radio" name="rapor_durumu" id="rapor_durumuH" value="E" class="w-4 h-4 text-primary bg-zinc-100 border-zinc-300">
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Çalıştı</span>
                    </label>
                </div>
            </div>

            <!-- Save Button -->
            <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
            <div class="border-t border-zinc-100 dark:border-zinc-800/80 pt-3 mt-1 flex">
                <button type="submit" name="bildirim_kaydet_buton" class="h-9 w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Kaydet ve Bildir</span>
                </button>
            </div>
            <?php endif ?>
        </form>
    </div>
</div>

<script>
(function() {
    if (window.flatpickr) {
        flatpickr("#rapor_baslangic_tarihi", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });
        flatpickr("#rapor_bitis_tarihi", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
</script>
