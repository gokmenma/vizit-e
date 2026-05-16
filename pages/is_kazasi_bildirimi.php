<?php

use App\Helper\Security;
require_once __DIR__ . '/../Core/Services/SgkViziteService.php';


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


$hataMesaji = '';
$title = 'İş Kazası Bildirimi';

// ... (Security ve require'lar)
$hataMesaji = '';
$isKazalari = [];
$formGonderildi = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formGonderildi = true;
    try {
        $sgkClient = new SgkViziteService(); // Session'dan bilgileri alacak
        
        // Hangi formun gönderildiğini kontrol et
        if (isset($_POST['tarih_ile_ara'])) {
            if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
                throw new Exception("Lütfen tarih aralığını tam olarak girin.");
            }
            $tarih1 = new DateTime($_POST['tarih1']);
            $tarih2 = new DateTime($_POST['tarih2']);
            $isKazalari = $sgkClient->isKazasiGetirTarihIle($tarih1, $tarih2);
        } elseif (isset($_POST['tc_ile_ara'])) {
            if (empty($_POST['tc_kimlik_no'])) {
                 throw new Exception("Lütfen TC Kimlik Numarasını girin.");
            }
            $tcKimlikNo = $_POST['tc_kimlik_no'];
            $isKazalari = $sgkClient->isKazasiGetirTcIle($tcKimlikNo);

            
        }
    } catch (Exception $e) {
        $hataMesaji = $e->getMessage();
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
<section class="content">
    <div class="container">
        <!-- ARAMA FORMLARI -->
        <div class="row clearfix">
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>TC Kimlik No ile Ara</strong></h2>
                    </div>
                    <div class="body">
                        <form method="post">
                            <label><b>TC Kimlik Numarası</b></label>
                            <div class="form-group">
                                <input type="text" name="tc_kimlik_no"
                                    value="<?php echo isset($_POST['tc_kimlik_no']) ? htmlspecialchars($_POST['tc_kimlik_no']) : ''; ?>"
                                    class="form-control" placeholder="TC Kimlik Numarasını giriniz">
                            </div>
                            <button type="submit" name="tc_ile_ara" class="btn btn-primary btn-round waves-effect">TC
                                Kimlik ile Ara</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Tarih Aralığı ile Ara</strong></h2>
                    </div>
                    <div class="body">
                        <form method="post">
                            <div class="row">
                                <div class="col-sm-6"><label><b>Başlangıç Tarihi</b></label>
                                    <div class="form-group"><input type="date" name="tarih1" class="form-control"
                                            value="<?php echo date('Y-m-01'); ?>"></div>
                                </div>
                                <div class="col-sm-6"><label><b>Bitiş Tarihi</b></label>
                                    <div class="form-group"><input type="date" name="tarih2" class="form-control"
                                            value="<?php echo date('Y-m-d'); ?>"></div>
                                </div>
                            </div>
                            <button type="submit" name="tarih_ile_ara"
                                class="btn btn-primary btn-round waves-effect">Tarihlere Göre Ara</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <small>

                    15 dakika aralık ile ve aynı işyeri için 24 saat içinde 2 sorgu sınırlaması vardır.
                </small>

            </div>
        </div>



        <!-- ARAMA SONUÇLARI -->
        <?php if ($formGonderildi): ?>
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>İş Kazası Bildirim Listesi</strong></h2>
                    </div>
                    <div class="body">
                        <?php if ($hataMesaji): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
                        <?php else: ?>
                        <table class="table table-bordered table-striped table-responsive">
                            <thead>
                                <tr>
                                    <th>Bildirim ID</th>
                                    <th>TC Kimlik No</th>
                                    <th>Provizyon Tarihi</th>
                                    <th>Tesis Adı</th>
                                    <th>İş Kazası Tarihi</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($isKazalari)): ?>
                                <?php foreach ($isKazalari as $kaza): ?>
                                <tr data-bildirim-id="<?php echo htmlspecialchars($kaza['BILDIRIMID']); ?>">
                                    <td><?php echo htmlspecialchars($kaza['BILDIRIMID']); ?></td>
                                    <td><?php echo htmlspecialchars($kaza['TCKIMLIKNO']); ?></td>
                                    <td><?php echo htmlspecialchars($kaza['PROVIZYONTARIHI']); ?></td>
                                    <td><?php echo htmlspecialchars($kaza['TESISADI']); ?></td>
                                    <td><?php echo htmlspecialchars($kaza['ISKAZASITARIHI']); ?></td>
                                    <td><button class="btn btn-warning btn-sm btn-kapat">Okundu İşaretle</button></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Belirtilen kriterlere uygun iş kazası bildirimi
                                        bulunamadı.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Script'ler -->
<?php include 'layouts/vendor-scripts.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-kapat').forEach(button => {
        button.addEventListener('click', function() {
            const satir = this.closest('tr');
            const bildirimId = satir.dataset.bildirimId;

            swal({
                title: "Emin misiniz?",
                text: "Bu bildirim 'okundu' olarak işaretlenecek ve bir sonraki sorguda görünmeyecektir.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Evet, İşaretle!",
                cancelButtonText: "İptal",
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
            }, function(isConfirm) {
                if (!isConfirm) return;

                // API.php'ye isKazasiKapat action'ı ile istek gönder
                fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'isKazasiKapat',
                            bildirimId: bildirimId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            swal("Başarılı!", "Bildirim okundu olarak işaretlendi.",
                                "success");
                            satir.remove();
                        } else {
                            swal("Hata!", data.message, "error");
                        }
                    })
                    .catch(error => swal("Ağ Hatası!", "Sunucuya bağlanılamadı: " +
                        error, "error"));
            });
        });
    });
});
</script>
<?php include 'layouts/foot.php'; ?>