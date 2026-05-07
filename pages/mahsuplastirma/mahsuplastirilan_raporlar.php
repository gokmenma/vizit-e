<?php

use App\Helper\Security;

Security::checkUserRole();

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


$title = 'Mahsuplaştırılan Raporlar'; // Sayfa başlığı

require_once 'Core/Services/SgkViziteService.php';

$mahsuplasmisRaporlar = []; // Yeni değişken adı
$hataMesaji = '';
$formGonderildi = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sorgula_buton'])) {
    $formGonderildi = true;
    if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
        $hataMesaji = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {
        try {
            $sgkClient = new SgkViziteService();

            $tarih1 = new DateTime($_POST['tarih1']);
            $tarih2 = new DateTime($_POST['tarih2']);



            // Yeni metodumuzu çağırıyoruz
            $mahsuplasmisRaporlar = $sgkClient->mahsuplasmisRaporlariGetir($tarih1, $tarih2);

            //var_dump($mahsuplasmisRaporlari); // Debugging için, daha sonra kaldırabilirsiniz

        } catch (Exception $e) {
            $hataMesaji = $e->getMessage();
        }
    }
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
        <div class="row pt-2">
            <div class="col-lg-6 col-md-12">
                <a href="mahsuplastirilacak-raporlar">


                    <div class="card project_widget">
                        <div class="body">
                            <div class="row pw_content">
                                <div class="col-12 pw_header">
                                    <h6>Mahsuplaştıracak Rapor Listesi</h6>
                                    <small class="text-muted">
                                        Mahsuplaştıracak raporları görüntüleyebilirsiniz.
                                    </small>

                                </div>

                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6 col-md-12">
                <a href="prim-borcuna-mahsup-edilen-odemeler">
                
                <div class="card project_widget">
                    <div class="body">
                        <div class="row pw_content">
                            <div class="col-12 pw_header">
                                <h6> İşveren Prim Borcuna Mahsup Edilen Ödeme Listesi</h6>
                                <small class="text-muted">
                                    İşveren prim borcuna mahsup edilen ödemeleri görüntüleyebilirsiniz.

                                </small>

                            </div>

                        </div>
                    </div>

                </div>
