<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Helper\Security;
use Models\UserModel;
use Models\KullaniciAyarModel;
use Models\KvkkRizaModel;

Security::checkLogin();

$UserModel = new UserModel();
$KullaniciAyarModel = new KullaniciAyarModel();
$KvkkRizaModel = new KvkkRizaModel();

$user = $UserModel->find($_SESSION['kullanici_id'] ?? null);
$saat_9_mail_bildirimi = $KullaniciAyarModel->getSetting('saat_9_mail_bildirimi');
$rapor_otomatik_onay_bildirim = $KullaniciAyarModel->getSetting('rapor_otomatik_onay_bildirim');
$giris_kayitlari = $UserModel->getLoginRecords($_SESSION['kullanici_id'], 5); // Mobil için son 5 yeterli

$userRole = $_SESSION["role"] ?? "user";

$initials = '';
$nameParts = explode(' ', $user->adi_soyadi ?? 'K');
foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
$initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Profile Card Header -->
    <div class="p-4 bg-zinc-900 dark:bg-zinc-800 text-white rounded-2xl flex items-center gap-3.5 shadow-sm">
        <div class="w-12 h-12 rounded-full bg-zinc-700 dark:bg-zinc-600 border-2 border-zinc-500/30 flex items-center justify-center font-bold text-sm text-white">
            <?php echo $initials; ?>
        </div>
        <div class="flex flex-col text-left min-w-0">
            <span class="text-sm font-bold truncate leading-none mb-1.5"><?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?></span>
            <span class="text-[10px] text-zinc-300 truncate font-medium"><?php echo htmlspecialchars($user->email); ?></span>
        </div>
    </div>

    <!-- Tab chips -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
        <button type="button" id="tab-btn-personal" onclick="switchProfileTab('personal')" class="profile-tab-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all bg-zinc-900 text-white dark:bg-zinc-50 dark:text-zinc-950 shadow-sm border border-transparent">
            Bilgiler
        </button>
        <button type="button" id="tab-btn-password" onclick="switchProfileTab('password')" class="profile-tab-chip px-3 py-1.5 rounded-full text-xs font-semibold text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:text-zinc-950 transition-all">
            Şifre
        </button>
        <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
        <button type="button" id="tab-btn-notifications" onclick="switchProfileTab('notifications')" class="profile-tab-chip px-3 py-1.5 rounded-full text-xs font-semibold text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:text-zinc-950 transition-all">
            Bildirimler
        </button>
        <?php endif; ?>
        <button type="button" id="tab-btn-logins" onclick="switchProfileTab('logins')" class="profile-tab-chip px-3 py-1.5 rounded-full text-xs font-semibold text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:text-zinc-950 transition-all">
            Girişler
        </button>
    </div>

    <!-- Forms Area -->
    <div id="profile-tabs-content">
        <!-- Form: profileForm (Personal & Password) -->
        <form id="profileForm" onsubmit="event.preventDefault();" class="flex flex-col gap-4">
            
            <!-- SECTION 1: Kişisel Bilgiler -->
            <div id="section-personal" class="profile-section flex flex-col gap-4">
                <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-3 shadow-xs">
                    <div class="flex items-center gap-1.5 border-b border-zinc-100 dark:border-zinc-800 pb-2 mb-1">
                        <i data-lucide="user" class="w-4 h-4 text-zinc-500"></i>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Kişisel Bilgiler</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] text-zinc-400 font-semibold uppercase tracking-wider">Kullanıcı Adı</label>
                        <input type="text" id="kullanici_adi" name="kullanici_adi" value="<?php echo htmlspecialchars($user->kullanici_adi); ?>" readonly class="w-full h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 text-zinc-500 text-xs font-semibold cursor-not-allowed">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] text-zinc-400 font-semibold uppercase tracking-wider">E-posta Adresi</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" readonly class="w-full h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 text-zinc-500 text-xs font-semibold cursor-not-allowed">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] text-zinc-400 font-semibold uppercase tracking-wider">Ad Soyad</label>
                        <input type="text" id="adi_soyadi" name="adi_soyadi" value="<?php echo htmlspecialchars($user->adi_soyadi); ?>" class="w-full h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 text-xs font-semibold">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] text-zinc-400 font-semibold uppercase tracking-wider">Telefon Numarası</label>
                        <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($user->telefon); ?>" class="w-full h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 text-xs font-semibold">
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Şifre Değiştir -->
            <div id="section-password" class="profile-section flex flex-col gap-4 hidden">
                <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-3 shadow-xs">
                    <div class="flex items-center gap-1.5 border-b border-zinc-100 dark:border-zinc-800 pb-2 mb-1">
                        <i data-lucide="key-round" class="w-4 h-4 text-zinc-500"></i>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Şifre Ayarları</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] text-zinc-400 font-semibold uppercase tracking-wider">Mevcut Şifre</label>
                        <input type="password" id="mevcut_sifre" name="mevcut_sifre" autocomplete="new-password" placeholder="Mevcut Şifre" class="w-full h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 text-xs font-semibold">
                        <span class="text-[9px] text-zinc-400 mt-0.5">Bilgileri veya şifreyi güncellemek için mevcut şifreniz zorunludur.</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] text-zinc-400 font-semibold uppercase tracking-wider">Yeni Şifre</label>
                        <input type="password" id="yeni_sifre" name="yeni_sifre" placeholder="Yeni Şifre" class="w-full h-9 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 text-xs font-semibold">
                        <span class="text-[9px] text-zinc-400 mt-0.5 font-medium">Değiştirmek istemiyorsanız boş bırakınız.</span>
                    </div>
                </div>
            </div>

            <!-- Profile form save triggers (Both Bilgiler and Şifre are processed by #kaydetButton) -->
            <div id="profile-save-container" class="profile-section">
                <button type="button" id="kaydetButton" class="w-full h-10 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Bilgileri ve Şifreyi Kaydet</span>
                </button>
            </div>
        </form>

        <!-- SECTION 3: Bildirim Ayarları -->
        <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
        <div id="section-notifications" class="profile-section flex flex-col gap-4 hidden">
            <form id="bildirimForm" onsubmit="event.preventDefault();" class="flex flex-col gap-4">
                <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-4 shadow-xs">
                    <div class="flex items-center gap-1.5 border-b border-zinc-100 dark:border-zinc-800 pb-2 mb-1">
                        <i data-lucide="bell" class="w-4 h-4 text-zinc-500"></i>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">E-posta Bildirim Tercihleri</span>
                    </div>
                    
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" id="saat_9_mail_bildirimi" name="saat_9_mail_bildirimi" <?php echo $saat_9_mail_bildirimi == 1 ? 'checked' : ''; ?> class="mt-0.5 rounded text-primary focus:ring-0" style="width: 15px; height: 15px; accent-color: #09090b;">
                        <div class="flex flex-col text-left">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 leading-none">Rapor Onayı Hatırlatma</span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 leading-normal">Hafta içi saat 09:00'da bekleyen raporların hatırlatma bildirimi gönderilir.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" id="rapor_otomatik_onay_bildirim" name="rapor_otomatik_onay_bildirim" <?php echo $rapor_otomatik_onay_bildirim == 1 ? 'checked' : ''; ?> class="mt-0.5 rounded text-primary focus:ring-0" style="width: 15px; height: 15px; accent-color: #09090b;">
                        <div class="flex flex-col text-left">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 leading-none">Otomatik Rapor Onay Bildirimi</span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1 leading-normal">Otomatik onaylanan raporlar hakkında bilgilendirme e-postası alırsınız.</span>
                        </div>
                    </label>

                    <button type="button" id="bildirimKaydetButton" class="w-full h-10 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Tercihleri Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- SECTION 4: Giriş Kayıtları -->
        <div id="section-logins" class="profile-section flex flex-col gap-4 hidden">
            <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-3 shadow-xs">
                <div class="flex items-center gap-1.5 border-b border-zinc-100 dark:border-zinc-800 pb-2 mb-1">
                    <i data-lucide="history" class="w-4 h-4 text-zinc-500"></i>
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Son 5 Giriş Kaydı</span>
                </div>
                <?php if (count($giris_kayitlari) > 0) { ?>
                    <div class="flex flex-col gap-2.5 divide-y divide-zinc-100 dark:divide-zinc-800/80">
                        <?php foreach ($giris_kayitlari as $giris) { ?>
                            <div class="flex flex-col text-left gap-1 pt-2 first:pt-0">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-zinc-700 dark:text-zinc-300"><?php echo htmlspecialchars($giris->ip_address); ?></span>
                                    <span class="text-[9px] text-zinc-400 font-mono"><?php echo $giris->created_at; ?></span>
                                </div>
                                <span class="text-[9px] text-zinc-500 truncate" title="<?php echo htmlspecialchars($giris->browser); ?>"><?php echo htmlspecialchars($giris->browser); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="p-4 text-center text-zinc-400 text-xs">Kayıtlı giriş verisi bulunmamaktadır.</div>
                <?php } ?>
            </div>
            
            <!-- Mobile Account Deletion Trigger -->
            <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
            <div class="p-4 bg-rose-50/50 dark:bg-rose-950/10 border border-rose-200/60 dark:border-rose-900/30 rounded-xl flex flex-col gap-3">
                <div class="flex items-center gap-1.5 text-rose-800 dark:text-rose-400">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    <span class="text-xs font-bold">Hesap Kapatma</span>
                </div>
                <p class="text-[10px] text-rose-700/80 dark:text-rose-400/80 leading-normal">
                    Hesabınızı kalıcı olarak silmek istediğinizde, tüm kişisel verileriniz, tanımlı işyerleriniz ve tüm geçmiş vizite verileriniz kalıcı olarak silinir. Bu işlem geri alınamaz!
                </p>
                <form id="deleteAccountForm" class="flex flex-col gap-2.5 mt-1">
                    <input type="password" id="mevcut_sifre_modal" name="mevcut_sifre" placeholder="Silme Onayı İçin Şifreniz" class="w-full h-9 px-3 rounded-lg border border-rose-200 dark:border-rose-900 bg-white dark:bg-zinc-950 text-zinc-800 dark:text-zinc-100 text-xs font-semibold">
                    <button type="button" class="delete-account w-full h-9 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>Hesabımı Kalıcı Olarak Sil</span>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchProfileTab(tabName) {
    // Reset and select active tabs style
    document.querySelectorAll('.profile-tab-chip').forEach(btn => {
        btn.className = "profile-tab-chip px-3 py-1.5 rounded-full text-xs font-semibold text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:text-zinc-950 transition-all";
    });
    
    const activeBtn = document.getElementById('tab-btn-' + tabName);
    if (activeBtn) {
        activeBtn.className = "profile-tab-chip px-3 py-1.5 rounded-full text-xs font-bold transition-all bg-zinc-900 text-white dark:bg-zinc-50 dark:text-zinc-950 shadow-sm border border-transparent";
    }

    // Hide all sections
    document.getElementById('section-personal').classList.add('hidden');
    document.getElementById('section-password').classList.add('hidden');
    
    const notifSection = document.getElementById('section-notifications');
    if (notifSection) notifSection.classList.add('hidden');
    
    document.getElementById('section-logins').classList.add('hidden');
    
    // Save button display toggle (Show save button only for Personal and Password forms)
    const saveContainer = document.getElementById('profile-save-container');

    if (tabName === 'personal') {
        document.getElementById('section-personal').classList.remove('hidden');
        saveContainer.classList.remove('hidden');
    } else if (tabName === 'password') {
        document.getElementById('section-password').classList.remove('hidden');
        saveContainer.classList.remove('hidden');
    } else if (tabName === 'notifications') {
        if (notifSection) notifSection.classList.remove('hidden');
        saveContainer.classList.add('hidden');
    } else if (tabName === 'logins') {
        document.getElementById('section-logins').classList.remove('hidden');
        saveContainer.classList.add('hidden');
    }
}

// Mobile page initial run hooks
(function() {
    if (window.lucide) {
        window.lucide.createIcons();
    }
    switchProfileTab('personal');
})();
</script>
<script src="App/Src/kullanici.js?v=<?php echo time(); ?>"></script>
