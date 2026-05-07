<?php
require_once 'Core/Services/SgkViziteService.php';
use App\Helper\Security;


// ... (Diğer require ve Security kontrolleriniz)

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$hataMesaji = '';
$gecmisBildirimler = [];
$formGonderildi = false;

$title = 'Manuel Rapor Görüntüleme';

// Form gönderilmişse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['goruntule_buton'])) {
    $formGonderildi = true;
    $tcKimlikNo = $_POST['tc_kimlik_no'] ?? '';

    if (empty($tcKimlikNo)) {
        $hataMesaji = 'Lütfen görüntülemek için bir TC Kimlik Numarası girin.';
    } else {
        try {
            $sgkClient = new SgkViziteService(); // Session'dan bilgileri alacak
            $gecmisBildirimler = $sgkClient->manuelBildirimleriGetir($tcKimlikNo);
        } catch (Exception $e) {
            $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
        }
    }
}
?>
<!-- Head ve diğer layout include'ları -->
<?php include 'layouts/head.php'; ?>
<?php include 'layouts/preloader.php'; ?>
<?php include 'layouts/topbar.php'; ?>
<?php include 'layouts/navbar.php'; ?>

<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">
        <!-- ARAMA FORMU -->
        <div class="card">
            <div class="header">
                <h2><strong>Manuel Bildirilen Rapor Görüntüleme</strong></h2>
                <small>Görüntülemek istediğiniz kişinin TC Kimlik Numarasını giriniz</small>
            </div>
            <div class="body">
                <form method="post">
                    <div class="row clearfix align-items-end">
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <label for="tc_kimlik_no"><b>TC Kimlik No</b></label>
                            <input type="text" id="tc_kimlik_no" name="tc_kimlik_no" value="<?php echo htmlspecialchars($_POST['tc_kimlik_no'] ?? ''); ?>" class="form-control" placeholder="TC Kimlik No...">
                        </div>

                        <div class="col-lg-2 col-md-4 col-sm-12">
                            <label for="goruntule_buton"><b>Ara</b></label>
                            <button type="submit" name="goruntule_buton" class="btn btn-primary btn-round btn-block waves-effect">Görüntüle</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ARAMA SONUÇLARI -->
        <?php if ($formGonderildi): ?>
            <div class="card">
                <div class="header">
                    <h2><strong>Görüntüleme Sonuçları</strong></h2>
                </div>
                <div class="body">
                    <?php if ($hataMesaji): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover dataTable js-exportable">
                                <thead>
                                    <tr>
                                        <th>İşlem Tarihi</th>
                                        <th>TC Kimlik No</th>
                                        <th>Ad Soyad</th>
                                        <th>Rapor Başlangıç</th>
                                        <th>İşe Dönüş</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($gecmisBildirimler)): ?>
                                        <?php foreach ($gecmisBildirimler as $bildirim): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bildirim['islemTar']); ?></td>
                                                <td><?php echo htmlspecialchars($bildirim['tcKimlikNo']); ?></td>
                                                <td><?php echo htmlspecialchars($bildirim['adi'] . ' ' . $bildirim['soyadi']); ?></td>
                                                <td><?php echo htmlspecialchars($bildirim['istenAyrTarih']); ?></td>
                                                <td><?php echo htmlspecialchars($bildirim['iseDonusTarih']); ?></td>
                                                <td><?php echo ($bildirim['nitelikDurumu'] == 'H') ? '<span class="badge badge-warning">Çalışmadı</span>' : '<span class="badge badge-success">Çalıştı</span>'; ?></td>
                                                <td>
                                                    <button data-id="<?php echo htmlspecialchars($bildirim['ID']); ?>"
                                                        data-tc="<?php echo htmlspecialchars($bildirim['tcKimlikNo']) ?>" class="btn btn-danger btn-sm btn-icon btn-icon-mini btn-round waves-effect btn-sil" title="Sil">
                                                        <i class="zmdi zmdi-delete"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Bu kişi için daha önce yapılmış manuel bildirim bulunamadı.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<!-- Script include'ları -->
<?php include 'layouts/vendor-scripts.php'; ?>



<!-- YENİ JAVASCRIPT KODU -->
<script>
    let url = "App/Api/APImanuel_rapor.php"; // API URL'si
    $(document).ready(function() {
        // Tüm sil butonlarını seç

        $(document).on('click', '.btn-sil', function() {
            const bildirimId = $(this).data('id');
            const tcKimlikNo = $(this).data('tc');


            Swal.fire({
                title: "Emin misiniz?",
                text: "Bu işlemi geri alınamaz!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Evet,sil!",
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    var formData = new FormData();
                    formData.append('action', 'manuel_rapor_sil');
                    formData.append('bildirimId', bildirimId);
                    formData.append('tcKimlikNo', tcKimlikNo);


                    // API'ye isteği gönder
                    fetch(url, {
                            method: 'POST',
                            body: formData,
                        })
                        .then(response => response.json())
                        .then(data => {
                           let title= data.status == "success" ? "Başarılı!" : "Hata!";
                           swal.fire({
                                title: title,
                                text: data.message,
                                icon: data.status,
                                confirmButtonText: "Tamam"
                            }).then(() => {
                                if (data.status == "success") {
                                    // Başarılı ise sayfayı yenile
                                    location.reload();
                                }
                            });
                            
                        })
                        .catch(error => {
                            swal.fire("Ağ Hatası!", "Sunucuya ulaşılamadı: " + error, "error");
                        });
                }
            });

        });

    });
</script>
<?php include 'layouts/foot.php'; ?>