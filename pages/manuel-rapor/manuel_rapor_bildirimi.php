<?php

use App\Helper\Security;


Security::checkUserRole();
Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php'; // Yolunuzu kontrol edin

$hataMesaji = '';
$basariMesaji = '';


// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bildirim_kaydet_buton'])) {
    try {
        // Formdan gelen verileri al ve doğrula
        $tcKimlikNo = $_POST['tc_kimlik_no'];
        $raporBasTarih = new DateTime($_POST['rapor_baslangic_tarihi']);
        $iseBasTarih = new DateTime($_POST['rapor_bitis_tarihi']);
        $nitelik = $_POST['rapor_durumu'];


        $sgkClient = new SgkViziteService();

        //$response = $sgkClient->manuelBildirimGir(
        // $tcKimlikNo, 
        // $raporBasTarih, 
        // $iseBasTarih, 
        // $nitelik);

        // if ($response->sonucKod == '0') {
        //     $basariMesaji = $response->sonucAciklama ?? 'Bildirim başarıyla kaydedildi!';
        // } else {
        //     $hataMesaji = $response->sonucAciklama ?? 'Bildirim kaydedilirken bir hata oluştu.';
        // }
    } catch (Exception $e) {
        $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
    }
}
?>
<!-- Head ve diğer layout include'ları -->
<?php include 'layouts/head.php'; ?>
<?php include 'layouts/preloader.php'; ?>
<?php include 'layouts/topbar.php'; ?>
<?php include 'layouts/navbar.php'; ?>

