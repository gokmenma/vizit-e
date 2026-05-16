<?php
use App\Helper\Security;
Security::checkUserRole();

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$hataMesaji = '';
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';
$title = 'Mahsuplaştırılacak Raporlar';
$mahsuplasmaRaporlari = []; // Yeni değişken adı
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
            $mahsuplasmaRaporlari = $sgkClient->mahsuplastirilacakRaporlariGetir($tarih1, $tarih2);
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
            <a href="mahsuplastirilan-raporlar">

                    <div class="card project_widget">
                    <div class="body">
                        <div class="row pw_content">
                            <div class="col-12 pw_header">
                                <h6>Mahsuplaştırma Onaylanan Ödeme Listesi</h6>
                                <small class="text-muted">
                                    Onaylanan mahsuplaştırma ödemelerini görüntüleyebilirsiniz.
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
                <h2><strong>Mahsuplaştırılacak Rapor Sorgula</strong></h2>
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
                <div class="header d-flex justify-content-between align-items-center">
                    <h2><strong>Mahsuplaştırılacak Ödeme Listesi</strong></h2>
                    <div>
                        <button type="button" class="btn btn-info waves-effect excel-aktar">
                            <i class="zmdi zmdi-file-text"></i> Excele Aktar
                        </button>
                        <button type="button" class="btn btn-success waves-effect secilenleri-mahsuplastir" disabled>
                            <i class="zmdi zmdi-check-all"></i> Seçilenleri Mahsuplaştır
                        </button>
                    </div>
                </div>
                <div class="body table-responsive">
                    <?php if ($hataMesaji): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
                    <?php else: ?>
                        <table class="table table-bordered table-hover dataTable js-exportable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <div class="checkbox">
                                            <input id="select_all" type="checkbox" class="select-all">
                                            <label for="select_all" style="margin-bottom: 0; padding-left: 0;"></label>
                                        </div>
                                    </th>
                                    <th>Sıra</th>
                                    <th>TC Kimlik No</th>
                                    <th>Ad Soyad</th>
                                    <th>Vaka</th>
                                    <th>Ödenek Başlangıç</th>
                                    <th>Ödenek Bitiş</th>
                                    <th>Ödenen Tutar</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($mahsuplasmaRaporlari)): $i = 0; ?>
                                    <?php foreach ($mahsuplasmaRaporlari as $rapor): $i++; ?>
                                        <tr data-rapor='<?php echo htmlspecialchars(json_encode($rapor)); ?>'>
                                            <td class="text-center">
                                                <div class="checkbox">
                                                    <input id="checkbox_<?php echo $rapor['id']; ?>" class="rapor-checkbox" type="checkbox" value="<?php echo $rapor['id']; ?>">
                                                    <label for="checkbox_<?php echo $rapor['id']; ?>" style="margin-bottom: 0; padding-left: 0;"></label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php echo htmlspecialchars($i ?? ''); ?>

                                            </td>
                                            <td><?php echo htmlspecialchars($rapor['tcKimlikNo'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['adiSoyadi'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['vakaAdi'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['odemeBasTar'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['odemeBitTar'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['odenenTutar'] ?? ''); ?> TL</td>
                                            <td class="text-center">
                                                <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
                                                    <button class="btn btn-success btn-icon btn-icon-mini btn-round mahsuplastir" title="Mahsuplaştır">
                                                        <i class="zmdi zmdi-check" style="font-weight: 900;"></i>
                                                    </button>
                                                <?php endif ?>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Belirtilen kriterlere uygun mahsuplaştırılacak rapor
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

<!-- Script'leri dahil ediyoruz -->
<?php include 'layouts/vendor-scripts.php'; ?>

<style>

</style>

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

        // Excel Aktar Butonu
        document.querySelector('.excel-aktar').addEventListener('click', function() {
            // DataTables'ın kendi excel butonunu tetikle
            const dtButton = document.querySelector('.buttons-excel');
            if (dtButton) {
                dtButton.click();
            } else {
                // Eğer buton yoksa manuel export (DataTables exportable zaten açık olmalı)
                $('.js-exportable').DataTable().button('.buttons-excel').trigger();
            }
        });

        // Seçilenleri Mahsuplaştır Butonu
        const secilenleriMahsuplastirBtn = document.querySelector('.secilenleri-mahsuplastir');
        const selectAllCheckbox = document.getElementById('select_all');
        const raporCheckboxes = document.querySelectorAll('.rapor-checkbox');

        // Checkbox değişimlerini izle
        function updateBulkButtonState() {
            const checkedCount = document.querySelectorAll('.rapor-checkbox:checked').length;
            secilenleriMahsuplastirBtn.disabled = checkedCount === 0;
            if (checkedCount === 0) {
                secilenleriMahsuplastirBtn.innerHTML = '<i class="zmdi zmdi-check-all"></i> Seçilenleri Mahsuplaştır';
            } else {
                secilenleriMahsuplastirBtn.innerHTML = `<i class="zmdi zmdi-check-all"></i> Seçilenleri Mahsuplaştır (${checkedCount})`;
            }
        }

        selectAllCheckbox.addEventListener('change', function() {
            raporCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkButtonState();
        });

        raporCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkButtonState);
        });

        secilenleriMahsuplastirBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.rapor-checkbox:checked');
            const secilenRaporlar = [];
            
            checkedBoxes.forEach(cb => {
                const satir = cb.closest('tr');
                const raporData = JSON.parse(satir.dataset.rapor);
                secilenRaporlar.push(raporData);
            });

            if (secilenRaporlar.length === 0) return;

            swal.fire({
                title: "Emin misiniz?",
                text: `${secilenRaporlar.length} adet raporu toplu olarak mahsuplaştırmak üzeresiniz.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                confirmButtonText: "Evet, hepsini mahsuplaştır!",
                cancelButtonText: "Hayır, iptal et",
                showLoaderOnConfirm: true,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    var formData = new FormData();
                    formData.append('action', 'mahsuplastiOnaylaToplu');
                    formData.append('raporData', JSON.stringify(secilenRaporlar));

                    fetch('App/Api/APImahsuplastirma.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            swal.fire("Başarılı!", data.message, "success").then(() => {
                                // Sayfayı yenileyebiliriz veya satırları silebiliriz
                                location.reload();
                            });
                        } else {
                            swal.fire("Hata!", "İşlem başarısız oldu: " + data.message, "error");
                        }
                    })
                    .catch(error => {
                        swal.fire("Ağ Hatası!", "Sunucuya ulaşılamadı: " + error, "error");
                    });
                }
            });
        });

        // Tekli mahsuplaştırma butonları
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
                    console.log(`${key}: ${value}`); // Form verilerini konsola yazdır (debugging için)
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
                                    swal.fire("Hata!", "İşlem başarısız oldu: " + data.message,
                                        "error");
                                }
                            })
                            .catch(error => {
                                // 4. ADIM: Ağ hatası olursa, hata mesajı göster
                                swal.fire("Ağ Hatası!", "Sunucuya ulaşılamadı: " + error,
                                    "error");
                            });
                    }
                });
            });
        });
    });







</script>








<?php include 'layouts/foot.php'; ?>