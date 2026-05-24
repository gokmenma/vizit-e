<?php

use App\Helper\Security;
require_once __DIR__ . '/../Core/Services/SgkViziteService.php';


Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();


$hataMesaji = '';
$title = 'İş Kazası Bildirimi';
$isKazalari = [];
$formGonderildi = false;

$tcKimlikNo = !empty($_REQUEST['tc_kimlik_no']) ? $_REQUEST['tc_kimlik_no'] : '';
$tarih1Str = !empty($_REQUEST['tarih1']) ? $_REQUEST['tarih1'] : date('Y-m-01');
$tarih2Str = !empty($_REQUEST['tarih2']) ? $_REQUEST['tarih2'] : date('Y-m-d');

$tarih1 = new DateTime($tarih1Str);
$tarih2 = new DateTime($tarih2Str);

$searchType = ''; // 'tc' veya 'date' veya ''

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formGonderildi = true;
    try {
        $sgkClient = new SgkViziteService(); // Session'dan bilgileri alacak
        
        // Hangi formun gönderildiğini kontrol et
        if (isset($_POST['tarih_ile_ara'])) {
            $searchType = 'date';
            if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
                throw new Exception("Lütfen tarih aralığını tam olarak girin.");
            }
            $isKazalari = $sgkClient->isKazasiGetirTarihIle($tarih1, $tarih2);
        } elseif (isset($_POST['tc_ile_ara'])) {
            $searchType = 'tc';
            if (empty($_POST['tc_kimlik_no'])) {
                 throw new Exception("Lütfen TC Kimlik Numarasını girin.");
            }
            $isKazalari = $sgkClient->isKazasiGetirTcIle($tcKimlikNo);
        }
    } catch (Exception $e) {
        $hataMesaji = $e->getMessage();
    }
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

