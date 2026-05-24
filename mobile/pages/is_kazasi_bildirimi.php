<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
require_once 'Core/Services/SgkViziteService.php';

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$hataMesaji = '';
$isKazalari = [];
$formGonderildi = false;
$searchType = ''; // 'tc' veya 'date' veya ''

$tcKimlikNo = !empty($_REQUEST['tc_kimlik_no']) ? $_REQUEST['tc_kimlik_no'] : '';
$tarih1Str = !empty($_REQUEST['tarih1']) ? $_REQUEST['tarih1'] : date('Y-m-01');
$tarih2Str = !empty($_REQUEST['tarih2']) ? $_REQUEST['tarih2'] : date('Y-m-d');

$tarih1 = new DateTime($tarih1Str);
$tarih2 = new DateTime($tarih2Str);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formGonderildi = true;
    try {
        $sgkClient = new SgkViziteService();
        
        if (isset($_POST['tarih_ile_ara'])) {
            $searchType = 'date';
            if (empty($_POST['tarih1']) || empty($_POST['tarih2'])) {
                throw new Exception("Lütfen tarih aralığını girin.");
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

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">İş Kazası Bildirimi</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">İş kazası bildirim kayıtlarını TC No veya Tarih sorgusu ile sorgulayın.</p>
    </div>

    <!-- Info Alert Box -->
    <div class="p-3 bg-amber-50 dark:bg-amber-955/20 border border-amber-200 dark:border-amber-900/30 text-amber-800 dark:text-amber-300 rounded-xl flex gap-2 text-[10px]">
        <i data-lucide="help-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
        <span>15 dakika aralık ile aynı işyeri için 24 saat içinde 2 sorgu sınırı mevcuttur.</span>
    </div>

    <!-- Hata Mesajı -->
    <?php if ($hataMesaji): ?>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-xl flex gap-2 text-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <span><?php echo htmlspecialchars($hataMesaji); ?></span>
        </div>
    <?php endif; ?>

    <!-- Query Tabs Selector -->
    <div class="grid grid-cols-2 gap-2 bg-zinc-100 dark:bg-zinc-800/80 p-1 rounded-xl">
        <button type="button" onclick="switchKazaTab('tc')" id="btn-tab-tc" class="py-1.5 rounded-lg text-xs font-bold transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 shadow-xs border border-transparent">
            TC Sorgula
        </button>
        <button type="button" onclick="switchKazaTab('date')" id="btn-tab-date" class="py-1.5 rounded-lg text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 transition-all">
            Tarih Sorgula
        </button>
    </div>

    <!-- Query Panels -->
    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
        
        <!-- Panel 1: TC Sorgula -->
        <div id="panel-tc" class="kaza-panel flex flex-col gap-3">
            <form action="is-kazasi-bildirimi" method="POST" class="flex flex-col gap-3.5 w-full">
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="tc_kimlik_no">TC Kimlik Numarası</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input type="text" name="tc_kimlik_no" value="<?php echo htmlspecialchars($tcKimlikNo); ?>" placeholder="TC Kimlik No girin" class="h-9 w-full pl-9 pr-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold" required maxlength="11" pattern="\d{11}">
                    </div>
                </div>
                <button type="submit" name="tc_ile_ara" class="h-9 w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>TC Sorgulama Başlat</span>
                </button>
            </form>
        </div>

        <!-- Panel 2: Tarih Sorgula -->
        <div id="panel-date" class="kaza-panel flex flex-col gap-3 hidden">
            <form action="is-kazasi-bildirimi" method="POST" class="flex flex-col gap-3.5 w-full">
                <div class="grid grid-cols-2 gap-2">
                    <div class="flex flex-col gap-1 text-left">
                        <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="tarih1">Başlangıç</label>
                        <input type="text" id="tarih1" name="tarih1" value="<?php echo htmlspecialchars($tarih1Str); ?>" class="h-9 w-full px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold text-center">
                    </div>
                    <div class="flex flex-col gap-1 text-left">
                        <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="tarih2">Bitiş</label>
                        <input type="text" id="tarih2" name="tarih2" value="<?php echo htmlspecialchars($tarih2Str); ?>" class="h-9 w-full px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold text-center">
                    </div>
                </div>
                <button type="submit" name="tarih_ile_ara" class="h-9 w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>Tarih Sorgulama Başlat</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Results Info Bar -->
    <?php if ($formGonderildi): ?>
        <div class="text-left font-bold text-xs text-zinc-700 dark:text-zinc-300 mt-1">
            <?php if ($searchType === 'date'): ?>
                <?php echo $tarih1->format('d.m.Y'); ?> - <?php echo $tarih2->format('d.m.Y'); ?> Sorgu Sonuçları
            <?php else: ?>
                <?php echo htmlspecialchars($tcKimlikNo); ?> Sorgu Sonuçları
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Results Cards -->
    <div class="flex flex-col gap-3">
        <?php if ($formGonderildi): ?>
            <?php if (!empty($isKazalari)): ?>
                <?php foreach ($isKazalari as $kaza): ?>
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl flex flex-col gap-3 shadow-xs text-left" data-bildirim-id="<?php echo htmlspecialchars($kaza['BILDIRIMID']); ?>">
                        
                        <!-- Header ID -->
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 font-mono">Bildirim ID: <?php echo htmlspecialchars($kaza['BILDIRIMID']); ?></span>
                            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-700 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded-full">
                                İş Kazası
                            </span>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-3 text-xs leading-normal">
                            <div class="flex flex-col">
                                <span class="text-[9px] text-zinc-400 uppercase tracking-wider font-semibold">TC Kimlik No</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200 font-mono mt-0.5"><?php echo htmlspecialchars($kaza['TCKIMLIKNO']); ?></span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] text-zinc-400 uppercase tracking-wider font-semibold">Provizyon Tarihi</span>
                                <span class="font-bold text-zinc-650 dark:text-zinc-400 mt-0.5"><?php echo htmlspecialchars($kaza['PROVIZYONTARIHI']); ?></span>
                            </div>
                        </div>

                        <div class="flex flex-col">
                            <span class="text-[9px] text-zinc-400 uppercase tracking-wider font-semibold">Tesis Adı</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5"><?php echo htmlspecialchars($kaza['TESISADI']); ?></span>
                        </div>

                        <div class="flex flex-col">
                            <span class="text-[9px] text-zinc-400 uppercase tracking-wider font-semibold">Kaza Tarihi</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5"><?php echo htmlspecialchars($kaza['ISKAZASITARIHI']); ?></span>
                        </div>

                        <!-- Card Action -->
                        <div class="border-t border-zinc-100 dark:border-zinc-800/80 pt-3 mt-1 flex justify-end">
                            <button type="button" class="btn-kapat h-8 px-3 rounded-lg border border-amber-200 dark:border-amber-900/30 bg-amber-50/50 dark:bg-amber-955/10 text-amber-750 hover:bg-amber-50 flex items-center justify-center gap-1.5 transition-all text-xs font-bold cursor-pointer">
                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                <span>Okundu İşaretle</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 text-center border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-white dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">Belirtilen kriterlere uygun iş kazası bildirimi bulunamadı.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="p-6 text-center border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-white dark:bg-zinc-900 flex flex-col items-center justify-center gap-2">
                <i data-lucide="search" style="width: 20px; height: 20px;" class="text-zinc-450 animate-pulse"></i>
                <p class="text-xs text-zinc-500">Lütfen sorgulamak için TC No veya tarih seçip arama yapın.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchKazaTab(tab) {
    document.getElementById('btn-tab-tc').className = "py-1.5 rounded-lg text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 transition-all";
    document.getElementById('btn-tab-date').className = "py-1.5 rounded-lg text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 transition-all";
    
    document.getElementById('btn-tab-' + tab).className = "py-1.5 rounded-lg text-xs font-bold transition-all bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 shadow-xs border border-transparent";
    
    document.querySelectorAll('.kaza-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById('panel-' + tab).classList.remove('hidden');
}

(function() {
    if (window.flatpickr) {
        const fp1 = flatpickr("#tarih1", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true,
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    let date1 = selectedDates[0];
                    let year = date1.getFullYear();
                    let month = date1.getMonth() + 1;
                    let lastDay = new Date(year, month, 0).getDate();
                    
                    let monthStr = String(month).padStart(2, '0');
                    let dayStr = String(lastDay).padStart(2, '0');
                    let date2Str = `${year}-${monthStr}-${dayStr}`;
                    
                    if (window.kazaFp2Instance) {
                        window.kazaFp2Instance.setDate(date2Str, true);
                    }
                }
            }
        });

        const fp2 = flatpickr("#tarih2", {
            locale: "tr",
            dateFormat: "Y-m-d",
            allowInput: true
        });

        window.kazaFp2Instance = fp2;
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }

    // Tab default state selection based on URL search query inputs
    const initialTab = "<?php echo (!empty($searchType) ? $searchType : 'tc'); ?>";
    switchKazaTab(initialTab);

    // Dynamic Okundu (Kapat) Button Binding
    document.querySelectorAll('.btn-kapat').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('[data-bildirim-id]');
            const id = card.dataset.bildirimId;

            Swal.fire({
                title: "Emin misiniz?",
                text: "Bu bildirim 'okundu' olarak işaretlenecek ve bir sonraki sorguda görünmeyecektir.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Evet, İşaretle!",
                cancelButtonText: "İptal",
                confirmButtonColor: '#09090b',
                cancelButtonColor: '#71717a',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'isKazasiKapat', bildirimId: id })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire("Başarılı!", "Bildirim okundu olarak işaretlendi.", "success");
                            $(card).fadeOut(400, function() { $(this).remove(); });
                        } else {
                            Swal.fire("Hata!", data.message, "error");
                        }
                    })
                    .catch(err => Swal.fire("Hata!", "Sunucu bağlantısı koptu.", "error"));
                }
            });
        });
    });
})();
</script>
