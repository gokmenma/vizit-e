<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
use App\Helper\Date;
use Models\RaporModel;

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$userRole = $_SESSION["role"] ?? "user";
$isyeri_id = $_SESSION['isyeri_id'] ?? 0;
$iptalRaporlar = [];
$hataMesaji = '';

try {
    $db = \Core\Database::getInstance()->getConnection();
    // İptal edilen raporları veritabanından çekelim
    $stmt = $db->prepare("SELECT * FROM onaylanan_raporlar WHERE isyeri_id = ? AND onay_durumu = 'iptal' ORDER BY updated_at DESC");
    $stmt->execute([$isyeri_id]);
    $iptalRaporlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $hataMesaji = $e->getMessage();
}
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1 text-left">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">İptal Edilen Raporlar</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Onay iptali yapılmış ve işlem bekleyen raporlar.</p>
    </div>

    <!-- Filtreler ve Arama -->
    <div class="relative flex items-center w-full">
        <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-zinc-400 z-10 pointer-events-none"></i>
        <input type="text" id="mobile-rapor-search" placeholder="İsim, TCKN veya Rapor No ara..." 
            class="h-9 w-full pl-9 pr-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold shadow-xs">
    </div>

    <!-- Error Alert -->
    <?php if ($hataMesaji): ?>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-xl flex gap-2.5 text-xs text-left">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold">Sistem Hatası</h4>
                <p class="mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mobile Touch Cards List -->
    <div class="flex flex-col gap-3" id="mobile-reports-container">
        <?php if (!empty($iptalRaporlar)): ?>
            <?php foreach ($iptalRaporlar as $rapor): 
                $enc_id = Security::encrypt($rapor['id']);
                $initials = '';
                $nameParts = explode(' ', $rapor['SIGORTALIADSOYAD']);
                foreach ($nameParts as $part) { 
                    if (!empty(trim($part))) {
                        $initials .= mb_substr(trim($part), 0, 1, 'UTF-8'); 
                    }
                }
                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');

                $vakaBg = 'rgba(0,0,0,0.03)';
                $vakaColor = 'text-zinc-700 dark:text-zinc-300';
                if (stripos($rapor['VAKAADI'], 'HASTALIK') !== false) {
                    $vakaBg = 'rgba(37, 99, 235, 0.08)';
                    $vakaColor = 'text-blue-600 dark:text-blue-400';
                } elseif (stripos($rapor['VAKAADI'], 'KAZASI') !== false) {
                    $vakaBg = 'rgba(245, 158, 11, 0.08)';
                    $vakaColor = 'text-amber-600 dark:text-amber-400';
                } elseif (stripos($rapor['VAKAADI'], 'ANALIK') !== false) {
                    $vakaBg = 'rgba(236, 72, 153, 0.08)';
                    $vakaColor = 'text-pink-600 dark:text-pink-400';
                }
            ?>
                <div class="mobile-report-card p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl flex flex-col gap-3 shadow-xs" 
                    data-search-text="<?php echo strtolower($rapor['SIGORTALIADSOYAD'] . ' ' . $rapor['TCKIMLIKNO'] . ' ' . $rapor['RAPORTAKIPNO']); ?>">
                    
                    <!-- Avatar Initials & Details -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-xs font-extrabold flex items-center justify-center flex-shrink-0">
                                <?php echo $initials; ?>
                            </div>
                            <div class="flex flex-col text-left min-w-0">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-50 truncate"><?php echo htmlspecialchars($rapor['SIGORTALIADSOYAD']); ?></span>
                                <span class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 mt-0.5">T.C.: <?php echo htmlspecialchars($rapor['TCKIMLIKNO']); ?></span>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold flex-shrink-0" style="background: <?php echo $vakaBg; ?>; color: inherit;"><?php echo htmlspecialchars($rapor['VAKAADI']); ?></span>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 gap-2 bg-zinc-50/50 dark:bg-zinc-900/50 border border-zinc-100 dark:border-zinc-800/80 rounded-xl p-2.5 text-left text-[10px]">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-zinc-400 font-semibold">Poliklinik Tarihi</span>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo !empty($rapor['POLIKLINIKTAR']) ? date('d.m.Y', strtotime($rapor['POLIKLINIKTAR'])) : '-'; ?></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-zinc-400 font-semibold">Rapor Takip No</span>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars($rapor['RAPORTAKIPNO'] ?? '-'); ?></span>
                        </div>
                        <div class="flex flex-col gap-0.5 col-span-2 mt-1 pt-1 border-t border-zinc-100 dark:border-zinc-800/50">
                            <span class="text-zinc-400 font-semibold">İptal Edilme Zamanı</span>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo !empty($rapor['updated_at']) ? date('d.m.Y H:i', strtotime($rapor['updated_at'])) : '-'; ?></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 mt-1">
                        <button type="button" class="rapor-tekrar-onayla-mobile h-8 px-4 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1 shadow-xs cursor-pointer"
                            data-id="<?php echo $enc_id; ?>"
                            data-name="<?php echo htmlspecialchars($rapor['SIGORTALIADSOYAD']); ?>">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            <span>Tekrar Onayla</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="py-12 text-center text-xs font-semibold text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
                <div class="flex flex-col items-center justify-center gap-2">
                    <i data-lucide="folder-open" class="w-8 h-8 opacity-40"></i>
                    <span>İptal edilmiş onay kaydı bulunmamaktadır.</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Lucide Icons'ları oluştur
    if (window.lucide) {
        lucide.createIcons();
    }

    // Client-side Arama Filtresi (Mobil)
    document.getElementById('mobile-rapor-search').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('#mobile-reports-container .mobile-report-card');

        cards.forEach(card => {
            const searchText = card.getAttribute('data-search-text');
            if (searchText) {
                if (searchText.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
        });
    });

    // Mobil Tekrar Onayla Tetikleyicisi
    document.querySelectorAll('.rapor-tekrar-onayla-mobile').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const enc_id = this.dataset.id;
            const name = this.dataset.name;

            Swal.fire({
                title: 'Emin misiniz?',
                text: `${name} isimli çalışanın rapor onayını tekrar SGK'ya bildirmek istiyor musunuz?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'hsl(var(--primary))',
                cancelButtonColor: '#71717a',
                confirmButtonText: 'Evet, Onayla',
                cancelButtonText: 'Vazgeç',
                background: document.documentElement.classList.contains('dark') ? '#09090b' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#09090b'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'SGK Bildirimi Gönderiliyor',
                        text: 'Lütfen bekleyin, işlem yapılıyor...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX call to re-approve
                    fetch('App/Api/APIiptal_raporlar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type: 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'rapor_tekrar_onayla',
                            id: enc_id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload active dynamic subpage in mobile shell
                                if (window.App && typeof window.App.refreshContent === 'function') {
                                    window.App.refreshContent();
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'SGK Bildirim Hatası',
                                text: data.message || 'Onaylama işlemi sırasında bir hata oluştu.',
                                confirmButtonColor: 'hsl(var(--primary))'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Sistem Hatası',
                            text: 'Bir sunucu bağlantı hatası meydana geldi.',
                            confirmButtonColor: 'hsl(var(--primary))'
                        });
                    });
                }
            });
        });
    });
</script>
