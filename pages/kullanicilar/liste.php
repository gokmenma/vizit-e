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
                        <div class="col-12 col-md-9 col-lg-10">
                            <h2><strong>Kullanıcı Listesi</strong></h2>
                            <small class="d-block mb-1">Aboneliğiniz için alt kullanıcılar ekleyebilirsiniz.
                                Kullanıcıların işlem yapma yetkileri <strong>yoktur</strong>
                            </small>
                            <p class="text-primary mb-2 mb-md-0" style="padding: 0;margin: 0;font-size: 13px;font-weight: 600;">
                                En fazla <?php echo $altKullaniciLimiti; ?> alt kullanıcı ekleyebilirsiniz.
                            </p>
                        </div>
                        <div class="col-12 col-md-3 col-lg-2 text-md-end text-start mt-2 mt-md-0">
                            <?php if ($altKullaniciSayisi < $altKullaniciLimiti): ?>
                                <a href="#defaultModal" data-bs-toggle="modal" data-bs-target="#defaultModal"
                                    class="btn btn-raised btn-primary waves-effect w-100 w-md-auto m-0"><i
                                        class="zmdi zmdi-plus"></i> Yeni Ekle</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="body">
                        <div class="form-container">

                            <!-- DESKTOP GÖRÜNÜMÜ: TABLO -->
                            <div class="table-responsive d-none d-md-block">
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

                            <!-- MOBİL GÖRÜNÜMÜ: KARTLAR -->
                            <div class="mobile-kullanici-container d-md-none d-block">
                                <?php if ($altKullaniciSayisi == 0): ?>
                                    <div class="text-center p-4 bg-white rounded shadow-sm text-muted mb-3" style="border: 1px dashed #dee2e6;">
                                        <p class="mb-2">Alt kullanıcı bulunmamaktadır. Lütfen yeni alt kullanıcı ekleyin.</p>
                                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal"
                                            data-bs-target="#defaultModal"><i class="zmdi zmdi-plus"></i> Yeni Ekle</button>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $mi = 0;
                                    foreach ($kullanicilar as $kullanici): 
                                        $mi++;
                                        $enc_id = Security::encrypt($kullanici->id);
                                        $yetkiler = $kullanici->yetkiler ?? '';
                                        
                                        // Status styling
                                        $status_badge_class = ($kullanici->durum == "Aktif") ? 'bg-success' : 'bg-danger';
                                        $status_durum_val = ($kullanici->durum == "Aktif") ? '0' : '1';
                                    ?>
                                        <div class="card mobile-kullanici-card p-3 mb-3 border-0 shadow-sm" style="border-radius: 12px; background: #fff; border: 1px solid #eaeaea !important; box-shadow: 0 4px 15px rgba(0,0,0,0.04) !important;">
                                            
                                            <!-- Header: Name and Status -->
                                            <div class="d-flex justify-content-between align-items-start mb-2 pb-2" style="border-bottom: 1px dashed #f1f5f9;">
                                                <div>
                                                    <h6 class="font-weight-bold mb-0" style="font-size: 1.05rem; color: #2c3e50; font-weight: 700;"><?php echo htmlspecialchars($kullanici->adi_soyadi); ?></h6>
                                                    <span class="text-muted" style="font-size: 0.8rem;">@<?php echo htmlspecialchars($kullanici->kullanici_adi); ?></span>
                                                </div>
                                                <span class="badge <?php echo $status_badge_class; ?> cursor-pointer kullanici-durum px-2.5 py-1.5 rounded-pill" 
                                                      data-durum="<?php echo $status_durum_val; ?>" 
                                                      data-kullanici-id="<?php echo $enc_id; ?>" 
                                                      style="font-size: 0.75rem; font-weight: 600;">
                                                    <?php echo $kullanici->durum; ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Email field -->
                                            <div class="mb-2.5 d-flex align-items-center gap-2" style="font-size: 0.85rem; color: #4b5563;">
                                                <i class="zmdi zmdi-email text-muted" style="font-size: 1rem;"></i>
                                                <span><?php echo htmlspecialchars($kullanici->email); ?></span>
                                            </div>

                                            <!-- Authorized Workplaces -->
                                            <div class="mb-3">
                                                <div class="text-muted small font-weight-bold mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.02em;">Yetkili Olduğu İşyerleri</div>
                                                <div class="p-2.5 rounded" style="background-color: #f8fafc; border: 1px solid #f1f5f9; font-size: 0.825rem; color: #334155; max-height: 120px; overflow-y: auto; line-height: 1.45;">
                                                    <?php
                                                    if (!empty($kullanici->firma_adi)) {
                                                        $firmalar = explode(',', $kullanici->firma_adi);
                                                        echo '<ul class="list-unstyled mb-0 d-flex flex-column gap-1.5">';
                                                        foreach ($firmalar as $f) {
                                                            echo '<li class="d-flex align-items-center gap-1.5"><i class="zmdi zmdi-city text-primary" style="font-size: 0.85rem;"></i> ' . htmlspecialchars(trim($f)) . '</li>';
                                                        }
                                                        echo '</ul>';
                                                    } else {
                                                        echo '<span class="text-muted italic" style="font-size: 0.8rem;">Tanımlı işyeri yok</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>

                                            <!-- Action Permissions -->
                                            <div class="mb-3">
                                                <div class="text-muted small font-weight-bold mb-1.5" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.02em;">İşlem Yetkileri</div>
                                                <div class="d-flex flex-wrap gap-1.5">
                                                    <?php 
                                                    $has_onay = (strpos($yetkiler, 'rapor_onay') !== false);
                                                    $has_manuel = (strpos($yetkiler, 'manuel_bildirim') !== false);
                                                    ?>
                                                    <span class="badge rounded px-2 py-1 <?php echo $has_onay ? 'bg-info text-white' : 'bg-light text-muted border'; ?>" style="font-size: 0.7rem; font-weight: 500;">
                                                        <i class="zmdi <?php echo $has_onay ? 'zmdi-check-circle' : 'zmdi-close-circle'; ?> me-1"></i> Rapor Onay
                                                    </span>
                                                    <span class="badge rounded px-2 py-1 <?php echo $has_manuel ? 'bg-info text-white' : 'bg-light text-muted border'; ?>" style="font-size: 0.7rem; font-weight: 500;">
                                                        <i class="zmdi <?php echo $has_manuel ? 'zmdi-check-circle' : 'zmdi-close-circle'; ?> me-1"></i> Manuel Bildirim
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Actions Footer -->
                                            <div class="d-flex gap-2 border-top pt-2.5 mt-1">
                                                <button type="button" 
                                                        data-kullanici-id="<?php echo $enc_id; ?>"
                                                        data-yetkiler="<?php echo htmlspecialchars($yetkiler); ?>"
                                                        class="btn btn-outline-secondary btn-sm w-50 py-2 kullanici-duzenle d-flex align-items-center justify-content-center gap-1"
                                                        style="border-radius: 8px; font-weight: 600; font-size: 0.8rem; margin: 0;">
                                                    <i class="zmdi zmdi-edit"></i> Düzenle
                                                </button>
                                                <button type="button" 
                                                        data-kullanici-id="<?php echo $enc_id; ?>"
                                                        class="btn btn-danger btn-sm w-50 py-2 alt-kullanici-sil d-flex align-items-center justify-content-center gap-1"
                                                        style="border-radius: 8px; font-weight: 600; font-size: 0.8rem; margin: 0;">
                                                    <i class="zmdi zmdi-delete"></i> Sil
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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

    /* Mobile users card list style */
    .mobile-kullanici-card {
        transition: all 0.25s ease;
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03) !important;
    }
    
    .mobile-kullanici-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06) !important;
        border-color: #cbd5e1 !important;
    }

    .mobile-kullanici-card .btn {
        box-shadow: none !important;
    }

    .gap-2 {
        gap: 8px;
    }

    .gap-1.5 {
        gap: 6px;
    }

    .mb-2.5 {
        margin-bottom: 10px;
    }

    .pt-2.5 {
        padding-top: 10px;
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