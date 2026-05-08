<?php

use App\Helper\Security;
use Models\RaporModel;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


require_once 'Core/Services/SgkViziteService.php';

$title = "Onaylı Raporlar";

$RaporModel = new RaporModel();

$onayliRaporlar = []; // Başlangıçta boş bir dizi
$hataMesaji = '';

// Form gönderilmişse, metodu çağır
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tarihleri kontrol et boş ise uyarı ver
    if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
        $hataMesaji = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {

        try {
            $sgkClient = new SgkViziteService();

            $tarih1 = new DateTime($_POST['tarih1']);
            $tarih2 = new DateTime($_POST['tarih2']);

            $onayliRaporlar = $sgkClient->onayliRaporlariGetir($tarih1, $tarih2);
            // echo "<pre>";
            // print_r($onayliRaporlar);
            // echo "</pre>";
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
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-lg-10">
                            <h2><strong>Onaylanmış Rapor Listesi</strong></h2>
                            <?php if (isset($tarih1) && isset($tarih2)): ?>
                                <small class="text-muted" style="font-size: 14px;">
                                    <strong><?php echo $tarih1->format('d.m.Y'); ?></strong> - <strong><?php echo $tarih2->format('d.m.Y'); ?></strong> tarihleri arası listelenmektedir.
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-2">
                            <a href="onayli-rapor-ara"
                                class="btn btn-raised btn-primary btn-round waves-effect float-right"><i
                                    class="zmdi zmdi-arrow-back"></i> Geri Dön</a>
                        </div>

                    </div>
                    <div class="body">

                        <?php if ($hataMesaji): ?>
                            <p style="color:red; text-align:center; padding: 20px;">Hata:
                                <?php echo htmlspecialchars($hataMesaji); ?>
                            </p>
                        <?php else: ?>
                            <form method="post">
                                <?php if (!empty($onayliRaporlar)): ?>
                                    <div class="export-buttons float-right mb-3">
                                        <button type="button" id="export-excel" class="btn btn-primary btn-simple waves-effect"><i class="zmdi zmdi-file-text"></i> Excel'e Aktar</button>
                                        <button type="button" id="export-pdf" class="btn btn-primary waves-effect"><i class="zmdi zmdi-collection-pdf"></i> PDF'e Aktar</button>
                                    </div>
                                <?php endif; ?>
                                <div class="table-responsive d-none d-md-block">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Sıra</th>
                                                <th style="width: 10%;">Tc Kimlik No</th>
                                                <th style="width: 40%;">Ad Soyad</th>
                                                <th class="text-center" style="width: 10%;">Vaka</th>
                                                <th class="text-center" style="width: 10%;">Onay Türü</th>
                                                <th class="text-center" style="width: 10%;">Poliklinik Tarihi</th>
                                                <th class="text-center" style="width: 10%;">İşbaşı / Kontrol Tarihi</th>
                                                <th class="text-center" style="width: 10%;">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($onayliRaporlar)): ?>
                                                <?php
                                                $i = 0; // Sıra numarasını başlatıyoruz
                                                foreach ($onayliRaporlar as &$rapor):
                                                    $i++;

                                                    $onay_turu = $RaporModel->onaylanmaTuru($rapor['MEDULARAPORID'] ?? 0);
                                                    $rapor['ONAYTURU'] = $onay_turu ?? 'Belirtilmemiş';

                                                ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $i; ?></td>

                                                        <!-- Bu veri genelde 1 olur -->
                                                        <td><?php echo htmlspecialchars($rapor['TCKIMLIKNO'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')); ?>
                                                        </td>
                                                        <td class="text-center"><?php echo htmlspecialchars($rapor['VAKAADI'] ?? ''); ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php echo htmlspecialchars($rapor['ONAYTURU'] ?? ''); ?></td>

                                                        <td class="text-center">
                                                            <?php echo htmlspecialchars($rapor['POLIKLINIKTAR'] ?? ''); ?></td>
                                                        <td class="text-center">
                                                            <?php echo htmlspecialchars($rapor['ISBASKONTTAR'] ?? ''); ?></td>
                                                        <td class="text-center d-flex">

                                                            <!-- Detay Butonu -->
                                                            <!-- <a href="onayli-rapor-detay?rapor_id=<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>"
                                                                class="btn btn-sm btn-primary btn-round ">Detay</a> -->
                                                            <!-- Onay İptal Butonu -->
                                                            <a href="#"
                                                                data-id=<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>
                                                                class="btn btn-sm btn-danger btn-simple btn-round text-nowrap onay-iptal">Onay
                                                                İptal</a>
                                                            <!-- Raporu Göster Butonu -->
                                                            <a href="rapor-onay-goster?id=<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>"
                                                                target="_blank" class="btn btn-info btn-sm btn-round text-nowrap">Fişi
                                                                Göster</a>
                                                        </td>

                                                    </tr>
                                                <?php endforeach; 
                                                unset($rapor);
                                                ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" style="text-align:center;">Belirtilen kriterlere uygun
                                                        onaylanmış rapor
                                                        bulunamadı.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- MOBİL GÖRÜNÜM (Kart Yapısı) -->
                                <div class="mobile-rapor-container d-md-none d-block">
                                    <?php if (empty($onayliRaporlar)): ?>
                                        <div class="alert alert-info text-center">Kayıtlı onaylı rapor bulunamadı.</div>
                                    <?php else: ?>
                                        <?php foreach ($onayliRaporlar as $rapor): ?>
                                            <div class="mobile-rapor-card mb-3 p-3 shadow-sm border-radius-10 bg-white border">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 fw-bold text-primary"><?php echo htmlspecialchars(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')); ?></h6>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($rapor['VAKAADI'] ?? ''); ?></span>
                                                </div>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">TC Kimlik No</small>
                                                        <span class="fw-500"><?php echo htmlspecialchars($rapor['TCKIMLIKNO'] ?? ''); ?></span>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted d-block">Onay Türü</small>
                                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($rapor['ONAYTURU'] ?? ''); ?></span>
                                                    </div>
                                                </div>

                                                <div class="row mb-3 bg-light p-2 mx-0 rounded">
                                                    <div class="col-6 border-end">
                                                        <small class="text-muted d-block text-center">Poliklinik</small>
                                                        <div class="text-center fw-bold small"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR'] ?? ''); ?></div>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block text-center">İşbaşı/Kontrol</small>
                                                        <div class="text-center fw-bold small text-success"><?php echo htmlspecialchars($rapor['ISBASKONTTAR'] ?? ''); ?></div>
                                                    </div>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <a href="rapor-onay-goster?id=<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>" target="_blank" class="btn btn-info w-50 py-2 waves-effect">
                                                        <i class="zmdi zmdi-eye me-1"></i> Fişi Göster
                                                    </a>
                                                    <a href="#" data-id="<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>" class="btn btn-outline-danger w-50 py-2 waves-effect onay-iptal">
                                                        <i class="zmdi zmdi-close-circle me-1"></i> İptal
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
</section>
<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages/onayli-raporlar/export.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnExcel = document.getElementById('export-excel');
    const btnPdf = document.getElementById('export-pdf');

    // PHP'den gelen onaylı raporlar dizisini JavaScript'e aktarıyoruz
    const raporlarData = <?php echo json_encode($onayliRaporlar); ?>;

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
        document.getElementById('export-format').value = format;
        document.getElementById('export-data').value = JSON.stringify(raporlarData);
        document.getElementById('export-form').submit();
    }
});
</script>

<script src="App/Src/onayli_raporlar.js"></script>


<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>