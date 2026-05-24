<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
use Models\KullaniciIsyeriModel;
use Models\KullaniciAbonelikModel;

Security::checkLogin();

$İsyeriModel = new KullaniciIsyeriModel();
$KulllaniciAbonelik = new KullaniciAbonelikModel();

$kullaniciId = $_SESSION['kullanici_id'];
$userRole = $_SESSION["role"] ?? "user";

if ($userRole == "user") {
    $kullaniciId = $_SESSION['user']->admin_id ?? $kullaniciId;
}

// Kullanıcının aktif aboneliğini al
$firma_hakki = $KulllaniciAbonelik->getSubscriptionByUserId($kullaniciId)->firma_hakki ?? 0;

// Kullanılan firma hakkı
$kullanilan_firma_hakki = $İsyeriModel->countFirmByUserId($kullaniciId) ?? 0;

// Progres yüzdesi
if ($firma_hakki == 0) {
    $progress = 0; 
    $kalan_firma_hakki = 0;
} else {
    $kullanilan_firma_hakki = min($kullanilan_firma_hakki, $firma_hakki);
    $progress = ($kullanilan_firma_hakki / $firma_hakki) * 100;
    $kalan_firma_hakki = $firma_hakki - $kullanilan_firma_hakki;
}

// Kullanıcının işyerlerini al
if ($userRole == "user") {
    $isyeri_ids = $_SESSION['user']->yetkili_oldugu_isyeri_ids ?? '';
    $isyerleri = $İsyeriModel->AltKullaniciİsyerleri($isyeri_ids);
} else {
    $isyerleri = $İsyeriModel->whereRaw('kullanici_id = ? AND aktif_mi = ?', [$kullaniciId, 1]);
}
$selected_firma_id = $_SESSION['isyeri_id'] ?? null;
$hataMesaji = $_SESSION['hata'] ?? '';
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">İşyerlerim</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">İşyerlerinizi yönetin veya vizite işlemleri için aktif firma seçin.</p>
    </div>

    <!-- Limit Indicator -->
    <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
        <?php if ($firma_hakki == 0) : ?>
            <div class="p-3 bg-amber-50 dark:bg-amber-955/20 border border-amber-200 dark:border-amber-900/30 text-amber-800 dark:text-amber-300 rounded-xl flex gap-2 text-xs">
                <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <div class="flex flex-col">
                    <span class="font-bold">Abonelik Uyarısı</span>
                    <span class="mt-0.5">Aktif bir firma hakkınız bulunmamaktadır. Lütfen paket satın alın.</span>
                </div>
            </div>
        <?php else : ?>
            <div class="p-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-2 shadow-xs">
                <div class="flex justify-between items-center text-xs">
                    <div class="flex flex-col text-left">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-450 dark:text-zinc-500">Firma Aktivasyon Limiti</span>
                        <span class="font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">Kullanılan: <?php echo $kullanilan_firma_hakki; ?> / <?php echo $firma_hakki; ?></span>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-2.5 py-0.5 rounded-full"><?php echo round($progress); ?>% Dolu</span>
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-850 rounded-full h-1.5 mt-0.5">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-300" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Firma Listesi</span>
        <?php if ($kullanilan_firma_hakki < $firma_hakki && ($userRole == "admin" || $userRole == "superadmin")): ?>
            <button type="button" onclick="$('#isyeri_id').val(0); $('#isyeri-form')[0].reset(); $('#defaultModal').removeClass('hidden').addClass('flex');" class="h-8 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center gap-1 shadow-xs cursor-pointer">
                <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                <span>Yeni Ekle</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Workplaces Grid (Flat Cards) -->
    <div class="flex flex-col gap-3">
        <?php if (!empty($isyerleri)): ?>
            <?php 
            $i = 0; 
            foreach ($isyerleri as $isyeri): 
                $i++;
                $enc_id = Security::encrypt($isyeri->id);
                $is_selected = ((int)$isyeri->id === (int)$selected_firma_id);
            ?>
                <div class="mobile-isyeri-card p-4 bg-white dark:bg-zinc-900 border <?php echo $is_selected ? 'border-zinc-900 dark:border-zinc-100 ring-1 ring-zinc-900 dark:ring-zinc-100' : 'border-zinc-200 dark:border-zinc-800'; ?> rounded-2xl flex flex-col gap-3 shadow-xs">
                    
                    <!-- Title & Code -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex flex-col text-left min-w-0">
                            <span class="font-bold text-xs text-zinc-900 dark:text-zinc-50 leading-tight"><?php echo htmlspecialchars($isyeri->firma_adi); ?></span>
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-mono mt-1">Kod: <?php echo htmlspecialchars($isyeri->isyeri_kodu); ?></span>
                        </div>
                        <?php if ($is_selected): ?>
                            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/20 px-2 py-0.5 rounded-full">
                                <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                Seçili
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Config Chips -->
                    <div class="flex flex-wrap gap-2 text-[10px]">
                        <div class="flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-50 dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">Oto Onay:</span>
                            <?php if ($isyeri->otomatik_rapor_onay == "1"): ?>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">Açık</span>
                            <?php else: ?>
                                <span class="font-bold text-rose-600 dark:text-rose-400">Kapalı</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($isyeri->otomatik_onay_eposta)): ?>
                            <div class="flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-50 dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-800 max-w-[200px] truncate" title="<?php echo htmlspecialchars($isyeri->otomatik_onay_eposta); ?>">
                                <i data-lucide="mail" style="width: 10px; height: 10px;" class="text-zinc-400"></i>
                                <span class="text-zinc-500 truncate"><?php echo htmlspecialchars($isyeri->otomatik_onay_eposta); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800/80 pt-3 mt-1">
                        <div class="flex items-center gap-1.5">
                            <?php if (!$is_selected): ?>
                                <button type="button" onclick="App.selectWorkplace('<?php echo $enc_id; ?>')" class="h-7 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-lg text-[10px] font-bold transition-all cursor-pointer shadow-xs">
                                    Aktif Yap
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
                                <button type="button" data-id="<?php echo $enc_id; ?>" class="isyeri-duzenle w-7 h-7 rounded-lg border border-zinc-250 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-650 dark:text-zinc-400 flex items-center justify-center cursor-pointer shadow-xs">
                                    <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                </button>
                                <button type="button" data-isyeri-id="<?php echo Security::encrypt($isyeri->id); ?>" class="isyeri-sil w-7 h-7 rounded-lg border border-rose-200 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-950/10 text-rose-650 flex items-center justify-center cursor-pointer shadow-xs">
                                    <i data-lucide="trash-2" style="width: 13px; height: 13px;" class="text-rose-600"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-6 text-center border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-white dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">Kayıtlı işyeriniz bulunmamaktadır.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Yeni/Düzenle İşyeri Form Overlay -->
