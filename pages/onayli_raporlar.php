<?php

use App\Helper\Security;
use Models\RaporModel;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


require_once __DIR__ . '/../Core/Services/SgkViziteService.php';

$title = "Onaylı Raporlar";

$RaporModel = new RaporModel();

$onayliRaporlar = []; // Başlangıçta boş bir dizi
$hataMesaji = '';

// Form gönderilmişse, metodu çağır
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tarihleri kontrol et boş ise uyarı ver
    if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
        $hataMesaji = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {

        try {
            $sgkClient = new SgkViziteService();

            $tarih1 = new DateTime($_POST['tarih1']);
            $tarih2 = new DateTime($_POST['tarih2']);

            $onayliRaporlar = $sgkClient->onayliRaporlariGetir($tarih1, $tarih2);
            // echo "<pre>";
            // print_r($onayliRaporlar);
            // echo "</pre>";
        } catch (Exception $e) {
            $hataMesaji = $e->getMessage();
        }
    }
}

/**
 * SGK hata mesajlarını kullanıcı dostu ve anlaşılır kılmak için parse eder
 */
function parseSgkError($errorMessage) {
    $result = [
        'type' => 'unknown',
        'title' => 'Sistemsel Bir İşlem Hatası',
        'description' => 'SGK sistemleri ile iletişim kurulurken bir sorun meydana geldi.',
        'severity' => 'danger',
        'solutions' => [],
        'technical_details' => $errorMessage
    ];

    if (empty($errorMessage)) {
        return null;
    }

    if (stripos($errorMessage, 'aktif bir seans mevcuttur') !== false || stripos($errorMessage, 'Oturum Hatası') !== false) {
        $result['type'] = 'session_conflict';
        $result['title'] = 'SGK Sunucusunda Aktif Oturum Var';
        $result['description'] = 'SGK sistemi güvenlik ve veri güvenliği politikaları gereği aynı işveren için aynı anda yalnızca tek bir oturuma izin vermektedir. Şu anda başka bir IP adresi veya cihaz üzerinden aktif bir seans bulunmaktadır.';
        $result['severity'] = 'warning';
        $result['solutions'] = [
            '**Bekleyin (En Kolay Çözüm):** SGK sunucusundaki eski oturumun otomatik olarak sonlanması için **10 - 15 dakika** bekleyip tekrar aramayı deneyin.',
            '**Yeni IP Alın:** İnternet bağlantınızı kesip tekrar bağlanarak (modem kapat-aç vb.) yeni bir IP adresi almayı deneyin.',
            '**Diğer Tarayıcıları Kapatın:** Eğer SGK Vizite sistemine başka bir tarayıcı sekmesinde veya başka bir bilgisayarda girdiyseniz, oradaki oturumları kapatın.'
        ];
    } elseif (stripos($errorMessage, 'Login başarısız') !== false || stripos($errorMessage, 'şifre hatalı') !== false || stripos($errorMessage, 'Kullanıcı adı') !== false) {
        $result['type'] = 'auth_failed';
        $result['title'] = 'SGK Giriş Bilgileri Hatası';
        $result['description'] = 'SGK web servislerine giriş yetkilendirmesi başarısız oldu. Tanımlı olan SGK kullanıcı adı, şifre veya işyeri kodunuz hatalı veya süresi dolmuş olabilir.';
        $result['severity'] = 'danger';
        $result['solutions'] = [
            '**Ayarları Kontrol Edin:** Yönetim panelinden **"SGK Ayarları"** sayfasına giderek bilgilerinizi kontrol edin ve eksiksiz/doğru yazıldığından emin olun.',
            '**Şifre Güncelliği:** SGK işveren şifrenizin veya sistem şifrenizin süresinin dolup dolmadığını SGK resmi sitesine direkt giriş yaparak teyit edin.'
        ];
    } elseif (stripos($errorMessage, 'timeout') !== false || stripos($errorMessage, 'connect') !== false || stripos($errorMessage, 'ulaşılamadı') !== false || stripos($errorMessage, 'SoapFault') !== false) {
        $result['type'] = 'connection';
        $result['title'] = 'SGK Servis Bağlantı Sorunu';
        $result['description'] = 'SGK web servislerine erişim sağlanırken zaman aşımı veya bağlantı hatası oluştu. Bu durum genellikle SGK sunucularının geçici olarak yoğun veya bakımda olmasından kaynaklanır.';
        $result['severity'] = 'info';
        $result['solutions'] = [
            '**Tekrar Deneyin:** Birkaç dakika bekledikten sonra sayfayı yenileyerek aramayı veya işlemi tekrarlayın.',
            '**SGK Genel Durumu:** SGK sistemlerinin genel bir kesinti yaşayıp yaşamadığını kontrol edin.'
        ];
    } else {
        $cleanMessage = preg_replace('/^(KRİTİK HATA:\s*|HATA:\s*)/i', '', $errorMessage);
        $result['title'] = 'SGK Servis Hatası';
        $result['description'] = $cleanMessage;
        $result['solutions'] = [
            'Sayfayı yenileyerek işlemi tekrar deneyin.',
            'Sorun devam ederse sistem yöneticinizle iletişime geçerek teknik detay kodunu paylaşın.'
        ];
    }

    return $result;
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

<style>
    /* Premium Alert Card Styling */
    .premium-alert {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 25px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        animation: alertSlideInDown 0.4s ease-out;
    }

    @keyframes alertSlideInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .premium-alert:hover {
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
        transform: translateY(-2px);
    }

    /* Left accent bar */
    .premium-alert::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
    }

    /* Danger variant (Red) */
    .premium-alert.alert-danger-premium {
        border-color: #fee2e2;
        background: linear-gradient(180deg, #ffffff 0%, #fffbfb 100%);
    }
    .premium-alert.alert-danger-premium::before {
        background-color: #ef4444;
    }
    .premium-alert.alert-danger-premium .alert-icon-container {
        background-color: #fef2f2;
        color: #ef4444;
    }
    .premium-alert.alert-danger-premium .alert-title {
        color: #991b1b;
    }

    /* Warning variant (Orange/Amber) */
    .premium-alert.alert-warning-premium {
        border-color: #fef3c7;
        background: linear-gradient(180deg, #ffffff 0%, #fffdf6 100%);
    }
    .premium-alert.alert-warning-premium::before {
        background-color: #f59e0b;
    }
    .premium-alert.alert-warning-premium .alert-icon-container {
        background-color: #fffbeb;
        color: #d97706;
    }
    .premium-alert.alert-warning-premium .alert-title {
        color: #92400e;
    }

    /* Info/Connection variant (Blue/Cyan) */
    .premium-alert.alert-info-premium {
        border-color: #e0f2fe;
        background: linear-gradient(180deg, #ffffff 0%, #f7fbfe 100%);
    }
    .premium-alert.alert-info-premium::before {
        background-color: #3b82f6;
    }
    .premium-alert.alert-info-premium .alert-icon-container {
        background-color: #f0f9ff;
        color: #0284c7;
    }
    .premium-alert.alert-info-premium .alert-title {
        color: #075985;
    }

    /* Success variant (Green) */
    .premium-alert.alert-success-premium {
        border-color: #d1fae5;
        background: linear-gradient(180deg, #ffffff 0%, #f6fdf9 100%);
    }
    .premium-alert.alert-success-premium::before {
        background-color: #10b981;
    }
    .premium-alert.alert-success-premium .alert-icon-container {
        background-color: #ecfdf5;
        color: #059669;
    }
    .premium-alert.alert-success-premium .alert-title {
        color: #065f46;
    }

    /* Internal Layout */
    .alert-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 14px;
    }

    .alert-icon-container {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .alert-title-area {
        display: flex;
        flex-direction: column;
    }

    .alert-category {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .alert-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
    }

    .alert-body {
        padding-left: 64px;
    }

    .alert-description {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    /* Solutions Panel */
    .alert-solutions-card {
        background-color: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 16px;
    }

    .solutions-title {
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #374151;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .solutions-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .solutions-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 0.9rem;
        color: #4b5563;
        line-height: 1.4;
    }

    .solutions-list li i {
        color: #10b981;
        font-size: 1.1rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    /* Technical Details Accordion */
    .tech-details-toggle {
        background: none;
        border: none;
        color: #6b7280;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: color 0.2s;
    }

    .tech-details-toggle:hover {
        color: #374151;
    }

    .tech-details-toggle i {
        transition: transform 0.2s;
    }

    .tech-details-toggle.active i {
        transform: rotate(180deg);
    }

    .tech-details-content {
        display: none;
        margin-top: 10px;
        background-color: #f3f4f6;
        border-radius: 8px;
        padding: 12px 16px;
        font-family: monospace;
        font-size: 0.8rem;
        color: #374151;
        word-break: break-all;
        border-left: 3px solid #d1d5db;
        max-height: 200px;
        overflow-y: auto;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .premium-alert {
            padding: 16px;
        }
        .alert-header {
            gap: 12px;
        }
        .alert-icon-container {
            width: 40px;
            height: 40px;
            font-size: 1.25rem;
        }
        .alert-title {
            font-size: 1rem;
        }
        .alert-body {
            padding-left: 0;
            margin-top: 10px;
        }
        .alert-description {
            font-size: 0.9rem;
        }
        .alert-solutions-card {
            padding: 12px 14px;
        }
    }
</style>
<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header row d-flex justify-content-between align-items-center">
                        <div class="col-lg-10">
                            <h2><strong>Onaylanmış Rapor Listesi</strong></h2>
                            <?php if (isset($tarih1) && isset($tarih2)): ?>
                                <small class="text-muted" style="font-size: 14px;">
                                    <strong><?php echo $tarih1->format('d.m.Y'); ?></strong> - <strong><?php echo $tarih2->format('d.m.Y'); ?></strong> tarihleri arası listelenmektedir.
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-2 text-md-end text-center mt-2 mt-md-0">
                            <a href="onayli-rapor-ara"
                                class="btn btn-raised btn-primary btn-round waves-effect w-100 w-md-auto"><i
                                    class="zmdi zmdi-arrow-back"></i> Geri Dön</a>
                        </div>

                    </div>
                    <div class="body">

                        <?php if ($hataMesaji): 
                            $parsedError = parseSgkError($hataMesaji);
                            $severityClass = 'alert-danger-premium';
                            $iconClass = 'zmdi-alert-triangle';
                            if ($parsedError['severity'] === 'warning') {
                                $severityClass = 'alert-warning-premium';
                                $iconClass = 'zmdi-alert-circle';
                            } elseif ($parsedError['severity'] === 'info') {
                                $severityClass = 'alert-info-premium';
                                $iconClass = 'zmdi-info';
                            }
                        ?>
                            <div class="premium-alert <?php echo $severityClass; ?>">
                                <div class="alert-header">
                                    <div class="alert-icon-container">
                                        <i class="zmdi <?php echo $iconClass; ?>"></i>
                                    </div>
                                    <div class="alert-title-area">
                                        <span class="alert-category">Sistem Bildirimi</span>
                                        <h4 class="alert-title"><?php echo htmlspecialchars($parsedError['title']); ?></h4>
                                    </div>
                                </div>
                                <div class="alert-body">
                                    <p class="alert-description"><?php echo htmlspecialchars($parsedError['description']); ?></p>
                                    
                                    <?php if (!empty($parsedError['solutions'])): ?>
                                        <div class="alert-solutions-card">
                                            <h5 class="solutions-title">
                                                <i class="zmdi zmdi-wrench"></i> Önerilen Çözüm Adımları
                                            </h5>
                                            <ul class="solutions-list">
                                                <?php foreach ($parsedError['solutions'] as $sol): 
                                                    $solFormatted = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', htmlspecialchars($sol));
                                                ?>
                                                    <li>
                                                        <i class="zmdi zmdi-check-circle"></i>
                                                        <span><?php echo $solFormatted; ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <button type="button" class="tech-details-toggle" onclick="toggleTechDetails(this)">
                                        <i class="zmdi zmdi-chevron-down"></i> Teknik Detaylar ve Hata Kodu
                                    </button>
                                    <div class="tech-details-content">
                                        <?php echo htmlspecialchars($parsedError['technical_details']); ?>
                                    </div>
                                </div>
                            </div>

                            <script>
                            function toggleTechDetails(btn) {
                                const content = btn.nextElementSibling;
                                btn.classList.toggle('active');
                                if (content.style.display === 'block') {
                                    content.style.display = 'none';
                                } else {
                                    content.style.display = 'block';
                                }
                            }
                            </script>
                        <?php else: ?>
                            <form method="post">
                                <?php if (!empty($onayliRaporlar)): ?>
                                    <div class="export-buttons d-flex justify-content-md-end justify-content-center gap-2 mb-4 mt-2">
                                        <button type="button" id="export-excel" class="btn btn-primary btn-simple waves-effect m-0"><i class="zmdi zmdi-file-text"></i> Excel'e Aktar</button>
                                        <button type="button" id="export-pdf" class="btn btn-primary waves-effect m-0"><i class="zmdi zmdi-collection-pdf"></i> PDF'e Aktar</button>
                                    </div>
                                <?php endif; ?>
                                <div class="table-responsive d-none d-md-block">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Sıra</th>
                                                <th style="width: 10%;">Tc Kimlik No</th>
                                                <th style="width: 40%;">Ad Soyad</th>
                                                <th class="text-center" style="width: 10%;">Vaka</th>
                                                <th class="text-center" style="width: 10%;">Onay Türü</th>
                                                <th class="text-center" style="width: 10%;">Poliklinik Tarihi</th>
                                                <th class="text-center" style="width: 10%;">İşbaşı / Kontrol Tarihi</th>
                                                <th class="text-center" style="width: 10%;">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($onayliRaporlar)): ?>
                                                <?php
                                                $i = 0; // Sıra numarasını başlatıyoruz
                                                foreach ($onayliRaporlar as &$rapor):
                                                    $i++;

                                                    $onay_turu = $RaporModel->onaylanmaTuru($rapor['MEDULARAPORID'] ?? 0);
                                                    $rapor['ONAYTURU'] = $onay_turu ?? 'Belirtilmemiş';

                                                ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $i; ?></td>

                                                        <!-- Bu veri genelde 1 olur -->
                                                        <td><?php echo htmlspecialchars($rapor['TCKIMLIKNO'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')); ?>
                                                        </td>
                                                        <td class="text-center"><?php echo htmlspecialchars($rapor['VAKAADI'] ?? ''); ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php echo htmlspecialchars($rapor['ONAYTURU'] ?? ''); ?></td>

                                                        <td class="text-center">
                                                            <?php echo htmlspecialchars($rapor['POLIKLINIKTAR'] ?? ''); ?></td>
                                                        <td class="text-center">
                                                            <?php echo htmlspecialchars($rapor['ISBASKONTTAR'] ?? ''); ?></td>
                                                        <td class="text-center d-flex">

                                                            <!-- Detay Butonu -->
                                                            <!-- <a href="onayli-rapor-detay?rapor_id=<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>"
                                                                class="btn btn-sm btn-primary btn-round ">Detay</a> -->
                                                            <!-- Onay İptal Butonu -->
                                                            <a href="#"
                                                                data-id=<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>
                                                                class="btn btn-sm btn-danger btn-simple btn-round text-nowrap onay-iptal">Onay
                                                                İptal</a>
                                                            <!-- Raporu Göster Butonu -->
                                                            <a href="rapor-onay-goster?id=<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>"
                                                                target="_blank" class="btn btn-info btn-sm btn-round text-nowrap">Fişi
                                                                Göster</a>
                                                        </td>

                                                    </tr>
                                                <?php endforeach; 
                                                unset($rapor);
                                                ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" style="text-align:center;">Belirtilen kriterlere uygun
                                                        onaylanmış rapor
                                                        bulunamadı.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- MOBİL GÖRÜNÜM (Kart Yapısı) -->
                                <div class="mobile-rapor-container d-md-none d-block">
                                    <?php if (empty($onayliRaporlar)): ?>
                                        <div class="alert alert-info text-center">Kayıtlı onaylı rapor bulunamadı.</div>
                                    <?php else: ?>
                                        <?php foreach ($onayliRaporlar as $rapor): ?>
                                            <div class="mobile-rapor-card mb-3 p-3 shadow-sm border-radius-10 bg-white border">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 fw-bold text-primary"><?php echo htmlspecialchars(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')); ?></h6>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($rapor['VAKAADI'] ?? ''); ?></span>
                                                </div>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">TC Kimlik No</small>
                                                        <span class="fw-500"><?php echo htmlspecialchars($rapor['TCKIMLIKNO'] ?? ''); ?></span>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <small class="text-muted d-block">Onay Türü</small>
                                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($rapor['ONAYTURU'] ?? ''); ?></span>
                                                    </div>
                                                </div>

                                                <div class="row mb-3 bg-light p-2 mx-0 rounded">
                                                    <div class="col-6 border-end">
                                                        <small class="text-muted d-block text-center">Poliklinik</small>
                                                        <div class="text-center fw-bold small"><?php echo htmlspecialchars($rapor['POLIKLINIKTAR'] ?? ''); ?></div>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block text-center">İşbaşı/Kontrol</small>
                                                        <div class="text-center fw-bold small text-success"><?php echo htmlspecialchars($rapor['ISBASKONTTAR'] ?? ''); ?></div>
                                                    </div>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <a href="rapor-onay-goster?id=<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>" target="_blank" class="btn btn-info w-50 py-2 waves-effect">
                                                        <i class="zmdi zmdi-eye me-1"></i> Fişi Göster
                                                    </a>
                                                    <a href="#" data-id="<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>" class="btn btn-outline-danger w-50 py-2 waves-effect onay-iptal">
                                                        <i class="zmdi zmdi-close-circle me-1"></i> İptal
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
</section>
<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages/onayli-raporlar/export.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnExcel = document.getElementById('export-excel');
    const btnPdf = document.getElementById('export-pdf');

    // PHP'den gelen onaylı raporlar dizisini JavaScript'e aktarıyoruz
    const raporlarData = <?php echo json_encode($onayliRaporlar); ?>;

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
        document.getElementById('export-format').value = format;
        document.getElementById('export-data').value = JSON.stringify(raporlarData);
        document.getElementById('export-form').submit();
    }
});
</script>

<script src="App/Src/onayli_raporlar.js"></script>


<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>