<?php
require_once 'Core/Services/SgkViziteService.php';

// Bilgiler
$kullaniciAdi = '32450401908';
$isyeriKodu = '3';
$wsSifre = '87174585';

$rapor = null;
$hataMesaji = '';
$basariMesaji = '';

// Sayfaya POST ile gelinip gelinmediğini kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- ONAY İŞLEMİ ---
    if (isset($_POST['onay_buton'])) {
        // Formdan gelen verileri al
        $raporJson = $_POST['rapor_data'];
        $rapor = json_decode($raporJson, true);
        $iseBasiTarihi = new DateTime($_POST['ise_basi_tarihi']);
        $nitelikDurumu = $_POST['nitelik_durumu']; // "0" veya "1"

        try {
            $sgkClient = new SgkViziteService();
            $response = $sgkClient->raporuOnayla(
                $rapor['MEDULARAPORID'],
                $rapor['TCKIMLIKNO'],
                $rapor['VAKA'],
                $nitelikDurumu,
                $iseBasiTarihi
            );
            if ($response->sonucKod == '0') {
                $basariMesaji = "Rapor başarıyla onaylandı! Bildirim ID: " . ($response->bildirimId ?? '');
                // Başarılı onay sonrası raporu "okundu" olarak kapatmak iyi bir pratiktir.
                $sgkClient->raporuKapat($rapor['MEDULARAPORID']);
            } else {
                $hataMesaji = "Onaylama Başarısız: " . ($response->sonucAciklama ?? 'Bilinmeyen Hata');
            }
        } catch (Exception $e) {
            $hataMesaji = "KRİTİK HATA: " . $e->getMessage();
        }

    } 
    // --- DETAY GÖRÜNTÜLEME İŞLEMİ ---
    else if (isset($_POST['detay_goruntule_buton'])) {
        if (!empty($_POST['secilen_rapor'])) {
            $raporJson = $_POST['secilen_rapor'];
            $rapor = json_decode($raporJson, true);
        } else {
            $hataMesaji = "Lütfen detayını görüntülemek için bir rapor seçin ve geri dönün.";
        }
    }

} else {
     $hataMesaji = "Bu sayfaya doğrudan erişilemez. Lütfen listeden bir rapor seçin.";
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İş Göremezlik Belgesi Detayı</title>
    <style>
        body { font-family: Verdana, Arial, sans-serif; font-size: 12px; background-color: #f0f0f0; }
        .container { width: 900px; margin: 20px auto; background: #fff; padding: 20px; }
        .header-bar { background-color: #6a8ab9; color: white; padding: 8px; font-weight: bold; }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 1px; }
        .detail-table td { border: 1px solid #d3a15a; padding: 6px; }
        .detail-table td:nth-child(odd) { background-color: #f7f7f7; font-weight: bold; width: 25%; }
        .onay-form-container { text-align: center; margin-top: 20px; }
        .onay-form-text { color: red; font-weight: bold; margin-bottom: 10px; }
        .button { padding: 6px 12px; border: 1px outset #aaa; cursor: pointer; }
        .button-primary { background-color: #e0e0e0; }
        .button-link { background-color: #0000ff; color: white; text-decoration: none; font-weight: bold; }
        .message-box { padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; font-weight: bold; }
        .error-box { background-color: #f8d7da; color: #721c24; }
        .success-box { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-bar">İş Göremezlik Belgesi</div>

    <?php if ($hataMesaji): ?>
        <div class="message-box error-box"><?php echo htmlspecialchars($hataMesaji); ?></div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="onay_bekleyen_raporlar.php" class="button button-link">LİSTEYE GERİ DÖN</a>
        </div>
    <?php elseif ($basariMesaji): ?>
         <div class="message-box success-box"><?php echo htmlspecialchars($basariMesaji); ?></div>
         <div style="text-align: center; margin-top: 20px;">
            <a href="onay_bekleyen_raporlar.php" class="button button-link">LİSTEYE GERİ DÖN</a>
        </div>
    <?php elseif ($rapor): ?>
        <table class="detail-table">
            <tr>
                <td>TC Kimlik No :</td><td><?php echo $rapor['TCKIMLIKNO']; ?></td>
                <td>Ad Soyad:</td><td><?php echo $rapor['AD'] . ' ' . $rapor['SOYAD']; ?></td>
            </tr>
            <tr>
                <td>Rapor Takip No:</td><td><?php echo $rapor['RAPORTAKIPNO']; ?></td>
                <td>Rapor Sıra No:</td><td><?php echo $rapor['RAPORSIRANO'] ?? '1'; ?></td>
            </tr>
            <!-- Diğer detaylar dökümandan ve gelen veriden eşleştirilecek -->
             <tr>
                <td>Sağlık Tesis Adı :</td><td><?php echo $rapor['TESISADI'] ?? 'Bilinmiyor'; ?></td>
                <td>Poliklinik Defter Sıra No:</td><td><?php echo $rapor['POLDEFTERSIRANO'] ?? '0'; ?></td>
            </tr>
             <tr>
                <td>Poliklinik Tarihi:</td><td><?php echo $rapor['POLIKLINIKTAR']; ?></td>
                <td>Rapor Durumu:</td><td><?php echo $rapor['RAPORDURUMADI'] ?? 'ÇALIŞIR'; ?></td>
            </tr>
            <tr>
                <td>Vaka:</td><td><?php echo $rapor['VAKAADI']; ?></td>
                <td>Hastane Çıkış Tarihi:</td><td><?php echo $rapor['YATRAPBITTAR'] ?? '0001-01-01'; ?></td>
            </tr>
             <tr>
                <td>Hastane Yatış Tarihi:</td><td><?php echo $rapor['YATRAPBASTAR'] ?? '0001-01-01'; ?></td>
                <td>Rapor Bitiş Tarihi:</td><td><?php echo $rapor['ABITTAR'] ?? $rapor['ISBASKONTTAR']; ?></td>
            </tr>
             <tr>
                <td>Rapor Başlama Tarihi:</td><td><?php echo $rapor['ABASTAR'] ?? $rapor['POLIKLINIKTAR']; ?></td>
                <td>Ekrana Düştüğü Tarih:</td><td><?php echo date('Y-m-d'); /* Bu veri servisten gelmez, anlık tarih basılabilir */ ?></td>
            </tr>
             <tr>
                <td>Rapor Türü:</td><td><?php echo $rapor['RAPDUZENTURUADI'] ?? 'TEK HEKİM'; ?></td>
                <td>İş Kazası Tarihi:</td><td><?php echo $rapor['ISKAZASITARIHI'] ?? '-'; ?></td>
            </tr>
        </table>

        <div class="onay-form-container">
            <form method="post">
                <!-- Raporun tüm verisini form içinde saklayalım ki onay butonuna basınca kullanabilelim -->
                <input type="hidden" name="rapor_data" value="<?php echo htmlspecialchars(json_encode($rapor)); ?>">

                <div class="onay-form-text">
                    <?php echo $rapor['TCKIMLIKNO']; ?> kimlik numaralı <?php echo $rapor['AD'] . ' ' . $rapor['SOYAD']; ?> isimli çalışanım <br>
                    <?php echo $rapor['POLIKLINIKTAR']; ?> ile 
                    <input type="date" name="ise_basi_tarihi" value="<?php echo $rapor['ISBASKONTTAR']; ?>"> tarihleri arasında
                </div>
                <select name="nitelik_durumu">
                    <option value="0">Çalışmamıştır</option>
                    <option value="1">Çalışmıştır</option>
                </select>
                <br><br>
                <button type="submit" name="onay_buton" class="button button-primary">Onay</button>
            </form>
            <br>
            <a href="onay_bekleyen_raporlar.php" class="button button-link">LİSTEYE GERİ DÖN</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>