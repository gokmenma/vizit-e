<?php

use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once __DIR__ . '/../Core/Services/SgkViziteService.php';
use Models\RaporModel;


$title = 'Onay Bekleyen Raporlar';
$raporlar = [];
$hataMesaji = '';
$basariMesaji = '';
$toplamBulunanRaporSayisi = 0; // Filtrelemeden önceki toplam sayıyı tutmak için
$kisaRaporlariGoster = isset($_POST['kisa_raporlari_goster']); // JavaScript için bu satır gerekmeyecek
$uzunSureliRaporlar = []; // 3 günden uzun raporları tutmak için
try {
    $sgkClient = new SgkViziteService();
    $raporModel = new RaporModel();
    $tarih = !empty($_POST["rapor_tarihi"]) ? $_POST["rapor_tarihi"] : date('Y-m-d');

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

            // Eğer rapor durumu "ONAYLI" veya "ONAYLANDI" içeriyorsa bu raporu atla (SGK'dan gelen veri)
            if ((isset($rapor['RAPORDURUMADI']) && stripos($rapor['RAPORDURUMADI'], 'ONAY') !== false) ||
                (isset($rapor['ONAYLI']) && ($rapor['ONAYLI'] == '1' || $rapor['ONAYLI'] == 'E')) ||
                (isset($rapor['ONAYDURUMU']) && ($rapor['ONAYDURUMU'] == '1' || $rapor['ONAYDURUMU'] == 'E'))) {
                continue;
            }

            // Eğer bu rapor bizim veritabanımızda zaten onaylanmış görünüyorsa atla
            if ($raporModel->findReportByRaporTakipNo($rapor['RAPORTAKIPNO'])) {
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


            // İşlenmiş raporu listeye ekle
            $islenmisRaporlar[] = $rapor;
            $toplamBulunanRaporSayisi++; 
        }
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
                <style>
                    /* Premium Filter Card Styling */
                    .filter-card {
                        background: #fdfdfd;
                        border: 1px solid #eaeaea;
                        border-radius: 16px;
                        padding: 15px 20px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
                        margin-bottom: 25px;
                    }
                    .search-form-layout {
                        margin-bottom: 0;
                    }
                    .date-label-style {
                        font-weight: 700;
                        color: #34495e;
                        font-size: 0.95rem;
                        margin-right: 15px;
                        margin-bottom: 0;
                    }
                    .custom-date-input {
                        height: 46px !important;
                        border-radius: 10px !important;
                        border: 1px solid #ced4da !important;
                        padding: 10px 16px !important;
                        font-size: 0.95rem !important;
                        background-color: #fff !important;
                        box-shadow: inset 0 1px 2px rgba(0,0,0,0.05) !important;
                        transition: border-color 0.2s, box-shadow 0.2s !important;
                        width: 200px;
                    }
                    .custom-date-input:focus {
                        border-color: #007bff !important;
                        box-shadow: 0 0 0 3px rgba(0,123,255,0.15) !important;
                    }
                    .custom-search-btn {
                        height: 46px !important;
                        width: 46px !important;
                        border-radius: 10px !important;
                        padding: 0 !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        margin: 0 !important;
                        background-color: #2c3e50 !important;
                        border-color: #2c3e50 !important;
                        box-shadow: 0 3px 12px rgba(44,62,80,0.15) !important;
                        transition: all 0.2s !important;
                    }
                    .custom-search-btn:hover {
                        background-color: #1a252f !important;
                        border-color: #1a252f !important;
                        transform: translateY(-1px) !important;
                        box-shadow: 0 5px 15px rgba(44,62,80,0.25) !important;
                    }
                    .custom-export-btn {
                        height: 46px !important;
                        border-radius: 10px !important;
                        padding: 0 20px !important;
                        font-weight: 600 !important;
                        font-size: 0.9rem !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        gap: 8px !important;
                        margin: 0 !important;
                        transition: all 0.2s !important;
                    }
                    .custom-export-btn#export-excel {
                        color: #2e7d32 !important;
                        border: 1px solid #2e7d32 !important;
                        background: transparent !important;
                    }
                    .custom-export-btn#export-excel:hover {
                        background-color: #e8f5e9 !important;
                        transform: translateY(-1px) !important;
                    }
                    .custom-export-btn#export-pdf {
                        background-color: #c62828 !important;
                        border-color: #c62828 !important;
                        color: #fff !important;
                        box-shadow: 0 3px 12px rgba(198,40,40,0.15) !important;
                    }
                    .custom-export-btn#export-pdf:hover {
                        background-color: #b71c1c !important;
                        transform: translateY(-1px) !important;
                        box-shadow: 0 5px 15px rgba(198,40,40,0.25) !important;
                    }

                    /* Mobile Responsive Adjustments */
                    @media (max-width: 991px) {
                        .filter-card {
                            padding: 16px;
                            flex-direction: column !important;
                            align-items: stretch !important;
                            gap: 15px !important;
                        }
                        .search-form-layout {
                            flex-direction: column !important;
                            align-items: stretch !important;
                            width: 100% !important;
                            gap: 10px !important;
                        }
                        .date-label-style {
                            margin-bottom: 0 !important;
                            margin-right: 0 !important;
                        }
                        .input-with-button {
                            display: flex !important;
                            flex-direction: row !important;
                            align-items: center !important;
                            gap: 10px;
                            width: 100% !important;
                        }
                        .custom-date-input {
                            flex: 1 !important;
                            width: auto !important;
                        }
                        .custom-search-btn {
                            width: 46px !important;
                            height: 46px !important;
                        }
                        .export-buttons-layout {
                            display: flex;
                            width: 100%;
                            gap: 10px;
                        }
                        .custom-export-btn {
                            flex: 1 !important;
                            height: 42px !important;
                            font-size: 0.85rem !important;
                        }
                    }
                </style>

                <!-- Tarih Seçimi, Arama ve Dışa Aktarma Elemanları -->
                <div class="filter-card d-flex align-items-center justify-content-between flex-wrap">
                    <!-- Sol Taraf: Arama Formu -->
                    <form action="onay-bekleyen-raporlar" method="POST" class="search-form-layout d-flex align-items-center flex-wrap" style="gap: 15px;">
                        <label for="rapor_tarihi" class="date-label-style">Rapor Tarihi</label>
                        <div class="input-with-button d-flex align-items-center" style="gap: 10px;">
                            <input type="date" id="rapor_tarihi" name="rapor_tarihi" value="<?php echo htmlspecialchars($tarih); ?>" class="form-control custom-date-input">
                            <button type="submit" name="rapor_ara_buton" class="btn btn-primary btn-round waves-effect custom-search-btn">
                                <i class="zmdi zmdi-search" style="font-size: 1.2rem;"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Sağ Taraf: Excel / PDF Butonları (Tablo verisi varsa gösterilir) -->
                    <?php if (!empty($raporlar)): ?>
                        <div class="export-buttons-layout d-flex align-items-center" style="gap: 12px;">
                            <button type="button" id="export-excel" class="btn btn-outline-primary btn-round waves-effect custom-export-btn">
                                <i class="zmdi zmdi-file-text"></i> Excel'e Aktar
                            </button>
                            <button type="button" id="export-pdf" class="btn btn-danger btn-round waves-effect custom-export-btn">
                                <i class="zmdi zmdi-file"></i> PDF'e Aktar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

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
                    <div class="responsive d-none d-md-block">


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
                                                <?php 
                                                $is_future = false;
                                                $is_invalid_date = ($rapor['ABITTAR'] == '0001-01-01' || empty($rapor['ABITTAR']));
                                                try {
                                                    $raporBitis = new DateTime($rapor['ABITTAR']);
                                                    $raporBitis->setTime(0, 0, 0);
                                                    $bugun = new DateTime();
                                                    $bugun->setTime(0, 0, 0);
                                                    if ($raporBitis > $bugun || $is_invalid_date) {
                                                        $is_future = true;
                                                    }
                                                } catch (Exception $e) {
                                                    $is_future = true;
                                                }
                                                $disabled_attr = $is_future ? 'disabled title="Rapor süresi henüz dolmadı veya bitiş tarihi belirsiz"' : '';
                                                ?>
                                                <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
                                                    <button type="button" class="btn btn-info btn-sm btn-onayla" <?php echo $disabled_attr; ?>>
                                                    <!-- İkon Ekle     -->
                                                     <i class="zmdi zmdi-check zmdi-hc-fw"></i>
                                                    Onayla</button>
                                                    <button class="btn btn-secondary btn-sm btn-personel-degil">
                                                    <i class="zmdi zmdi-folder-person zmdi-hc-fw"></i>    
                                                    Personelim Değil</button>
                                                    <button type="button" class="btn btn-warning btn-sm btn-kapat" <?php echo $disabled_attr; ?> data-id="<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>">
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

                    <!-- MOBİL LİSTE GÖRÜNÜMÜ -->
                    <div class="mobile-rapor-container d-md-none d-block">
                        <div id="mobile-rapor-yok-mesaji" class="text-center p-4 bg-white rounded shadow-sm text-muted mb-3" style="display: none; border: 1px dashed #dee2e6;">
                            Onay bekleyen rapor bulunamadı.
                        </div>
                        <?php if (!empty($raporlar)): ?>
                            <?php 
                            $mi = 0;
                            foreach ($raporlar as $rapor): 
                                $mi++;
                                $is_future = false;
                                $is_invalid_date = ($rapor['ABITTAR'] == '0001-01-01' || empty($rapor['ABITTAR']));
                                try {
                                    $raporBitis = new DateTime($rapor['ABITTAR']);
                                    $raporBitis->setTime(0, 0, 0);
                                    $bugun = new DateTime();
                                    $bugun->setTime(0, 0, 0);
                                    if ($raporBitis > $bugun || $is_invalid_date) {
                                        $is_future = true;
                                    }
                                } catch (Exception $e) {
                                    $is_future = true;
                                }
                                $disabled_attr = $is_future ? 'disabled title="Rapor süresi henüz dolmadı veya bitiş tarihi belirsiz"' : '';

                                // Vaka türüne göre renk belirle
                                $badge_style = 'background-color: #007bff;'; // Varsayılan mavi
                                if (stripos($rapor['VAKAADI'], 'ANALIK') !== false) {
                                    $badge_style = 'background-color: #ff69b4;'; // Pembe
                                }
                            ?>
                                <div class="card mobile-rapor-card p-3 mb-4 border-0 shadow-sm <?php echo $is_future ? 'opacity-75' : ''; ?>" data-rapor='<?php echo htmlspecialchars(json_encode($rapor)); ?>' data-gun-farki="<?php echo $rapor['gun_farki']; ?>" style="border-radius: 12px; background: #fff; border: 1px solid <?php echo $is_future ? '#ffeeba' : '#eaeaea'; ?> !important; box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important;">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="font-weight-bold mb-0" style="font-size: 1.1rem; color: #2c3e50; font-weight: 700;"><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></h6>
                                            <span class="badge text-white rounded-pill px-2.5 py-1" style="font-size: 0.7rem; font-weight: 600; <?php echo $badge_style; ?>"><?php echo htmlspecialchars($rapor['VAKAADI']); ?></span>
                                        </div>
                                        <span class="text-muted" style="font-size: 0.8rem;">TC: <?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></span>
                                    </div>

                                    <div class="row text-center bg-light rounded p-2 mb-3 g-0">
                                        <div class="col-5">
                                            <div class="text-muted small" style="font-size: 0.75rem;">Başlangıç</div>
                                            <div class="font-weight-bold" style="color: #34495e; font-size: 0.9rem;"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></div>
                                        </div>
                                        <div class="col-2 d-flex align-items-center justify-content-center">
                                            <i class="zmdi zmdi-arrow-right text-muted" style="font-size: 1.2rem;"></i>
                                        </div>
                                        <div class="col-5">
                                            <div class="text-muted small" style="font-size: 0.75rem;">Bitiş</div>
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <div class="font-weight-bold" style="color: #34495e; font-size: 0.9rem;"><?php echo htmlspecialchars($rapor['ABITTAR']); ?></div>
                                                <?php if (!$is_invalid_date): ?>
                                                    <span class="badge bg-danger text-white rounded-pill" style="font-size: 0.65rem; padding: 2px 5px;"><?php echo $rapor['gun_farki']; ?> G</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small font-weight-bold mb-1">Nitelik Durumu</label>
                                        <select class="form-control nitelik-durumu" style="border-radius: 8px; border: 1px solid #ced4da; height: auto; padding: 6px 12px; font-size: 0.9rem;">
                                            <option value="0">ÇALIŞMAMIŞTIR</option>
                                            <option value="1">ÇALIŞMIŞTIR</option>
                                        </select>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
                                            <button type="button" class="btn btn-success w-100 py-2.5 btn-onayla d-flex align-items-center justify-content-center gap-1" <?php echo $disabled_attr; ?> style="border-radius: 8px; font-weight: 600; background-color: <?php echo $is_future ? '#6c757d' : '#2cc711'; ?>; border-color: <?php echo $is_future ? '#6c757d' : '#2cc711'; ?>; font-size: 0.95rem; color: #fff;">
                                                <i class="zmdi zmdi-check"></i> Onayla
                                            </button>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-outline-secondary w-100 py-2 btn-personel-degil d-flex align-items-center justify-content-center gap-1" style="border-radius: 8px; font-size: 0.8rem;">
                                                        <i class="zmdi zmdi-folder-person"></i> Personelim Değil
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-warning w-100 py-2 btn-kapat d-flex align-items-center justify-content-center gap-1 text-white" <?php echo $disabled_attr; ?> data-id="<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>" style="border-radius: 8px; font-size: 0.8rem; <?php if($is_future) echo 'background-color: #ffc107; opacity: 0.65;'; ?>">
                                                        <i class="zmdi zmdi-block"></i> Raporu Kapat
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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