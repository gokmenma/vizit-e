<?php

use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


$title = "Tarihe Göre Rapor Arama";
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
                    <div class="header">
                        <h2><strong>Tarihe Göre</strong> Rapor Arama <small> Sorgulama yapmak için tarih
                                seçiniz!</small> </h2>

                    </div>
                    <form action="onay-bekleyen-raporlar" method="POST">

                        <div class="body">
                        <div class="row d-flex align-items-center">
                                <div class="col-md-2">
                                    <b>Tarih Seçiniz</b>
                                </div>

                                <div class="col-md-4 col-lg-4">
                                    <div class="form-group">

                                        <input type="date" id="rapor_tarihi" name="rapor_tarihi" value="<?php echo date('Y-m-d'); ?>"

                                            class="form-control " placeholder="Rapor Tarihi">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2"></div>

                                <div class="col-lg-10 col-md-10">
                                    <button type="submit" name="rapor_ara_buton"
                                        class="btn btn-primary waves-effect mt-0 text-nowrap">Rapor Ara</button>
                                </div>
                            </div>


                            
                    </form>

                </div>
            </div>
        </div>
    </div>
    </div>
</section>


<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>