<?php

use App\Helper\Security;
use Models\RaporModel;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


require_once __DIR__ . '/../Core/Services/SgkViziteService.php';

$title = "Onaylı Raporlar";

$RaporModel = new RaporModel();

$onayliRaporlar = []; 
$hataMesaji = '';

$tarih1Str = !empty($_REQUEST['tarih1']) ? $_REQUEST['tarih1'] : date('01.m.Y');
$tarih2Str = !empty($_REQUEST['tarih2']) ? $_REQUEST['tarih2'] : date('d.m.Y');

$tarih1 = DateTime::createFromFormat('d.m.Y', $tarih1Str);
if (!$tarih1) {
    $tarih1 = new DateTime($tarih1Str);
}

$tarih2 = DateTime::createFromFormat('d.m.Y', $tarih2Str);
if (!$tarih2) {
    $tarih2 = new DateTime($tarih2Str);
}

$isQueried = !empty($_REQUEST['tarih1']) && !empty($_REQUEST['tarih2']);

if ($isQueried) {
    try {
        $sgkClient = new SgkViziteService();
        $onayliRaporlar = $sgkClient->onayliRaporlariGetir($tarih1, $tarih2);
        foreach ($onayliRaporlar as $rapor) {
            $medulaRaporId = (string)($rapor['MEDULARAPORID'] ?? '');
            if ($medulaRaporId !== '') {
                $_SESSION['rapor_fisleri'][$medulaRaporId] = $rapor;
            }
        }
    } catch (Exception $e) {
        $hataMesaji = $e->getMessage();
    }
} else {
    $onayliRaporlar = [];
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


<div class="flex flex-col gap-6 w-full py-2 px-1">
    <!-- Header Bölümü -->
    <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Onaylı Raporlar</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Tarih aralığı belirleyerek SGK sisteminde onaylanmış vizite raporlarını listeleyin.
            </p>
        </div>
        <div class="flex items-center gap-2 text-nowrap self-start md:self-auto flex-shrink-0">
            <!-- Tarih Seçim Formu (Header İçinde) -->
            <form action="onayli-raporlar" method="POST" class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <i data-lucide="calendar"
                        class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" id="tarih1" name="tarih1" value="<?php echo htmlspecialchars($tarih1Str); ?>"
                        class="h-9 w-[155px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <span class="text-zinc-400 dark:text-zinc-600 text-xs font-semibold">-</span>
                <div class="relative flex items-center">
                    <i data-lucide="calendar"
                        class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" id="tarih2" name="tarih2" value="<?php echo htmlspecialchars($tarih2Str); ?>"
                        class="h-9 w-[155px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <button type="submit"
                    class="h-9 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 shadow cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Sorgula</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Hata Mesajları -->
    <?php if ($hataMesaji): 
        $parsedError = parseSgkError($hataMesaji);
        $severityClass = 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300';
        $iconClass = 'alert-triangle';
        if ($parsedError['severity'] === 'warning') {
            $severityClass = 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-300';
            $iconClass = 'alert-circle';
        } elseif ($parsedError['severity'] === 'info') {
            $severityClass = 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/30 dark:bg-blue-950/20 dark:text-blue-300';
            $iconClass = 'info';
        }
    ?>
    <div class="border rounded-xl p-4 flex gap-3 <?php echo $severityClass; ?>">
        <i data-lucide="<?php echo $iconClass; ?>" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <h4 class="font-bold text-sm"><?php echo htmlspecialchars($parsedError['title']); ?></h4>
            <p class="text-xs mt-1 leading-relaxed opacity-90">
                <?php echo htmlspecialchars($parsedError['description']); ?></p>

            <?php if (!empty($parsedError['solutions'])): ?>
            <div class="mt-3 bg-white/50 dark:bg-black/20 rounded-lg p-3 border border-current/10">
                <h5 class="text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                    <i data-lucide="wrench" class="w-3.5 h-3.5"></i> Önerilen Çözüm Adımları
                </h5>
                <ul class="list-none p-0 m-0 mt-2 flex flex-col gap-1.5 text-xs">
                    <?php foreach ($parsedError['solutions'] as $sol): 
                                $solFormatted = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', htmlspecialchars($sol));
                            ?>
                    <li class="flex items-start gap-1.5">
                        <i data-lucide="check-circle-2"
                            class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0"></i>
                        <span><?php echo $solFormatted; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <details class="mt-2 text-[11px] font-mono cursor-pointer opacity-85">
                <summary class="hover:underline">Teknik Detaylar ve Hata Kodu</summary>
                <div class="mt-1.5 bg-black/5 dark:bg-black/35 rounded p-2 overflow-x-auto max-h-[120px] select-all">
                    <?php echo htmlspecialchars($parsedError['technical_details']); ?>
                </div>
            </details>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtreler ve Kontroller -->
    <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        <div>
            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                <?php if ($isQueried): ?>
                <strong><?php echo $tarih1->format('d.m.Y'); ?></strong> ile
                <strong><?php echo $tarih2->format('d.m.Y'); ?></strong> tarihleri arası listelenmektedir.
                <?php else: ?>
                Onaylı Rapor Listesi
                <?php endif; ?>
            </span>
        </div>

        <?php if (!empty($onayliRaporlar)): ?>
        <div class="flex items-center gap-2 self-end md:self-auto">
            <button type="button" id="export-excel"
                class="w-9 h-9 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center transition-colors shadow-sm cursor-pointer"
                title="Excel'e Aktar">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
            </button>
            <button type="button" id="export-pdf"
                class="w-9 h-9 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center transition-colors shadow-sm cursor-pointer"
                title="PDF'e Aktar">
                <i data-lucide="file-down" class="w-4 h-4 text-rose-600"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tablo Alanı -->
    <form method="post" class="w-full">
        <div
            class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full border-collapse text-left" id="onayli-rapor-table">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[60px] text-center">
                            Sıra</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Sigortalı Bilgileri</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Vaka Türü</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Onaylama Türü</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            Poliklinik Tarihi</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">
                            İşbaşı / Kontrol</th>
                        <th
                            class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center pr-6 w-[120px]">
                            İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php if ($isQueried): ?>
                    <?php if (!empty($onayliRaporlar)): ?>
                    <?php
                            $i = 0; 
                            foreach ($onayliRaporlar as &$rapor):
                                $i++;
                                $onay_turu = $RaporModel->onaylanmaTuru($rapor['MEDULARAPORID'] ?? 0);
                                $rapor['ONAYTURU'] = $onay_turu ?? 'Belirtilmemiş';
                            ?>
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="py-3.5 px-4 text-sm text-center font-medium text-zinc-500 dark:text-zinc-400">
                            <?php echo $i; ?></td>
                        <td class="py-3.5 px-4 text-sm">
                            <div class="flex flex-col">
                                <span
                                    class="font-semibold text-zinc-900 dark:text-zinc-100 leading-tight"><?php echo htmlspecialchars(($rapor['AD'] ?? '') . ' ' . ($rapor['SOYAD'] ?? '')); ?></span>
                                <span
                                    class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5"><?php echo htmlspecialchars($rapor['TCKIMLIKNO'] ?? ''); ?></span>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-sm text-center">
                            <?php
                                        $vakaBadgeClass = 'border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300';
                                        if (stripos($rapor['VAKAADI'], 'ANALIK') !== false) {
                                            $vakaBadgeClass = 'border-pink-200 dark:border-pink-900/30 bg-pink-50 dark:bg-pink-950/20 text-pink-700 dark:text-pink-300';
                                        } elseif (stripos($rapor['VAKAADI'], 'HASTALIK') !== false) {
                                            $vakaBadgeClass = 'border-blue-200 dark:border-blue-900/30 bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300';
                                        } elseif (stripos($rapor['VAKAADI'], 'KAZASI') !== false) {
                                            $vakaBadgeClass = 'border-amber-200 dark:border-amber-900/30 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300';
                                        }
                                        ?>
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-all <?php echo $vakaBadgeClass; ?>">
                                <?php echo htmlspecialchars($rapor['VAKAADI'] ?? ''); ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-sm text-center font-medium text-zinc-700 dark:text-zinc-300">
                            <span
                                class="inline-flex items-center rounded-md bg-zinc-50 dark:bg-zinc-800 px-2 py-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400 ring-1 ring-inset ring-zinc-500/10"><?php echo htmlspecialchars($rapor['ONAYTURU'] ?? ''); ?></span>
                        </td>
                        <td class="py-3.5 px-4 text-sm text-center text-zinc-600 dark:text-zinc-400 font-medium">
                            <?php echo htmlspecialchars($rapor['POLIKLINIKTAR'] ?? ''); ?></td>
                        <td class="py-3.5 px-4 text-sm text-center text-zinc-600 dark:text-zinc-400 font-medium">
                            <?php echo htmlspecialchars($rapor['ISBASKONTTAR'] ?? ''); ?></td>
                        <td class="py-3.5 px-4 text-sm pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Raporu Göster (Fiş) Icon Button -->
                                <a href="rapor-onay-goster.php?id=<?php echo htmlspecialchars($rapor['MEDULARAPORID']); ?>"
                                    target="_blank"
                                    class="w-8 h-8 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center transition-colors shadow-sm"
                                    title="Fişi Göster">
                                    <i data-lucide="eye" class="w-4 h-4 text-sky-600"></i>
                                </a>
                                <!-- Onay İptal Icon Button -->
                                <a href="#" data-id="<?php echo Security::encrypt($rapor['MEDULARAPORID'] ?? ''); ?>"
                                    class="onay-iptal w-8 h-8 rounded-md border border-red-200 dark:border-red-900/30 bg-red-50/50 dark:bg-red-950/10 text-red-650 hover:bg-red-50 dark:hover:bg-red-950/20 hover:text-red-700 dark:hover:text-red-400 flex items-center justify-center transition-colors shadow-sm"
                                    title="Onay İptal">
                                    <i data-lucide="x-circle" class="w-4 h-4 text-rose-600"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; 
                            unset($rapor);
                            ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
                                <span>Belirtilen tarih aralığında onaylanmış rapor bulunamadı.</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2 animate-pulse">
                                <i data-lucide="calendar-search" class="w-8 h-8 opacity-45"></i>
                                <span>Lütfen onaylı raporları görüntülemek için tarih seçip sorgulama yapın.</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Bu gizli form, veriyi export.php'ye göndermek için kullanılacak -->
<form id="export-form" action="pages/onayli-raporlar/export.php" method="post" style="display: none;">
    <input type="hidden" name="format" id="export-format">
    <textarea name="rapor_data" id="export-data"></textarea>
</form>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script>
(function() {
    if (window.flatpickr) {
        const fp1 = flatpickr("#tarih1", {
            locale: "tr",
            dateFormat: "d.m.Y",
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    let tarih1 = selectedDates[0];
                    let yil = tarih1.getFullYear();
                    let ay = tarih1.getMonth() + 1;
                    let sonGun = new Date(yil, ay, 0).getDate();

                    let ayStr = String(ay).padStart(2, '0');
                    let gunStr = String(sonGun).padStart(2, '0');
                    let newDate2 = `${gunStr}.${ayStr}.${yil}`;

                    if (window.fp2Instance) {
                        window.fp2Instance.setDate(newDate2, true);
                    }
                }
            }
        });

        const fp2 = flatpickr("#tarih2", {
            locale: "tr",
            dateFormat: "d.m.Y",
            allowInput: true
        });

        window.fp2Instance = fp2;
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }

    const btnExcel = document.getElementById('export-excel');
    const btnPdf = document.getElementById('export-pdf');
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
})();
</script>

<script src="App/Src/onayli_raporlar.js"></script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>
