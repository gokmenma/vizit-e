<?php $title = 'İletişim Bilgileri'; ?>


<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>


<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>

<?php

use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php';

$hataMesaji = '';

try {
    $sgkClient = new SgkViziteService();
    // Sayfa yüklendiğinde mevcut bilgileri çek
    $mevcutBilgiler = $sgkClient->iletisimBilgileriniGetir();
} catch (Exception $e) {
    $hataMesaji = "Mevcut bilgiler getirilirken bir hata oluştu: " . $e->getMessage();
}
$mevcutEposta = $mevcutBilgiler ? (string)$mevcutBilgiler->eposta : 'Kayıt bulunamadı.';
$mevcutTel = $mevcutBilgiler ? (string)$mevcutBilgiler->tel : 'Kayıt bulunamadı.';
?>


<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">




        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-lg-10">
                            <h2><strong>İletişim Bilgileri</strong></h2>
                            <small>İşyerinizin iletişim bilgilerini görüntüleyebilir ve değiştirebilirsiniz</small>

                        </div>

                    </div>
                    <div class="card">
                        <div class="body">

                            <!-- MEVCUT BİLGİLER BÖLÜMÜ -->
                            <div class="row clearfix">
                                <div class="col-12">
                                    <h6>Mevcut Kayıtlı Bilgiler</h6>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 150px;"><strong>E-Posta:</strong></td>
                                                    <td id="mevcut-eposta"><?php echo htmlspecialchars($mevcutEposta); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Cep Telefonu:</strong></td>
                                                    <td id="mevcut-tel"><?php echo htmlspecialchars($mevcutTel); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <hr> <!-- Ayırıcı çizgi -->


                            <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
                            <!-- GÜNCELLEME FORMU BÖLÜMÜ -->
                            <h6>İletişim Bilgilerini Güncelleme</h6>

                            <!-- Formun kendisi -->
                            <form id="guncelleme-formu" method="post">
                                <div class="row clearfix">
                                    <div class="col-lg-5 col-md-5 col-sm-12">
                                        <label for="yeni-eposta">Yeni E-Posta Adresi</label>
                                        <div class="form-group">
                                            <input type="email" id="yeni-eposta" name="eposta" class="form-control" placeholder="E-Posta Adresi Giriniz">
                                        </div>
                                    </div>

                                    <div class="col-lg-5 col-md-5 col-sm-12">
                                        <label for="yeni-tel">Yeni Cep Telefonu</label>
                                        <div class="form-group">
                                            <input type="text" id="yeni-tel" name="cepTel" class="form-control" placeholder="05xxxxxxxxx (11 Hane)" maxlength="11">
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-md-2 col-sm-12">
                                        <label>&nbsp;</label> <!-- Butonun diğerleriyle aynı hizada olması için -->
                                        <button type="submit" id="kaydet-buton" class="btn btn-primary btn-round btn-block waves-effect">Kaydet</button>
                                    </div>
                                </div>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    İletişim bilgilerini güncelleme yetkiniz bulunmamaktadır. Lütfen sistem yöneticinize başvurun.
                                </div>
                            <?php endif; ?>


                            <?php if ($hataMesaji): ?>
                                <div class="alert alert-danger mt-3">
                                    <strong>Hata:</strong> <?php echo htmlspecialchars($hataMesaji); ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- 
    Bu sayfanın altına bir önceki mesajda verdiğim
    JavaScript kodunu eklemeyi unutmayın. O kod, bu form ile
    sorunsuz bir şekilde çalışacaktır.
-->
                    <script>
                        // Bir önceki mesajdaki SweetAlert'li JavaScript kodu buraya gelecek...
                    </script>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script>
$(document).ready(function() {
    
    // Form gönderildiğinde çalışacak fonksiyon
    $('#guncelleme-formu').on('submit', function(event) {
        event.preventDefault(); // Formun normal gönderimini engelle

        // Butonu referans alalım
        const $kaydetButon = $('#kaydet-buton');
        
        // Form verilerini al
        const yeniEposta = $('#yeni-eposta').val().trim();
        const yeniTel = $('#yeni-tel').val().trim();

        // Boş kontrolü
        if (yeniEposta === '' && yeniTel === '') {
            Swal.fire('Uyarı!', 'Lütfen en az bir iletişim bilgisi giriniz.', 'warning');
            return;
        }

        // AJAX isteğini başlat
        $.ajax({
            url: 'App/Api/APIiletisim_bilgileri.php', // API endpoint URL'si
            type: 'POST',
            dataType: 'json', // Gelen cevabın JSON olacağını belirt
            data: {
                action: 'iletisimGuncelle',
                eposta: yeniEposta,
                cepTel: yeniTel
            },
            // İstek başlamadan önce çalışır
            beforeSend: function() {
                $kaydetButon.prop('disabled', true).text('Kaydediliyor...');
            },
            // İstek başarılı olursa çalışır
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Başarılı!', response.message, 'success').then(() => {
                        location.reload(); // Sayfayı yeniden yükle
                    });
                } else {
                    Swal.fire('Hata!', response.message, 'error');
                }
            },
            // İstek başarısız olursa (ağ hatası vb.) çalışır
            error: function(xhr, status, error) {
                Swal.fire('Ağ Hatası!', 'Sunucuya bağlanırken bir sorun oluştu.', 'error');
            },
            // İstek başarılı da olsa başarısız da olsa en sonda çalışır
            complete: function() {
                $kaydetButon.prop('disabled', false).text('Kaydet');
            }
        });
    });
});
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>