<!-- ANA İÇERİK BÖLÜMÜ -->
<div class="animate-in flex flex-col gap-6 w-full py-2 px-1">
    <!-- Sayfa Başlığı -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Manuel Rapor Bildirimi</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                SGK sistemine otomatik düşmeyen vizite istirahat raporlarını manuel olarak bildirebilir ve takibini sağlayabilirsiniz.
            </p>
        </div>
    </div>

    <!-- Üst Yönlendirme Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="manuel-rapor-goruntule" class="group border border-zinc-200 dark:border-zinc-800/80 rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm hover:border-zinc-300 dark:hover:border-zinc-700 transition-all text-decoration-none" style="display: block;">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300 flex items-center justify-center group-hover:bg-zinc-900 dark:group-hover:bg-zinc-50 group-hover:text-white dark:group-hover:text-zinc-900 transition-all flex-shrink-0">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="font-bold text-xs text-zinc-900 dark:text-zinc-50 mb-0.5" style="margin: 0;">Manuel Bildirilen Rapor Görüntüleme</h4>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400" style="margin: 0;">Manuel bildirdiğiniz geçmiş rapor kayıtlarını listeleyebilir ve inceleyebilirsiniz.</p>
                </div>
            </div>
        </a>

        <a href="manuel-rapor-guncelleme" class="group border border-zinc-200 dark:border-zinc-800/80 rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm hover:border-zinc-300 dark:hover:border-zinc-700 transition-all text-decoration-none" style="display: block;">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300 flex items-center justify-center group-hover:bg-zinc-900 dark:group-hover:bg-zinc-50 group-hover:text-white dark:group-hover:text-zinc-900 transition-all flex-shrink-0">
                    <i data-lucide="edit-3" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="font-bold text-xs text-zinc-900 dark:text-zinc-50 mb-0.5" style="margin: 0;">Manuel Bildirilen Rapor Güncelleme</h4>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400" style="margin: 0;">Bildirdiğiniz mevcut raporlar üzerinde güncelleme ve değişiklik yapabilirsiniz.</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Bildirim Form Kartı -->
    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm">
        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <i data-lucide="plus-circle" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                Yeni Manuel Rapor Bildirimi Ekle
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Lütfen personel ve rapor detaylarını eksiksiz giriniz.</p>
        </div>

        <?php if ($hataMesaji): ?>
            <div class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3 mb-5 animate-in">
                <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm">Hata!</h4>
                    <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($basariMesaji): ?>
            <div class="border border-green-200 bg-green-50 text-green-800 dark:border-green-900/30 dark:bg-green-950/20 dark:text-green-300 rounded-xl p-4 flex gap-3 mb-5 animate-in">
                <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm">Başarılı!</h4>
                    <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($basariMesaji); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="flex flex-col gap-5 w-full">
            <!-- 3'lü Grid: TC, Sicil No, Ad Soyad -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="tc_kimlik_no">TC Kimlik No</label>
                    <input type="text" id="tc_kimlik_no" name="tc_kimlik_no" class="form-input" placeholder="TC Kimlik No" required maxlength="11" pattern="\d{11}">
                </div>
                <div class="form-group">
                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="sicil_no">Sigorta Sicil No <span class="text-zinc-400 font-normal">(Opsiyonel)</span></label>
                    <input type="text" id="sicil_no" name="sicil_no" class="form-input" placeholder="Sigorta Sicil No (Opsiyonel)">
                </div>
                <div class="form-group">
                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="ad_soyad">Ad Soyad <span class="text-zinc-400 font-normal">(Opsiyonel)</span></label>
                    <input type="text" id="ad_soyad" name="ad_soyad" class="form-input" placeholder="Ad Soyad (Opsiyonel)">
                </div>
            </div>

            <!-- 2'li Grid: Tarihler -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="rapor_baslangic_tarihi">Rapor Başlangıç Tarihi</label>
                    <input type="date" id="rapor_baslangic_tarihi" name="rapor_baslangic_tarihi" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="rapor_bitis_tarihi">Bitiş Tarihi</label>
                    <input type="date" id="rapor_bitis_tarihi" name="rapor_bitis_tarihi" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <!-- Rapor Durumu (Çalıştı / Çalışmadı) Radio'lar -->
            <div class="flex flex-col gap-2">
                <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-50">Rapor Süresinde Durumu</span>
                <div class="flex items-center gap-4 mt-1">
                    <label class="label gap-2 cursor-pointer" style="display: inline-flex; align-items: center; min-height: 0; margin: 0;">
                        <input type="radio" name="rapor_durumu" id="rapor_durumuE" value="H" checked class="input cursor-pointer" style="margin: 0;">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">Çalışmadı</span>
                    </label>
                    <label class="label gap-2 cursor-pointer" style="display: inline-flex; align-items: center; min-height: 0; margin: 0;">
                        <input type="radio" name="rapor_durumu" id="rapor_durumuH" value="E" class="input cursor-pointer" style="margin: 0;">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">Çalıştı</span>
                    </label>
                </div>
            </div>

            <!-- Kaydet Butonu -->
            <div class="border-t border-zinc-100 dark:border-zinc-800/80 pt-4 mt-2 flex justify-end">
                <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
                    <button type="submit" name="bildirim_kaydet_buton" class="btn btn-primary h-9 px-4 flex items-center justify-center gap-1.5 shadow cursor-pointer">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Kaydet ve Bildir</span>
                    </button>
                <?php endif ?>
            </div>
        </form>
    </div>
</div>

<!-- Script include'ları -->
<?php include 'layouts/vendor-scripts.php'; ?>

<style>
    .form-label {
        font-size: 0.8125rem !important;
        font-weight: 500 !important;
        margin-bottom: 0.375rem !important;
        color: var(--foreground) !important;
    }
    .form-input {
        font-size: 0.8125rem !important;
        height: 36px !important;
        padding-top: 0.375rem !important;
        padding-bottom: 0.375rem !important;
        width: 100% !important;
        border-radius: 6px !important;
        border: 1px solid var(--border) !important;
        background: var(--background) !important;
        color: var(--foreground) !important;
        box-sizing: border-box !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .form-input:focus {
        outline: none !important;
        border-color: hsl(var(--primary)) !important;
    }
</style>

<script>
(function() {
    function init() {
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php include 'layouts/foot.php'; ?>