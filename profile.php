<?php

use App\Helper\Security;
use Models\UserModel;
use Models\KullaniciAyarModel;
use Models\KvkkRizaModel;


Security::checkFirma();
// Security::hasActiveSubscription();

$UserModel = new UserModel();
$KullaniciAyarModel = new KullaniciAyarModel();
$KvkkRizaModel = new KvkkRizaModel();


$title = "Profil Bilgileri";

$hataMesaji = '';
$basariMesaji = '';


$user = $UserModel->find($_SESSION['kullanici_id'] ?? null);
$aydinlatma_metni = $KvkkRizaModel->getKvkkRizaByUserId($_SESSION['kullanici_id'], 'aydinlatma_metni');
$gizlilik_sozlesmesi = $KvkkRizaModel->getKvkkRizaByUserId($_SESSION['kullanici_id'], 'gizlilik_sozlesmesi');
$acik_riza_beyani = $KvkkRizaModel->getKvkkRizaByUserId($_SESSION['kullanici_id'], 'acik_riza_beyani');

$giris_kayitlari = []; // Giriş kayıtlarını buraya ekleyin
$giris_kayitlari = $UserModel->getLoginRecords($_SESSION['kullanici_id'], 15);


if (!$user) {
    $hataMesaji = 'Kullanıcı bulunamadı.';
}


$saat_9_mail_bildirimi = $KullaniciAyarModel->getSetting('saat_9_mail_bildirimi');
$rapor_otomatik_onay_bildirim = $KullaniciAyarModel->getSetting('rapor_otomatik_onay_bildirim');



?>
<!-- Head ve diğer layout include'ları -->
<?php include 'layouts/head.php'; ?>
<?php include 'layouts/preloader.php'; ?>
<?php include 'layouts/topbar.php'; ?>
<?php include 'layouts/navbar.php'; ?>

<!-- ANA İÇERİK BÖLÜMÜ -->

