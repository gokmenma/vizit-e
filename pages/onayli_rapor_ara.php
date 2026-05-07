<?php

use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$title = "Onaylı Rapor Ara";
$hataMesaji = '';
$tarih1 = date('Y-m-01');
$tarih2 = date('Y-m-d');


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
                            <h2><strong>Onaylı Rapor Ara</strong></h2>
                            <small>Tarihleri seçerek onaylı raporları görüntüleyin</small>

                        </div>

                    </div>
                    <div class="body d-flex justify-content-center align-items-center">
                        <form method="post" action="onayli-raporlar" class="row d-flex justify-content-center align-items-center">

                            <div class="row">

                                <div class="col-lg-5 col-md-5">
                                    <b>Başlangıç Tarihi</b>
                                    <div class="form-group">
                                       
                                        <input type="date" id="tarih1" name="tarih1" value="<?php echo date('Y-m-01'); ?>"
                                            class="form-control datetimepicker" placeholder="Tarih Seçiniz...">
                                    </div>
                                </div>

                                <div class="col-lg-5 col-md-5">
                                    <b>Bitiş Tarihi</b>
                                    <div class="form-group">
                                         <input type="date" id="tarih2" name="tarih2" value="<?php echo date('Y-m-d'); ?>"
                                            class="form-control datetimepicker" placeholder="Tarih Seçiniz...">
                                    </div>
                                </div>

                                <div class="col-lg-2 col-md-2">
                                    <b>Ara</b>
                                    <button type="submit" class="btn btn-primary waves-effect mt-0 text-nowrap">Rapor Ara</button>
                                </div>
                                
                        </form>



                        <?php if ($hataMesaji): ?>
                        <p style="color:red; text-align:center; padding: 20px;">Hata:
                            <?php echo htmlspecialchars($hataMesaji); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
</section>
<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script>
         //Tarih1 alanında seçim yapınca tarih2 alanını o ayın son günü yap
         const tarih1Input = $('#tarih1');
        const tarih2Input = $('#tarih2');

        tarih1Input.on('change', function () {
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
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>