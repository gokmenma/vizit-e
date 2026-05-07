<?php

use Models\KullaniciAbonelikModel;

$KullaniciAbonelikModel = new KullaniciAbonelikModel();

$abonelik_varmi = $KullaniciAbonelikModel->hasActiveSubscription($_SESSION['kullanici_id'] ?? 0);

$firmaAdi = $_SESSION['firma_adi'] ?? '';
$isyeriKodu = $_SESSION['isyeriKodu'] ?? '';



function orta_kisalt($metin, $adet = 10, $ek = '..', $enc = 'UTF-8')
{
    $uz = mb_strlen($metin, $enc);
    // Metin yeterince uzun değilse olduğu gibi döndür
    if ($uz <= 2 * $adet) {
        return $metin;
    }
    $bas = mb_substr($metin, 0, $adet, $enc);
    $son = mb_substr($metin, $uz - $adet, $adet, $enc);
    return $bas . $ek . $son;
}



//Sakarya Üniversitesi Hastanesi şeklinde olan veriyi Sakarya ... nesi şeklinde yaz
if (strlen($firmaAdi) > 22) {
    $firmaAdiKisa = orta_kisalt($firmaAdi, 10, ' ... ', 'UTF-8');
} else {
    $firmaAdiKisa = $firmaAdi;
}


// Aktif sayfayı tespit et
$currentPage = $_GET["url"] ?? '';
// Menü öğeleri ve alt sayfaları
$menuItems = [
    'sgk-paneli' => [
        '',
        'tarihe-gore-rapor-ara',
        'onay-bekleyen-raporlar',
        'onayli-rapor-ara',
        'onayli-raporlar',
        'arsivlenmis-raporlar',
        'mahsuplastirilacak-raporlar',
        'manuel-rapor-bildirimi',
        'iletisim-bilgileri',
        'is-kazasi-bildirimi'
    ],
    'isyerlerim' => ['isyerlerim', 'isyeri-sec',"excelden-yukle"],
    'kullanicilar' => ['kullanicilar', 'ekle']

];

// Aktif menü öğesini belirle
function getActiveMenu($currentPage, $menuItems)
{
    foreach ($menuItems as $menuKey => $pages) {
        if (in_array($currentPage, $pages)) {
            return $menuKey;
        }
    }
    return '';
}

$activeMenu = getActiveMenu($currentPage, $menuItems);

//echo "activeMenu: " . htmlspecialchars($activeMenu, ENT_QUOTES, 'UTF-8') . "<br>";

?>







<div class="overlay"></div><!-- Overlay For Sidebars -->

<div class="menu-container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <!-- 
                    ANA DEĞİŞİKLİK: Menü elemanlarını içeren bu div'e
                    'd-flex' ve 'justify-content-between' ekliyoruz.
                -->
                <div class="d-flex justify-content-between align-items-center">

                    <!-- SOL TARAFTAKİ MENÜ GRUBU -->
                    <ul class="h-menu mb-0">
                        <!-- mb-0 alt boşluğu sıfırlar -->
                        <!-- Ana Sayfa -->
                        <li>
                            <a href="<?php echo $_ENV['BASE_PATH']; ?>"><i class="zmdi zmdi-home"></i></a>

                        </li>
                        <li class="<?= ($activeMenu === 'sgk-paneli') ? 'open active' : 'open' ?>">
                            <a href="#">Sgk Paneli</a>
                            <!-- //Eğer abonelik yoksa bu menüyü gösterme -->
                            <?php if ($abonelik_varmi): ?>


                                <ul class="sub-menu">
                                    <li><a href="tarihe-gore-rapor-ara">Tarihe göre Rapor Ara</a></li>
                                    <li><a href="onayli-rapor-ara">Onaylı Rapor Ara</a></li>
                                    <li><a href="mahsuplastirilacak-raporlar">Mahsuplaşma İşlemleri</a></li>

                                    <li><a href="manuel-rapor-bildirimi">Manuel Rapor Bildirimi</a></li>
                                    <li><a href="#">İptal Edilen Raporlar</a></li>
                                    <li><a href="arsivlenmis-raporlar">Arşive Alınan Raporlar</a></li>

                                    <li><a href="is-kazasi-bildirimi">İş Kazası Bildirimleri</a></li>
                                    <li><a href="iletisim-bilgileri">İletişim Bilgileri</a></li>

                                </ul>
                            <?php endif ?>
                        </li>
                        <li class="<?= ($activeMenu === 'isyerlerim') ? 'open active' : 'open' ?>">
                            <a href="isyerlerim">İşyerlerim</a>
                        </li>
                        <!-- Kullanıcılar     -->
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
                            <li class="<?= ($activeMenu === 'kullanicilar') ? 'open active' : 'open' ?>">
                                <a href="kullanicilar">Kullanıcılar</a>

                            </li>
                        <?php endif; ?>

                    </ul>


                    <!-- SAĞ TARAFTAKİ MENÜ GRUBU -->
                    <ul class="h-menu mb-0 ">

                        <li class="float-right">
                            <a href="isyerlerim"
                                data-toggle="tooltip"
                                data-placement="top"
                                title="<?php echo $firmaAdi; ?>">
                                <?php echo $firmaAdiKisa; ?>
                            </a>

                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
</div>