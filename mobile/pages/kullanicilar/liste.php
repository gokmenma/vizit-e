<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Helper\Security;
use App\Helper\IsyeriHelper;
use Models\UserModel;

Security::checkLogin();

$userRole = $_SESSION["role"] ?? "user";

if ($userRole !== 'admin' && $userRole !== 'superadmin') {
    echo "<div class='animate-in p-6 text-center border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-white dark:bg-zinc-900 m-4 flex flex-col items-center justify-center gap-3 shadow-xs'>
            <div class='w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-950/20 text-rose-600 flex items-center justify-center'>
                <i data-lucide='shield-alert' style='width:24px;height:24px;'></i>
            </div>
            <div class='flex flex-col gap-1'>
                <h3 class='text-sm font-bold text-zinc-900 dark:text-zinc-50'>Yetkisiz Erişim</h3>
                <p class='text-xs text-zinc-500 dark:text-zinc-400'>Bu sayfayı görüntülemek için yetkiniz bulunmamaktadır.</p>
            </div>
          </div>
          <script>
            if (window.lucide) window.lucide.createIcons();
          </script>";
    exit();
}

$UserModel = new UserModel();

$kullanicilar = $UserModel->AltKullanicilar($_SESSION['kullanici_id']);
$altKullaniciSayisi = count($kullanicilar);
$altKullaniciLimiti = $UserModel->getAltKullaniciLimiti($_SESSION['kullanici_id']);
$hataMesaji = $_SESSION['hata'] ?? '';
?>

