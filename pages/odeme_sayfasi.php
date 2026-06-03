<?php

use App\Helper\Security;
use Models\AbonelikPaketModel;
use Models\UserModel;

// Gerekli sınıfları ve modelleri dahil et
$AbonelikPaket = new AbonelikPaketModel();
$UserModel = new UserModel();

// Kullanıcı giriş yapmış mı kontrol et
Security::checkLogin();

// Sayfa başlığını ayarla
$title = "Ödeme Sayfası";

// Değişkenleri başlangıç değeriyle tanımla
$hataMesaji = '';
$paket_id = isset($_GET['paket_id']) ? Security::decrypt($_GET['paket_id']) : null;

// Paket ve kullanıcı bilgilerini veritabanından al
$paket = $AbonelikPaket->find($paket_id);

//paket fiyatını  tr format ile göster
if ($paket) {
    $paket_fiyat = number_format($paket->fiyat, 2, ',', '.');
}

$user = $UserModel->find($_SESSION['kullanici_id']);

// Eğer paket bilgisi alınamadıysa, kullanıcıyı bilgilendir ve işlemi durdur
if (!$paket) {
    $hataMesaji = "Geçerli bir abonelik paketi bulunamadı. Lütfen tekrar deneyin.";
}

?>

<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!-- Topbar'ı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>

