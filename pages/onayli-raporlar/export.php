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
$dosyaAdi = "onayli_raporlar_" . date('Y-m-d');

// --- EXCEL OLUŞTURMA BÖLÜMÜ ---
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Onaylı Raporlar');

    // Başlık satırı 1: Firma Adı & Rapor Türü
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', mb_strtoupper($_SESSION['firma_adi'] ?? 'FİRMA', 'UTF-8') . ' - ONAYLANMIŞ RAPOR LİSTESİ');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Başlık satırı 2: Filtrelenmiş Tarihler
    $t1 = !empty($_POST['tarih1']) ? date('d.m.Y', strtotime($_POST['tarih1'])) : '';
    $t2 = !empty($_POST['tarih2']) ? date('d.m.Y', strtotime($_POST['tarih2'])) : '';
    $tarihAraligiText = ($t1 && $t2) ? "Sorgulanan Tarih Aralığı: {$t1} - {$t2}" : "Tüm Onaylanmış Raporlar";

    $sheet->mergeCells('A2:F2');
    $sheet->setCellValue('A2', $tarihAraligiText);
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Tablo Başlıkları (Satır 4)
    $sheet->setCellValue('A4', 'TC Kimlik No');
    $sheet->setCellValue('B4', 'Ad Soyad');
    $sheet->setCellValue('C4', 'Vaka');
    $sheet->setCellValue('D4', 'Onay Türü');
    $sheet->setCellValue('E4', 'Poliklinik Tarihi');
    $sheet->setCellValue('F4', 'İşbaşı / Kontrol Tarihi');

    // Başlıkları kalın ve arka planlı yap
    $sheet->getStyle('A4:F4')->getFont()->setBold(true);
    $sheet->getStyle('A4:F4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EAEAEA');

    // Veri satırlarını yaz (Satır 5'ten başlayarak)
    $satir = 5;
    foreach ($raporlar as $rapor) {
        $adSoyad = (isset($rapor['AD']) || isset($rapor['SOYAD'])) 
            ? trim(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')) 
            : trim($rapor['SIGORTALIADSOYAD'] ?? '');
        $sheet->setCellValueExplicit('A' . $satir, $rapor['TCKIMLIKNO'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $satir, $adSoyad);
        $sheet->setCellValue('C' . $satir, $rapor['VAKAADI'] ?? '');
        $sheet->setCellValue('D' . $satir, $rapor['ONAYTURU'] ?? 'Belirtilmemiş');
        $sheet->setCellValue('E' . $satir, $rapor['POLIKLINIKTAR'] ?? '');
        $sheet->setCellValue('F' . $satir, $rapor['ISBASKONTTAR'] ?? '');
        $satir++;
    }
    
    // Sütun genişliklerini otomatik ayarla
    foreach (range('A', 'F') as $columnID) {
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
        <title>Onaylı Raporlar</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h1 { text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>Onaylanmış Rapor Listesi</h1>
        <h5>İşyeri Adı : ' . htmlspecialchars($_SESSION['firma_adi'] ?? 'Bilinmiyor') . '</h5>  <br>
        <table>
            <thead>
                <tr>
                    <th>TC Kimlik No</th>
                    <th>Ad Soyad</th>
                    <th>Vaka</th>
                    <th>Onay Türü</th>
                    <th>Poliklinik Tarihi</th>
                    <th>İşbaşı / Kontrol Tarihi</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($raporlar as $rapor) {
        $adSoyad = (isset($rapor['AD']) || isset($rapor['SOYAD'])) 
            ? trim(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')) 
            : trim($rapor['SIGORTALIADSOYAD'] ?? '');
        $html .= '<tr>
                    <td>' . htmlspecialchars($rapor['TCKIMLIKNO'] ?? '') . '</td>
                    <td>' . htmlspecialchars($adSoyad) . '</td>
                    <td>' . htmlspecialchars($rapor['VAKAADI'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['ONAYTURU'] ?? 'Belirtilmemiş') . '</td>
                    <td>' . htmlspecialchars($rapor['POLIKLINIKTAR'] ?? '') . '</td>
                    <td>' . htmlspecialchars($rapor['ISBASKONTTAR'] ?? '') . '</td>
                  </tr>';
    }
            
    $html .= '</tbody></table></body></html>';

    // Dompdf'i yapılandır ve başlat
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    
    // Kağıt boyutunu ve yönünü ayarla
    $dompdf->setPaper('A4', 'landscape'); // Yatay A4

    // HTML'i PDF'e dönüştür
    $dompdf->render();

    // PDF'i tarayıcıya gönder
    $dompdf->stream($dosyaAdi . ".pdf", ["Attachment" => true]);
    exit;
}
