<?php $title = 'İletişim Bilgileri'; ?>


<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>


<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>

<?php

use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php';

$hataMesaji = '';

try {
    $sgkClient = new SgkViziteService();
    // Sayfa yüklendiğinde mevcut bilgileri çek
    $mevcutBilgiler = $sgkClient->iletisimBilgileriniGetir();
} catch (Exception $e) {
    $hataMesaji = "Mevcut bilgiler getirilirken bir hata oluştu: " . $e->getMessage();
}
$mevcutEposta = $mevcutBilgiler ? (string)$mevcutBilgiler->eposta : 'Kayıt bulunamadı.';
$mevcutTel = $mevcutBilgiler ? (string)$mevcutBilgiler->tel : 'Kayıt bulunamadı.';
?>


<!-- ANA İÇERİK BÖLÜMÜ -->
<div class="animate-in flex flex-col gap-6 w-full py-2 px-1">
    <!-- Sayfa Başlığı -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">İletişim Bilgileri</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                İşyerinizin SGK sisteminde kayıtlı olan iletişim bilgilerini görüntüleyin ve güncelleyin.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Mevcut Bilgiler Kartı -->
        <div class="lg:col-span-4 card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm flex flex-col gap-5">
            <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                    <i data-lucide="info" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                    Mevcut Kayıtlı Bilgiler
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">İşyerinizin SGK sistemindeki güncel kayıtları.</p>
            </div>
            
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">E-Posta</span>
                    <span id="mevcut-eposta" class="text-sm font-semibold text-zinc-950 dark:text-zinc-50 truncate"><?php echo htmlspecialchars($mevcutEposta); ?></span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Cep Telefonu</span>
                    <span id="mevcut-tel" class="text-sm font-semibold text-zinc-950 dark:text-zinc-50"><?php echo htmlspecialchars($mevcutTel); ?></span>
                </div>
            </div>
        </div>

        <!-- Güncelleme Formu Kartı -->
        <div class="lg:col-span-8 card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm">
            <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                    <i data-lucide="edit-3" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                    İletişim Bilgilerini Güncelleme
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Yeni iletişim bilgilerini girerek SGK sistemine kaydedin.</p>
            </div>

            <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
                <form id="guncelleme-formu" method="post" class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="yeni-eposta">Yeni E-Posta Adresi</label>
                            <input type="email" id="yeni-eposta" name="eposta" class="form-input" placeholder="ornek@firma.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="yeni-tel">Yeni Cep Telefonu</label>
                            <input type="text" id="yeni-tel" name="cepTel" class="form-input" placeholder="05xxxxxxxxx (11 Hane)" maxlength="11">
                        </div>
                    </div>

                    <div class="flex justify-end mt-2">
                        <button type="submit" id="kaydet-buton" class="btn btn-primary h-9 px-4 flex items-center justify-center gap-1.5 shadow cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Bilgileri Kaydet</span>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="border border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/30 dark:bg-blue-950/20 dark:text-blue-300 rounded-xl p-4 flex gap-3">
                    <i data-lucide="info" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-sm">Yetki Sınırlaması</h4>
                        <p class="text-xs mt-1 opacity-90">İletişim bilgilerini güncelleme yetkiniz bulunmamaktadır. Lütfen sistem yöneticinize başvurun.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($hataMesaji): ?>
                <div class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3 mt-4">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-sm">Hata!</h4>
                        <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Vendor Js -->
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
$(document).ready(function() {
    if (window.lucide) {
        lucide.createIcons();
    }
    
    // Form gönderildiğinde çalışacak fonksiyon
    $('#guncelleme-formu').on('submit', function(event) {
        event.preventDefault(); // Formun normal gönderimini engelle

        // Butonu referans alalım
        const $kaydetButon = $('#kaydet-buton');
        
        // Form verilerini al
        const yeniEposta = $('#yeni-eposta').val().trim();
        const yeniTel = $('#yeni-tel').val().trim();

        // Boş kontrolü
        if (yeniEposta === '' && yeniTel === '') {
            Swal.fire('Uyarı!', 'Lütfen en az bir iletişim bilgisi giriniz.', 'warning');
            return;
        }

        // AJAX isteğini başlat
        $.ajax({
            url: 'App/Api/APIiletisim_bilgileri.php', // API endpoint URL'si
            type: 'POST',
            dataType: 'json', // Gelen cevabın JSON olacağını belirt
            data: {
                action: 'iletisimGuncelle',
                eposta: yeniEposta,
                cepTel: yeniTel
            },
            // İstek başlamadan önce çalışır
            beforeSend: function() {
                $kaydetButon.prop('disabled', true).html('<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>Kaydediliyor...</span>');
                if (window.lucide) lucide.createIcons();
            },
            // İstek başarılı olursa çalışır
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Başarılı!', response.message, 'success').then(() => {
                        location.reload(); // Sayfayı yeniden yükle
                    });
                } else {
                    Swal.fire('Hata!', response.message, 'error');
                }
            },
            // İstek başarısız olursa (ağ hatası vb.) çalışır
            error: function(xhr, status, error) {
                Swal.fire('Ağ Hatası!', 'Sunucuya bağlanırken bir sorun oluştu.', 'error');
            },
            // İstek başarılı da olsa başarısız da olsa en sonda çalışır
            complete: function() {
                $kaydetButon.prop('disabled', false).html('<i data-lucide="save" class="w-4 h-4"></i><span>Bilgileri Kaydet</span>');
                if (window.lucide) lucide.createIcons();
            }
        });
    });
});
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>