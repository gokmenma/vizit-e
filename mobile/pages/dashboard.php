<?php
require_once __DIR__ . '/../../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Helper\Security;
use App\Helper\Date;
use Models\KullaniciAbonelikModel;

Security::checkLogin();
Security::checkFirma();

$KullaniciAbonelikModel = new KullaniciAbonelikModel();
$aktif_abonelik = $KullaniciAbonelikModel->getSubscriptionByUserId($_SESSION['kullanici_id']);
$abonelik_bitis_tarihi = $aktif_abonelik->bitis_tarihi ?? null;

// Database Connection & Workplace Specific Statistics
require_once __DIR__ . '/../../Core/Database.php';
$db = \Core\Database::getInstance()->getConnection();
$active_isyeri_id = $_SESSION['isyeri_id'] ?? 0;

// Stat 1: Toplam Onaylı Rapor
$stmt = $db->prepare("SELECT COUNT(*) as total FROM onaylanan_raporlar WHERE isyeri_id = ?");
$stmt->execute([$active_isyeri_id]);
$totalReports = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;

// Stat 2: Bu Ayki Raporlar
$dateExpr = "COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at)";
$stmt = $db->prepare("SELECT COUNT(*) as total FROM onaylanan_raporlar WHERE isyeri_id = ? AND MONTH($dateExpr) = MONTH(CURRENT_DATE()) AND YEAR($dateExpr) = YEAR(CURRENT_DATE())");
$stmt->execute([$active_isyeri_id]);
$monthlyReports = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;

// Stat 3: Son 7 Günlük Rapor
$stmt = $db->prepare("SELECT COUNT(*) as total FROM onaylanan_raporlar WHERE isyeri_id = ? AND $dateExpr >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)");
$stmt->execute([$active_isyeri_id]);
$weeklyReports = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;

// Stat 4: Ortalama Rapor Süresi (Gün)
$stmt = $db->prepare("SELECT AVG(rapor_gun_sayisi) as avg_days FROM onaylanan_raporlar WHERE isyeri_id = ?");
$stmt->execute([$active_isyeri_id]);
$avgDays = $stmt->fetch(PDO::FETCH_OBJ)->avg_days;
$avgReportDays = $avgDays ? round((float)$avgDays, 1) : 0;

