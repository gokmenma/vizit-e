<?php
if (defined('SPA_LAYOUT')) {
    return;
}
use Models\KullaniciIsyeriModel;
use App\Helper\Security;

$isyeriModel = new KullaniciIsyeriModel();

$user_id = $_SESSION['role'] == "user" ? $_SESSION['user']->admin_id : $_SESSION['kullanici_id'];

$isyerleri = $isyeriModel->findByUserId($user_id);
$aktif_isyeri = $_SESSION['isyeri_id'] ?? null;

?>

<style>
    .navbar-header {
        display: flex;
        align-items: center;
        /* içerikleri dikeyde ortalar */
        height: 60px;
        /* navbar yüksekliği (sen kendi navbar yüksekliğine göre değiştirebilirsin) */
    }

    .navbar-header .navbar-brand {
        display: flex;
        align-items: center;
        /* logo ve yazıyı dikeyde ortalar */
        gap: 10px;
        /* logo ile yazı arasındaki boşluk */
    }

    .navbar-header .navbar-brand img {
        height: 35px;
        /* logonun yüksekliğini sabitle */
        width: auto;
        /* oranı korusun */
    }
</style>

<nav class="navbar">
    <div class="container">
        <ul class="nav navbar-nav logo vertical-align-center">
            <li class="logo">
                <div class="navbar-header">
                    <!-- <a href="javascript:void(0);" class="h-bars"></a> -->
                    <a class="navbar-brand" href="<?php echo  $_ENV['BASE_PATH'] ?? '/'; ?>"><img src="assets/images/logo_dark.svg" width="35" alt="Alpino"><span class="m-l-10">VİZİT-E</span></a>
                </div>
            </li>



            <li class="float-right">
                <?php echo $_SESSION['kullanici_adi']; ?>



                <a href="https://api.whatsapp.com/send?phone=905079432723" target="_blank" class="mega-menu" title="Destek"><i class="zmdi zmdi-whatsapp"></i></a>

                <a href="profile" class="mega-menu" title="Profil"><i class="zmdi zmdi-account"></i></a>
                <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
                    <a href="abonelik-paketleri" class="mega-menu" title="Paketler"><i class="zmdi zmdi-shopping-basket"></i></a>
                <?php endif; ?>
                <a href="logout" class="mega-menu" title="Çıkış Yap"><i class="zmdi zmdi-power"></i></a>
            </li>
        </ul>
    </div>
</nav>


<!-- ============================================================== -->
<!-- MOBİL ALT NAVİGASYON (Sadece küçük ekranlarda görünür) -->
<!-- ============================================================== -->
<nav class="mobile-bottom-nav">
    <a href="<?php echo  $_ENV['BASE_PATH']; ?>" class="mobile-bottom-nav__item">

        <i class="zmdi zmdi-home"></i>
        <span class="mobile-bottom-nav__text">Ana Sayfa</span>
    </a>
    <button type="button" class="mobile-bottom-nav__item" data-menu-id="sgk-menu">
        <i class="zmdi zmdi-folder"></i>
        <span class="mobile-bottom-nav__text">Sgk Paneli</span>
    </button>
    <button type="button" class="mobile-bottom-nav__item" data-menu-id="isyerlerim-menu">
        <i class="zmdi zmdi-balance"></i>
        <span class="mobile-bottom-nav__text">İşyerlerim</span>
        </a>
        <button type="button" class="mobile-bottom-nav__item" data-menu-id="diger-menu">
            <i class="zmdi zmdi-more-vert"></i>
            <span class="mobile-bottom-nav__text">Diğer</span>
        </button>
</nav>

<!-- ============================================================== -->
<!-- AÇILIR MENÜLER VE ARKA PLAN KARARTMASI -->
<!-- ============================================================== -->

<!-- Arka planı karartmak için kullanılacak overlay -->
<div class="menu-overlay"></div>

<!-- SGK Paneli Açılır Menüsü -->
<div id="sgk-menu" class="popup-menu">
    <div class="popup-menu__header">
        <h4>SGK Paneli</h4>
        <button class="popup-menu__close"><i class="zmdi zmdi-close"></i></button>
    </div>
    <ul class="popup-menu__list">
        <li><a href="tarihe-gore-rapor-ara">Tarihe göre Rapor Ara</a></li>
        <li><a href="onayli-rapor-ara">Onaylı Rapor Ara</a></li>
        <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
            <li><a href="mahsuplastirilacak-raporlar">Mahsuplaşma İşlemleri</a></li>
            <li><a href="manuel-rapor-bildirimi">Manuel Rapor Bildirimi</a></li>
        <?php endif; ?>
        <li><a href="iptal-edilen-raporlar">İptal Edilen Raporlar</a></li>
        <li><a href="arsivlenmis-raporlar">Arşive Alınan Raporlar</a></li>
        <li><a href="is-kazasi-bildirimi">İş Kazası Bildirimleri</a></li>
        <li><a href="iletisim-bilgileri">İletişim Bilgileri</a></li>
    </ul>
</div>

<!-- İşyerlerim için açılır menü -->
<div id="isyerlerim-menu" class="popup-menu">
    <div class="popup-menu__header">
        <a href="isyerlerim" class="nav-link">
            <h4>İŞYERLERİM</h4>
        </a>
        <button class="popup-menu__close"><i class="zmdi zmdi-close"></i></button>
    </div>
    <ul class="popup-menu__list">
        <?php foreach ($isyerleri as $isyeri) {
            $active_color = ((int)$isyeri->id === (int)$aktif_isyeri) ? '#d1e7dd' : 'transparent'; // Aktif işyeri için renk belirleme
        ?>
            <li style="background-color:<?php echo $active_color ?>;"><a href="isyeri-sec?isyeri_id=<?php echo Security::encrypt($isyeri->id); ?>">

                    <?php echo $isyeri->firma_adi;  ?>
                    <p style="margin: 0;padding: 0;">
                        <small><?php echo "İşyeri Kodu : " . $isyeri->isyeri_kodu; ?></small>
                    </p>
                </a></li>
        <?php }
        if (count($isyerleri) == 0) { ?>

            <li><a href="isyerlerim">Yeni İşyeri Ekle</a></li>
        <?php } ?>


    </ul>
</div>


<!-- Diğer İşlemler Açılır Menüsü -->
<div id="diger-menu" class="popup-menu">
    <div class="popup-menu__header">
        <h4>Diğer İşlemler</h4>
        <button class="popup-menu__close"><i class="zmdi zmdi-close"></i></button>
    </div>
    <ul class="popup-menu__list">
        <li><a href="https://api.whatsapp.com/send?phone=905079432723" target="_blank">Destek</a></li>
        <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
            <li><a href="/kullanicilar">Kullanıcılar</a></li>
        <?php endif; ?>

        <li><a href="/profile">Profil</a></li>
        <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
            <li><a href="/abonelik-paketleri">Abonelik Paketleri</a></li>
        <?php endif; ?>

        <li><a href="logout">Çıkış Yap</a></li>
    </ul>
</div>