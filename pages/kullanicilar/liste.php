<?php

use App\Helper\Security;
use App\Helper\IsyeriHelper;
use Models\UserModel;


Security::checkLogin();

$title = 'Kullanıcılar';


$kullanici = new UserModel();

$kullanicilar = $kullanici->AltKullanicilar($_SESSION['kullanici_id']);
$altKullaniciSayisi = count($kullanicilar);
$altKullaniciLimiti = $kullanici->getAltKullaniciLimiti($_SESSION['kullanici_id']);

$hataMesaji = $_SESSION['hata'] ?? '';


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
            <!-- Hata mesajlarını göstermek için alert -->
            <div class="col-lg-12 col-md-12 col-sm-12 mt-3">
                <?php if (!empty($hataMesaji)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $hataMesaji; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-lg-10">
                            <h2><strong>Kullanıcı Listesi</strong></h2>
                            <small>Aboneliğiniz için alt kullanıcılar ekleyebilirsiniz.
                                Kullanıcıların işlem yapma yetkileri <strong>yoktur</strong>
                                <p style="padding: 0;margin: 0;font-size: 13px;color: blue;">
                                    En fazla <?php echo $altKullaniciLimiti; ?> alt kullanıcı ekleyebilirsiniz.
                                </p>
                            </small>

                        </div>
                        <div class="col-lg-2">
                            <?php if ($altKullaniciSayisi < $altKullaniciLimiti): ?>
                                <a href="#defaultModal" data-bs-toggle="modal" data-bs-target="#defaultModal"
                                    class="btn btn-raised btn-primary waves-effect float-right"><i
                                        class="zmdi zmdi-arrow-plus"></i>Yeni Ekle</a>
                            <?php endif; ?>

                        </div>

                    </div>
                    <div class="body">
                        <div class="form-container">

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sıra</th>
                                            <th>Kullanıcı Adı</th>
                                            <th>Adı Soyadı</th>
                                            <th>Email</th>
                                            <th>Yetkili Olduğu İşyerleri</th>
                                            <th>Durumu</th>
                                            <th style="width: 220px;" class="text-center">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 0;
                                        foreach ($kullanicilar as $kullanici) {
                                            $i++;
                                            $enc_id = Security::encrypt($kullanici->id);
                                        ?>
                                            <tr>
                                                <td style="width:7%"><?php echo $i; ?></td>

                                                <td>
                                                    <div
                                                        class="d-flex flex-column justify-content-center align-items-start">
                                                        <span
                                                            class="list-name"><?php echo $kullanici->kullanici_adi; ?></span>
                                                    </div>
                                                </td>

                                                <td style="width: 10%;">
                                                    <?php echo $kullanici->adi_soyadi; ?>
                                                </td>
                                                <td><?php echo $kullanici->email; ?></td>
                                                <td>
                                                    <?php
                                                    echo str_replace(',', '<br>', $kullanici->firma_adi);
                                                    ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    if ($kullanici->durum == "Aktif") {
                                                        echo '<span class="badge bg-success cursor-pointer kullanici-durum" data-durum="0" data-kullanici-id="' . $enc_id . '">Aktif</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger cursor-pointer kullanici-durum" data-durum="1" data-kullanici-id="' . $enc_id . '">Pasif</span>';
                                                    }

                                                    ?>
                                                </td>

                                                <td class="text-center" style="width: 220px;">
                                                    <a href="#" data-kullanici-id="<?php echo $enc_id; ?>"
                                                       data-yetkiler="<?php echo htmlspecialchars($kullanici->yetkiler ?? ''); ?>"
                                                        class="btn btn-sm btn-simple btn-round kullanici-duzenle"><i
                                                            class="zmdi zmdi-edit me-1"></i> Düzenle</a>


                                                    <a href="#" data-kullanici-id="<?php echo $enc_id; ?>"
                                                        class="btn btn-sm btn-danger btn-round alt-kullanici-sil">
                                                        <i class="zmdi zmdi-delete me-1"></i>Sil</a>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <!-- Eğer kayıt yoksa yeni ekle butonu koy -->
                                        <?php if (count($kullanicilar) == 0): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <p>Alt kullanıcı bulunmamaktadır. Lütfen yeni alt kullanıcı ekleyin.</p>
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#defaultModal">Yeni Ekle</button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
    textarea.form-control {
        text-align: left;
    }
</style>

<!-- Modal Dialogs ========= -->
<!-- Default Size -->
<div class="modal fade" id="defaultModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="altKullaniciForm">
                <input type="text" class="d-none" name="kullanici_id" id="kullanici_id" value="0">
                <div class="modal-header">
                    <h4 class="title" id="defaultModalLabel">Alt Kullanıcı Bilgileri</h4>
                </div>
                <div class="modal-body">

                    <input id="csrf_token" name="csrf_token" type="hidden" value="">
                    <fieldset>
                        <div class="mb-3">
                            <label class="form-label" for="kullanici_adi">Kullanıcı Adı</label>
                            <input class="form-control" id="kullanici_adi" name="kullanici_adi" required="" type="text"
                                value="">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="adi_soyadi">Adı Soyadı</label>
                            <input class="form-control" id="adi_soyadi" name="adi_soyadi" type="text" value="">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email adresi</label>
                            <input class="form-control" id="email" name="email" required="" type="email"
                                autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="sifre">Giriş Şifresi</label>
                            <input class="form-control" id="sifre" name="sifre" required="" autocomplete="new-password"
                                value="" type="password" value="">
                        </div>
                        <style>
                            .form-control.select2 {
                                margin: 0px !important;
                                padding: 0px !important;
                            }

                            .btn:active,
                            .btn.active,
                            .btn:active:focus,
                            .show>.btn.dropdown-toggle,
                            .show>.btn.dropdown-toggle:focus,
                            .show>.btn.dropdown-toggle:hover {
                                background-color: #22252B !important;
                                color: fff !important;
                            }
                        </style>
                        <div class="mb-3">

                            <label class="form-label" for="sifre">Yetki Verilecek işyerleri</label>
                            <?php echo IsyeriHelper::IsyeriSelect("isyerleri_ids[]") ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">İşlem Yetkileri</label>
                            <div class="checkbox">
                                <input id="yetki_rapor_onay" type="checkbox" name="yetkiler[]" value="rapor_onay">
                                <label for="yetki_rapor_onay">Rapor Onaylama/Kapatma Yetkisi</label>
                            </div>
                            <div class="checkbox">
                                <input id="yetki_manuel_bildirim" type="checkbox" name="yetkiler[]" value="manuel_bildirim">
                                <label for="yetki_manuel_bildirim">Manuel Rapor Bildirimi Yetkisi</label>
                            </div>
                        </div>



                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-simple waves-effect"
                        data-bs-dismiss="modal">KAPAT</button>
                    <button type="button" data-loading="true" data-loading-text="Kaydediliyor..." 
                            class="btn btn-primary waves-effect alt-kullanici-kaydet text-nowrap">KAYDET</button>
                </div>
        </div>
        </form>
    </div>
</div>


<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- sayfanın js kodu -->
<script src="App/Src/kullanici.js?v=<?php echo filemtime('App/Src/kullanici.js'); ?>"></script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>