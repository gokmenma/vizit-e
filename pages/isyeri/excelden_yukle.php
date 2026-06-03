<?php $title = 'Excelden İşyeri Yükle'; ?>


<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>

<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>


<div id="loading-overlay" style="display: none;" >
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <div class="loading-text ml-2">Yükleniyor, lütfen bekleyiniz...</div>

</div>

<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">

    <?php 
    $alertClass = isset($_SESSION['hata']) ? 'alert-danger' : 'd-none';
    ?>
    <div class="alert alert-description <?php echo $alertClass; ?> " role="alert" id="alert-box">
        
        <span id="alert-message">
            <?php if(isset($_SESSION['hata'])) { ?>
                <?php echo $_SESSION['hata']; ?>
                <?php unset($_SESSION['hata']); // Mesajı gösterdikten sonra temizle ?>
            
            <?php } ?>
        </span>    
    </div>


        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-md-8">
                            <h2><strong>İşyeri</strong> Yükle <small> İşyeri verilerini excel dosyasından yükleyebilirsiniz</small> </h2>
                        </div>
                        <div class="col-md-4 text-end">
                            <style>
                                a > i {
                                    font-size: 14px !important;
                                }
                            </style>
                            <a href="isyerlerim" class="btn btn-primary">
                                <i class="zmdi zmdi-arrow-left mr-1"></i>    
                            İşyerlerime Dön</a>
                            <a href="pages/isyeri/isyeri-yukle.xlsx" class="btn btn-primary btn-simple">
                                <i class="zmdi zmdi-download mr-1"></i>
                            Örnek Dosyayı İndir</a>
                        </div>


                    </div>
                    <form action="#" method="POST">

                        <div class="body">
                            <div class="row d-flex align-items-center">
                              
                                <div class="col-md-4 col-lg-12">
                                    <b>Yüklenecek dosyayı seçiniz!</b>
                                    <div class="form-group">

                                        <input type="file" id="excel_dosya" name="excel_dosya" 
                                            class="form-control " placeholder="Dosya Yükleyiniz!">
                                    </div>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col-lg-12 col-md-12">
                                    <button type="button" id="excel_yukle_buton" name="excel_yukle_buton"
                                        class="btn btn-primary waves-effect mt-0 text-nowrap">Yükle</button>
                                    <button type="button" id="form_temizle_buton" name="form_temizle_buton"
                                        class="btn btn-primary btn-simple waves-effect mt-0 text-nowrap">Temizle</button>
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

<script src="pages/isyeri/upload-from-xls.js?v=<?php echo filemtime("pages/isyeri/upload-from-xls.js")?>"></script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>