<!-- Navbar'ı dahil ediyoruz --><!-- ANA İÇERİK BÖLÜMÜ -->
<div class="max-w-6xl mx-auto py-4 px-2">
    <!-- Sayfa Başlığı ve Yönlendirme Butonu -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-zinc-950 dark:text-zinc-50">Ödeme Sayfası</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Abonelik paketinizi tamamlamak için lütfen aşağıdaki adımları izleyin.</p>
        </div>
        <a href="abonelik-paketleri" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-all shadow-sm active:scale-[0.98]">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Paketlere Dön
        </a>
    </div>

    <!-- Hata veya Bilgi Mesajları -->
    <div class="mb-8">
        <?php if (!empty($hataMesaji)): ?>
            <div class="rounded-xl border border-rose-200 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-950/20 p-4 text-rose-900 dark:text-rose-200 shadow-sm flex items-start gap-3 mb-4">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 dark:text-rose-400 mt-0.5"></i>
                <div class="text-sm font-medium"><?php echo $hataMesaji; ?></div>
            </div>
        <?php endif; ?>

        <div class="rounded-xl border border-blue-100 dark:border-blue-900/30 bg-blue-50/50 dark:bg-blue-950/20 p-5 text-blue-900 dark:text-blue-200 shadow-sm flex items-start gap-4">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400 flex-shrink-0">
                <i data-lucide="info" class="w-5 h-5"></i>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-base leading-none mb-2">Önemli Bilgilendirme!</h4>
                <p class="text-sm leading-relaxed mb-1.5">
                    Ödemenizi aşağıda belirtilen banka hesaplarına <strong>EFT/Havale</strong> yoluyla yapmanız gerekmektedir. 
                    Ödemeyi tamamladıktan sonra sağdaki onay bölümünden talebinizi bize iletin.
                </p>
                <p class="text-xs text-blue-750 dark:text-blue-300/80 font-medium">
                    Yöneticilerimiz ödemenizi kontrol ettikten sonra aboneliğiniz en kısa sürede aktif hale getirecektir.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- SOL TARAF: Paket ve Banka Bilgileri -->
        <div class="lg:col-span-7 flex flex-col gap-8">
            
            <!-- 1. Adım: Paket Bilgileri -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-zinc-950 text-white dark:bg-zinc-50 dark:text-zinc-950 text-xs font-bold">1</span>
                    <h2 class="text-base font-bold text-zinc-950 dark:text-zinc-50">Paket Bilgileri</h2>
                </div>
                <div class="p-6">
                    <?php if ($paket): ?>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-zinc-100 dark:border-zinc-800">
                            <div>
                                <span class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Seçilen Paket</span>
                                <h3 class="text-xl font-extrabold text-zinc-950 dark:text-zinc-50 mt-0.5"><?php echo htmlspecialchars($paket->ad); ?></h3>
                                <?php if (isset($paket->aciklama) && !empty($paket->aciklama)): ?>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?php echo htmlspecialchars($paket->aciklama); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold tracking-wide uppercase">
                                Aktif Sipariş
                            </div>
                        </div>
                        
                        <div class="mt-6 flex flex-col gap-4">
                            <div class="flex items-center justify-between text-sm py-2">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Paket Fiyatı</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100"><?php echo $paket_fiyat; ?> ₺</span>
                            </div>
                            <div class="flex items-center justify-between text-sm py-2 border-t border-zinc-100 dark:border-zinc-800/40">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Abonelik Süresi</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100"><?php echo $paket->sure; ?> Gün</span>
                            </div>
                            
                            <?php if($user->referral_used == "pending"): ?>
                            <div class="flex items-center justify-between text-sm py-2 border-t border-zinc-100 dark:border-zinc-800/40 text-emerald-600 dark:text-emerald-400 font-medium">
                                <span>İndirim Süresi (Referans)</span>
                                <span>30 Gün / <?php echo $paket_fiyat ?> ₺ İndirim</span>
                            </div>
                            <div class="flex items-center justify-between py-4 border-t border-zinc-100 dark:border-zinc-800 mt-2 bg-emerald-50/30 dark:bg-emerald-950/10 px-4 rounded-lg">
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">Toplam Ödenecek Tutar</span>
                                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">0,00 TL</span>
                            </div>
                            <?php else: ?>
                            <div class="flex items-center justify-between py-4 border-t border-zinc-100 dark:border-zinc-800 mt-2 bg-zinc-50 dark:bg-zinc-800/20 px-4 rounded-lg animate-fade-in">
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">Ödenecek Toplam Tutar</span>
                                <span class="text-2xl font-black text-zinc-950 dark:text-zinc-50"><?php echo $paket_fiyat; ?> TL</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Adım: Banka Hesapları -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-zinc-950 text-white dark:bg-zinc-50 dark:text-zinc-950 text-xs font-bold">2</span>
                    <h2 class="text-base font-bold text-zinc-950 dark:text-zinc-50">Banka Hesaplarımız</h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">Lütfen ödemeyi aşağıdaki hesaplardan birine yapın.</p>
                    
                    <div class="flex flex-col gap-6">
                        <!-- Ziraat Bankası -->
                        <div class="bank-account-card p-5 rounded-xl border border-zinc-100 dark:border-zinc-800/50 bg-zinc-50/50 dark:bg-zinc-800/10 hover:border-zinc-300 dark:hover:border-zinc-700 transition-all duration-200 flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-white rounded-xl border border-zinc-100 dark:border-zinc-800 p-2 flex items-center justify-center shadow-sm">
                                    <img src="assets/images/ziraat_logo.png" alt="Ziraat Bankası" class="w-full h-full object-contain">
                                </div>
                                <div>
                                    <h4 class="font-bold text-zinc-950 dark:text-zinc-550 text-base">ZİRAAT BANKASI</h4>
                                    <p class="text-xs text-zinc-450 dark:text-zinc-400 font-medium">Mehmet Ali Gökmen</p>
                                </div>
                            </div>
                            <div class="relative flex items-center">
                                <input type="text" class="w-full pl-4 pr-24 py-3 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-mono font-medium text-zinc-800 dark:text-zinc-250 select-all focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:focus:ring-zinc-100 shadow-inner" id="ziraatIban" value="TR66 0001 0009 8453 7612 3450 06" readonly>
                                <button class="absolute right-2 px-3 py-1.5 text-xs font-bold rounded-md bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-all shadow-sm js-copy-btn flex items-center gap-1.5 active:scale-95" type="button" data-clipboard-target="#ziraatIban">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i> <span>Kopyala</span>
                                </button>
                            </div>
                        </div>

                        <!-- Türkiye İş Bankası -->
                        <div class="bank-account-card p-5 rounded-xl border border-zinc-100 dark:border-zinc-800/50 bg-zinc-50/50 dark:bg-zinc-800/10 hover:border-zinc-300 dark:hover:border-zinc-700 transition-all duration-200 flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-white rounded-xl border border-zinc-100 dark:border-zinc-800 p-2 flex items-center justify-center shadow-sm">
                                    <img src="assets/images/isbank_logo.png" alt="İş Bankası" class="w-full h-full object-contain">
                                </div>
                                <div>
                                    <h4 class="font-bold text-zinc-950 dark:text-zinc-550 text-base">TÜRKİYE İŞ BANKASI</h4>
                                    <p class="text-xs text-zinc-450 dark:text-zinc-400 font-medium">Mehmet Ali Gökmen</p>
                                </div>
                            </div>
                            <div class="relative flex items-center">
                                <input type="text" class="w-full pl-4 pr-24 py-3 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm font-mono font-medium text-zinc-800 dark:text-zinc-250 select-all focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:focus:ring-zinc-100 shadow-inner" id="isbankIban" value="TR60 0006 4000 0014 3201 5946 53" readonly>
                                <button class="absolute right-2 px-3 py-1.5 text-xs font-bold rounded-md bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-all shadow-sm js-copy-btn flex items-center gap-1.5 active:scale-95" type="button" data-clipboard-target="#isbankIban">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i> <span>Kopyala</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ TARAF: Ödeme Onayı -->
        <div class="lg:col-span-5 lg:sticky lg:top-24">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-zinc-950 text-white dark:bg-zinc-50 dark:text-zinc-950 text-xs font-bold">3</span>
                    <h2 class="text-base font-bold text-zinc-950 dark:text-zinc-550">Ödemeyi Onayla</h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                        Yukarıdaki tutarı banka hesaplarımızdan birine gönderdiyseniz, aşağıdaki butona tıklayarak işlemi tamamlayın.
                    </p>
                    
                    <form id="odemeForm" class="mt-6">
                        <input type="hidden" name="paket_id" value="<?php echo htmlspecialchars($paket_id); ?>">
                        <button type="button" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-lg bg-zinc-900 hover:bg-zinc-850 text-white dark:bg-zinc-100 dark:hover:bg-zinc-200 dark:text-zinc-950 font-bold transition-all shadow-md hover:shadow-lg active:scale-[0.98] odeme-yap">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="button-text">Ödemeyi Yaptım, Onay Bekliyorum</span>
                        </button>
                    </form>
                    <div class="flex items-start gap-2 mt-4 text-xs text-zinc-400 dark:text-zinc-500 leading-normal">
                        <i data-lucide="shield-check" class="w-4 h-4 flex-shrink-0 mt-0.5 text-zinc-400"></i>
                        <span>Butona tıkladığınızda talebiniz güvenli bir şekilde yönetici onayına gönderilecektir.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ekstra Stil (CSS) -->