<section class="content">
    <div class="container" style="margin-bottom: 20px;">

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-lg-10">
                            <h2><strong>Profil Bilgieri</strong></h2>
                            <small>Profil bilgilerinizi güncelleyin


                            </small>


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
                    <ul class="nav nav-tabs text-nowrap" role="tablist">

                        <li class="nav-item"><a class="nav-link active" data-toggle="tab"
                                href="#profile_with_icon_title"><i class="zmdi zmdi-account zmdi-hc-fw"></i>
                                PROFİL BİLGİLERİ </a></li>
                        <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#messages_with_icon_title"><i
                                        class="zmdi zmdi-email zmdi-hc-fw"></i>
                                    BİLDİRİM AYARLARI </a></li>
                        <?php endif; ?>

                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#logins_with_icon_title"><i
                                    class="zmdi zmdi-lock zmdi-hc-fw"></i>
                                GİRİŞ KAYITLARI </a></li>
                        <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#kvkk_bilgileri"><i
                                        class="zmdi zmdi-file-text zmdi-hc-fw"></i>

                                    KVKK BİLGİLERİ </a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#account_with_icon_title"><i
                                        class="zmdi zmdi-settings zmdi-hc-fw"></i>
                                    HESAP İŞLEMLERİ </a></li>
                        <?php endif; ?>

                    </ul>


                    <div class="body">




                        <!-- Nav tabs -->

                        <!-- Tab panes -->
                        <div class="tab-content">

                            <div role="tabpanel" class="tab-pane in active" id="profile_with_icon_title">
                                <form id="profileForm" class="row d-flex justify-content-center align-items-center">

                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Kullanıcı Adı</b>
                                        </div>

                                        <div class="col-md-4 col-lg-4">
                                            <div class="form-group">

                                                <input type="text" id="kullanici_adi" name="kullanici_adi"
                                                    value="<?php echo $user->kullanici_adi; ?>" readonly
                                                    class="form-control " placeholder="Kullanıcı Adı">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Email Adresi</b>
                                        </div>
                                        <div class="col-md-4 col-lg-4">
                                            <div class="form-group">
                                                <input type="text" id="email" name="email"
                                                    value="<?php echo $user->email; ?>" readonly class="form-control "
                                                    placeholder="Email Adresi">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Ad Soyad</b>
                                        </div>
                                        <div class="col-md-4 col-lg-4">
                                            <div class="form-group">
                                                <input type="text" id="adi_soyadi" name="adi_soyadi"
                                                    value="<?php echo $user->adi_soyadi; ?>" class="form-control "
                                                    placeholder="Ad Soyad(Opsiyonel)">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Telefon</b>
                                        </div>
                                        <div class="col-md-4 col-lg-4">
                                            <div class="form-group">
                                                <input type="text" id="telefon" name="telefon"
                                                    value="<?php echo $user->telefon; ?>" class="form-control "
                                                    placeholder="Telefon(Opsiyonel)">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Mevcut Şifre</b>
                                        </div>
                                        <div class="col-md-4 col-lg-4">
                                            <div class="form-group">
                                                <input type="password" id="mevcut_sifre" name="mevcut_sifre"
                                                    autocomplete="new-password" class="form-control "
                                                    placeholder="Mevcut Şifrenizi Girin">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Yeni Şifre</b>
                                        </div>
                                        <div class="col-md-4 col-lg-4">
                                            <div class="form-group">
                                                <input type="password" id="yeni_sifre" name="yeni_sifre"
                                                    class="form-control " placeholder="Yeni Şifre">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-md-2"></div>

                                        <div class="col-lg-10 col-md-10">
                                            <button type="button" id="kaydetButton"
                                                class="btn btn-primary waves-effect mt-0 text-nowrap">Kaydet</button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="messages_with_icon_title">
                                <form id="bildirimForm" class="row d-flex justify-content-center align-items-center">

                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Bekleyen Rapor Onayı Hatırlatma</b>
                                        </div>

                                        <div class="col-sm-8 col-md-8">
                                            <div class="checkbox">
                                                <input id="saat_9_mail_bildirimi" name="saat_9_mail_bildirimi"
                                                    type="checkbox"
                                                    <?php echo $saat_9_mail_bildirimi == 1 ? 'checked' : ''; ?>>

                                                <label for="saat_9_mail_bildirimi">
                                                    Hafta içi her gün Saat 09:00'da bildirim gönder
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row d-flex align-items-center">
                                        <div class="col-md-2">
                                            <b>Otomatik Rapor Onayı</b>
                                        </div>

                                        <div class="col-sm-8 col-md-8">
                                            <div class="checkbox">
                                                <input id="rapor_otomatik_onay_bildirim" name="rapor_otomatik_onay_bildirim"
                                                    type="checkbox"
                                                    <?php echo $rapor_otomatik_onay_bildirim == 1 ? 'checked' : ''; ?>>

                                                <label for="rapor_otomatik_onay_bildirim">
                                                    Otomatik onaylanan rapoları mail gönder
                                                </label>
                                            </div>
                                        </div>
                                    </div>



                                    <div class="row">
                                        <div class="col-md-2"></div>

                                        <div class="col-lg-10 col-md-10">
                                            <button type="button" id="bildirimKaydetButton"
                                                class="btn btn-primary waves-effect mt-0 text-nowrap">Değişiklikleri
                                                Kaydet</button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="logins_with_icon_title">
                                <h5>Son 15 Giriş Kaydı</h5>
                                <?php if (count($giris_kayitlari) > 0) { ?>
                                    <div class="table-responsive">


                                        <table class="table table-striped table-hover ">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Tarih</th>
                                                    <th scope="col">IP Adresi</th>
                                                    <th scope="col">Tarayıcı</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($giris_kayitlari as $giris) { ?>
                                                    <tr>
                                                        <td style="min-width:33%;"><?php echo $giris->created_at; ?></td>
                                                        <td style="min-width: 33%;"><?php echo $giris->ip_address; ?></td>
                                                        <td style="min-width: 33%;"><?php echo $giris->browser; ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php } else { ?>
                                    <p>Henüz giriş kaydınız bulunmamaktadır.</p>
                                <?php } ?>




                            </div>
                            <div role="tabpanel" class="tab-pane" id="kvkk_bilgileri">
                                <b>KVKK Bilgileri</b>
                                <div class="row clearfix">
                                    <div class="col-md-12 col-lg-12">
                                        <div class="panel-group" id="accordion_1" role="tablist"
                                            aria-multiselectable="true">
                                            <div class="panel panel-primary">
                                                <div class="panel-heading" role="tab" id="headingOne_1">
                                                    <h4 class="panel-title"> <a role="button" data-toggle="collapse"
                                                            data-parent="#accordion_1" href="#collapseOne_1"
                                                            aria-expanded="false" aria-controls="collapseOne_1"
                                                            class="collapsed"> Aydınlatma Metni </a> </h4>
                                                </div>
                                                <div id="collapseOne_1" class="panel-collapse in collapse"
                                                    role="tabpanel" aria-labelledby="headingOne_1" style="">
                                                    <div class="panel-body">
                                                        <?php echo $aydinlatma_metni->icerik ?? ''; ?>


                                                    </div>
                                                </div>
                                            </div>
                                            <div class="panel panel-primary">
                                                <div class="panel-heading" role="tab" id="headingTwo_1">
                                                    <h4 class="panel-title"> <a class="collapsed" role="button"
                                                            data-toggle="collapse" data-parent="#accordion_1"
                                                            href="#collapseTwo_1" aria-expanded="false"
                                                            aria-controls="collapseTwo_1"> Gizlilik Sözleşmesi
                                                        </a> </h4>
                                                </div>
                                                <div id="collapseTwo_1" class="panel-collapse collapse" role="tabpanel"
                                                    aria-labelledby="headingTwo_1">
                                                    <div class="panel-body">
                                                        <?php echo $gizlilik_sozlesmesi->icerik ?? ''; ?>


                                                    </div>
                                                </div>
                                            </div>
                                            <div class="panel panel-primary">
                                                <div class="panel-heading" role="tab" id="headingThree_1">
                                                    <h4 class="panel-title"> <a class="collapsed" role="button"
                                                            data-toggle="collapse" data-parent="#accordion_1"
                                                            href="#collapseThree_1" aria-expanded="false"
                                                            aria-controls="collapseThree_1"> Açık Rıza Metni
                                                        </a> </h4>
                                                </div>
                                                <div id="collapseThree_1" class="panel-collapse collapse"
                                                    role="tabpanel" aria-labelledby="headingThree_1" style="">
                                                    <div class="panel-body">
                                                        <?php echo $acik_riza_beyani->icerik ?? ''; ?>



                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div role="tabpanel" class="tab-pane" id="account_with_icon_title">
                                <b>Hesap İşlemleri</b>
                                <p>Hesabınızı silmek istediğinizde, tüm kişisel verileriniz ve bilgileriniz sistemden
                                    kalıcı olarak silinecektir. Bu işlem geri alınamaz.</p>
                                <button type="button" id="hesapSilButton" data-toggle="modal"
                                    data-target="#hesapSilModal"
                                    class="btn btn-danger waves-effect mt-0 text-nowrap">Hesabımı Sil</button>



                            </div>
                        </div>




                    </div>
                </div>
            </div>
        </div>
</section>

<!-- Default Size -->
<div class="modal fade" id="hesapSilModal" tabindex="-1" role="dialog">

    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="title" id="defaultModalLabel">Hesabımı Sil!</h4>
            </div>
            <form action="" id="deleteAccountForm">
                <div class="modal-body">
                    <p>Hesabınızı silmek istediğinizde, tüm kişisel verileriniz ve ödeme bilgileriniz sistemden kalıcı
                        olarak
                        silinecektir. Bu işlem geri alınamaz.</p>

                    <small>Hesabınızı silmek istediğinizden eminseniz şifrenizi giriniz.</small>
                    <input type="password" id="mevcut_sifre" name="mevcut_sifre" autocomplete="new-password" class="form-control "
                        placeholder="Mevcut şifreniz">


                </div>
                <div class="modal-footer">
                    <button type="button" data-dismiss="modal"
                        class="btn btn-primary btn-simple btn-round waves-effect">VAZGEÇ</button>
                    <button type="button" class="btn btn-danger btn-round waves-effect delete-account">SİL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script include'ları -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script src="App/Src/kullanici.js?<?php echo filemtime('App/Src/kullanici.js'); ?>"></script>
<?php include 'layouts/foot.php'; ?>