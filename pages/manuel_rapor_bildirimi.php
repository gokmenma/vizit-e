<?php

use App\Helper\Security;




Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php'; // Yolunuzu kontrol edin

$hataMesaji = '';
$basariMesaji = '';
$title = 'Manuel Rapor Bildirimi';




// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bildirim_kaydet_buton'])) {
    try {
        // Formdan gelen verileri al ve doğrula
        $tcKimlikNo = $_POST['tc_kimlik_no'];
        $raporBasTarih = new DateTime($_POST['rapor_baslangic_tarihi']);
        $iseBasTarih = new DateTime($_POST['rapor_bitis_tarihi']);
        $nitelik = $_POST['rapor_durumu'] ;


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

<section class="content">
    <div class="container">




        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-lg-10">
                            <h2><strong>Manuel Rapor Bildirimi</strong></h2>
                            <small>Manuel rapor bildirimini girebilirsiniz</small>

                        </div>

                    </div>
                    <?php if ($hataMesaji): ?>
                    <div class="alert alert-danger">
                        <strong>Hata!</strong><?php echo htmlspecialchars($hataMesaji); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($basariMesaji): ?>
                    <div class="alert alert-success">
                        <strong>Başarılı!</strong><?php echo htmlspecialchars($basariMesaji); ?>
                    </div>
                    <?php endif; ?>


                    <div class="body">

                        <form method="post" class="row d-flex justify-content-center align-items-center">

                            <div class="row d-flex align-items-center">
                                <div class="col-md-2">
                                    <b>Tc Kimlik No</b>
                                </div>

                                <div class="col-md-4 col-lg-4">
                                    <div class="form-group">

                                        <input type="text" id="tc_kimlik_no" name="tc_kimlik_no" value=""
                                            class="form-control " placeholder="TC Kimlik No">
                                    </div>
                                </div>
                            </div>
                            <div class="row d-flex align-items-center">
                                <div class="col-md-2">
                                    <b>Sigorta Sicil No</b>
                                </div>
                                <div class="col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <input type="text" id="sicil_no" name="sicil_no" value="" class="form-control "
                                            placeholder="Sigorta Sicil No (Opsiyonel)">
                                    </div>
                                </div>
                            </div>
                            <div class="row d-flex align-items-center">
                                <div class="col-md-2">
                                    <b>Ad Soyad</b>
                                </div>
                                <div class="col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <input type="text" id="ad_soyad" name="ad_soyad" value="" class="form-control "
                                            placeholder="Ad Soyad(Opsiyonel)">
                                    </div>
                                </div>
                            </div>


                            <div class="row d-flex align-items-center">
                                <div class="col-md-2">
                                    <b>Rapor Başlangıç Tarihi</b>
                                </div>
                                <div class="col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <input type="date" id="rapor_baslangic_tarihi" name="rapor_baslangic_tarihi"
                                            value="<?php echo date('Y-m-d'); ?>" class="form-control datetimepicker"
                                            placeholder="Rapor Başlangıç Tarihini Seçiniz...">
                                    </div>
                                </div>
                            </div>
                            <div class="row d-flex align-items-center">
                                <div class="col-md-2">
                                    <b>Bitiş Tarihi</b>
                                </div>
                                <div class="col-md-4 col-lg-4">
                                    <div class="form-group">
                                        <input type="date" id="rapor_bitis_tarihi" name="rapor_bitis_tarihi"
                                            value="<?php echo date('Y-m-d'); ?>" class="form-control datetimepicker"
                                            placeholder="Rapor Bitiş Tarihini Seçiniz...">
                                    </div>
                                </div>
                            </div>
                            <div class="row d-flex align-items-center">
                                <div class="col-md-2">
                                    <b>Rapor Süresinde</b>
                                </div>
                                <div class="col-md-4 col-lg-4 d-flex align-items-center gap-3">
                                    <div class="radio">
                                        <input type="radio" name="rapor_durumu" id="rapor_durumuE" value="H" checked="">
                                        <label for="rapor_durumuE">
                                            Çalışmadı
                                        </label>
                                    </div>
                                    <div class="radio ">
                                        <input type="radio" name="rapor_durumu" id="rapor_durumuH" value="E">

                                        <label for="rapor_durumuH">
                                            Çalıştı
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-2"></div>

                                <div class="col-lg-10 col-md-10">
                                    <button type="submit" name="bildirim_kaydet_buton"
                                        class="btn btn-primary waves-effect mt-0 text-nowrap">Kaydet</button>
                                </div>
                            </div>

                        </form>



                    </div>
                </div>
            </div>
        </div>
</section>

<!-- Script include'ları -->
<?php include 'layouts/vendor-scripts.php'; ?>
<?php include 'layouts/foot.php'; ?>