<div class="animate-in flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Kullanıcılar</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Aboneliğiniz için yetkili alt kullanıcıları tanımlayın ve yönetin.</p>
    </div>

    <!-- Limits Indicators -->
    <?php if ($userRole == "admin" || $userRole == "superadmin"): ?>
        <div class="p-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-2 shadow-xs">
            <div class="flex justify-between items-center text-xs">
                <div class="flex flex-col text-left">
                    <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-450 dark:text-zinc-500">Alt Kullanıcı Limiti</span>
                    <span class="font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">Eklenen: <?php echo $altKullaniciSayisi; ?> / <?php echo $altKullaniciLimiti; ?></span>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-2.5 py-0.5 rounded-full"><?php echo round(($altKullaniciSayisi / max(1, $altKullaniciLimiti)) * 100); ?>% Dolu</span>
            </div>
            <div class="w-full bg-zinc-100 dark:bg-zinc-850 rounded-full h-1.5 mt-0.5">
                <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-300" style="width: <?php echo ($altKullaniciSayisi / max(1, $altKullaniciLimiti)) * 100; ?>%"></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Tanımlı Alt Kullanıcılar</span>
        <?php if ($altKullaniciSayisi < $altKullaniciLimiti): ?>
            <button type="button" onclick="$('#kullanici_id').val(0); $('#altKullaniciForm')[0].reset(); $('#isyerleri_ids').val([]).trigger('change'); $('input[name=\'yetkiler[]\']').prop('checked', false); $('#defaultModalLabel').text('Yeni Alt Kullanıcı'); $('#defaultModal').removeClass('hidden').addClass('flex');" class="h-8 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all flex items-center gap-1 shadow-xs cursor-pointer">
                <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
                <span>Yeni Ekle</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Sub-Users List (Flat Cards) -->
    <div class="flex flex-col gap-3">
        <?php if (!empty($kullanicilar)): ?>
            <?php 
            $i = 0; 
            foreach ($kullanicilar as $k): 
                $i++;
                $enc_id = Security::encrypt($k->id);
                
                // Initials
                $parts = explode(' ', $k->adi_soyadi ?? 'K');
                $initials = '';
                if (count($parts) >= 2) {
                    $initials = mb_substr($parts[0], 0, 1, 'UTF-8') . mb_substr($parts[count($parts)-1], 0, 1, 'UTF-8');
                } else if (count($parts) == 1) {
                    $initials = mb_substr($parts[0], 0, 2, 'UTF-8');
                }
                $initials = mb_strtoupper($initials, 'UTF-8');

                $yetkiler = $k->yetkiler ?? '';
                $has_onay = (strpos($yetkiler, 'rapor_onay') !== false);
                $has_manuel = (strpos($yetkiler, 'manuel_bildirim') !== false);
            ?>
                <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl flex flex-col gap-3.5 shadow-xs">
                    
                    <!-- Avatar, Names & Status -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 flex items-center justify-center font-bold text-xs text-zinc-750 dark:text-zinc-350 uppercase">
                                <?php echo $initials; ?>
                            </div>
                            <div class="flex flex-col text-left">
                                <span class="font-bold text-xs text-zinc-900 dark:text-zinc-50 leading-tight"><?php echo htmlspecialchars($k->adi_soyadi); ?></span>
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1 font-mono">@<?php echo htmlspecialchars($k->kullanici_adi); ?></span>
                            </div>
                        </div>
                        
                        <!-- Status Toggle Pill -->
                        <?php if ($k->durum == "Aktif"): ?>
                            <button type="button" class="inline-flex items-center gap-1 rounded-full border border-emerald-250 dark:border-emerald-950/30 bg-emerald-50 dark:bg-emerald-950/15 px-2 py-0.5 text-[9px] font-bold text-emerald-700 dark:text-emerald-400 cursor-pointer kullanici-durum transition-all shadow-xs" data-durum="0" data-kullanici-id="<?php echo $enc_id; ?>">
                                <span class="h-1 w-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Aktif</span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="inline-flex items-center gap-1 rounded-full border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 px-2 py-0.5 text-[9px] font-bold text-zinc-550 dark:text-zinc-400 cursor-pointer kullanici-durum transition-all shadow-xs" data-durum="1" data-kullanici-id="<?php echo $enc_id; ?>">
                                <span class="h-1 w-1 rounded-full bg-zinc-400"></span>
                                <span>Pasif</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Workplace scopes -->
                    <div class="flex flex-col text-left gap-1">
                        <span class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Yetkili Olduğu İşyerleri</span>
                        <div class="flex flex-col gap-1 text-[10px] font-semibold text-zinc-700 dark:text-zinc-350">
                            <?php
                            if (!empty($k->firma_adi)) {
                                $firmalar = explode(',', $k->firma_adi);
                                foreach ($firmalar as $f) {
                                    echo '<div class="flex items-center gap-1"><i data-lucide="building-2" style="width:10px;height:10px;" class="text-zinc-400"></i><span class="truncate">' . htmlspecialchars(trim($f)) . '</span></div>';
                                }
                            } else {
                                echo '<span class="text-zinc-400 dark:text-zinc-650 italic">Tanımlı işyeri yok</span>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Permissions chips & edit options -->
                    <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800/80 pt-3 mt-1">
                        
                        <!-- Chips -->
                        <div class="flex flex-wrap gap-1">
                            <?php if ($has_onay): ?>
                                <span class="inline-flex items-center rounded bg-blue-50/50 dark:bg-blue-950/20 px-1.5 py-0.5 text-[9px] font-bold text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-900/30">Rapor Onay</span>
                            <?php endif; ?>
                            <?php if ($has_manuel): ?>
                                <span class="inline-flex items-center rounded bg-teal-50/50 dark:bg-teal-950/20 px-1.5 py-0.5 text-[9px] font-bold text-teal-700 dark:text-teal-300 border border-teal-100 dark:border-teal-900/30">Manuel Bildirim</span>
                            <?php endif; ?>
                            <?php if (!$has_onay && !$has_manuel): ?>
                                <span class="text-[10px] text-zinc-400 italic">Yetki verilmemiş</span>
                            <?php endif; ?>
                        </div>

                        <!-- Card Actions -->
                        <div class="flex items-center gap-1.5">
                            <button type="button" data-kullanici-id="<?php echo $enc_id; ?>" class="kullanici-duzenle w-7 h-7 rounded-lg border border-zinc-250 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-650 dark:text-zinc-400 flex items-center justify-center cursor-pointer shadow-xs">
                                <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                            </button>
                            <button type="button" data-kullanici-id="<?php echo $enc_id; ?>" class="alt-kullanici-sil w-7 h-7 rounded-lg border border-rose-250 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-950/10 text-rose-650 flex items-center justify-center cursor-pointer shadow-xs">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;" class="text-rose-600"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-6 text-center border border-zinc-200 dark:border-zinc-800 rounded-2xl bg-white dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">Tanımlı alt kullanıcı bulunmamaktadır.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Yeni/Düzenle Sub-User Overlay Form -->
<div id="defaultModal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4 bg-zinc-950/45 backdrop-blur-sm animate-fade-in">
    <div class="relative w-full max-w-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl flex flex-col max-h-[85vh] overflow-y-auto">
        <form method="POST" id="altKullaniciForm" class="w-full">
            <input type="hidden" name="kullanici_id" id="kullanici_id" value="0">
            
            <div class="border-b border-zinc-100 dark:border-zinc-850 px-5 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="user-plus" class="w-4 h-4 text-zinc-900 dark:text-zinc-50"></i>
                    <h4 class="font-bold text-xs text-zinc-900 dark:text-zinc-50" id="defaultModalLabel">Alt Kullanıcı</h4>
                </div>
                <button type="button" onclick="$('#defaultModal').removeClass('flex').addClass('hidden');" class="text-zinc-400 dark:text-zinc-500 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-5 flex flex-col gap-3.5">
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="kullanici_adi">Kullanıcı Adı</label>
                    <input class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="kullanici_adi" name="kullanici_adi" required type="text" placeholder="Kullanıcı adı">
                </div>
                
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="adi_soyadi">Adı Soyadı</label>
                    <input class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="adi_soyadi" name="adi_soyadi" type="text" placeholder="Ad Soyad">
                </div>
                
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="email">E-posta</label>
                    <input class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="email" name="email" required type="email" placeholder="E-posta adresi">
                </div>
                
                <div class="flex flex-col gap-1 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="sifre">Giriş Şifresi</label>
                    <input class="h-9 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs font-semibold" id="sifre" name="sifre" autocomplete="new-password" type="password" placeholder="En az 6 karakter">
                </div>
                
                <div class="flex flex-col gap-1 text-left select2-container-modern select2-mobile">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" for="isyerleri_ids">Yetki Verilecek İşyerleri</label>
                    <?php echo IsyeriHelper::IsyeriSelect("isyerleri_ids[]") ?>
                </div>
                
                <div class="flex flex-col gap-2 text-left">
                    <label class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">İşlem Yetkileri</label>
                    <div class="flex flex-col gap-2 pt-1">
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            <input id="yetki_rapor_onay" type="checkbox" name="yetkiler[]" value="rapor_onay" class="w-4 h-4 rounded text-zinc-900 border-zinc-300 cursor-pointer">
                            <span>Rapor Onaylama Yetkisi</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            <input id="yetki_manuel_bildirim" type="checkbox" name="yetkiler[]" value="manuel_bildirim" class="w-4 h-4 rounded text-zinc-900 border-zinc-300 cursor-pointer">
                            <span>Manuel Rapor Bildirimi Yetkisi</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-850 px-5 py-3.5 flex items-center justify-end gap-2">
                <button type="button" onclick="$('#defaultModal').removeClass('flex').addClass('hidden');" class="h-9 px-4 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl text-xs font-bold shadow-sm cursor-pointer">Kapat</button>
                <button type="button" class="h-9 px-4 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-850 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-xl text-xs font-bold shadow alt-kullanici-kaydet cursor-pointer">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    // Polyfill JQuery Modal actions
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

        // Initialize Select2 in sub-user form context specifically for mobile shell styling
        App.initGlobalSelect2($('#defaultModal'));
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
</script>
<script src="App/Src/kullanici.js?v=<?php echo time(); ?>"></script>
