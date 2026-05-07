<?php $title = 'İşyerlerim'; ?>

<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>

<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>


<?php

use App\Helper\Security;
use Models\KullaniciIsyeriModel;
use Models\KullaniciAbonelikModel;


Security::checkLogin();




$İsyeriModel = new KullaniciIsyeriModel();
$KulllaniciAbonelik = new KullaniciAbonelikModel();

$kullaniciId = $_SESSION['kullanici_id'];
$user = ($_SESSION["user"]);

if($userRole == "user"){
    $kullaniciId = $_SESSION['user']->admin_id;
}


// Kullanıcının aktif aboneliğini al
$firma_hakki = $KulllaniciAbonelik->getSubscriptionByUserId($kullaniciId)->firma_hakki ?? 0;

//Kullanilan firma hakkı
$kullanilan_firma_hakki = $İsyeriModel->countFirmByUserId($kullaniciId) ?? 0;
//Progres yüzdesi
if ($firma_hakki == 0) {
    $progress = 0; // Eğer firma hakkı 0 ise, ilerleme yüzdesi de 0 olur
    $kalan_firma_hakki = 0;
} else {
    // Kullanılan firma hakkı, firma hakkının %100'ü olarak kabul edilir
    $kullanilan_firma_hakki = min($kullanilan_firma_hakki, $firma_hakki);
    $progress = ($kullanilan_firma_hakki / $firma_hakki) * 100;
    $kalan_firma_hakki = $firma_hakki - $kullanilan_firma_hakki;

}

// Kullanıcının işyerlerini al

if($userRole == "user"){
    $isyeri_ids = $user->yetkili_oldugu_isyeri_ids;
    $isyerleri = $İsyeriModel->AltKullaniciİsyerleri($isyeri_ids);

}else{
    $isyerleri = $İsyeriModel->whereRaw('kullanici_id = ? AND aktif_mi = ?', [$kullaniciId, 1]);
}
$selected_firma_id = $_SESSION['isyeri_id'] ?? null;

$hataMesaji = $_SESSION['hata'] ?? '';


?>





