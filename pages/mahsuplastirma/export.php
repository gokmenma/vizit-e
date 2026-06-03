<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// PhpSpreadsheet sınıflarını kullanıma hazırla
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Dompdf sınıflarını kullanıma hazırla
use Dompdf\Dompdf;
use Dompdf\Options;

// Hangi format istendiğini ve verinin ne olduğunu al
$format = $_POST['format'] ?? null;
$raporlarJson = $_POST['rapor_data'] ?? null;

if (!$format || !$raporlarJson) {
    die("Eksik parametre.");
}

// Gelen JSON verisini PHP dizisine çevir
$raporlar = json_decode($raporlarJson, true);
$dosyaAdi = "mahsuplasmis_raporlar_" . date('Y-m-d');

// --- EXCEL OLUŞTURMA BÖLÜMÜ ---
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Mahsuplaşmış Raporlar');

    // Başlık satırını yaz
    $sheet->setCellValue('A1', 'TC Kimlik No');
    $sheet->setCellValue('B1', 'Ad Soyad');
    $sheet->setCellValue('C1', 'Vaka');
    $sheet->setCellValue('D1', 'Ödenek Dönemi');
    $sheet->setCellValue('E1', 'Ödenen Tutar');
    $sheet->setCellValue('F1', 'Mahsuplaşma Tarihi');
    $sheet->setCellValue('G1', 'Makbuz Durumu');

    // Başlıkları kalın yap
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);

    // Veri satırlarını yaz
    $satir = 2;
    foreach ($raporlar as $rapor) {
        $sheet->setCellValue('A' . $satir, $rapor['tcKimlikNo'] ?? '');
        $sheet->setCellValue('B' . $satir, $rapor['adiSoyadi'] ?? '');
        $sheet->setCellValue('C' . $satir, $rapor['vakaAdi'] ?? '');
        $sheet->setCellValue('D' . $satir, ($rapor['odemeBasTar'] ?? '') . ' - ' . ($rapor['odemeBitTar'] ?? ''));
        $sheet->setCellValue('E' . $satir, $rapor['odenenTutar'] ?? '');
        $sheet->setCellValue('F' . $satir, $rapor['mahsuplasmaTar'] ?? '');
        $sheet->setCellValue('G' . $satir, $rapor['durumStr'] ?? '');
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
        <title>Mahsuplaşmış Raporlar</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h1 { text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>Mahsuplaşmış Rapor Listesi</h1>
         <h5>İşyeri Adı : ' . ($_SESSION['firma_adi'] ?? 'Bilinmiyor'). '</h5>  <br>
        <table>
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
            <tbody>';
    
    foreach ($raporlar as $rapor) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($rapor['tcKimlikNo'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['adiSoyadi'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['vakaAdi'] ?? '') . '</td>
                    <td>' . htmlspecialchars(($rapor['odemeBasTar'] ?? '') . ' - ' . ($rapor['odemeBitTar'] ?? '')) . '</td>
                    <td>' . htmlspecialchars($rapor['odenenTutar'] ?? '') . ' TL</td>
                    <td>' . htmlspecialchars($rapor['mahsuplasmaTar'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['durumStr'] ?? '') . '</td>
                  </tr>';
    }
            
    $html .= '</tbody></table></body></html>';

    // Dompdf'i yapılandır ve başlat
    $options = new Options();
    $options->set('isRemoteEnabled', true); // Uzak resimleri (varsa) yüklemek için
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    
    // Kağıt boyutunu ve yönünü ayarla
    $dompdf->setPaper('A4', 'landscape'); // Yatay A4

    // HTML'i PDF'e dönüştür
    $dompdf->render();

    // PDF'i tarayıcıya gönder
    $dompdf->stream($dosyaAdi . ".pdf", ["Attachment" => true]); // true: indir, false: göster
    exit;
}