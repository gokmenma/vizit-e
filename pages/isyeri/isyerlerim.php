<?php $title = 'İşyerlerim'; ?>

<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>

<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>

<?php

use App\Helper\Security;
use Models\KullaniciIsyeriModel;
use Models\KullaniciAbonelikModel;


Security::checkLogin();

$İsyeriModel = new KullaniciIsyeriModel();
$KulllaniciAbonelik = new KullaniciAbonelikModel();

$kullaniciId = $_SESSION['kullanici_id'];
$user = ($_SESSION["user"]);

if($userRole == "user"){
    $kullaniciId = $_SESSION['user']->admin_id;
}

// Kullanıcının aktif aboneliğini al
$firma_hakki = $KulllaniciAbonelik->getSubscriptionByUserId($kullaniciId)->firma_hakki ?? 0;

//Kullanilan firma hakkı
$kullanilan_firma_hakki = $İsyeriModel->countFirmByUserId($kullaniciId) ?? 0;
//Progres yüzdesi
if ($firma_hakki == 0) {
    $progress = 0; 
    $kalan_firma_hakki = 0;
} else {
    $kullanilan_firma_hakki = min($kullanilan_firma_hakki, $firma_hakki);
    $progress = ($kullanilan_firma_hakki / $firma_hakki) * 100;
    $kalan_firma_hakki = $firma_hakki - $kullanilan_firma_hakki;
}

// Kullanıcının işyerlerini al
if($userRole == "user"){
    $isyeri_ids = $user->yetkili_oldugu_isyeri_ids;
    $isyerleri = $İsyeriModel->AltKullaniciİsyerleri($isyeri_ids);
}else{
    $isyerleri = $İsyeriModel->whereRaw('kullanici_id = ? AND aktif_mi = ?', [$kullaniciId, 1]);
}
$selected_firma_id = $_SESSION['isyeri_id'] ?? null;
$hataMesaji = $_SESSION['hata'] ?? '';
?>

<style>
/* Premium Modals, Inputs & Checkboxes Styling */
.d-none {
    display: none !important;
}
.modal-backdrop {
    background-color: rgba(9, 9, 11, 0.45) !important;
    backdrop-filter: blur(4px) !important;
    -webkit-backdrop-filter: blur(4px) !important;
}
.modal-backdrop.show {
    opacity: 1 !important;
}
.modal {
    background-color: rgba(9, 9, 11, 0.45) !important;
    backdrop-filter: blur(4px) !important;
    -webkit-backdrop-filter: blur(4px) !important;
}
.modal.fade {
    transition: opacity 0.15s linear;
}
.modal.show {
    display: flex !important;
    align-items: center;
    justify-content: center;
}
.modal input:not([type="checkbox"]):focus, 
.modal textarea:focus {
    border-color: #18181b !important;
    outline: none !important;
    box-shadow: 0 0 0 2px rgba(24, 24, 27, 0.08) !important;
}
.dark .modal input:not([type="checkbox"]):focus, 
.dark .modal textarea:focus {
    border-color: #f4f4f5 !important;
    box-shadow: 0 0 0 2px rgba(244, 244, 245, 0.08) !important;
}

/* Custom Checkbox Design matching Shadcn styling */
.modal input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border: 1px solid #d4d4d8;
    border-radius: 4px;
    background-color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.dark .modal input[type="checkbox"] {
    border-color: #3f3f46;
    background-color: #09090b;
}
.modal input[type="checkbox"]:checked {
    background-color: #18181b;
    border-color: #18181b;
}
.dark .modal input[type="checkbox"]:checked {
    background-color: #f4f4f5;
    border-color: #f4f4f5;
}
.modal input[type="checkbox"]:checked::after {
    content: "";
    width: 4px;
    height: 8px;
    border: solid #ffffff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    position: absolute;
    top: 2px;
}
.dark .modal input[type="checkbox"]:checked::after {
    border-color: #09090b;
}