<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">
        <?php if($userRole == "admin"): ?>
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <!-- firma hakkı yoksa bu alanı gösterme -->
                    <?php if ($firma_hakki == 0) : ?>
                        <div class="alert alert-warning" role="alert">
                            <strong>Uyarı!</strong> Aktif bir firma hakkınız bulunmamaktadır.
                            SGK işlemlerini yapmak için <a href="abonelik-paketleri"> aktif bir aboneliğiniz</a>
                            olması gerekmektedir.
                        </div>

                    <?php else : ?>
                        <div class="header">
                            <h2><strong>Firma</strong> Aktivasyon Durumu
                                <small>Firma aktivasyon durumunu ve kalan kullanım hakkınızı görüntüleyin</small>
                            </h2>
                        </div>
                        <div class="body">
                            <small>Kullanılan : <?php echo $kullanilan_firma_hakki; ?> / <?php echo $firma_hakki; ?></small>
                            <div class="progress m-b-5">
                                <div class="progress-bar progress-bar-success" role="progressbar"
                                    aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"
                                    style="width: <?php echo $progress; ?>%"> <span
                                        class="sr-only"><?php echo $progress; ?>% Complete (success)</span> </div>
                            </div>
                        </div>

                    <?php endif  ?>


                </div>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="row clearfix">
            <!-- Hata mesajlarını göstermek için alert -->
            <div class="col-lg-12 col-md-12 col-sm-12 mt-3">
                <?php if (!empty($hataMesaji && $firma_hakki > 0)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $hataMesaji; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-lg-9">
                            <h2><strong>İşyeri Listesi</strong></h2>
                            <small>İşyerlerinizden birini seçerek o işyerinin raporlarında işlem yapabilirsiniz</small>

                        </div>
                        <div class="col-lg-3 text-lg-end mt-3 mt-lg-0">

                            <!-- Firma ekleme hakkı hala varsa -->
                            <?php if ($kullanilan_firma_hakki < $firma_hakki && $userRole == "admin"): ?>
                                <a href="#defaultModal" data-bs-toggle="modal" data-bs-target="#defaultModal"
                                    class="btn btn-raised btn-primary waves-effect"><i
                                        class="zmdi zmdi-arrow-plus"></i>Yeni Ekle</a>

                                <a href="excelden-yukle" class="btn btn-raised btn-primary btn-simple waves-effect">Excel'den Yükle</a>

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
                                            <th>Firma Adı</th>
                                            <th>Otomatik Onay</th>
                                            <th>Otomatik Onay E-posta</th>
                                            <th style="width: 220px;" class="text-center">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 0;
                                        foreach ($isyerleri as $isyeri) {
                                            $i++;
                                            $enc_id = Security::encrypt($isyeri->id);
                                            // Seçili firma ile id eşitse seçili
                                            if ($isyeri->id === $selected_firma_id) {
                                                $selected = 'Seçili';
                                                $selected_btn = 'btn-success disabled';
                                            } else {
                                                $selected = 'Seç';
                                                $selected_btn = 'btn-primary';
                                            }
                                        ?>
                                            <tr>
                                                <td style="width:7%"><?php echo $i; ?></td>

                                                <td>
                                                    <div
                                                        class="d-flex flex-column justify-content-center align-items-start">

                                                        <span class="list-name"><?php echo $isyeri->firma_adi; ?></span>
                                                        <small>İşyeri Kodu : <?php echo $isyeri->isyeri_kodu; ?></small>


                                                    </div>

                                                </td>

                                                <td>
                                                    <?php
                                                    if ($isyeri->otomatik_rapor_onay == "1") {
                                                        echo '<span class="badge bg-success">Açık</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger">Kapalı</span>';
                                                    }

                                                    ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    if (!empty($isyeri->otomatik_onay_eposta)) {
                                                        $eposta_adresleri = explode(',', $isyeri->otomatik_onay_eposta);
                                                        foreach ($eposta_adresleri as $eposta) {
                                                            echo $eposta . '<br>';
                                                        }
                                                    } else {
                                                        echo '-';
                                                    }

                                                    ?>
                                                </td>

                                                <td style="width: 10%;">
                                                    <div class="d-flex flex-wrap flex-md-nowrap w-100">

                                                        <form action="<?php if ($firma_hakki > 0) {
                                                                            echo 'isyeri-sec';
                                                                        } ?>"
                                                            method="POST">


                                                            <input type="hidden" name="isyeri_id"
                                                                value="<?php echo $isyeri->id; ?>">
                                                            <!-- bir önceki sayfa (bu sayfaya gelmeden önceki) -->
                                                            <input type="hidden" name="previous_page" value="<?php echo $_SERVER['HTTP_REFERER'] ?? ''; ?>">


                                                            <!-- seçim yap butonu -->
                                                            <button type="submit" class="btn <?php echo $selected_btn; ?> waves-effect me-2"
                                                                <?php if ($firma_hakki == 0) {echo 'disabled';} ?>>
                                                                <?php echo $selected; ?>
                                                            </button>
                                                        </form>
                                                        <?php if($userRole == "admin"): ?>
                                                        <!-- Düzenle Butonu -->
                                                        <button type="button" data-id="<?php echo $enc_id; ?>"
                                                            class="btn btn-primary btn-simple waves-effect isyeri-duzenle">
                                                            Düzenle
                                                        </button>

                                                        <!-- Kaldır Butonu -->
                                                        <button type="button"
                                                            data-isyeri-id="<?php echo Security::encrypt($isyeri->id); ?>"
                                                            class="btn btn-danger btn-simple isyeri-sil">

                                                            Kaldır
                                                        </button>
                                                        <?php endif; ?>

                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <!-- Eğer kayıt yoksa yeni ekle butonu koy -->
                                        <?php if (count($isyerleri) == 0 && $kalan_firma_hakki > 0) : ?>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <p>İşyeriniz bulunmamaktadır. Lütfen yeni işyeri ekleyin.</p>
                                                    <?php if($userRole == "admin"): ?>
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#defaultModal">Yeni Ekle</button>
                                                    <?php endif; ?>
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
            <form method="POST" id="isyeri-form">
                <input type="hidden" class="d-none" name="isyeri_id" id="isyeri_id" value="0">
                <div class="modal-header">
                    <h4 class="title" id="defaultModalLabel">Sgk İşyerleri</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Bilgi!</strong> Şifreleriniz uçtan uca şifrelenmiş olup sizden başka kimse
                        erişememektedir!
                    </div>
                    <input id="csrf_token" name="csrf_token" type="hidden" value="">
                    <fieldset>
                        <div class="mb-3">
                            <label class="form-label" for="firma_adi">Firma Unvanı (Kolay hatırlamak için)</label>
                            <input class="form-control" id="firma_adi" name="firma_adi"
                                placeholder="Örn: Hastane İşçiler" required="" type="text" value="">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="kullanici_adi">SGK Kullanıcı Adı</label>
                            <input type="password" class="form-control" id="kullanici_adi" name="kullanici_adi" required
                                type="number" value="" maxlength="11" pattern="\d{1,11}"
                                placeholder="Tc Kimlik numarası" title="Lütfen sayı girin!">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="isyeri_kodu">SGK İşyeri Kodu</label>
                            <input class="form-control" id="isyeri_kodu" name="isyeri_kodu" required="" type="number"
                                placeholder="-'den sonraki kod Örn: 2" value="" maxlength="4" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="ws_sifre">SGK İşyeri Şifresi</label>
                            <input class="form-control" id="ws_sifre" name="ws_sifre" required=""
                                placeholder="İşyeri şifresi" autocomplete="new-password" value="" type="password"
                                value="">
                        </div>
                        <div class="checkbox text-left">

                            <input id="otomatik_rapor_onay" type="checkbox" name="otomatik_rapor_onay" >
                            <label for="otomatik_rapor_onay"
                             data-toggle="tooltip"
                                data-placement="top"
                                title="Eğer bu seçenek işaretlenirse,hafta içi her gün saat 16:00'da bu işyerine ait raporlar otomatik olarak onaylanır ve belirtilen e-posta adreslerine bildirim gönderilir."
                            >Otomatik Rapor Onaylama</label>
                        </div>
                        <div class="mb-3 otomatik-onay-eposta d-none">
                            <textarea class="form-control" id="otomatik_onay_eposta" name="otomatik_onay_eposta"
                                placeholder="E-posta adresleri"></textarea>
                            <small class="form-text text-muted">Birden fazla e-posta adresi varsa aralarına virgül
                                koyun</small>
                        </div>

                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-simple waves-effect"
                        data-bs-dismiss="modal">KAPAT</button>
                    <button type="button" class="btn btn-primary waves-effect isyeri-kaydet text-nowrap"
                     data-loading-text="Kaydediliyor..." >KAYDET</button>
                </div>
        </div>
        </form>
    </div>
</div>


<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- sayfanın js kodu -->
<script src="App/Src/isyerlerim.js?v=<?php echo filemtime('App/Src/isyerlerim.js'); ?>"></script>


<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>