<div class="flex flex-col gap-6 w-full py-2 px-1">
    <!-- Header Bölümü -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">İş Kazası Bildirimi</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                TC Kimlik Numarası veya Tarih Aralığı sorgusu ile iş kazası bildirim kayıtlarını sorgulayın.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 text-nowrap self-start lg:self-auto flex-shrink-0">
            <!-- Form 1: TC ile Ara -->
            <form action="is-kazasi-bildirimi" method="POST" class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <i data-lucide="user" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" name="tc_kimlik_no" value="<?php echo htmlspecialchars($tcKimlikNo); ?>" placeholder="TC Kimlik No..." class="h-9 w-[130px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <button type="submit" name="tc_ile_ara" class="h-9 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 shadow cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>TC Sorgula</span>
                </button>
            </form>

            <!-- Form 2: Tarih ile Ara -->
            <form action="is-kazasi-bildirimi" method="POST" class="flex items-center gap-2 border-t sm:border-t-0 sm:border-l border-zinc-200 dark:border-zinc-800 pt-2 sm:pt-0 sm:pl-4">
                <div class="relative flex items-center">
                    <i data-lucide="calendar" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" id="tarih1" name="tarih1" value="<?php echo htmlspecialchars($tarih1Str); ?>" class="h-9 w-[100px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <span class="text-zinc-400 dark:text-zinc-600 text-xs font-semibold">-</span>
                <div class="relative flex items-center">
                    <i data-lucide="calendar" class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
                    <input type="text" id="tarih2" name="tarih2" value="<?php echo htmlspecialchars($tarih2Str); ?>" class="h-9 w-[100px] pl-9 pr-3 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300">
                </div>
                <button type="submit" name="tarih_ile_ara" class="h-9 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 shadow cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Tarih Sorgula</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Hata Mesajları -->
    <?php if ($hataMesaji): ?>
        <div class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Hata Oluştu</h4>
                <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtreler ve Kontroller -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        <div>
            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                <?php if ($formGonderildi && $searchType === 'date'): ?>
                    <strong><?php echo $tarih1->format('d.m.Y'); ?></strong> ile <strong><?php echo $tarih2->format('d.m.Y'); ?></strong> tarihleri arası sorgu sonuçları.
                <?php elseif ($formGonderildi && $searchType === 'tc'): ?>
                    <strong><?php echo htmlspecialchars($tcKimlikNo); ?></strong> TCKN sorgu sonuçları.
                <?php else: ?>
                    İş Kazası Bildirim Listesi
                <?php endif; ?>
            </span>
        </div>
        <div>
            <span class="text-xs text-amber-600 dark:text-amber-400 font-medium flex items-center gap-1">
                <i data-lucide="help-circle" class="w-4 h-4"></i>
                15 dakika aralık ile aynı işyeri için 24 saat içinde 2 sorgu sınırı mevcuttur.
            </span>
        </div>
    </div>

    <!-- Tablo Alanı -->
    <form method="post" class="w-full">
        <div class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full border-collapse text-left" id="is-kazasi-table">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center w-[120px]">Bildirim ID</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">TC Kimlik No</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">Provizyon Tarihi</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tesis Adı</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center">İş Kazası Tarihi</th>
                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center pr-6 w-[80px]">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <?php if ($formGonderildi): ?>
                        <?php if (!empty($isKazalari)): ?>
                            <?php foreach ($isKazalari as $kaza): ?>
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors" data-bildirim-id="<?php echo htmlspecialchars($kaza['BILDIRIMID']); ?>">
                                    <td class="py-3.5 px-4 text-sm text-center font-bold text-zinc-900 dark:text-zinc-50"><?php echo htmlspecialchars($kaza['BILDIRIMID']); ?></td>
                                    <td class="py-3.5 px-4 text-sm text-zinc-700 dark:text-zinc-300 font-mono"><?php echo htmlspecialchars($kaza['TCKIMLIKNO']); ?></td>
                                    <td class="py-3.5 px-4 text-sm text-center text-zinc-600 dark:text-zinc-400 font-medium"><?php echo htmlspecialchars($kaza['PROVIZYONTARIHI']); ?></td>
                                    <td class="py-3.5 px-4 text-sm text-zinc-700 dark:text-zinc-300 font-semibold leading-tight"><?php echo htmlspecialchars($kaza['TESISADI']); ?></td>
                                    <td class="py-3.5 px-4 text-sm text-center text-zinc-600 dark:text-zinc-400 font-medium"><?php echo htmlspecialchars($kaza['ISKAZASITARIHI']); ?></td>
                                    <td class="py-3.5 px-4 text-sm pr-6">
                                        <div class="flex items-center justify-end">
                                            <!-- Okundu İşaretle Icon Button -->
                                            <button type="button" class="btn-kapat w-8 h-8 rounded-md border border-amber-200 dark:border-amber-900/30 bg-amber-50/50 dark:bg-amber-950/10 text-amber-650 hover:bg-amber-50 dark:hover:bg-amber-950/20 hover:text-amber-700 dark:hover:text-amber-400 flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Okundu Olarak İşaretle">
                                                <i data-lucide="check" class="w-4 h-4 text-amber-600"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
                                        <span>Belirtilen kriterlere uygun iş kazası bildirimi bulunamadı.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2 animate-pulse">
                                    <i data-lucide="search" class="w-8 h-8 opacity-45"></i>
                                    <span>Lütfen iş kazası bildirimlerini görüntülemek için TC No veya tarih aralığı girip sorgulama yapın.</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Script'ler -->
<?php include 'layouts/vendor-scripts.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.flatpickr) {
        const fp1 = flatpickr("#tarih1", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    let tarih1 = selectedDates[0];
                    let yil = tarih1.getFullYear();
                    let ay = tarih1.getMonth() + 1;
                    let sonGun = new Date(yil, ay, 0).getDate();

                    let ayStr = String(ay).padStart(2, '0');
                    let gunStr = String(sonGun).padStart(2, '0');
                    let newDate2 = `${yil}-${ayStr}-${gunStr}`;

                    if (window.fp2Instance) {
                        window.fp2Instance.setDate(newDate2, true);
                    }
                }
            }
        });

        const fp2 = flatpickr("#tarih2", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });

        window.fp2Instance = fp2;
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }

    document.querySelectorAll('.btn-kapat').forEach(button => {
        button.addEventListener('click', function() {
            const satir = this.closest('tr');
            const bildirimId = satir.dataset.bildirimId;

            Swal.fire({
                title: "Emin misiniz?",
                text: "Bu bildirim 'okundu' olarak işaretlenecek ve bir sonraki sorguda görünmeyecektir.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Evet, İşaretle!",
                cancelButtonText: "İptal",
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    // API.php'ye isKazasiKapat action'ı ile istek gönder
                    fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'isKazasiKapat',
                            bildirimId: bildirimId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire("Başarılı!", "Bildirim okundu olarak işaretlendi.", "success");
                            satir.remove();
                        } else {
                            Swal.fire("Hata!", data.message, "error");
                        }
                    })
                    .catch(error => Swal.fire("Ağ Hatası!", "Sunucuya bağlanılamadı: " + error, "error"));
                }
            });
        });
    });
});
</script>
<?php include 'layouts/foot.php'; ?>