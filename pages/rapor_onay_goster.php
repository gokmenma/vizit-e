<?php
require_once 'Core/Services/SgkViziteService.php';

use App\Helper\Security;


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


require_once 'vendor/autoload.php';

// Kütüphane sınıflarını dahil et
use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;

$sgkClient = new SgkViziteService();

// Gerekli verileri al
$raporId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? 'view'; // Varsayılan 'view', 'download' olabilir
$rapor = null;
$title = 'Rapor Onay Göster';

if ($raporId && isset($_SESSION['rapor_fisleri'][$raporId])) {
    $rapor = $_SESSION['rapor_fisleri'][$raporId];
} else {
    die("Geçersiz veya süresi dolmuş rapor fişi. Lütfen listeye geri dönüp tekrar deneyin.");
}


// Barkod verisi
$barkodVerisi = $rapor['RAPORTAKIPNO'] ?? $rapor['MEDULARAPORID'] ?? '0000000000';
$generator = new BarcodeGeneratorPNG();
$barkodResmiBase64 = base64_encode($generator->getBarcode($barkodVerisi, $generator::TYPE_CODE_128, 2, 50));

// --- PDF ve HTML için ortak içeriği bir değişkene alalım ---
ob_start(); // Çıktı tamponlamayı başlat
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İş Göremezlik Raporu - <?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
        .container { width: 700px; margin: 20px auto !important; border: 1px solid #000; padding: 15px; }
        .barcode { text-align: right; margin-bottom: 5px; }
        .barcode-text { text-align: right; font-size: 10px; margin-bottom: 20px; letter-spacing: 2px; }
        .header-title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table td { border: 1px solid #000; padding: 5px; vertical-align: middle; height: 25px; }
        .report-table td:nth-child(1), .report-table td:nth-child(3) { font-weight: bold; width: 170px; }
        .actions { text-align: center; margin-top: 20px; }
        .actions button, .actions a { margin: 0 5px; padding: 10px 15px; font-size: 14px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="barcode">
            <img src="data:image/png;base64,<?php echo $barkodResmiBase64; ?>" alt="Barkod" height="40">
        </div>
        <div class="barcode-text"><?php echo htmlspecialchars($barkodVerisi); ?></div>

        <div class="header-title">İŞ GÖREMEZLİK RAPORU</div>

        <table class="report-table">
            <tr>
                <td>TC Kimlik No:</td>
                <td><?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></td>
                <td>Ad Soyad:</td>
                <td><?php echo htmlspecialchars($rapor['SIGORTALIADSOYAD']); ?></td>
            </tr>
            <tr>
                <td>Rapor Takip No:</td>
                <td><?php echo htmlspecialchars($rapor['RAPORTAKIPNO']); ?></td>
                <td>Rapor Sıra No:</td>
                <td><?php echo htmlspecialchars($rapor['RAPORSIRANO'] ?? '1'); ?></td>
            </tr>
            <tr>
                <td>Tesis Kodu:</td>
                <td><?php echo htmlspecialchars($rapor['TESISKODU'] ?? ''); ?></td>
                <td>Branş Kodu:</td>
                <td><?php echo htmlspecialchars($rapor['BRANSKODU'] ?? ''); ?></td>
            </tr>
            <tr>
                <td>Poliklinik Tarihi:</td>
                <td><?php echo htmlspecialchars($rapor['POLIKLINIKTAR']); ?></td>
                <td>Ekrana Düştüğü Tarih:</td>
                <td></td>
            </tr>
            <tr>
                <td>Vaka:</td>
                <td><?php echo htmlspecialchars($rapor['VAKAADI']); ?></td>
                <td>Rapor Durumu:</td>
                <td><?php echo htmlspecialchars($rapor['RAPORDURUMADI'] ?? 'CALISIR'); ?></td>
            </tr>
             <tr>
                <td>Hastane Yatış Tarihi:</td>
                <td><?php echo htmlspecialchars($rapor['YATRAPBASTAR'] ?? '0001-01-01'); ?></td>
                <td>Hastane Çıkış Tarihi:</td>
                <td><?php echo htmlspecialchars($rapor['YATRAPBITTAR'] ?? '0001-01-01'); ?></td>
            </tr>
             <tr>
                <td>Ayaktan Başlama Tarihi:</td>
                <td><?php echo htmlspecialchars($rapor['ABASTAR'] ?? $rapor['POLIKLINIKTAR']); ?></td>
                <td>Ayaktan Bitiş Tarihi:</td>
                <td><?php echo htmlspecialchars($rapor['ISBASKONTTAR']); ?></td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php
$html = ob_get_clean(); // Tamponlanan HTML'i al

// --- İsteğe göre işlem yap ---
if ($action === 'download') {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dosyaAdi = "is_goremezlik_raporu_" . ($rapor['TCKIMLIKNO'] ?? 'bilinmiyor') . ".pdf";
    $dompdf->stream($dosyaAdi, ["Attachment" => true]);
    exit;
} else {
    // Varsayılan olarak HTML'i göster ve butonları ekle
    echo $html;
    echo '<div class="actions">
            <button onclick="window.print()">Yazdır</button>
            <a href="?id='.$raporId.'&action=download">PDF Olarak İndir</a>
          </div>';
}
?>