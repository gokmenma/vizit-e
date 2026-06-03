<?php

require_once 'Core/Services/SgkViziteService.php';


echo "<pre>"; // Çıktının daha okunaklı olması için
echo "Akıllı rapor kuyruğu temizleme işlemi başlatıldı...\n";
echo "SADECE onay bekleyenler DIŞINDAKİ eski raporlar kapatılacak.\n\n";

try {
    $sgkClient = new SgkViziteService();
    $toplamKapatilan = 0;
    $toplamAtlanan = 0;
    
    while (true) {
        echo "SGK'dan yeni bir 100'lük rapor paketi isteniyor...\n";
        
        $raporlar = $sgkClient->raporlariGetir(new DateTime('tomorrow'));

        if (empty($raporlar)) {
            echo "\nKuyrukta başka rapor kalmadı. İşlem tamamlandı.\n";
            break;
        }
        
        $gelenRaporSayisi = count($raporlar);
        echo "{$gelenRaporSayisi} adet rapor bulundu. Durumları kontrol ediliyor...\n";

        foreach ($raporlar as $rapor) {
            $raporId = $rapor['MEDULARAPORID'];
            $raporDurumu = $rapor['RAPORDURUMU'] ?? null;
            $poliklinikTarihi = $rapor['POLIKLINIKTAR'] ?? null;
            $adSoyad = $rapor['AD'] . ' ' . $rapor['SOYAD'];

            // =============================================================
            // ===                KRİTİK KONTROL BURADA                  ===
            // =============================================================
            // Eğer rapor durumu '1' (Çalışır/Onay Bekleyen) ise, bu raporu atla.
            if ($raporDurumu == '1') {
                echo " - [ATLANDI] {$adSoyad} (ID: {$raporId}) - Durum: Onay Bekliyor (Kod: 1)\n";
                $toplamAtlanan++;
                continue; // Döngünün bir sonraki adımına geç
            }
            // =============================================================

            echo " - [KAPATILIYOR] {$adSoyad} (ID: {$raporId}) - Durum Kodu: {$raporDurumu} - Poliklinik Tarihi: {$poliklinikTarihi}\n";

            
            try {
                $kapatResponse =  $sgkClient->raporuKapat($raporId);
                if($kapatResponse->sonucKod == '0'){

                    echo "   -> BAŞARILI\n";
                    $toplamKapatilan++;
                } else {
                    // echo "   -> BAŞARISIZ: " . $kapatResponse->sonucAciklama . "\n";
                    echo "   -> BAŞARISIZ: ";
                }
            } catch (Exception $kapatmaHatasi) {
                echo "   -> KRİTİK HATA: " . $kapatmaHatasi->getMessage() . "\n";
            }
        }
        
        echo "\nBu paketteki raporlar işlendi. Yeniden kontrol ediliyor...\n";
        sleep(1);
    }
    
    echo "\n--------------------------------------------\n";
    echo "TEMİZLEME İŞLEMİ TAMAMLANDI\n";
    echo "Toplam {$toplamKapatilan} adet eski rapor kuyruktan temizlendi.\n";
    echo "Toplam {$toplamAtlanan} adet onay bekleyen rapor atlandı ve kapatılmadı.\n";
    echo "--------------------------------------------\n";

} catch (Exception $e) {
    echo "\n!!! KRİTİK BİR HATA OLUŞTU !!!\n";
    echo "Hata: " . $e->getMessage();
}

echo "</pre>";
?>