<div id="defaultModal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4 bg-zinc-950/45 backdrop-blur-sm animate-fade-in">
    <div class="relative w-full max-w-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl flex flex-col max-h-[85vh] overflow-y-auto">
        <form method="POST" id="isyeri-form" class="w-full">
            <input type="hidden" name="isyeri_id" id="isyeri_id" value="0">
            
            <div class="border-b border-zinc-100 dark:border-zinc-850 px-5 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-zinc-900 dark:text-zinc-50"></i>
                    <h4 class="font-bold text-xs text-zinc-900 dark:text-zinc-50" id="defaultModalLabel">İşyeri Bilgileri</h4>
                </div>
                <button type="button" onclick="$('#defaultModal').removeClass('flex').addClass('hidden');" class="text-zinc-400 dark:text-zinc-500 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-5 flex flex-col gap-3.5">
                <div class="p-3 bg-blue-50/50 dark:bg-blue-950/10 text-blue-800 dark:text-blue-300 rounded-lg text-[10px] leading-relaxed font-medium">
                    <strong>Güvenli Depolama:</strong> Şifreleriniz veri tabanında güçlü şifreleme yöntemleriyle korunmaktadır.
                </div>

                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="firma_adi">Firma Unvanı (Kısa Ad)</label>
                    <input class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="firma_adi" name="firma_adi" placeholder="Örn: Merkez Ofis" required type="text">
                </div>
                
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="kullanici_adi">SGK TC Kimlik No</label>
                    <input type="password" class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="kullanici_adi" name="kullanici_adi" required maxlength="11" pattern="\d{11}" placeholder="SGK TCKN">
                </div>
                
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="isyeri_kodu">SGK İşyeri Kodu</label>
                    <input class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="isyeri_kodu" name="isyeri_kodu" required type="number" placeholder="SGK İşyeri Kodu" maxlength="4">
                </div>
                
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="ws_sifre">SGK İşyeri Şifresi</label>
                    <input class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="ws_sifre" name="ws_sifre" required placeholder="Webservis Şifresi" autocomplete="new-password" type="password">
                </div>
                
                <label class="flex items-center gap-2 cursor-pointer py-1 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    <input id="otomatik_rapor_onay" type="checkbox" name="otomatik_rapor_onay" class="w-4 h-4 rounded text-zinc-900 border-zinc-300 cursor-pointer">
                    <span>Otomatik Rapor Onaylama</span>
                </label>
                
                <div class="otomatik-onay-eposta d-none flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="otomatik_onay_eposta">E-posta Adresleri (Virgülle Ayırın)</label>
                    <textarea class="w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 py-2 text-xs font-medium min-h-[50px]" id="otomatik_onay_eposta" name="otomatik_onay_eposta" placeholder="ornek1@mail.com, ornek2@mail.com"></textarea>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-850 px-5 py-3.5 flex items-center justify-end gap-2">
                <button type="button" onclick="$('#defaultModal').removeClass('flex').addClass('hidden');" class="h-9 px-4 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl text-xs font-bold shadow-sm cursor-pointer">Kapat</button>
                <button type="button" class="h-9 px-4 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-850 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl text-xs font-bold shadow isyeri-kaydet cursor-pointer">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Silme Onay Dialog -->
<dialog id="alert-dialog" aria-labelledby="alert-dialog-title" aria-describedby="alert-dialog-description">
  <div>
    <header>
      <h2 id="alert-dialog-title">Emin misiniz?</h2>
      <p id="alert-dialog-description">Bu işlem geri alınamaz. Seçili işyeri kalıcı olarak silinecektir.</p>
    </header>

    <footer>
      <button type="button" class="btn-outline alert-dialog-cancel">İptal</button>
      <button type="button" class="btn-primary alert-dialog-confirm">Evet, Sil</button>
    </footer>
  </div>
</dialog>

<script>
// Polyfill bootstrap modal actions for JQuery in dynamic context
(function() {
    if (window.jQuery) {
        const $ = window.jQuery;
        $.fn.modal = function(action) {
            if (action === 'show') {
                this.removeClass('hidden').addClass('flex');
            } else if (action === 'hide') {
                this.removeClass('flex').addClass('hidden');
            }
            return this;
        };
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
</script>
<script src="App/Src/isyerlerim.js?v=<?php echo time(); ?>"></script>