// Fetch Last 5 Approved Reports
$stmt = $db->prepare("SELECT * FROM onaylanan_raporlar WHERE isyeri_id = ? ORDER BY COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at) DESC LIMIT 5");
$stmt->execute([$active_isyeri_id]);
$recentApproved = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch Last 5 Sick Employees
$stmt = $db->prepare("SELECT SIGORTALIADSOYAD, TCKIMLIKNO, VAKAADI, ABASTAR, ABITTAR, POLIKLINIKTAR, ISBASKONTTAR, YATRAPBITTAR, rapor_gun_sayisi FROM onaylanan_raporlar WHERE isyeri_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$active_isyeri_id]);
$recentSick = $stmt->fetchAll(PDO::FETCH_OBJ);

$quickActions = [
    [
        'title' => 'Tarihe Göre Rapor Ara',
        'icon' => 'calendar-search',
        'link' => '#tarihe-gore-rapor-ara',
        'icon_bg' => 'rgba(245, 158, 11, 0.08)',
        'icon_color' => 'text-amber-500'
    ],
    [
        'title' => 'Onaylanmış Raporlar',
        'icon' => 'check-circle-2',
        'link' => '#onayli-raporlar',
        'icon_bg' => 'rgba(16, 185, 129, 0.08)',
        'icon_color' => 'text-emerald-500'
    ],
    [
        'title' => 'Manuel Bildirim',
        'icon' => 'edit-3',
        'link' => '#manuel-rapor-bildirimi',
        'icon_bg' => 'rgba(99, 102, 241, 0.08)',
        'icon_color' => 'text-indigo-500'
    ],
    [
        'title' => 'İş Kazası Bildirimi',
        'icon' => 'shield-alert',
        'link' => '#is-kazasi-bildirimi',
        'icon_bg' => 'rgba(244, 63, 94, 0.08)',
        'icon_color' => 'text-rose-500'
    ]
];
?>

<div class="animate-in flex flex-col gap-4">
    
    <!-- Welcome Card -->
    <div class="p-4 bg-zinc-900 dark:bg-zinc-800 text-white rounded-2xl shadow-sm">
        <h2 class="text-base font-bold">Hoş Geldiniz</h2>
        <p class="text-xs text-zinc-300 mt-1 leading-normal" style="color: rgba(255,255,255,0.75);">
            <strong><?php echo htmlspecialchars($_SESSION['firma_adi'] ?? 'Firma Seçilmedi'); ?></strong><br>
            <span class="text-[10px] font-mono opacity-80" style="font-size: 10px;">Kod: <?php echo htmlspecialchars($_SESSION['isyeri_kodu'] ?? $_SESSION['isyeriKodu'] ?? ''); ?></span>
        </p>
    </div>

    <!-- Subscription Status Badge -->
    <div class="p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $abonelik_bitis_tarihi ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600' : 'bg-rose-50 dark:bg-rose-950/20 text-rose-600'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <?php if ($abonelik_bitis_tarihi): ?>
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="m9 12 2 2 4-4"></path>
                    <?php else: ?>
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    <?php endif; ?>
                </svg>
            </div>
            <div class="flex flex-col text-left">
                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100">Abonelik Durumu</span>
                <span class="text-[10px] text-zinc-500 mt-0.5">
                    <?php if ($abonelik_bitis_tarihi): ?>
                    Aktif (Bitiş: <?php echo date('d.m.Y', strtotime($aktif_abonelik->bitis_tarihi)); ?>)
                    <?php else: ?>
                    <span class="text-rose-500 font-bold">Aktif abonelik bulunmamaktadır!</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <a href="#abonelik-paketleri" class="text-xs font-bold text-primary flex items-center gap-1">
            Paketler
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </a>
    </div>

    <!-- Quick Stats Cards (Grid 2x2) -->
    <div class="grid grid-cols-2 gap-3">
        <div class="p-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Toplam Rapor</span>
            <div class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mt-1"><?php echo number_format($totalReports); ?></div>
            <p class="text-[9px] text-zinc-500 mt-1">Onaylanan tüm viziteler</p>
        </div>
        <div class="p-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Cari Ay Raporu</span>
            <div class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mt-1"><?php echo number_format($monthlyReports); ?></div>
            <p class="text-[9px] text-zinc-500 mt-1">Bu ay eklenen viziteler</p>
        </div>
        <div class="p-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Haftalık Rapor</span>
            <div class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mt-1"><?php echo number_format($weeklyReports); ?></div>
            <p class="text-[9px] text-zinc-500 mt-1">Son 7 günde onaylananlar</p>
        </div>
        <div class="p-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Ort. İstirahat</span>
            <div class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mt-1"><?php echo $avgReportDays; ?> Gün</div>
            <p class="text-[9px] text-zinc-500 mt-1">Ortalama rapor süresi</p>
        </div>
    </div>

    <!-- Quick Operations Menu -->
    <div class="mt-1">
        <h3 class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-2.5">Hızlı İşlemler</h3>
        <div class="grid grid-cols-2 gap-3">
            <?php foreach ($quickActions as $action): ?>
            <a href="<?php echo $action['link']; ?>" class="p-3.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col gap-2 shadow-xs transition-transform active:scale-[0.98]">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center <?php echo $action['icon_color']; ?>" style="background: <?php echo $action['icon_bg']; ?>;">
                    <i data-lucide="<?php echo $action['icon']; ?>" style="width:16px;height:16px;"></i>
                </div>
                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100"><?php echo $action['title']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Approved Reports (Touch List) -->
    <div class="mt-2">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Son Raporlar</h3>
            <a href="#onayli-raporlar" class="text-xs font-bold text-primary flex items-center gap-0.5">
                Tümü
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>

        <div class="flex flex-col gap-2.5">
            <?php foreach ($recentApproved as $rapor): 
                $initials = '';
                $nameParts = explode(' ', $rapor->SIGORTALIADSOYAD);
                foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                
                $vakaBg = 'rgba(0,0,0,0.03)';
                $vakaColor = 'text-zinc-700 dark:text-zinc-300';
                if (stripos($rapor->VAKAADI, 'HASTALIK') !== false) {
                    $vakaBg = 'rgba(37, 99, 235, 0.08)';
                    $vakaColor = 'text-blue-600 dark:text-blue-400';
                } elseif (stripos($rapor->VAKAADI, 'KAZASI') !== false) {
                    $vakaBg = 'rgba(245, 158, 11, 0.08)';
                    $vakaColor = 'text-amber-600 dark:text-amber-400';
                } elseif (stripos($rapor->VAKAADI, 'ANALIK') !== false) {
                    $vakaBg = 'rgba(236, 72, 153, 0.08)';
                    $vakaColor = 'text-pink-600 dark:text-pink-400';
                }
            ?>
            <div class="p-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 flex items-center justify-center font-bold text-xs border border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                        <?php echo $initials; ?>
                    </div>
                    <div class="flex flex-col text-left min-w-0">
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100 truncate"><?php echo htmlspecialchars($rapor->SIGORTALIADSOYAD); ?></span>
                        <span class="text-[10px] text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($rapor->TCKIMLIKNO); ?></span>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?php echo $vakaColor; ?>" style="background: <?php echo $vakaBg; ?>; font-size: 8px;">
                                <?php echo htmlspecialchars($rapor->VAKAADI); ?>
                            </span>
                            <span class="text-[9px] text-zinc-400 dark:text-zinc-500" style="font-size: 8px;">
                                <?php echo date('d.m.Y', strtotime((!empty($rapor->onay_tarihi) && $rapor->onay_tarihi != '0000-00-00 00:00:00') ? $rapor->onay_tarihi : $rapor->created_at)); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <a href="rapor-onay-goster?id=<?php echo htmlspecialchars($rapor->MEDULARAPORID); ?>" target="_blank" class="w-8 h-8 rounded-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-center flex-shrink-0 active:bg-zinc-50 dark:active:bg-zinc-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($recentApproved)): ?>
            <div class="p-6 text-center text-xs text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
                Onaylanmış rapor bulunmamaktadır.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
