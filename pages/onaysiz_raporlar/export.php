<?php
session_start();
require_once '../../vendor/autoload.php';

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
$dosyaAdi = "onaysiz_raporlar_" . date('Y-m-d');

// --- EXCEL OLUŞTURMA BÖLÜMÜ ---
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Mahsuplaşmış Raporlar');
    // Sıra	TC Kimlik No	Ad Soyad	Vaka	Rapor Başlama Tarihi	Rapor Bitiş Tarihi	Nitelik
    // Başlık satırını yaz
    $sheet->setCellValue('A1', 'TC Kimlik No');
    $sheet->setCellValue('B1', 'Ad Soyad');
    $sheet->setCellValue('C1', 'Vaka');
    $sheet->setCellValue('D1', 'Başlama Tarihi');
    $sheet->setCellValue('E1', 'Bitiş Tarihi');
    $sheet->setCellValue('f1', 'Rapor Günü');
   

    // Başlıkları kalın yap
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);

    // gelen veriyi yazdır
    // var_dump($raporlar);
    // exit;

    // Veri satırlarını yaz
    $satir = 2;
    foreach ($raporlar as $rapor) {
        $sheet->setCellValue('A' . $satir, $rapor['TCKIMLIKNO'] ?? '');
        $sheet->setCellValue('B' . $satir, rtrim($rapor['AD']) . " " . rtrim($rapor["SOYAD"]) ?? '');
        $sheet->setCellValue('C' . $satir, $rapor['VAKAADI'] ?? '');
        $sheet->setCellValue('D' . $satir, ($rapor['POLIKLINIKTAR'] ?? ''));
        $sheet->setCellValue('E' . $satir, $rapor['ISBASKONTTAR'] ?? '');
        $sheet->setCellValue('F' . $satir, $rapor['gun_farki'] ?? '');
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
        <title>Onaysız Raporlar</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h1 { text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>Onaysız Rapor Listesi</h1>
        <h5>İşyeri Adı : ' . ($_SESSION['firma_adi'] ?? 'Bilinmiyor'). '</h5>  <br>
        <table>
            <thead>
                <tr>
                    <th>TC Kimlik No</th>
                    <th>Ad Soyad</th>
                    <th>Vaka</th>
                    <th>Başlama Tarihi</th>
                    <th>Bitiş Tarihi</th>
                    <th>Rapor Günü</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($raporlar as $rapor) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($rapor['TCKIMLIKNO'] ?? '') . '</td>
                    <td>' . htmlspecialchars(rtrim($rapor['AD']) . " " . rtrim($rapor["SOYAD"])) . '</td>
                    <td>' . htmlspecialchars($rapor['VAKAADI'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['POLIKLINIKTAR'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['ISBASKONTTAR'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['gun_farki'] ?? '') . '</td>
              
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