/* Premium HTML5 Delete Confirmation Dialog Styling */
#alert-dialog {
    border: 1px solid #e4e4e7;
    background: #ffffff;
    border-radius: 12px;
    padding: 1.5rem;
    max-width: 400px;
    width: calc(100% - 2rem);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    position: fixed;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    margin: 0 !important;
    right: auto !important;
    bottom: auto !important;
    z-index: 99999 !important;
}

#alert-dialog[open] {
    display: flex !important;
    flex-direction: column;
}

.dark #alert-dialog {
    background: #09090b;
    border-color: #27272a;
    color: #f4f4f5;
}

#alert-dialog::backdrop {
    background-color: rgba(9, 9, 11, 0.45) !important;
    backdrop-filter: blur(4px) !important;
    -webkit-backdrop-filter: blur(4px) !important;
}

#alert-dialog div {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

#alert-dialog header {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
}

#alert-dialog header h2 {
    font-size: 1.125rem;
    font-weight: 700;
    color: #09090b;
    margin: 0;
    text-align: left;
}

.dark #alert-dialog header h2 {
    color: #f4f4f5;
}

#alert-dialog header p {
    font-size: 0.875rem;
    color: #71717a;
    margin: 0;
    line-height: 1.5;
    text-align: left;
}

.dark #alert-dialog header p {
    color: #a1a1aa;
}

#alert-dialog footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    background: transparent !important;
    padding: 0 !important;
    border: none !important;
}

#alert-dialog .btn-outline {
    height: 2.25rem;
    padding: 0 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s;
    cursor: pointer;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    color: #09090b;
}

#alert-dialog .btn-outline:hover {
    background: #f4f4f5;
}

.dark #alert-dialog .btn-outline {
    background: transparent;
    border-color: #27272a;
    color: #f4f4f5;
}

.dark #alert-dialog .btn-outline:hover {
    background: #27272a;
}

#alert-dialog .btn-primary {
    height: 2.25rem;
    padding: 0 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s;
    cursor: pointer;
    background: #ef4444 !important; /* Destructive red */
    border: 1px solid #ef4444 !important;
    color: #ffffff !important;
}

#alert-dialog .btn-primary:hover {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
}

.dark #alert-dialog .btn-primary {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
}

.dark #alert-dialog .btn-primary:hover {
    background: #b91c1c !important;
    border-color: #b91c1c !important;
}
</style>

