<?php
use App\Helper\Security;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

require_once 'Core/Services/SgkViziteService.php';
use Models\RaporModel;


$raporlar = [];
$hataMesaji = '';
$toplamRaporSayisi = 0; // SGK'dan gelen ham rapor sayısı
$onayBekleyenSayisi = 0; // Arşivlenmemiş olanların sayısı

try {
    $sgkClient = new SgkViziteService();
    $raporModel = new RaporModel();
    $tarih = $_POST["rapor_tarihi"]; // Bir önceki sayfadan POST ile geldiğini varsayıyoruz.

    // 1. SGK'dan tüm raporları çek
    $tumRaporlar = $sgkClient->raporlariGetir(new DateTime($tarih));
    $toplamRaporSayisi = count($tumRaporlar);
    
    // 2. Raporları işle ve ön filtreleme yap
    if (!empty($tumRaporlar)) {
        
        $islenmisRaporlar = []; 
    
        foreach ($tumRaporlar as $rapor) {
            // Arşivlenmiş olanları bu listede hiç gösterme
            if(isset($rapor['ARSIV']) && $rapor['ARSIV'] == 1){
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
                } catch (Exception $e) { /* Hatalı tarihi atla */ }
            }
               
            // Her rapora süresini ekle
            $rapor['gun_farki'] = $gunFarki;
            
            // İşlenmiş raporu listeye ekle
            $islenmisRaporlar[] = $rapor;
        }
    
        // Gösterilecek raporlar, arşivlenmemiş olanların tamamıdır
        $raporlar = $islenmisRaporlar;
        $onayBekleyenSayisi = count($raporlar);
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

<section class="content">
    <div class="container">
        <div class="card">
            <div class="header row d-flex justify-content-between align-items-center">
                <div class="col-lg-6">
                    <h2><strong>Onay Bekleyen Rapor Listesi.</strong></h2>
                    <small id="rapor-sayi-bilgisi">
                        <!-- Bu metin JavaScript tarafından dinamik olarak güncellenecek -->
                    </small>
                </div>
                <div class="col-lg-3">
                    <div class="checkbox">
                        <input id="kisa-rapor-goster-cb" type="checkbox">
                        <label for="kisa-rapor-goster-cb">3 Günden Kısa Raporları Göster</label>
                    </div>
                </div>
                <div class="col-lg-3 d-flex justify-content-end text-nowrap">
                    <a href="tarihe-gore-rapor-ara" class="btn btn-raised btn-primary btn-round waves-effect float-right"><i class="zmdi zmdi-arrow-back"></i> Geri Dön</a>
                    <a href="onayli-rapor-ara" class="btn btn-raised btn-primary btn-simple btn-round waves-effect float-right ms-2"><i class="zmdi zmdi-check"></i> Onaylı Raporlar</a>
                </div>
            </div>
            <div class="body">
                <?php if ($hataMesaji): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($hataMesaji); ?></div>
                <?php else: ?>
                    <div class="table-responsive">
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
                                <?php if (!empty($raporlar)): $i = 0; ?>
                                    <?php foreach ($raporlar as $rapor): $i++; ?>
                                        <!-- TR YAPISI DÜZELTİLDİ -->
                                        <tr data-rapor='<?php echo htmlspecialchars(json_encode($rapor)); ?>' data-gun-farki="<?php echo $rapor['gun_farki']; ?>">
                                            <td><?php echo $i; ?></td>
                                            <td><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['AD'] . ' ' . $rapor['SOYAD']); ?></td>
                                            <td><?php echo htmlspecialchars($rapor['VAKAADI']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($rapor['ISBASKONTTAR']); ?></td> <!-- ABITTAR değil, ISBASKONTTAR daha doğru -->
                                            <td class="text-center"><?php echo htmlspecialchars($rapor['gun_farki']); ?></td>
                                            <td>
                                                <select class="form-control nitelik-durumu">
                                                    <option value="0">ÇALIŞMAMIŞTIR</option>
                                                    <option value="1">ÇALIŞMIŞTIR</option>
                                                </select>
                                            </td>
                                            <td class="text-center d-flex justify-content-center align-items-center gap-1">
                                                <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
                                                    <button type="button" class="btn btn-info btn-md btn-onayla">Onayla</button>
                                                    <button type="button" class="btn btn-secondary btn-md btn-personel-degil">Personelim Değil</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <!-- "Rapor Yok" mesajı JavaScript tarafından dinamik olarak yönetilecek -->
                                <tr id="rapor-yok-mesaji" style="display: none;">
                                    <td colspan="9" class="text-center p-3">Listelenecek onay bekleyen rapor bulunamadı.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- DİKKAT: rapor_onay.js dosyanızın içeriğini bu script ile güncelleyin veya bu script'i o dosyanın yerine koyun -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- FİLTRELEME İŞLEVİ ---
    const checkbox = document.getElementById('kisa-rapor-goster-cb');
    const raporSatirlari = document.querySelectorAll('tbody tr[data-gun-farki]');
    const raporYokMesaji = document.getElementById('rapor-yok-mesaji');
    const raporSayiBilgisi = document.getElementById('rapor-sayi-bilgisi');
    const toplamOnayBekleyen = <?php echo $onayBekleyenSayisi; ?>;

    function filtreleRaporlari() {
        let gosterilenSayisi = 0;
        const kisaRaporlariGoster = checkbox.checked;

        raporSatirlari.forEach(satir => {
            const gunFarki = parseInt(satir.dataset.gunFarki, 10);
            if (kisaRaporlariGoster || gunFarki >= 3) {
                satir.style.display = '';
                gosterilenSayisi++;
            } else {
                satir.style.display = 'none';
            }
        });

        // Rapor yok mesajını göster/gizle
        raporYokMesaji.style.display = (gosterilenSayisi === 0) ? '' : 'none';

        // Bilgi metnini güncelle
        const tarih = "<?php echo (new DateTime($tarih))->format('d.m.Y'); ?>";
        if (kisaRaporlariGoster) {
             raporSayiBilgisi.innerHTML = `SGK'dan bulunan <strong>${toplamOnayBekleyen}</strong> raporun tamamı listelenmektedir.`;
        } else {
            raporSayiBilgisi.innerHTML = `${tarih} tarihine kadar bulunan <strong>${toplamOnayBekleyen}</strong> rapor içerisinden, 3 gün ve üzeri olan <strong>${gosterilenSayisi}</strong> rapor listelenmektedir.`;
        }
    }

    checkbox.addEventListener('change', filtreleRaporlari);
    filtreleRaporlari(); // Sayfa yüklendiğinde ilk filtrelemeyi yap

    // --- ONAY VE DİĞER İŞLEMLER ---
    // Bu kısım rapor_onay.js dosyanızda zaten mevcut olabilir.
    // Eğer ayrı bir dosyadaysa, bu filtreleme kodunu o dosyanın içine taşıyabilirsiniz.
    // Örnek:
    document.querySelectorAll('.btn-onayla').forEach(button => {
        button.addEventListener('click', function() {
            const satir = this.closest('tr');
            const raporData = JSON.parse(satir.dataset.rapor);
            const nitelikDurumu = satir.querySelector('.nitelik-durumu').value;
            
            // ... (SweetAlert ve fetch ile AJAX çağrınız burada devam eder) ...
            console.log("Onaylanacak Rapor:", raporData, "Nitelik:", nitelikDurumu);
            // callApi('raporOnayla', { raporData, nitelikDurumu }, satir);
        });
    });
    // ... (btn-personel-degil için de benzer bir event listener) ...
});
</script>

<?php include 'layouts/foot.php'; ?>
<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script src="App/Src/rapor_onay.js?v=<?php echo filemtime('App/Src/rapor_onay.js'); ?>"></script>
<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>