<style>
.bank-account-card {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.js-copy-btn {
    transition: all 0.2s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- Gerekli CDN'ler ve JS Dosyaları -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script src="assets/plugins/bootstrap-notify/bootstrap-notify.js"></script>
<script src="assets/js/pages/ui/notifications.js"></script>
<script src="App/Src/odeme_sayfasi.js"></script> <!-- Özel JS kodlarınız -->

<script>
    $(document).ready(function() {
        var clipboard = new ClipboardJS('.js-copy-btn');

        clipboard.on('success', function(e) {
            showNotification('bg-green', 'IBAN başarıyla kopyalandı!', 'top', 'center', 'animated fadeInDown', 'animated fadeOutUp');
            
            var btn = $(e.trigger);
            var originalHtml = btn.html();
            
            btn.html('<i data-lucide="check" class="w-3.5 h-3.5"></i> <span>Kopyalandı</span>')
               .addClass('bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-500 dark:hover:bg-emerald-450 dark:text-zinc-950')
               .removeClass('bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200');
            
            if (window.lucide) {
                lucide.createIcons();
            }
            
            setTimeout(function() {
                btn.html(originalHtml)
                   .addClass('bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200')
                   .removeClass('bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-500 dark:hover:bg-emerald-450 dark:text-zinc-950');
                if (window.lucide) {
                    lucide.createIcons();
                }
            }, 2000);

            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            showNotification('bg-red', 'Kopyalama sırasında bir hata oluştu.', 'top', 'center', 'animated fadeInDown', 'animated fadeOutUp');
        });
        
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>