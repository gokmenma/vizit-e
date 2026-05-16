<?php
// Session'dan veri okunabilir, bu yüzden başlatalım
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// PhpSpreadsheet sınıflarını kullanıma hazırla
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Dompdf sınıflarını kullanıma hazırla
use Dompdf\Dompdf;
use Dompdf\Options;

// Hangi format istendiğini ve verinin ne olduğunu POST isteğinden al
$format = $_POST['format'] ?? null;
$raporlarJson = $_POST['rapor_data'] ?? null;

// Gerekli parametreler yoksa işlemi sonlandır
if (!$format || !$raporlarJson) {
    die("Dışa aktarma için gerekli parametreler eksik.");
}

// Gelen JSON verisini PHP dizisine çevir
$raporlar = json_decode($raporlarJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Geçersiz rapor verisi formatı.");
}

$dosyaAdi = "mahsup_edilen_odemeler_" . date('Y-m-d');

// --- EXCEL OLUŞTURMA BÖLÜMÜ ---
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Mahsup Edilen Ödemeler');

    // Başlık satırını yaz (yeni rapora uygun)
    $sheet->setCellValue('A1', 'TC Kimlik No');
    $sheet->setCellValue('B1', 'Ad Soyad');
    $sheet->setCellValue('C1', 'Ödenek Dönemi');
    $sheet->setCellValue('D1', 'Ödenen Tutar (TL)');
    $sheet->setCellValue('E1', 'Tahsilat Tutarı (TL)');
    $sheet->setCellValue('F1', 'Mahsup Tarihi');
    $sheet->setCellValue('G1', 'Prim Tahsilat Dönemi');

    // Başlıkları kalın yap
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);

    // Veri satırlarını yaz
    $satir = 2;
    foreach ($raporlar as $rapor) {
        $sheet->setCellValue('A' . $satir, $rapor['tcKimlikNo'] ?? '');
        $sheet->setCellValue('B' . $satir, $rapor['adiSoyadi'] ?? '');
        $sheet->setCellValue('C' . $satir, ($rapor['odemeBasTar'] ?? '') . ' - ' . ($rapor['odemeBitTar'] ?? ''));
        $sheet->setCellValue('D' . $satir, $rapor['odenenTutar'] ?? '');
        $sheet->setCellValue('E' . $satir, $rapor['tahsilat_tutar'] ?? '');
        $sheet->setCellValue('F' . $satir, $rapor['mahsuplasmaTar'] ?? '');
        $sheet->setCellValue('G' . $satir, $rapor['primTahsilatDonem'] ?? '');
        $satir++;
    }
    
    // Sütun genişliklerini otomatik ayarla
    foreach (range('A', 'G') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Tarayıcıya dosya indirme başlıklarını gönder
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $dosyaAdi . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// --- PDF OLUŞTURMA BÖLÜMÜ ---
if ($format === 'pdf') {
    // PDF için HTML içeriği oluştur
    $html = '
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <title>Mahsup Edilen Ödemeler</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h1 { text-align: center; border-bottom: 1px solid #333; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>Prim Borcuna Mahsup Edilen Ödemeler Listesi</h1>
         <h5>İşyeri Adı : ' . ($_SESSION['firma_adi'] ?? 'Bilinmiyor'). '</h5>  <br>
        <table>
            <thead>
                <tr>
                    <th>TC Kimlik No</th>
                    <th>Ad Soyad</th>
                    <th>Ödenek Dönemi</th>
                    <th>Ödenen Tutar</th>
                    <th>Tahsilat Tutarı</th>
                    <th>Mahsup Tarihi</th>
                    <th>Prim Tahsilat Dönemi</th>
                </tr>
            </thead>
            <tbody>';
    
    if (empty($raporlar)) {
        $html .= '<tr><td colspan="7" style="text-align:center;">Listelenecek kayıt bulunamadı.</td></tr>';
    } else {
        foreach ($raporlar as $rapor) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($rapor['tcKimlikNo'] ?? '') . '</td>
                        <td>' . htmlspecialchars($rapor['adiSoyadi'] ?? '') . '</td>
                        <td>' . htmlspecialchars(($rapor['odemeBasTar'] ?? '') . ' - ' . ($rapor['odemeBitTar'] ?? '')) . '</td>
                        <td>' . htmlspecialchars($rapor['odenenTutar'] ?? '') . ' TL</td>
                        <td>' . htmlspecialchars($rapor['tahsilat_tutar'] ?? '') . ' TL</td>
                        <td>' . htmlspecialchars($rapor['mahsuplasmaTar'] ?? '') . '</td>
                        <td>' . htmlspecialchars($rapor['primTahsilatDonem'] ?? '') . '</td>
                      </tr>';
        }
    }
            
    $html .= '</tbody></table></body></html>';

    // Dompdf'i yapılandır ve başlat
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    
    // Kağıt boyutunu ve yönünü ayarla
    $dompdf->setPaper('A4', 'landscape'); // Yatay A4, çünkü çok sütun var

    // HTML'i PDF'e dönüştür
    $dompdf->render();

    // PDF'i tarayıcıya gönder
    $dompdf->stream($dosyaAdi . ".pdf", ["Attachment" => true]);
    exit;
}