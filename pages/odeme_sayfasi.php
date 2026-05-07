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

<!-- Navbar'ı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>

<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">

        <!-- Sayfa Başlığı ve Yönlendirme Butonu -->
        <div class="row clearfix">
            <div class="col-lg-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4><strong>Ödeme Sayfası</strong></h4>
                        <small>Abonelik paketinizi tamamlamak için lütfen aşağıdaki adımları izleyin.</small>
                    </div>
                    <a href="/abonelik-paketleri" class="btn btn-primary btn-simple waves-effect text-nowrap">
                        <i class="zmdi zmdi-arrow-left mr-2"></i>Paketlere Dön
                    </a>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <!-- Hata veya Bilgi Mesajları -->
            <div class="col-lg-12">
                <?php if (!empty($hataMesaji)): ?>
                    <div class="alert alert-danger">
                        <?php echo $hataMesaji; ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <h4 class="alert-heading">Önemli Bilgilendirme!</h4>
                    <p style="margin:0px">Ödemenizi aşağıda belirtilen banka hesaplarına <strong>EFT/Havale</strong> yoluyla yapmanız gerekmektedir. Ödemeyi tamamladıktan sonra sağdaki onay bölümünden talebinizi bize iletin.</p>
                    <p class="mb-0">Yöneticilerimiz ödemenizi kontrol ettikten sonra aboneliğiniz en kısa sürede aktif hale getirecektir.</p>
                </div>
            </div>

            <!-- SOL TARAF: Paket ve Banka Bilgileri -->
            <div class="col-lg-7 col-md-12">
                <!-- 1. Adım: Paket Bilgileri -->
                <div class="card">
                    <div class="header">
                        <h2><strong>1. Adım:</strong> Paket Bilgileri</h2>
                    </div>
                    <div class="body">
                        <?php if ($paket): ?>
                            <h5 class="font-weight-bold">Seçilen Paket: <span class="text-primary"><?php echo htmlspecialchars($paket->ad); ?></span></h5>
                            
                            <?php // Hata kontrolü: $paket nesnesinde 'aciklama' özelliği var mı? ?>
                            <?php if (isset($paket->aciklama) && !empty($paket->aciklama)): ?>
                                <p><?php echo htmlspecialchars($paket->aciklama); ?></p>
                            <?php endif; ?>
                            
                            <table class="table table-bordered mt-3">
                                <tbody>
                                    <tr>
                                        <td>Paket Aylık Fiyatı</td>
                                        <td class="text-right"><strong><?php echo $paket_fiyat; ?> ₺</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Abonelik Süresi</td>
                                        <td class="text-right"><strong>12 Ay</strong></td>
                                    </tr>
                                   
                                    <tr class="bg-light">
                                        <td class="font-weight-bold">Ödenecek Toplam Tutar</td>
                                        <td class="text-right font-weight-bold h4 m-0 text-info"><?php echo number_format($paket->fiyat * 12, 2, ',', '.'); ?> TL</td>
                                    </tr>
                                    <?php if($user->referral_used == "pending"): ?>
                                    <tr>
                                        <td>İndirim Süresi</td>
                                        <td class="text-right"><strong>1 Ay / <?php echo $paket_fiyat ?> ₺</strong></td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td class="font-weight-bold">Toplam Ödenecek Tutar</td>
                                        <td class="text-right font-weight-bold h4 m-0 text-success"><?php echo number_format($paket->fiyat * 11, 2, ',', '.'); ?> TL</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Adım: Banka Hesapları -->
                <div class="card">
                    <div class="header">
                        <h2><strong>2. Adım:</strong> Banka Hesaplarımız</h2>
                    </div>
                    <div class="body">
                        <p>Lütfen ödemeyi aşağıdaki hesaplardan birine yapın.</p>
                        
                        <!-- Ziraat Bankası -->
                        <div class="bank-account-card">
                            <div class="d-flex align-items-center mb-2">
                                <img src="assets/images/ziraat_logo.png" alt="Ziraat Bankası" class="bank-logo mr-3"> <!-- Logo yolunu güncelleyin -->
                                <div>
                                    <h6 class="mb-0"><strong>ZİRAAT BANKASI</strong></h6>
                                    <span>Mehmet Ali Gökmen</span>
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="ziraatIban" value="TR66 0001 0009 8453 7612 3450 06" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary js-copy-btn" type="button" data-clipboard-target="#ziraatIban">
                                        <i class="zmdi zmdi-copy mr-1"></i> Kopyala
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Türkiye İş Bankası -->
                        <div class="bank-account-card">
                             <div class="d-flex align-items-center mb-2">
                                <img src="assets/images/isbank_logo.png" alt="İş Bankası" class="bank-logo mr-3"> <!-- Logo yolunu güncelleyin -->
                                <div>
                                    <h6 class="mb-0"><strong>TÜRKİYE İŞ BANKASI</strong></h6>
                                    <span>Mehmet Ali Gökmen</span>
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="isbankIban" value="TR60 0006 4000 0014 3201 5946 53" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary js-copy-btn" type="button" data-clipboard-target="#isbankIban">
                                        <i class="zmdi zmdi-copy mr-1"></i> Kopyala
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SAĞ TARAF: Ödeme Onayı -->
            <div class="col-lg-5 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>3. Adım:</strong> Ödemeyi Onayla</h2>
                    </div>
                    <div class="body">
                        <p>Yukarıdaki tutarı banka hesaplarımızdan birine gönderdiyseniz, aşağıdaki butona tıklayarak işlemi tamamlayın.</p>
                        
                        <form id="odemeForm" class="mt-4">
                            <input type="hidden" name="paket_id" value="<?php echo htmlspecialchars($paket_id); ?>">
                            <button type="button" class="btn btn-raised btn-primary btn-lg btn-block waves-effect odeme-yap">
                                <i class="zmdi zmdi-check-circle mr-2"></i>Ödemeyi Yaptım, Onay Bekliyorum
                            </button>
                        </form>
                        <small class="text-muted d-block mt-2 text-center">Butona tıkladığınızda talebiniz yönetici onayına gönderilecektir.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Ekstra Stil (CSS) -->
<style>
    .alert-heading {
       margin-bottom: 0px;
       margin-top: 0px;
    }
.bank-account-card {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    transition: box-shadow 0.3s ease;
}
.bank-account-card:not(:last-child) {
    margin-bottom: 15px;
}
.bank-account-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
.bank-account-card .form-control {
    background-color: #f9f9f9;
}
.bank-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
}
.input-group-append .btn {
    margin: 0 !important;
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
            
            var originalText = $(e.trigger).html();
            $(e.trigger).html('<i class="zmdi zmdi-check mr-1"></i> Kopyalandı');
            $(e.trigger).addClass('btn-success').removeClass('btn-outline-secondary');
            
            setTimeout(function() {
                $(e.trigger).html(originalText);
                $(e.trigger).removeClass('btn-success').addClass('btn-outline-secondary');
            }, 2000);

            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            showNotification('bg-red', 'Kopyalama sırasında bir hata oluştu.', 'top', 'center', 'animated fadeInDown', 'animated fadeOutUp');
        });
    });
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>