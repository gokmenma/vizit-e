<?php
header('Content-Type: application/json');
require_once '../../Core/Services/SgkViziteService.php'; // Yolun doğru olduğundan emin olun

use Models\RaporModel;

$RaporModel = new RaporModel();

session_start();

$response = [
    'success' => false,
    'message' => ''
];


try {
    // API'ye sadece POST metoduyla ve JSON içeriğiyle gelinmeli
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek metodu.');
    }

    $inputData = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Geçersiz JSON formatı.');
    }

    $action = $inputData['action'] ?? '';
    if (empty($action)) {
        throw new Exception('İşlem (action) belirtilmedi.');
    }

    $sgkClient = new SgkViziteService();

    switch ($action) {

        case 'raporOnayla':
            // Gerekli verilerin varlığını kontrol et
            $rapor = $inputData ?? null;
            $nitelikDurumu = $inputData['nitelikDurumu'] ?? null;
            $raporBitisTarihiStr = $inputData['raporBitisTarihi'] ?? null;

            // Vaka(İş Kazası(1), Meslek Hastalığı (2), Hastalık(3), Analık(4))
            $vaka = [
                "İŞ KAZASI" => 1,
                "MESLEK HASTALIK" => 2,
                "HASTALIK" => 3,
                "ANALIK" => 4

            ];

            $rapor['VAKA'] = $vaka[$rapor['VAKA']]; // Varsayılan olarak 'Hastalık' (3) atandı

            //Eğer vaka boş işe veya geçersiz ise hata ver
            if (empty($rapor['VAKA']) || !in_array($rapor['VAKA'], [1, 2, 3, 4])) {
                throw new Exception("Geçersiz veya eksik parametre: VAKA (1-İş Kazası, 2-Meslek Hastalığı, 3-Hastalık, 4-Analık) zorunludur.");
            }



            if (!$rapor || $nitelikDurumu === null || !$raporBitisTarihiStr) {
                throw new Exception("Eksik parametre: raporData, nitelikDurumu ve iseBasiTarihi zorunludur.");
            }

            $raporBitisTarihi = new DateTime($raporBitisTarihiStr);

        

            //Gerçek kullanımda aşağıdaki satırları açın ve SGK API'sine uygun şekilde kullanın

            $onayResponse = $sgkClient->raporuOnayla(
                $rapor['MEDULARAPORID'],
                $rapor['TCKIMLIKNO'],
                $rapor['VAKA'],
                $nitelikDurumu,
                $raporBitisTarihi
            );


            // $onayResponse = new stdClass(); // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
            // $onayResponse->sonucKod = 0; // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
            // $onayResponse->sonucAciklama = "Test Rapor onaylandı."; // Örnek açıklama


            if (isset($onayResponse->sonucKod) && $onayResponse->sonucKod == '0') {
                // Onay başarılıysa, raporu kuyruktan da düşelim.
                $sgkClient->raporuKapat($rapor['MEDULARAPORID']);


                // Bu, hem veritabanına kaydedeceğiniz hem de session'a atacağınız tam veri setidir.
                $tamRaporBilgisi = [
                    // Session'dan gelen işyeri ve kullanıcı bilgileri
                    'isyeri_id'             => $_SESSION['isyeri_id'] ?? null,
                    'kullanici_id'          => $_SESSION['kullanici_id'] ?? null,

                    // SGK'dan gelen rapor bilgileri
                    'TCKIMLIKNO'            => $rapor['TCKIMLIKNO'],
                    'SIGORTALIADSOYAD'      => $rapor['SIGORTALIADSOYAD'],
                    'MEDULARAPORID'         => $rapor['MEDULARAPORID'],
                    'RAPORTAKIPNO'          => $rapor['RAPORTAKIPNO'] ?? null,
                    'RAPORSIRANO'           => $rapor['RAPORSIRANO'] ?? null,
                    'VAKA'                  => $rapor['VAKA'],
                    'VAKAADI'               => $rapor['VAKAADI'],
                    'RAPORDURUMU'           => $rapor['RAPORDURUMU'] ?? null,
                    'RAPORDURUMADI'         => $rapor['RAPORDURUMADI'] ?? null,
                    'POLIKLINIKTAR'         => $rapor['POLIKLINIKTAR'],
                    'ABASTAR'               => $rapor['ABASTAR'] ?? null,
                    'ABITTAR'               => $rapor['ABITTAR'] ?? null,
                    'ISBASKONTTAR'          => $rapor['ISBASKONTTAR'],
                    'YATRAPBASTAR'          => $rapor['YATRAPBASTAR'] ?? null,
                    'YATRAPBITTAR'          => $rapor['YATRAPBITTAR'] ?? null,
                    'TESISKODU'             => $rapor['TESISKODU'] ?? null,
                    'BRANSKODU'             => $rapor['BRANSKODU'] ?? null,

                    // Onay anında oluşan bilgiler
                    "onay_turu"              => "Manuel Onay",
                    'onay_durumu'           => ($nitelikDurumu == '1') ? 'calisti' : 'calismadi',
                    'onay_tarihi'           => date('Y-m-d H:i:s'),
                    'sgk_bildirim_id'       => $onayResponse->bildirimId ?? null,
                    'is_kapatildi'          => true
                ];

                $fisId = $tamRaporBilgisi['MEDULARAPORID']; // Fiş ID'si olarak Medula ID'yi kullanalım, daha tutarlı.
                $_SESSION['rapor_fisleri'][$fisId] = $tamRaporBilgisi;

                $RaporModel->saveWithAttr($tamRaporBilgisi);

                $response = [
                    'status' => 'success',
                    'message' => 'Rapor onaylama işlemi başarılı.',
                    'redirectUrl' =>  "rapor-onay-goster?id=" . $fisId
                    
                ];
                echo json_encode($response);
                exit();
            } else {
                throw new Exception($onayResponse->sonucAciklama ?? 'Rapor onaylama işlemi başarısız.');
            }
            break;

        case 'personelimDegil':
            $rapor = $inputData ?? null;
            if (!$rapor) {
                throw new Exception("Eksik parametre: raporData zorunludur.");
            };

            $pdResponse = $sgkClient->personelimDegilBildir(
                $rapor['MEDULARAPORID'],
                $rapor['TCKIMLIKNO'],
                $rapor['VAKA']
            );
            // $onayResponse = new stdClass(); // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
            // $pdResponse->sonucKod = '0'; // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
            // $pdResponse->sonucAciklama = "Test Personelim Değil bildirimi başarılı."; // Örnek açıklama


            if (isset($pdResponse->sonucKod) && ($pdResponse->sonucKod == '0' || $pdResponse->sonucKod == '600')) {
                $response['success'] = true;
                $response['message'] = $pdResponse->sonucAciklama ?? 'İşlem başarılı.';
            } else {
                throw new Exception($pdResponse->sonucAciklama ?? '"Personelim Değil" işlemi başarısız.');
            }
            break;

        // Mahsuplaştırma vb. diğer case'leriniz buraya gelebilir
        case 'raporOkunduKapat':
            $rapor = $inputData ?? null;
            if (!$rapor) {
                throw new Exception("Eksik parametre: MEDULARAPORID zorunludur.");
            };
            //Gerçek kullanımda aşağıdaki satırları açın ve SGK API'sine uygun şekilde kullanın

            $rkResponse = $sgkClient->raporuKapat(
                $rapor['MEDULARAPORID']
            );
            // $rkResponse = new stdClass(); // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
            // $rkResponse->sonucKod = '0'; // Örnek olarak, gerçek API çağrısında bu değer dinamik olarak dönecektir.
            // $rkResponse->sonucAciklama = "Rapor kapatma bildirimi başarılı."; // Örnek açıklama


            if (isset($rkResponse->sonucKod) && ($rkResponse->sonucKod == '0' || $rkResponse->sonucKod == '600')) {
                $response = [
                    'status' => 'success',
                    'message' => $rkResponse->sonucAciklama ?? 'Rapor kapatma işlemi başarılı.',
                    'raporData' => $rapor
                ];
            } else {
                throw new Exception($rkResponse->sonucAciklama ?? '"Rapor Kapat" işlemi başarısız.');
            }
            break;

        default:
            throw new Exception("Geçersiz işlem talebi: " . htmlspecialchars($action));
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
