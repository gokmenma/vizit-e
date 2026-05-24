<?php

use App\Helper\Security;
use App\Helper\IsyeriHelper;
use Models\UserModel;


Security::checkLogin();

$title = 'Kullanıcılar';


$kullanici = new UserModel();

$kullanicilar = $kullanici->AltKullanicilar($_SESSION['kullanici_id']);
$altKullaniciSayisi = count($kullanicilar);
$altKullaniciLimiti = $kullanici->getAltKullaniciLimiti($_SESSION['kullanici_id']);

$hataMesaji = $_SESSION['hata'] ?? '';


?>


<div class="flex flex-col gap-6 w-full py-2 px-1">
    <!-- Header Bölümü -->
    <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Kullanıcılar</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Aboneliğiniz için alt kullanıcılar ekleyebilirsiniz. Kullanıcıların yetkilendirildikleri alanlar
                dışında
                işlem yapma yetkileri yoktur.
            </p>
            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                En fazla <?php echo $altKullaniciLimiti; ?> alt kullanıcı ekleyebilirsiniz. (Eklenen:
                <?php echo $altKullaniciSayisi; ?> / <?php echo $altKullaniciLimiti; ?>)
            </p>
        </div>

        <div class="flex items-center gap-2 text-nowrap self-start md:self-auto flex-shrink-0">
            <?php if ($altKullaniciSayisi < $altKullaniciLimiti): ?>
            <button type="button" data-bs-toggle="modal" data-bs-target="#defaultModal"
                class="h-9 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 shadow cursor-pointer">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Yeni Kullanıcı Ekle</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hata Mesajları -->
    <?php if (!empty($hataMesaji)): ?>
    <div
        class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3 shadow-sm">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
        <div>
            <h4 class="font-bold text-sm">Hata</h4>
            <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tablo Alanı -->
    <div
        class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full border-collapse text-left" id="kullanicilar-table">
            <thead>
                <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                    <th
                        class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[60px] text-center">
                        Sıra</th>
                    <th
                        class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        Kullanıcı Bilgileri</th>
                    <th
                        class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        Kullanıcı Adı</th>
                    <th
                        class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        Yetkili Olduğu İşyerleri</th>
                    <th
                        class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        İşlem Yetkileri</th>
                    <th
                        class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center w-[100px]">
                        Durum</th>
                    <th
                        class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-right pr-6 w-[120px]">
                        İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <?php if (!empty($kullanicilar)): ?>
                <?php
                    $i = 0;
                    foreach ($kullanicilar as $kullanici) {
                        $i++;
                        $enc_id = Security::encrypt($kullanici->id);
                        
                        // Başharfleri al
                        $isim_parcalari = explode(' ', $kullanici->adi_soyadi);
                        $basharfler = '';
                        if (count($isim_parcalari) >= 2) {
                            $basharfler = mb_substr($isim_parcalari[0], 0, 1, 'UTF-8') . mb_substr($isim_parcalari[count($isim_parcalari)-1], 0, 1, 'UTF-8');
                        } else if (count($isim_parcalari) == 1) {
                            $basharfler = mb_substr($isim_parcalari[0], 0, 2, 'UTF-8');
                        }
                        $basharfler = mb_strtoupper($basharfler, 'UTF-8');
                    ?>
                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="py-3.5 px-4 text-sm text-center font-medium text-zinc-500 dark:text-zinc-400">
                        <?php echo $i; ?></td>
                    <td class="py-3.5 px-4 text-sm">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-650 dark:text-zinc-350 border border-zinc-200 dark:border-zinc-700/60 uppercase">
                                <?php echo $basharfler; ?>
                            </div>
                            <div class="flex flex-col">
                                <span
                                    class="font-semibold text-zinc-900 dark:text-zinc-100 leading-tight"><?php echo htmlspecialchars($kullanici->adi_soyadi); ?></span>
                                <span
                                    class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?php echo htmlspecialchars($kullanici->email); ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5 px-4 text-sm font-medium text-zinc-700 dark:text-zinc-300 font-mono">
                        @<?php echo htmlspecialchars($kullanici->kullanici_adi); ?>
                    </td>
                    <td class="py-3.5 px-4 text-sm">
                        <div class="flex flex-col gap-1 text-xs">
                            <?php
                                    if (!empty($kullanici->firma_adi)) {
                                        $firmalar = explode(',', $kullanici->firma_adi);
                                        foreach ($firmalar as $f) {
                                            echo '<span class="font-medium text-zinc-600 dark:text-zinc-400 leading-tight">🏢 ' . htmlspecialchars(trim($f)) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-zinc-400 dark:text-zinc-600 italic">Tanımlı işyeri yok</span>';
                                    }
                                    ?>
                        </div>
                    </td>
                    <td class="py-3.5 px-4 text-sm">
                        <div class="flex flex-wrap gap-1">
                            <?php 
                                    $yetkiler = $kullanici->yetkiler ?? '';
                                    $has_onay = (strpos($yetkiler, 'rapor_onay') !== false);
                                    $has_manuel = (strpos($yetkiler, 'manuel_bildirim') !== false);
                                    ?>
                            <?php if ($has_onay): ?>
                            <span
                                class="inline-flex items-center rounded-full border border-blue-200 dark:border-blue-900/30 bg-blue-50/50 dark:bg-blue-950/20 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:text-blue-300 shadow-sm">Rapor
                                Onay</span>
                            <?php endif; ?>
                            <?php if ($has_manuel): ?>
                            <span
                                class="inline-flex items-center rounded-full border border-teal-200 dark:border-teal-900/30 bg-teal-50/50 dark:bg-teal-950/20 px-2 py-0.5 text-[10px] font-semibold text-teal-700 dark:text-teal-300 shadow-sm">Manuel
                                Bildirim</span>
                            <?php endif; ?>
                            <?php if (!$has_onay && !$has_manuel): ?>
                            <span class="text-zinc-400 dark:text-zinc-600 font-medium">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="py-3.5 px-4 text-sm text-center">
                        <?php if ($kullanici->durum == "Aktif"): ?>
                        <button type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 dark:border-emerald-950/30 bg-emerald-50 dark:bg-emerald-950/15 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 cursor-pointer kullanici-durum shadow-sm transition-all"
                            data-durum="0" data-kullanici-id="<?php echo $enc_id; ?>">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Aktif</span>
                        </button>
                        <?php else: ?>
                        <button type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 px-2.5 py-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400 cursor-pointer kullanici-durum shadow-sm transition-all"
                            data-durum="1" data-kullanici-id="<?php echo $enc_id; ?>">
                            <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                            <span>Pasif</span>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td class="py-3.5 px-4 text-sm pr-6">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" data-kullanici-id="<?php echo $enc_id; ?>"
                                data-yetkiler="<?php echo htmlspecialchars($kullanici->yetkiler ?? ''); ?>"
                                class="kullanici-duzenle w-8 h-8 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center transition-colors shadow-sm cursor-pointer"
                                title="Düzenle">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button type="button" data-kullanici-id="<?php echo $enc_id; ?>"
                                class="alt-kullanici-sil w-8 h-8 rounded-md border border-red-200 dark:border-red-900/30 bg-red-50/50 dark:bg-red-950/10 text-red-650 hover:bg-red-50 dark:hover:bg-red-950/20 hover:text-red-700 dark:hover:text-red-400 flex items-center justify-center transition-colors shadow-sm cursor-pointer"
                                title="Sil">
                                <i data-lucide="trash-2" class="w-4 h-4 text-rose-600"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php } ?>
                <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i data-lucide="users" class="w-8 h-8 opacity-40"></i>
                            <span>Alt kullanıcı bulunmamaktadır. Lütfen yeni kullanıcı ekleyin.</span>
                            <?php if ($altKullaniciSayisi < $altKullaniciLimiti): ?>
                            <button type="button" data-bs-toggle="modal" data-bs-target="#defaultModal"
                                class="mt-2 h-8 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold shadow cursor-pointer">Yeni
                                Ekle</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Dialogs ========= -->
<!-- Default Size -->
<div class="modal fixed inset-0 z-50 hidden items-center justify-center p-4 bg-zinc-950/45 backdrop-blur-sm animate-fade-in"
    id="defaultModal" tabindex="-1" role="dialog">
    <div class="relative w-full max-w-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl flex flex-col max-h-[90vh] overflow-y-auto"
        role="document">
        <form method="POST" id="altKullaniciForm">
            <input type="hidden" class="hidden" name="kullanici_id" id="kullanici_id" value="0">
            <div
                class="modal-header border-b border-zinc-100 dark:border-zinc-850 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-5 h-5 text-zinc-900 dark:text-zinc-50"></i>
                    <h4 class="title font-bold text-sm text-zinc-900 dark:text-zinc-50" id="defaultModalLabel">Alt
                        Kullanıcı Bilgileri</h4>
                </div>
                <button type="button"
                    class="text-zinc-400 dark:text-zinc-500 hover:text-zinc-650 dark:hover:text-zinc-350 cursor-pointer"
                    data-bs-dismiss="modal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="modal-body p-6 flex flex-col gap-4">
                <input id="csrf_token" name="csrf_token" type="hidden" class="hidden" value="">

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="kullanici_adi">Kullanıcı
                        Adı</label>
                    <div class="relative">
                        <i data-lucide="at-sign"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input
                            class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400"
                            id="kullanici_adi" name="kullanici_adi" required="" type="text"
                            placeholder="Kullanıcı adı girin">
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="adi_soyadi">Adı
                        Soyadı</label>
                    <div class="relative">
                        <i data-lucide="user"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input
                            class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400"
                            id="adi_soyadi" name="adi_soyadi" type="text" placeholder="Ad ve soyad girin">
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="email">Email adresi</label>
                    <div class="relative">
                        <i data-lucide="mail"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input
                            class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400"
                            id="email" name="email" required="" type="email" autocomplete="off"
                            placeholder="E-posta adresi girin">
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="sifre">Giriş Şifresi</label>
                    <div class="relative">
                        <i data-lucide="lock"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input
                            class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400"
                            id="sifre" name="sifre" required="" autocomplete="new-password" type="password"
                            placeholder="En az 6 karakter girin">
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 select2-container-modern">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="isyerleri_ids">Yetki
                        Verilecek İşyerleri</label>
                    <?php echo IsyeriHelper::IsyeriSelect("isyerleri_ids[]") ?>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100">İşlem Yetkileri</label>
                    <div class="flex flex-col gap-2 pt-1">
                        <label class="label gap-3 cursor-pointer select-none text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center">
                            <input id="yetki_rapor_onay" type="checkbox" name="yetkiler[]" value="rapor_onay"
                                class="input w-4 h-4 rounded text-zinc-900 border-zinc-300 focus:ring-zinc-550 cursor-pointer">
                            <span>Rapor Onaylama/Kapatma Yetkisi</span>
                        </label>
                        <label class="label gap-3 cursor-pointer select-none text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center">
                            <input id="yetki_manuel_bildirim" type="checkbox" name="yetkiler[]" value="manuel_bildirim"
                                class="input w-4 h-4 rounded text-zinc-900 border-zinc-300 focus:ring-zinc-550 cursor-pointer">
                            <span>Manuel Rapor Bildirimi Yetkisi</span>
                        </label>
                    </div>
                </div>
            </div>
            <div
                class="modal-footer border-t border-zinc-100 dark:border-zinc-850 px-6 py-4 flex items-center justify-end gap-2.5">
                <button type="button"
                    class="h-10 px-6 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg text-xs font-bold shadow-sm transition-all flex items-center justify-center cursor-pointer"
                    data-bs-dismiss="modal">KAPAT</button>
                <button type="button" data-loading="true" data-loading-text="Kaydediliyor..."
                    class="h-10 px-6 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-850 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-lg text-xs font-bold shadow transition-all flex items-center justify-center cursor-pointer alt-kullanici-kaydet text-nowrap">KAYDET</button>
            </div>
        </form>
    </div>
</div>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- sayfanın js kodu -->
<script src="App/Src/kullanici.js?v=<?php echo filemtime('App/Src/kullanici.js'); ?>"></script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>