<?php
require_once 'Core/Services/SgkViziteService.php';
use App\Helper\Security;



$mahsupKayitlari = [];
$hataMesaji = '';
$formGonderildi = false;
$title = 'Prim Borcuna Mahsup Edilen Ödemeler';

Security::checkUserRole();

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


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
            $mahsupKayitlari = $sgkClient->primBorcunaMahsupEdilenleriGetir($tarih1, $tarih2);
        } catch (Exception $e) {
            $hataMesaji = $e->getMessage();
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
<section class="content">
    <div class="container">
        <!-- Sorgulama Formu -->
        <div class="card">
            <div class="header">
                <h2><strong>Prim Borcuna Mahsup Edilen Ödemeleri Sorgula</strong></h2>
            </div>
            <div class="body">
                <form method="post">
                    <div class="row clearfix align-items-end">
                        <div class="col-lg-5 col-md-5">
                            <label><b>Başlangıç Tarihi</b></label>
                            <input type="date" name="tarih1" id="tarih1" class="form-control" value="<?php echo $_POST['tarih1'] ?? date('Y-m-01'); ?>">
                        </div>
                        <div class="col-lg-5 col-md-5">
                            <label><b>Bitiş Tarihi</b></label>
                            <input type="date" name="tarih2" id="tarih2" class="form-control" value="<?php echo $_POST['tarih2'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <button type="submit" name="sorgula_buton" class="btn btn-primary btn-round btn-block">Sorgula</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sonuçlar Bölümü -->
        <?php if ($formGonderildi): ?>
            <div class="card">
                <div class="header">
                    <h2><strong>Mahsup Edilen Ödemeler Listesi</strong></h2>
                </div>
                <div class="body">
                    <?php if ($hataMesaji): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover dataTable js-exportable">
                                <thead>
                                    <tr>
                                        <th>TC Kimlik No</th>
                                        <th>Ad Soyad</th>
                                        <th>Ödenek Dönemi</th>
                                        <th>Ödenen Tutar</th>
                                        <th>Tahsilat Tutarı</th>
                                        <th>Mahsup Tarihi</th>
                                        <th>Prim Tahsilat Dönemi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($mahsupKayitlari)): ?>
                                        <div class="export-buttons float-right mb-3">
                                            <button id="export-excel" class="btn btn-primary btn-simple waves-effect">Excel'e
                                                Aktar</button>
                                            <button id="export-pdf" class="btn btn-primary waves-effect">PDF'e Aktar</button>
                                        </div> <?php endif; ?>
                                    <?php if (!empty($mahsupKayitlari)): ?>
                                        <?php foreach ($mahsupKayitlari as $kayit): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($kayit['tcKimlikNo'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($kayit['adiSoyadi'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars(($kayit['odemeBasTar'] ?? '') . ' - ' . ($kayit['odemeBitTar'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($kayit['odenenTutar'] ?? ''); ?> TL</td>
                                                <td><?php echo htmlspecialchars($kayit['tahsilat_tutar'] ?? ''); ?> TL</td>
                                                <td><?php echo htmlspecialchars($kayit['mahsuplasmaTar'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($kayit['primTahsilatDonem'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Belirtilen kriterlere uygun, prim borcuna mahsup edilmiş ödeme bulunamadı.</td>
                                        </tr>
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
<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages\mahsuplastirma\export_odeme.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Script'leri dahil ediyoruz -->
<?php include 'layouts/vendor-scripts.php'; ?>
<script>
    $(document).ready(function() {
        //Tarih1 alanında seçim yapınca tarih2 alanını o ayın son günü yap
        const tarih1Input = $('#tarih1');
        const tarih2Input = $('#tarih2');

        tarih1Input.on('change', function() {
            let tarih1 = new Date(this.value);
            if (isNaN(tarih1)) return;

            // Ayın son günü hesapla
            let yil = tarih1.getFullYear();
            let ay = tarih1.getMonth() + 1; // Aylar 0’dan başlar
            let sonGun = new Date(yil, ay, 0).getDate();

            // YYYY-MM-DD formatına çevir
            let ayStr = String(ay).padStart(2, '0');
            let gunStr = String(sonGun).padStart(2, '0');
            let tarih2 = `${yil}-${ayStr}-${gunStr}`;

            tarih2Input.val(tarih2);
        });

    });

    document.addEventListener('DOMContentLoaded', function() {
        const btnExcel = document.getElementById('export-excel');
        const btnPdf = document.getElementById('export-pdf');

        // PHP'den gelen raporlar dizisini JavaScript'e aktarıyoruz
        const raporlarData = <?php echo json_encode($mahsupKayitlari); ?>;

        if (btnExcel) {
            btnExcel.addEventListener('click', function() {
                exportData('excel');
            });
        }

        if (btnPdf) {
            btnPdf.addEventListener('click', function() {
                exportData('pdf');
            });
        }

        function exportData(format) {
            // Gizli formun alanlarını doldur
            document.getElementById('export-format').value = format;
            document.getElementById('export-data').value = JSON.stringify(raporlarData);

            // Formu gönder
            document.getElementById('export-form').submit();
        }
    });
</script>

<?php include 'layouts/foot.php'; ?>