<div class="flex flex-col gap-6 w-full py-2 px-1">
    <!-- Header Bölümü -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">İşyerlerim</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                İşyerlerinizden birini seçerek o işyerine ait raporlarda ve vizite işlemlerinde işlem yürütebilirsiniz.
            </p>
        </div>
        
        <div class="flex items-center gap-2 text-nowrap self-start md:self-auto flex-shrink-0">
            <!-- Firma ekleme hakkı hala varsa -->
            <?php if ($kullanilan_firma_hakki < $firma_hakki && ($userRole == "admin" || $userRole == "superadmin")): ?>
                <a href="excelden-yukle" class="inline-flex items-center gap-1.5 h-9 px-3 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md text-xs font-semibold shadow-sm transition-all">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    <span>Excel'den Yükle</span>
                </a>
                <button type="button" data-bs-toggle="modal" data-bs-target="#defaultModal" class="h-9 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold transition-all flex items-center gap-1.5 shadow cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Yeni Ekle</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aktivasyon Limiti Göstergesi -->
    <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
        <?php if ($firma_hakki == 0) : ?>
            <div class="border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-300 rounded-xl p-4 flex gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm">Abonelik Uyarısı!</h4>
                    <p class="text-xs mt-1 leading-relaxed opacity-95">
                        Aktif bir firma hakkınız bulunmamaktadır. SGK işlemlerini yürütmek için lütfen <a href="abonelik-paketleri" class="underline font-semibold">aktif bir abonelik paketi</a> satın alınız.
                    </p>
                </div>
            </div>
        <?php else : ?>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Firma Aktivasyon Limiti</span>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 block mt-1">Kullanılan: <?php echo $kullanilan_firma_hakki; ?> / <?php echo $firma_hakki; ?></span>
                    </div>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-2.5 py-1 rounded-full"><?php echo round($progress); ?>% Dolu</span>
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Hata Mesajları -->
    <?php if (!empty($hataMesaji && $firma_hakki > 0)): ?>
        <div class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3 shadow-sm">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Hata</h4>
                <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tablo Alanı -->
    <div class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
        <table class="w-full border-collapse text-left" id="isyerleri-table">
            <thead>
                <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                    <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-[60px] text-center">Sıra</th>
                    <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Firma Bilgileri</th>
                    <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-center w-[120px]">Otomatik Onay</th>
                    <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Otomatik Onay E-posta</th>
                    <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-right pr-6 w-[240px]">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <?php if (!empty($isyerleri)): ?>
                    <?php
                    $i = 0;
                    foreach ($isyerleri as $isyeri) {
                        $i++;
                        $enc_id = Security::encrypt($isyeri->id);
                        if ((int)$isyeri->id === (int)$selected_firma_id) {
                            $selected = 'Seçili';
                            $selected_btn = 'bg-emerald-600 dark:bg-emerald-500 text-white cursor-default opacity-90';
                            $is_active_firm = true;
                        } else {
                            $selected = 'Seç';
                            $selected_btn = 'bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 cursor-pointer shadow';
                            $is_active_firm = false;
                        }
                    ?>
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="py-3.5 px-4 text-sm text-center font-medium text-zinc-500 dark:text-zinc-400"><?php echo $i; ?></td>
                            <td class="py-3.5 px-4 text-sm">
                                <div class="flex flex-col">
                                    <span class="isyeri-duzenle font-semibold text-zinc-900 dark:text-zinc-100 leading-tight cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors flex items-center gap-1.5" data-id="<?php echo $enc_id; ?>">
                                        <span><?php echo htmlspecialchars($isyeri->firma_adi); ?></span>
                                        <?php if ($isyeri->varsayilan_mi == 1): ?>
                                            <span class="inline-flex items-center gap-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 px-1.5 py-0.5 text-[10px] font-semibold border border-zinc-200 dark:border-zinc-700">
                                                <i data-lucide="star" class="w-3 h-3 text-amber-500 fill-amber-500"></i>
                                                <span>Varsayılan</span>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">İşyeri Kodu: <?php echo htmlspecialchars($isyeri->isyeri_kodu); ?></span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-sm text-center">
                                <?php if ($isyeri->otomatik_rapor_onay == "1"): ?>
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 dark:border-emerald-900/30 bg-emerald-50 dark:bg-emerald-950/20 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Açık</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full border border-red-200 dark:border-red-900/30 bg-red-50 dark:bg-red-950/20 px-2 py-0.5 text-xs font-semibold text-red-700 dark:text-red-300">Kapalı</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-sm text-zinc-650 dark:text-zinc-350 font-medium">
                                <?php
                                if (!empty($isyeri->otomatik_onay_eposta)) {
                                    $eposta_adresleri = explode(',', $isyeri->otomatik_onay_eposta);
                                    $ilk_eposta = htmlspecialchars(trim($eposta_adresleri[0]));
                                    if (count($eposta_adresleri) > 1) {
                                        $tum_epostalar = htmlspecialchars(implode("\n", array_map('trim', $eposta_adresleri)));
                                        echo '<span class="cursor-help border-b border-dashed border-zinc-400 dark:border-zinc-600 pb-0.5" data-tooltip="' . $tum_epostalar . '">' . $ilk_eposta . ' <span class="text-zinc-400 dark:text-zinc-600">...</span></span>';
                                    } else {
                                        echo $ilk_eposta;
                                    }
                                } else {
                                    echo '<span class="text-zinc-400 dark:text-zinc-600">-</span>';
                                }
                                ?>
                            </td>
                            <td class="py-3.5 px-4 text-sm pr-6">
                                <div class="flex items-center justify-end gap-2">
                                    <form action="<?php echo ($firma_hakki > 0) ? 'isyeri-sec' : '#'; ?>" method="POST" class="inline-flex isyeri-sec-form" data-bypass>
                                        <input type="hidden" name="isyeri_id" value="<?php echo $isyeri->id; ?>">
                                        <input type="hidden" name="previous_page" value="<?php echo $_SERVER['HTTP_REFERER'] ?? ''; ?>">
                                        <button type="submit" class="h-8 px-3 rounded-md text-xs font-semibold transition-all flex items-center gap-1 <?php echo $selected_btn; ?>" <?php if ($firma_hakki == 0 || $is_active_firm) { echo 'disabled'; } ?>>
                                            <?php if ($is_active_firm): ?><i data-lucide="check" class="w-3.5 h-3.5"></i><?php endif; ?>
                                            <span><?php echo $selected; ?></span>
                                        </button>
                                    </form>

                                    <?php if($userRole == "admin" || $userRole == "superadmin"): ?>
                                        <button type="button" data-id="<?php echo $enc_id; ?>" class="isyeri-duzenle w-8 h-8 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Düzenle">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" data-isyeri-id="<?php echo Security::encrypt($isyeri->id); ?>" class="isyeri-sil w-8 h-8 rounded-md border border-red-200 dark:border-red-900/30 bg-red-50/50 dark:bg-red-950/10 text-red-650 hover:bg-red-50 dark:hover:bg-red-950/20 hover:text-red-700 dark:hover:text-red-400 flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Kaldır">
                                            <i data-lucide="trash-2" class="w-4 h-4 text-rose-600"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-10 text-zinc-400 dark:text-zinc-500 font-medium">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="inbox" class="w-8 h-8 opacity-40"></i>
                                <span>İşyeriniz bulunmamaktadır. Lütfen yeni işyeri ekleyin.</span>
                                <?php if(($userRole == "admin" || $userRole == "superadmin") && $kalan_firma_hakki > 0): ?>
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#defaultModal" class="mt-2 h-8 px-3 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-md text-xs font-semibold shadow cursor-pointer">Yeni Ekle</button>
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
<div class="modal fixed inset-0 z-50 hidden items-center justify-center p-4 bg-zinc-950/45 backdrop-blur-sm animate-fade-in" id="defaultModal" tabindex="-1" role="dialog">
    <div class="relative w-full max-w-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl flex flex-col max-h-[90vh] overflow-y-auto" role="document">
        <form method="POST" id="isyeri-form">
            <input type="hidden" class="hidden" name="isyeri_id" id="isyeri_id" value="0">
            <div class="modal-header border-b border-zinc-100 dark:border-zinc-850 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-5 h-5 text-zinc-900 dark:text-zinc-50"></i>
                    <h4 class="title font-bold text-sm text-zinc-900 dark:text-zinc-50" id="defaultModalLabel">Sgk İşyeri Bilgileri</h4>
                </div>
                <button type="button" class="text-zinc-400 dark:text-zinc-500 hover:text-zinc-650 dark:hover:text-zinc-350 cursor-pointer" data-bs-dismiss="modal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="modal-body p-6 flex flex-col gap-4">
                <div class="border border-blue-200 bg-blue-50/50 text-blue-800 dark:border-blue-900/30 dark:bg-blue-950/20 dark:text-blue-300 rounded-lg p-3 text-xs leading-relaxed font-medium">
                    <strong>Uçtan Uca Şifreleme!</strong> Şifreleriniz veri tabanında güçlü algoritmalarla şifrelenmiş olup sizden başka kimse erişemez.
                </div>
                <input id="csrf_token" name="csrf_token" type="hidden" class="hidden" value="">
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="firma_adi">Firma Unvanı (Hatırlatıcı Ad)</label>
                    <div class="relative">
                        <i data-lucide="building-2" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400" id="firma_adi" name="firma_adi" placeholder="Örn: Hastane İşçiler" required="" type="text">
                    </div>
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="kullanici_adi">SGK Kullanıcı Adı (TC Kimlik No)</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input type="password" class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400" id="kullanici_adi" name="kullanici_adi" required maxlength="11" pattern="\d{1,11}" placeholder="TC Kimlik numarası">
                    </div>
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="isyeri_kodu">SGK İşyeri Kodu</label>
                    <div class="relative">
                        <i data-lucide="hash" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400" id="isyeri_kodu" name="isyeri_kodu" required="" type="number" placeholder="-'den sonraki kod Örn: 2" maxlength="4" autocomplete="off">
                    </div>
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="ws_sifre">SGK İşyeri Şifresi</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                        <input class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 pl-9 pr-3 py-1 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 placeholder-zinc-400" id="ws_sifre" name="ws_sifre" required="" placeholder="İşyeri şifresi" autocomplete="new-password" type="password">
                    </div>
                </div>
                
                <label class="label gap-3 cursor-pointer select-none py-1 text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center" title="İşaretlenirse, sisteme giriş yaptığınızda bu işyeri otomatik olarak seçilir.">
                    <input id="varsayilan_mi" type="checkbox" name="varsayilan_mi" >
                    <span>Varsayılan İşyeri Yap</span>
                </label>
                
                <label class="label gap-3 cursor-pointer select-none py-1 text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center" title="İşaretlenirse, hafta içi her gün saat 16:00'da raporlar otomatik olarak onaylanır ve e-postayla bildirim gönderilir.">
                    <input id="otomatik_rapor_onay" type="checkbox" name="otomatik_rapor_onay" >
                    <span>Otomatik Rapor Onaylama</span>
                </label>

                <div class="otomatik-onay-eposta d-none flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-zinc-900 dark:text-zinc-100" for="otomatik_onay_eposta">Bildirim E-posta Adresleri</label>
                    <textarea class="w-full rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 py-2.5 text-sm transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300 min-h-[70px] text-left placeholder-zinc-400" id="otomatik_onay_eposta" name="otomatik_onay_eposta" placeholder="Birden fazla ise aralarına virgül koyun"></textarea>
                </div>
            </div>
            <div class="modal-footer border-t border-zinc-100 dark:border-zinc-850 px-6 py-4 flex items-center justify-end gap-2.5">
                <button type="button" class="h-10 px-6 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg text-xs font-bold shadow-sm transition-all flex items-center justify-center cursor-pointer" data-bs-dismiss="modal">KAPAT</button>
                <button type="button" class="h-10 px-6 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-850 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-950 rounded-lg text-xs font-bold shadow transition-all flex items-center justify-center cursor-pointer isyeri-kaydet text-nowrap" data-loading-text="Kaydediliyor...">KAYDET</button>
            </div>
        </form>
    </div>
</div>

<!-- Deletion Confirmation Dialog -->
<dialog id="alert-dialog" aria-labelledby="alert-dialog-title" aria-describedby="alert-dialog-description">
  <div>
    <header>
      <h2 id="alert-dialog-title">Emin misiniz?</h2>
      <p id="alert-dialog-description">Bu işlem geri alınamaz. Seçili firma kalıcı olarak silinecektir.</p>
    </header>

    <footer>
      <button type="button" class="btn-outline alert-dialog-cancel">İptal</button>
      <button type="button" class="btn-primary alert-dialog-confirm">Evet, Sil</button>
    </footer>
  </div>
</dialog>

<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- sayfanın js kodu -->
<script src="App/Src/isyerlerim.js?v=<?php echo filemtime('App/Src/isyerlerim.js'); ?>"></script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>