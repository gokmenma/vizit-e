<?php
use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php';
// ... (Diğer require ve Security kontrolleriniz)

$title = 'Arşivlenmiş Raporlar';
$hataMesaji = '';
$arsivlenmisRaporlar = [];
$formGonderildi = false;

// Form gönderilmişse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sorgula_buton'])) {
    $formGonderildi = true;
    if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
        $hataMesaji = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {
        try {
            $sgkClient = new SgkViziteService(); // Session'dan bilgileri alacak
            $tarih1 = new DateTime($_POST['tarih1']);
            $tarih2 = new DateTime($_POST['tarih2']);
            
            // Yeni metodumuzu çağırıyoruz
            $arsivlenmisRaporlar = $sgkClient->arsivlenmisRaporlariGetir($tarih1, $tarih2);

        } catch (Exception $e) {
            $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
        }
    }
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
<!-- ANA İÇERİK BÖLÜMÜ -->


<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">
        <!-- Sorgulama Formu -->
        <div class="card">
            <div class="header">
                <h2><strong>Arşivlenmiş (Kısa Süreli) Raporları Sorgula</strong></h2>
            </div>
            <div class="body">
                <form method="post">
                    <div class="row clearfix align-items-end">
                        <div class="col-lg-5 col-md-5">
                            <label><b>Başlangıç Tarihi</b></label>
                            <input type="date" name="tarih1" class="form-control" value="<?php echo $_POST['tarih1'] ?? date('Y-m-01'); ?>">
                        </div>
                        <div class="col-lg-5 col-md-5">
                            <label><b>Bitiş Tarihi</b></label>
                            <input type="date" name="tarih2" class="form-control" value="<?php echo $_POST['tarih2'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-lg-2 col-md-2">
                             <button type="submit" name="sorgula_buton" class="btn btn-primary btn-round btn-block">Sorgula</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Arama Sonuçları -->
        <?php if ($formGonderildi): ?>
            <div class="card">
                <div class="header">
                    <h2><strong>Arşivlenmiş Rapor Listesi</strong> <small>(3 günden kısa süreli raporlar)</small></h2>
                </div>
                <div class="body">
                
                <div class="alert alert-info">
                    <strong>Bilgilendirme!</strong>Bu liste, SGK web servisinden anlık olarak çekilen ve 
                    henüz işlemden geçirilmemiş arşivlenmiş raporları göstermektedir. Geçmişte 'okundu' 
                    olarak işaretlenmiş veya manuel olarak kapatılmış eski arşiv kayıtları bu listede görünmeyebilir. 
                    Tüm tarihsel arşive ulaşmak için lütfen SGK'nın resmi e-Vizite uygulamasını kullanınız.
                </div>

                    <?php if ($hataMesaji): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover dataTable js-exportable">
                                <thead>
                                    <tr>
                                        <th>TC Kimlik No</th>
                                        <th>Ad Soyad</th>
                                        <th>Vaka</th>
                                        <th>Poliklinik Tarihi</th>
                                        <th>İş Başı Tarihi</th>
                                        <th>Süre (Gün)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($arsivlenmisRaporlar)): ?>
                                        <?php foreach ($arsivlenmisRaporlar as $rapor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['VAKAADI']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['ISBASKONTTAR']); ?></td>
                                            <td>
                                                <?php
                                                    // Gün farkını tekrar hesaplayıp gösterelim
                                                    $baslangic = new DateTime($rapor['POLIKLINIKTAR']);
                                                    $iseBasi = new DateTime($rapor['ISBASKONTTAR']);
                                                    //echo $baslangic->diff($iseBasi)->days; 
                                                ?>
                                                2 gun ve daha kisa sureli hastalik raporu
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">Belirtilen tarih aralığında arşive kaldırılmış (3 günden kısa) rapor bulunamadı.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<!-- Script include'ları -->
<?php include 'layouts/vendor-scripts.php'; ?>
<?php include 'layouts/foot.php'; ?>