</a>

            </div>

        </div>
        <!-- Sorgulama Formu -->
        <div class="card">
            <div class="header">
                <h2><strong>Mahsuplaştırma Onaylanan Ödeme Listesi</strong></h2>
            </div>
            <div class="body d-flex justify-content-center align-items-center">
                <form method="post">
                    <div class="row">

                        <div class="col-lg-5 col-md-5">
                            <label for="tarih1">Başlangıç Tarihi</label>
                            <div class="form-group">
                                <input type="date" id="tarih1" name="tarih1" class="form-control"
                                    value="<?php echo $_POST['tarih1'] ?? date('Y-m-01'); ?>">
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-5">
                            <label for="tarih2">Bitiş Tarihi</label>
                            <div class="form-group">
                                <input type="date" id="tarih2" name="tarih2" class="form-control"
                                    value="<?php echo $_POST['tarih2'] ?? date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label>Ara</label>
                            <button type="submit" name="sorgula_buton"
                                class="btn btn-primary waves-effect mt-0">Sorgula</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <!-- Sonuçlar Bölümü -->
        <?php if ($formGonderildi): ?>

        <div class="card">
            <div class="header">
                <h2><strong>Mahsuplaştırılan Ödeme Listesi</strong></h2>
            </div>
            <div class="body">
                <?php if ($hataMesaji): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
                <?php else: ?>
                <table class="table table-bordered table-hover dataTable js-exportable table-responsive">
                    <thead>
                        <tr>
                            <th>TC Kimlik No</th>
                            <th>Ad Soyad</th>
                            <th>Vaka</th>
                            <th>Ödenek Dönemi</th>
                            <th>Ödenen Tutar</th>
                            <th>Mahsuplaşma Tarihi</th>
                            <th>Makbuz Durumu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (!empty($mahsuplasmisRaporlar)): ?>
                        <div class="export-buttons float-right mb-3">
                            <button id="export-excel" class="btn btn-primary btn-simple waves-effect">Excel'e
                                Aktar</button>
                            <button id="export-pdf" class="btn btn-primary waves-effect">PDF'e Aktar</button>
                        </div>
                        <?php foreach ($mahsuplasmisRaporlar as $rapor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rapor['tcKimlikNo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($rapor['adiSoyadi'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($rapor['vakaAdi'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(($rapor['odemeBasTar'] ?? '') . ' - ' . ($rapor['odemeBitTar'] ?? '')); ?>
                            </td>
                            <td><?php echo htmlspecialchars($rapor['odenenTutar'] ?? ''); ?> TL</td>
                            <td><?php echo htmlspecialchars($rapor['mahsuplasmaTar'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($rapor['durumStr'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center">Belirtilen kriterlere uygun onaylanmış mahsuplaşma kaydı
                                bulunamadı.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>


                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages\mahsuplastirma\export.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Script'leri dahil ediyoruz -->
<?php include 'layouts/vendor-scripts.php'; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {

    //Tarih1 alanında seçim yapınca tarih2 alanını o ayın son günü yap
    const tarih1Input = $('#tarih1');
    const tarih2Input = $('#tarih2');

    tarih1Input.on('change', function() {
        let tarih1 = new Date(this.value);
        if (isNaN(tarih1)) return;

        // Ayın son günü hesapla
        let yil = tarih1.getFullYear();
        let ay = tarih1.getMonth() + 1; // Aylar 0’dan başlar
        let sonGun = new Date(yil, ay, 0).getDate();

        // YYYY-MM-DD formatına çevir
        let ayStr = String(ay).padStart(2, '0');
        let gunStr = String(sonGun).padStart(2, '0');
        let tarih2 = `${yil}-${ayStr}-${gunStr}`;

        tarih2Input.val(tarih2);
    });



    // Tüm mahsuplaştır butonlarını seç
    const butonlar = document.querySelectorAll('.mahsuplastir');

    butonlar.forEach(buton => {
        buton.addEventListener('click', function(event) {

            const satir = this.closest('tr'); // Butonun bulunduğu satırı bul
            const raporData = JSON.parse(satir.dataset
                .rapor); // Satırın data-rapor özelliğindeki JSON verisini al




            var formData = new FormData();
            formData.append('action', 'mahsuplastiOnayla');
            formData.append('raporData', JSON.stringify(raporData));

            for (let [key, value] of formData.entries()) {
                console.log(
                    `${key}: ${value}`); // Form verilerini konsola yazdır (debugging için)
            }

            // 1. ADIM: Kullanıcıdan SweetAlert ile onay al
            swal.fire({
                title: "Emin misiniz?",
                text: raporData.adiSoyadi +
                    " kişisine ait raporu mahsuplaştırmak üzeresiniz.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745", // Yeşil renk
                confirmButtonText: "Evet, mahsuplaştır!",
                cancelButtonText: "Hayır, iptal et",
                closeOnConfirm: false, // Onaydan sonra hemen kapatma, biz kendimiz kapatacağız
                showLoaderOnConfirm: true, // Onay butonuna basınca yükleme animasyonu göster
                reverseButtons: true // Butonların sırasını değiştir
            }).then((result) => {
                // Kullanıcı "iptal" derse hiçbir şey yapma
                if (result.isConfirmed) {
                    // 2. ADIM: Kullanıcı onaylarsa, API'ye isteği gönder
                    fetch('App/Api/APImahsuplastirma.php', {

                            method: 'POST',
                            body: formData, // Form verilerini gönder
                        })
                        .then(response => response.json())
                        .then(data => {
                            // 3. ADIM: API'den gelen sonuca göre farklı bir SweetAlert göster
                            if (data.status === 'success') {
                                console.log(data);
                                swal.fire("Başarılı!", data.message, "success");

                                satir.style.transition = "opacity 0.5s ease";
                                satir.style.opacity = "0";
                                setTimeout(() => {
                                    satir.remove();
                                }, 500); // 0.5 saniye bekle
                            } else {
                                swal.fire("Hata!", "İşlem başarısız oldu: " + data
                                    .message,
                                    "error");
                            }
                        })
                        .catch(error => {
                            // 4. ADIM: Ağ hatası olursa, hata mesajı göster
                            swal.fire("Ağ Hatası!", "Sunucuya ulaşılamadı: " +
                                error,
                                "error");
                        });
                }
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const btnExcel = document.getElementById('export-excel');
    const btnPdf = document.getElementById('export-pdf');

    // PHP'den gelen raporlar dizisini JavaScript'e aktarıyoruz
    const raporlarData = <?php echo json_encode($mahsuplasmisRaporlar); ?>;

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

        // Formu gönder
        document.getElementById('export-form').submit();
    }
});
</script>






<?php include 'layouts/foot.php'; ?>