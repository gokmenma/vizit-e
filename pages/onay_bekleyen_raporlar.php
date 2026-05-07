<?php

use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php';

$title = 'Onay Bekleyen Raporlar';
$raporlar = [];
$hataMesaji = '';
$basariMesaji = '';
$toplamBulunanRaporSayisi = 0; // Filtrelemeden önceki toplam sayıyı tutmak için
$kisaRaporlariGoster = isset($_POST['kisa_raporlari_goster']); // JavaScript için bu satır gerekmeyecek
$uzunSureliRaporlar = []; // 3 günden uzun raporları tutmak için
try {
    $sgkClient = new SgkViziteService();
    $tarih = $_POST["rapor_tarihi"];

    // 1. SGK'dan tüm onay bekleyen raporları çek
    // $tumRaporlar = $sgkClient->raporlariGetir(new DateTime('tomorrow'));
    $tumRaporlar = $sgkClient->raporlariGetir(new DateTime($tarih)); // İkinci parametre true ise onay bekleyen raporları getir
    //var_dump($tumRaporlar); // Tüm raporları kontrol etmek için
  
  
    // 2. Filtreleme işlemini yap
    if (!empty($tumRaporlar)) {

        $islenmisRaporlar = []; // İşlenmiş raporları koyacağımız yeni dizi

        foreach ($tumRaporlar as $rapor) {
            //Eğer ARSIV durumu = 1 ise bu raporu atla
            if ($rapor['ARSIV'] == 1) {
                continue;
            }

            // Rapor süresini hesapla
            $gunFarki = 0;
            if (!empty($rapor['POLIKLINIKTAR']) && !empty($rapor['ISBASKONTTAR'])) {
                try {
                    $gunFarki = (new DateTime($rapor['POLIKLINIKTAR']))->diff(new DateTime($rapor['ISBASKONTTAR']))->days;
                } catch (Exception $e) { /* Hatalı tarihi atla */
                }
            }

            // HER RAPORA SÜRESİNİ EKLEYELİM. Bu, JavaScript için gerekli.
            $rapor['gun_farki'] = $gunFarki;

            // 3 günden uzun raporları ayrı tut
            if ($gunFarki >= 3) {
                $uzunSureliRaporlar[] = $rapor;
            }


            // Filtreleme yapmadan, işlenmiş raporu doğrudan listeye ekle.
            $islenmisRaporlar[] = $rapor;
            $toplamBulunanRaporSayisi++; // Filtrelemeden önceki toplam sayıyı al
        }

        // Gösterilecek raporlar, işlenmiş raporların tamamıdır.
        $raporlar = $islenmisRaporlar;
    }
} catch (Exception $e) {
    $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
}


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



        <div class="card">
            <div class="header row d-flex justify-content-between align-items-center">
                <div class="col-lg-9">
                    <h2><strong>Onay Bekleyen Rapor Listesi</strong></h2>
                    <small id="rapor-sayi-bilgisi">
                        <!-- ID'yi buraya ekliyoruz -->
                        <?php echo htmlspecialchars((new DateTime($tarih))->format('d.m.Y')); ?> tarihine kadar SGK'dan
                        bulunan toplam <strong><?php echo $toplamBulunanRaporSayisi; ?></strong> rapor içerisinden,
                        rapor süresi 3 gün ve daha uzun olan <strong><?php echo count($raporlar); ?></strong> rapor
                        listelenmektedir.
                    </small>
                    <input type="hidden" id="toplam-rapor-sayisi" value="<?php echo $toplamBulunanRaporSayisi ?>">

                </div>

                <div class="col-lg-3 d-flex justify-content-end text-nowrap">

                    <a href="tarihe-gore-rapor-ara"
                        class="btn btn-raised btn-primary btn-round waves-effect float-right"><i
                            class="zmdi zmdi-arrow-back"></i> Geri Dön</a>

                    <a href="onayli-rapor-ara"
                        class="btn btn-raised btn-primary btn-simple btn-round waves-effect float-right ms-2"><i
                            class="zmdi zmdi-check"></i> Onaylı Raporlar</a>
                </div>

            </div>
            <div class="row">
                <!-- YENİ CHECKBOX BURADA -->
                <div class="col-lg-3">
                    <div class="checkbox">
                        <input id="kisa-rapor-goster-cb" type="checkbox">
                        <label for="kisa-rapor-goster-cb">3 Günden Kısa Raporları Göster</label>
                    </div>
                </div>
            </div>
            <div class="body">

                <?php if ($hataMesaji): ?>
                    <div class="message-box error-box"><?php echo htmlspecialchars($hataMesaji); ?></div>
                <?php endif; ?>
                <?php if ($basariMesaji): ?>
                    <div class="message-box success-box"><?php echo htmlspecialchars($basariMesaji); ?></div>
                <?php endif; ?>
                <style>
                    .responsive {
                        overflow-x: auto;
                    }
                </style>

                <form method="post">
                    <div class="responsive">


                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Sıra</th>
                                    <th>TC Kimlik No</th>
                                    <th>Ad Soyad</th>
                                    <th>Vaka</th>
                                    <th>Rapor Başlama Tarihi</th>
                                    <th>Rapor Bitiş Tarihi</th>
                                    <th>Gün</th>
                                    <th>Nitelik</th>

                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>


                                <?php if (!empty($raporlar)): ?>
                              
                             <div class="export-buttons float-right mb-3">
                                 <button type="button" id="export-excel" class="btn btn-primary btn-simple waves-effect">Excel'e
                                     Aktar</button>
                                 <button type="button" id="export-pdf" class="btn btn-primary waves-effect">PDF'e Aktar</button>
                             </div>
                                <?php

                                    $i = 0; // Sıra numarası için sayaç
                                ?>
                                    <?php foreach ($raporlar as $rapor):
                                        $i++;
                                    ?>


                                        <tr data-rapor='<?php echo htmlspecialchars(json_encode($rapor)); ?>'
                                            data-gun-farki="<?php echo $rapor['gun_farki']; ?>">
                                            <td><?php echo $i; ?></td>
                                            <td><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['VAKAADI']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($rapor['ABITTAR']); ?></td>
                                            <td class="text-center"><?php echo $rapor['gun_farki']; ?></td>
                                            
                                            <td class="p-0 m-0">
                                                <select class="form-control nitelik-durumu">
                                                    <option value="0">ÇALIŞMAMIŞTIR</option>
                                                    <option value="1">ÇALIŞMIŞTIR</option>
                                                </select>
                                            </td>

                                            <td class="text-center d-flex justify-content-center align-items-center gap-1">
                                                <?php if ($userRole == "admin"): ?>
                                                    <button type="button" class="btn btn-info btn-sm btn-onayla">
                                                    <!-- İkon Ekle     -->
                                                     <i class="zmdi zmdi-check zmdi-hc-fw"></i>
                                                    Onayla</button>
                                                    <button class="btn btn-secondary btn-sm btn-personel-degil">
                                                    <i class="zmdi zmdi-folder-person zmdi-hc-fw"></i>    
                                                    Personelim Değil</button>
                                                    <button type="button" class="btn btn-warning btn-sm btn-kapat" data-id="<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>">
                                                    <!-- İkon Ekle     -->
                                                     <i class="zmdi zmdi-block zmdi-hc-fw"></i>
                                                    Raporu Kapat</button>
                                                <?php endif ?>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (count($uzunSureliRaporlar) == 0): ?>

                                    <tr id="rapor-yok-mesaji">
                                        <td colspan="9" style="text-align:center; padding: 20px;">Onay bekleyen rapor
                                            bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </form>
            </div>
        </div>


    </div>
</section>

<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages\onaysiz_raporlar\export.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>


<script>

    
document.addEventListener('DOMContentLoaded', function() {
    const btnExcel = document.getElementById('export-excel');
    const btnPdf = document.getElementById('export-pdf');

    // PHP'den gelen raporlar dizisini JavaScript'e aktarıyoruz
    const raporlarData = <?php echo json_encode($raporlar); ?>;

    if (btnExcel) {
        btnExcel.addEventListener('click', function() {
            exportData('excel');
        });
    }

    if (btnPdf) {
        btnPdf.addEventListener('click', function() {
            exportData('pdf');
        });
    }

    function exportData(format) {
        // Gizli formun alanlarını doldur
        document.getElementById('export-format').value = format;
        document.getElementById('export-data').value = JSON.stringify(raporlarData);

        console.log(raporlarData);
        // Formu gönder
        document.getElementById('export-form').submit();
    }
});
</script>

<script src="App/Src/rapor_onay.js?v=<?php echo filemtime('App/Src/rapor_onay.js'); ?>"></script>
<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>