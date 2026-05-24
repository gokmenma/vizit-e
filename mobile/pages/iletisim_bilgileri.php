<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
require_once __DIR__ . '/../../Core/Services/SgkViziteService.php';

Security::checkLogin();
Security::checkFirma();
Security::hasActiveSubscription();

$hataMesaji = '';
$userRole = $_SESSION["role"] ?? "user";

try {
    $sgkClient = new SgkViziteService();
    $mevcutBilgiler = $sgkClient->iletisimBilgileriniGetir();
} catch (Exception $e) {
    $hataMesaji = "Mevcut bilgiler çekilirken hata oluştu: " . $e->getMessage();
}

$mevcutEposta = $mevcutBilgiler ? (string)$mevcutBilgiler->eposta : 'Kayıt bulunamadı.';
$mevcutTel = $mevcutBilgiler ? (string)$mevcutBilgiler->tel : 'Kayıt bulunamadı.';
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">İletişim Bilgileri</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">İşyerinizin SGK sisteminde kayıtlı olan iletişim verilerini yönetin.</p>
    </div>

    <!-- Hata Mesajı -->
    <?php if ($hataMesaji): ?>
        <div class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-800 dark:text-rose-300 rounded-xl flex gap-2 text-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <span><?php echo htmlspecialchars($hataMesaji); ?></span>
        </div>
    <?php endif; ?>

    <!-- Current Credentials Card -->
    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-4 shadow-xs text-left">
        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-3 flex items-center gap-1.5">
            <i data-lucide="info" style="width: 16px; height: 16px;" class="text-zinc-500"></i>
            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100">SGK Sisteminde Kayıtlı Bilgiler</span>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex flex-col">
                <span class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">E-Posta</span>
                <span id="mevcut-eposta" class="text-xs font-semibold text-zinc-900 dark:text-zinc-50 truncate mt-0.5"><?php echo htmlspecialchars($mevcutEposta); ?></span>
            </div>
            <div class="flex flex-col">
                <span class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Cep Telefonu</span>
                <span id="mevcut-tel" class="text-xs font-semibold text-zinc-900 dark:text-zinc-50 mt-0.5"><?php echo htmlspecialchars($mevcutTel); ?></span>
            </div>
        </div>
    </div>

    <!-- Update Form Card -->
    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-4 shadow-xs text-left">
        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-3 flex items-center gap-1.5">
            <i data-lucide="edit-3" style="width: 16px; height: 16px;" class="text-zinc-500"></i>
            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100">Bilgileri Güncelle</span>
        </div>

        <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
            <form id="guncelleme-formu" onsubmit="event.preventDefault();" class="flex flex-col gap-4 w-full">
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="yeni-eposta">Yeni E-Posta Adresi</label>
                    <input type="email" id="yeni-eposta" name="eposta" placeholder="ornek@firma.com" class="h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold">
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="yeni-tel">Yeni Cep Telefonu</label>
                    <input type="text" id="yeni-tel" name="cepTel" placeholder="05xxxxxxxxx" maxlength="11" class="h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold">
                    <span class="text-[8px] text-zinc-400">Başında 0 olacak şekilde 11 hane giriniz.</span>
                </div>

                <button type="submit" id="kaydet-buton" class="h-9 w-full bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>SGK'ya Kaydet</span>
                </button>
            </form>
        <?php else: ?>
            <div class="p-3 bg-blue-50/50 dark:bg-blue-955/10 border border-blue-200/50 dark:border-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-[10px] leading-relaxed">
                <strong>Yetki Sınırlaması:</strong> İletişim bilgilerini güncelleme yetkiniz bulunmamaktadır. Lütfen yöneticiye başvurun.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    if (window.lucide) {
        window.lucide.createIcons();
    }

    const form = document.getElementById('guncelleme-formu');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('kaydet-buton');
            const eposta = document.getElementById('yeni-eposta').value.trim();
            const tel = document.getElementById('yeni-tel').value.trim();

            if (eposta === '' && tel === '') {
                Swal.fire('Uyarı!', 'Lütfen en az bir iletişim bilgisi giriniz.', 'warning');
                return;
            }

            // AJAX submit using fetch
            const formData = new URLSearchParams();
            formData.append('action', 'iletisimGuncelle');
            formData.append('eposta', eposta);
            formData.append('cepTel', tel);

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>Kaydediliyor...</span>';
            if (window.lucide) window.lucide.createIcons();

            fetch('App/Api/APIiletisim_bilgileri.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Başarılı!', data.message, 'success').then(() => {
                        App.refreshMobilePage(); // reload SPA content
                    });
                } else {
                    Swal.fire('Hata!', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Hata!', 'Sunucuya bağlanırken bir sorun oluştu.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i><span>SGK\'ya Kaydet</span>';
                if (window.lucide) window.lucide.createIcons();
            });
        });
    }
})();
</script>
