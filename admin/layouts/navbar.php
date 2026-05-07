
<?php 

// Aktif sayfayı tespit et
$currentPage = $_GET["url"] ?? '';
// Menü öğeleri ve alt sayfaları
$menuItems = [
    "index" => ['', 'index', 'anasayfa'],
    "kullanicilar" => ['kullanicilar'],
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
// admin/ önekini kaldır
$currentPage = str_replace('admin/', '', $currentPage);


$activeMenu = getActiveMenu($currentPage, $menuItems);


echo "aktif sayfa " . $activeMenu . "<br>";
?>


<div class="menu-container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">                
                <ul class="h-menu">
                    <li><a href="index"><i class="zmdi zmdi-home"></i></a></li>
                    <li class="<?= ($activeMenu === 'index') ? 'open active' : 'open' ?>">
                        <a href="/admin">Ana Sayfa</a>
                    </li>
                    <li class="<?= ($activeMenu === 'kullanicilar') ? 'open active' : 'open' ?>">
                        <a href="kullanicilar">Kullanıcılar</a>

                    </li>
                    <li><a href="javascript:void(0)">Paketler</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>