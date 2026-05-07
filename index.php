<?php

require_once "vendor/autoload.php";
session_start();

use App\Helper\Security;
use App\Helper\Date;
use Models\KullaniciAbonelikModel;


Security::checkLogin();
Security::checkFirma();

$KullaniciAbonelikModel = new KullaniciAbonelikModel();

$aktif_abonelik  = $KullaniciAbonelikModel->getSubscriptionByUserId($_SESSION['kullanici_id']);

$abonelik_bitis_tarihi = $aktif_abonelik->bitis_tarihi ?? null;


?>

<?php include 'layouts/head.php'; ?>
<?php include 'layouts/preloader.php'; ?>
<?php include 'layouts/topbar.php'; ?>
<?php include 'layouts/navbar.php'; ?>


<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">
        <div class="block-header">
            <div class="row clearfix">
                <div class="col-lg-12">
                    <h2>Kontrol Paneli; </h2>

                    <p class="text-muted">Hoş geldiniz, <strong><?php echo $_SESSION['firma_adi']; ?></strong> (İşyeri
                        Kodu: <?php echo $_SESSION['isyeriKodu']; ?>).<br>Aşağıdaki


                        kartları kullanarak işlemleri hızlıca gerçekleştirebilirsiniz.</p>
                    <p><small>
                            Abonelik bitiş tarihiniz :
                            <strong>
                                <?php
                                if ($abonelik_bitis_tarihi == null) {
                                    echo "<span class='text-danger'>Aktif aboneliğiniz bulunmamaktadır!</span> ";
                                } else {
                                    echo Date::dmY($aktif_abonelik->bitis_tarihi, "d.m.Y");
                                } ?>

                            </strong>

                        </small></p>
                </div>
            </div>
        </div>

        <?php
        // Kart verilerini bir dizi içinde tanımlayalım
        $cards = [
            [
                'title' => 'Tarihe Göre Rapor Ara',
                'description' => 'Henüz onaylanmamış raporları görüntüleyin ve onaylayın.',
                'icon' => 'zmdi-time',
                'button_text' => 'Ara',
                'button_class' => 'btn-warning',
                'border_class' => 'border-warning',
                'link' => 'tarihe-gore-rapor-ara'
            ],
            [
                'title' => 'Onaylanmış Raporlar',
                'description' => 'Geçmişte onayladığınız tüm raporları tarih aralığına göre listeleyin.',
                'icon' => 'zmdi-check-circle',
                'button_text' => 'Sorgula',
                'button_class' => 'btn-success',
                'border_class' => 'border-success',
                'link' => 'onayli-rapor-ara'
            ],
            [
                'title' => 'Mahsuplaşma İşlemleri',
                'description' => 'Personele ödediğiniz rapor parasını, SGK prim borcunuzdan düşün.',
                'icon' => 'zmdi-refresh-sync',
                'button_text' => 'Yönet',
                'button_class' => 'btn-success',
                'border_class' => 'border-success',
                'link' => 'mahsuplastirilacak-raporlar'
            ],
            [
                'title' => 'Manuel Rapor Bildirimi',
                'description' => 'Sisteme otomatik düşmeyen bildirimleri manuel olarak oluşturun.',
                'icon' => 'zmdi-edit',
                'button_text' => 'İşlem Yap',
                'button_class' => 'btn-info',
                'border_class' => 'border-info',
                'link' => 'manuel-rapor-bildirimi',

            ],
            [
                'title' => 'İptal Edilen Raporlar',
                'description' => 'Onayını iptal ettiğiniz ve tekrar işlem bekleyen raporları yönetin.',
                'icon' => 'zmdi-undo',
                'button_text' => 'Yönet',
                'button_class' => 'btn-primary',
                'border_class' => 'border-primary',
                'link' => '#',
                'disabled' => true // Bu kartı devre dışı bırakıyoruz
            ],
            [
                'title' => 'Arşive Alınan Raporlar',
                'description' => 'Arşive alınan raporları görüntüleyin ve işleyin.',
                'icon' => 'zmdi-archive',
                'button_text' => 'Ara',
                'button_class' => 'btn-primary bg-cyan',
                'border_class' => 'border-primary',
                'link' => 'arsivlenmis-raporlar'
            ],
            [
                'title' => 'İş Kazası Bildirimleri',
                'description' => 'İş kazası nedeniyle hastaneden alınan provizyonları takip edin.',
                'icon' => 'zmdi-hospital',
                'button_text' => 'Sorgula',
                'button_class' => 'btn-danger',
                'border_class' => 'border-danger',
                'link' => 'is-kazasi-bildirimi',

            ],
            [
                'title' => 'İletişim Bilgileri',
                'description' => 'SGK sisteminde kayıtlı e-posta ve telefon bilgilerinizi yönetin.',
                'icon' => 'zmdi-phone',
                'button_text' => 'Yönet',
                'button_class' => 'btn-secondary',
                'border_class' => 'border-secondary',
                'link' => 'iletisim-bilgileri'
            ],

        ];
        ?>
        <div class="row clearfix mobile-card">
        <div class="card shadow-sm sgk-card">
                         <!-- Card Body -->
                <ul class="list-group list-group-flush">
                    <?php foreach ($cards as $card): ?>
                        <!-- Item -->
                        <a href="<?php echo $card['link']; ?>" class="nav-link">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="icon-box <?php echo $card['border_class']; ?>">
                                    <i class="zmdi <?php echo $card['icon']; ?>"></i>
                                </div>

                                <div>
                                    <div class="fw-semibold"><?php echo $card['title']; ?></div>
                                    <small class="text-muted"><i class="bi bi-camera-video"></i> <?php echo $card['description']; ?></small>
                                </div>
                            </div>
                           
                        </li>
                        </a>
                    <?php endforeach; ?>


                </ul>
            </div>
        </div>
      
        <div class="row clearfix desktop-card">


          

            <?php foreach ($cards as $card): ?>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div
                        class="card sgk-card "
                        data-toggle="tooltip" data-placement="top" title="<?php echo $card['description']; ?>
                        <?php echo isset($card['disabled']) && $card['disabled'] ? 'disabled-card' : ''; ?>">
                        <div class="body text-center">
                            <div class="icon-box <?php echo $card['border_class']; ?>">
                                <i class="zmdi <?php echo $card['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title m-b-0"><?php echo $card['title']; ?></h5>
                            <a href="<?php echo $card['link']; ?>" class="btn <?php echo $card['button_class']; ?>"
                                <?php if (isset($card['disabled']) && $card['disabled']): ?> disabled <?php endif; ?>>
                                <?php echo $card['button_text']; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>



<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>