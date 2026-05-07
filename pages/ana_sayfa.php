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
            <div class="block-header">
                <div class="row clearfix">
                    <div class="col-lg-12">
                        <h2>Kontrol Paneli</h2>
                        <p class="text-muted">Hoş geldiniz, <strong>ADB ŞUBE</strong> (İşyeri Kodu: 3).<br>Aşağıdaki
                            kartları kullanarak işlemleri hızlıca gerçekleştirebilirsiniz.</p>
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
                'button_text' => 'Görüntüle',
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
                'link' => 'onayli_raporlar.php'
            ],
            [
                'title' => 'Mahsuplaşma İşlemleri',
                'description' => 'Personele ödediğiniz rapor parasını, SGK prim borcunuzdan düşün.',
                'icon' => 'zmdi-refresh-sync',
                'button_text' => 'Yönet',
                'button_class' => 'btn-success',
                'border_class' => 'border-success',
                'link' => '#'
            ],
            [
                'title' => 'Manuel Rapor Bildirimi',
                'description' => 'Sisteme otomatik düşmeyen bildirimleri manuel olarak oluşturun.',
                'icon' => 'zmdi-edit',
                'button_text' => 'İşlem Yap',
                'button_class' => 'btn-info',
                'border_class' => 'border-info',
                'link' => '#'
            ],
            [
                'title' => 'İptal Edilen Raporlar',
                'description' => 'Onayını iptal ettiğiniz ve tekrar işlem bekleyen raporları yönetin.',
                'icon' => 'zmdi-undo',
                'button_text' => 'Yönet',
                'button_class' => 'btn-primary',
                'border_class' => 'border-primary',
                'link' => '#'
            ],
            [
                'title' => 'İş Kazası Bildirimleri',
                'description' => 'İş kazası nedeniyle hastaneden alınan provizyonları takip edin.',
                'icon' => 'zmdi-hospital',
                'button_text' => 'Sorgula',
                'button_class' => 'btn-danger',
                'border_class' => 'border-danger',
                'link' => '#'
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
            [
                'title' => 'SGK Hesabını Değiştir',
                'description' => 'Başka bir SGK hesabı seçmek için oturumu kapatın.',
                'icon' => 'zmdi-swap',
                'button_text' => 'Hesap Değiştir',
                'button_class' => 'btn-danger',
                'border_class' => 'border-danger',
                'link' => '#'
            ],
        ];
        ?>

            <div class="row clearfix">
                <?php foreach ($cards as $card): ?>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card sgk-card">
                        <div class="body text-center">
                            <div class="icon-box <?php echo $card['border_class']; ?>">
                                <i class="zmdi <?php echo $card['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title m-b-0"><?php echo $card['title']; ?></h5>
                            <p class="text-muted card-description"><?php echo $card['description']; ?></p>
                            <a href="<?php echo $card['link']; ?>" class="btn <?php echo $card['button_class']; ?>">
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