<?php
require_once 'Core/Services/SgkViziteService.php';
use App\Helper\Security;



$mahsupKayitlari = [];
$hataMesaji = '';
$formGonderildi = false;
$title = 'Prim Borcuna Mahsup Edilen Ödemeler';

Security::checkUserRole();

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sorgula_buton'])) {
    $formGonderildi = true;
    if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
        $hataMesaji = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {
        try {
            $sgkClient = new SgkViziteService(); // Session'dan bilgileri alacak

            $tarih1 = new DateTime($_POST['tarih1']);
            $tarih2 = new DateTime($_POST['tarih2']);

            // Yeni metodumuzu çağırıyoruz
            $mahsupKayitlari = $sgkClient->primBorcunaMahsupEdilenleriGetir($tarih1, $tarih2);
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
<div class="animate-in flex flex-col gap-6 w-full py-2 px-1">
    <!-- Sayfa Başlığı -->
    <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Prim Borcuna Mahsup Edilen
                Ödemeler</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                İşveren prim borcuna mahsup edilen ödemelerin sorgulaması ve takibi.
            </p>
        </div>
    </div>



    <!-- Sorgulama Formu -->
    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm">
        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                <i data-lucide="search" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                Prim Borcuna Mahsup Edilen Ödemeleri Sorgula
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Sorgulamak istediğiniz tarih aralığını belirleyin.
            </p>
        </div>

        <form method="post" class="flex flex-col md:flex-row md:items-end gap-4 w-full">
            <div class="form-group flex-1">
                <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="tarih1">Başlangıç
                    Tarihi</label>
                <input type="date" id="tarih1" name="tarih1" class="form-input"
                    value="<?php echo $_POST['tarih1'] ?? date('Y-m-01'); ?>">
            </div>
            <div class="form-group flex-1">
                <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50" for="tarih2">Bitiş
                    Tarihi</label>
                <input type="date" id="tarih2" name="tarih2" class="form-input"
                    value="<?php echo $_POST['tarih2'] ?? date('Y-m-d'); ?>">
            </div>
            <button type="submit" name="sorgula_buton"
                class="btn btn-primary h-9 px-4 flex items-center justify-center gap-1.5 shadow cursor-pointer self-start md:self-auto">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>Sorgula</span>
            </button>
        </form>
    </div>

    <!-- Sonuçlar Bölümü -->
    <?php if ($formGonderildi): ?>
    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm">
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
            <div>
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                    <i data-lucide="list" style="width: 18px; height: 18px;"
                        class="text-zinc-700 dark:text-zinc-300"></i>
                    Mahsup Edilen Ödemeler Listesi
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Sorgu sonucunda bulunan mahsup kayıtları.</p>
            </div>

            <?php if (!empty($mahsupKayitlari)): ?>
            <div class="flex items-center gap-2 self-end md:self-auto">
                <button type="button" id="export-excel"
                    class="btn btn-outline h-9 px-3 flex items-center gap-1.5 text-xs font-semibold shadow-sm cursor-pointer">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                    <span>Excel'e Aktar</span>
                </button>
                <button type="button" id="export-pdf"
                    class="btn btn-primary h-9 px-3 flex items-center gap-1.5 text-xs font-semibold shadow cursor-pointer">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>PDF'e Aktar</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($hataMesaji): ?>
        <div
            class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Hata!</h4>
                <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
        <?php else: ?>
        <div
            class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm mt-2">
            <table class="w-full border-collapse text-left dataTable js-exportable">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            TC Kimlik No</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Ad Soyad</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Ödenek Dönemi</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Ödenen Tutar</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Tahsilat Tutarı</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Mahsup Tarihi</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Prim Tahsilat Dönemi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php if (!empty($mahsupKayitlari)): ?>
                    <?php foreach ($mahsupKayitlari as $kayit): ?>
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="py-3 px-4 text-xs font-mono text-zinc-700 dark:text-zinc-300">
                            <?php echo htmlspecialchars($kayit['tcKimlikNo'] ?? ''); ?></td>
                        <td class="py-3 px-4 text-xs font-bold text-zinc-900 dark:text-zinc-50">
                            <?php echo htmlspecialchars($kayit['adiSoyadi'] ?? ''); ?></td>
                        <td class="py-3 px-4 text-xs text-center text-zinc-600 dark:text-zinc-400 font-medium">
                            <?php echo htmlspecialchars(($kayit['odemeBasTar'] ?? '') . ' - ' . ($kayit['odemeBitTar'] ?? '')); ?>
                        </td>
                        <td class="py-3 px-4 text-xs text-center font-bold text-zinc-900 dark:text-zinc-50">
                            <?php echo htmlspecialchars($kayit['odenenTutar'] ?? ''); ?> TL</td>
                        <td class="py-3 px-4 text-xs text-center font-bold text-zinc-900 dark:text-zinc-50">
                            <?php echo htmlspecialchars($kayit['tahsilat_tutar'] ?? ''); ?> TL</td>
                        <td class="py-3 px-4 text-xs text-center text-zinc-600 dark:text-zinc-400 font-medium">
                            <?php echo htmlspecialchars($kayit['mahsuplasmaTar'] ?? ''); ?></td>
                        <td class="py-3 px-4 text-xs text-center text-zinc-600 dark:text-zinc-400 font-medium">
                            <?php echo htmlspecialchars($kayit['primTahsilatDonem'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="inbox" class="w-8 h-8 opacity-45"></i>
                                <span>Belirtilen kriterlere uygun, prim borcuna mahsup edilmiş ödeme bulunamadı.</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages/mahsuplastirma/export_odeme.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Script'leri dahil ediyoruz -->
<?php include 'layouts/vendor-scripts.php'; ?>

<style>
.form-label {
    font-size: 0.8125rem !important;
    font-weight: 500 !important;
    margin-bottom: 0.375rem !important;
    color: var(--foreground) !important;
}

.form-input {
    font-size: 0.8125rem !important;
    height: 36px !important;
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
    width: 100% !important;
    border-radius: 6px !important;
    border: 1px solid var(--border) !important;
    background: var(--background) !important;
    color: var(--foreground) !important;
    box-sizing: border-box !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

.form-input:focus {
    outline: none !important;
    border-color: hsl(var(--primary)) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
        lucide.createIcons();
    }

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

    const btnExcel = document.getElementById('export-excel');
    const btnPdf = document.getElementById('export-pdf');

    // PHP'den gelen raporlar dizisini JavaScript'e aktarıyoruz
    const raporlarData = <?php echo json_encode($mahsupKayitlari); ?>;

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