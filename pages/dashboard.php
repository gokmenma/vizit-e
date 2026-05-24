<?php
require_once __DIR__ . '/../vendor/autoload.php';

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
require_once __DIR__ . '/../Core/Database.php';
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

// Dynamic Turkish Month Names
$trMonths = ['Jan'=>'Oca', 'Feb'=>'Şub', 'Mar'=>'Mar', 'Apr'=>'Nis', 'May'=>'May', 'Jun'=>'Haz', 'Jul'=>'Tem', 'Aug'=>'Ağu', 'Sep'=>'Eyl', 'Oct'=>'Eki', 'Nov'=>'Kas', 'Dec'=>'Ara'];

// WEEKLY LABELS & VALUES (Last 7 Days)
$weeklyLabels = [];
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime("-$i days"));
    $trDays = ['Mon' => 'Pzt', 'Tue' => 'Sal', 'Wed' => 'Çar', 'Thu' => 'Per', 'Fri' => 'Cum', 'Sat' => 'Cmt', 'Sun' => 'Paz'];
    $weeklyLabels[] = $trDays[$dayName] ?? $dayName;
    
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM onaylanan_raporlar WHERE DATE(COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at)) = ? AND isyeri_id = ?");
    $stmt->execute([$date, $active_isyeri_id]);
    $c = (int)$stmt->fetch(PDO::FETCH_OBJ)->c;
    $weeklyData[] = $c;
}

// MONTHLY LABELS & VALUES (Last 30 Days in 5-day intervals)
$monthlyLabels = [];
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $startDays = $i * 5;
    $day = date('d', strtotime("-$startDays days"));
    $month = date('M', strtotime("-$startDays days"));
    $trMonth = $trMonths[$month] ?? $month;
    $monthlyLabels[] = "$day $trMonth";
    
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM onaylanan_raporlar WHERE COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at) >= DATE_SUB(NOW(), INTERVAL ? DAY) AND COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at) < DATE_SUB(NOW(), INTERVAL ? DAY) AND isyeri_id = ?");
    $stmt->execute([$startDays + 5, $startDays, $active_isyeri_id]);
    $c = (int)$stmt->fetch(PDO::FETCH_OBJ)->c;
    $monthlyData[] = $c;
}

// YEARLY LABELS & VALUES (Last 12 Months, dual blocks)
$yearlyLabels = [];
$yearlyData = [];
for ($i = 10; $i >= 0; $i -= 2) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd = date('Y-m-t', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $yearlyLabels[] = $trMonths[$monthLabel] ?? $monthLabel;
    
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM onaylanan_raporlar WHERE COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at) >= ? AND COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at) <= ? AND isyeri_id = ?");
    $stmt->execute([$monthStart, $monthEnd, $active_isyeri_id]);
    $c = (int)$stmt->fetch(PDO::FETCH_OBJ)->c;
    $yearlyData[] = $c;
}

// Fetch Last 5 Approved Reports
$stmt = $db->prepare("SELECT * FROM onaylanan_raporlar WHERE isyeri_id = ? ORDER BY COALESCE(NULLIF(onay_tarihi, '0000-00-00 00:00:00'), created_at) DESC LIMIT 5");
$stmt->execute([$active_isyeri_id]);
$recentApproved = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch Last 5 Sick Employees (Unique or Recent)
$stmt = $db->prepare("SELECT SIGORTALIADSOYAD, TCKIMLIKNO, VAKAADI, ABASTAR, ABITTAR, POLIKLINIKTAR, ISBASKONTTAR, YATRAPBITTAR, rapor_gun_sayisi FROM onaylanan_raporlar WHERE isyeri_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$active_isyeri_id]);
$recentSick = $stmt->fetchAll(PDO::FETCH_OBJ);

// Re-styling Quick actions list
$quickActions = [
    [
        'title' => 'Tarihe Göre Rapor Ara',
        'icon' => 'calendar-search',
        'link' => 'tarihe-gore-rapor-ara',
        'icon_bg' => 'rgba(245, 158, 11, 0.08)',
        'icon_color' => 'text-amber-500'
    ],
    [
        'title' => 'Onaylanmış Raporlar',
        'icon' => 'check-circle-2',
        'link' => 'onayli-raporlar',
        'icon_bg' => 'rgba(16, 185, 129, 0.08)',
        'icon_color' => 'text-emerald-500'
    ],
    [
        'title' => 'Mahsuplaşma İşlemleri',
        'icon' => 'refresh-cw',
        'link' => 'mahsuplastirilacak-raporlar',
        'icon_bg' => 'rgba(14, 165, 233, 0.08)',
        'icon_color' => 'text-sky-500'
    ],
    [
        'title' => 'Manuel Rapor Bildirimi',
        'icon' => 'edit-3',
        'link' => 'manuel-rapor-bildirimi',
        'icon_bg' => 'rgba(99, 102, 241, 0.08)',
        'icon_color' => 'text-indigo-500'
    ],
    [
        'title' => 'Arşive Alınan Raporlar',
        'icon' => 'archive',
        'link' => 'arsivlenmis-raporlar',
        'icon_bg' => 'rgba(20, 184, 166, 0.08)',
        'icon_color' => 'text-teal-500'
    ],
    [
        'title' => 'İş Kazası Bildirimleri',
        'icon' => 'shield-alert',
        'link' => 'is-kazasi-bildirimi',
        'icon_bg' => 'rgba(244, 63, 94, 0.08)',
        'icon_color' => 'text-rose-500'
    ]
];
?>

<div class="animate-in">
    <!-- Üst Hoş Geldiniz Alanı -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Kontrol Paneli</h1>
            <p style="color: hsl(var(--muted-foreground)); font-size: 0.875rem; margin-top: 0.25rem;">
                Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['firma_adi'] ?? ''); ?></strong>
                (İşyeri Kodu: <code><?php echo htmlspecialchars($_SESSION['isyeriKodu'] ?? ''); ?></code>).
            </p>
        </div>
    </div>

    <!-- Abonelik Bilgi Kartı -->
    <div class="card mb-6"
        style="border-left: 4px solid <?php echo $abonelik_bitis_tarihi ? 'hsl(var(--primary))' : '#ef4444'; ?>;">
        <div class="card-content"
            style="padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div
                    style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $abonelik_bitis_tarihi ? 'rgba(37, 99, 235, 0.08)' : 'rgba(239, 68, 68, 0.08)'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $abonelik_bitis_tarihi ? 'hsl(var(--primary))' : '#ef4444'; ?>;">
                    <i data-lucide="<?php echo $abonelik_bitis_tarihi ? 'award' : 'alert-triangle'; ?>"
                        style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h4 style="font-size: 0.9375rem; font-weight: 600; margin: 0;">Abonelik Durumu</h4>
                    <p style="font-size: 0.8125rem; color: var(--muted-foreground); margin: 0.125rem 0 0 0;">
                        <?php if ($abonelik_bitis_tarihi == null): ?>
                        <span style="color: #ef4444; font-weight: 600;">Aktif aboneliğiniz bulunmamaktadır!</span>
                        <?php else: ?>
                        Aboneliğiniz aktif. Sonlanma tarihi:
                        <strong><?php echo Date::dmY($aktif_abonelik->bitis_tarihi, "d.m.Y"); ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div>
                <a href="abonelik-paketleri" class="btn btn-outline btn-sm nav-link" data-route="abonelik-paketleri"
                    style="font-size: 0.75rem; height: 34px; padding: 0 0.875rem;">
                    <i data-lucide="shopping-bag" style="width: 12px; height: 12px; margin-right: 0.375rem;"></i>
                    Abonelik Paketleri
                </a>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları Grid Düzeni -->
    <div class="stats-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card stat-card" style="border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <div class="stat-header"
                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span class="stat-label"
                    style="font-size: 0.75rem; font-weight: 600; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Toplam
                    Rapor</span>
                <span class="trend-badge up"
                    style="font-size: 0.7rem; background: rgba(16, 185, 129, 0.08); color: #10b981; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">Aktif</span>
            </div>
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 700; color: var(--foreground);">
                <?php echo number_format($totalReports); ?></div>
            <div class="stat-trend" style="font-size: 0.75rem; margin-top: 0.25rem;">
                <span style="color: var(--muted-foreground);">Toplam onaylı vizite sayısı</span>
            </div>
        </div>
        <div class="card stat-card" style="border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <div class="stat-header"
                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span class="stat-label"
                    style="font-size: 0.75rem; font-weight: 600; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Bu
                    Ayki Raporlar</span>
                <span class="trend-badge neutral"
                    style="font-size: 0.7rem; background: rgba(37, 99, 235, 0.08); color: hsl(var(--primary)); padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">Cari
                    Ay</span>
            </div>
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 700; color: var(--foreground);">
                <?php echo number_format($monthlyReports); ?></div>
            <div class="stat-trend" style="font-size: 0.75rem; margin-top: 0.25rem;">
                <span style="color: var(--muted-foreground);">Bu ay onaylanan toplam rapor</span>
            </div>
        </div>
        <div class="card stat-card" style="border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <div class="stat-header"
                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span class="stat-label"
                    style="font-size: 0.75rem; font-weight: 600; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Son
                    7 Gün</span>
                <span class="trend-badge up"
                    style="font-size: 0.7rem; background: rgba(245, 158, 11, 0.08); color: #f59e0b; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">Haftalık</span>
            </div>
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 700; color: var(--foreground);">
                <?php echo number_format($weeklyReports); ?></div>
            <div class="stat-trend" style="font-size: 0.75rem; margin-top: 0.25rem;">
                <span style="color: var(--muted-foreground);">Son 7 gündeki rapor sayısı</span>
            </div>
        </div>
        <div class="card stat-card" style="border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <div class="stat-header"
                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span class="stat-label"
                    style="font-size: 0.75rem; font-weight: 600; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Ortalama
                    Süre</span>
                <span class="trend-badge up"
                    style="font-size: 0.7rem; background: rgba(99, 102, 241, 0.08); color: #6366f1; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">Gün</span>
            </div>
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 700; color: var(--foreground);">
                <?php echo $avgReportDays; ?> Gün</div>
            <div class="stat-trend" style="font-size: 0.75rem; margin-top: 0.25rem;">
                <span style="color: var(--muted-foreground);">Ortalama istirahat süresi</span>
            </div>
        </div>
    </div>

    <!-- Rapor İstatistikleri Grafik Kartı (SVG) -->
    <div class="card"
        style="margin-bottom: 2rem; padding: 1.25rem; border: 1px solid var(--border); border-radius: 12px;">
        <div class="card-header"
            style="display: flex; flex-direction: row; justify-content: space-between; align-items: flex-start; padding: 0 0 1rem 0;">
            <div>
                <h3 class="card-title"
                    style="font-size: 0.9375rem; font-weight: 600; color: var(--foreground); margin: 0;">Rapor Etkinliği
                </h3>
                <p class="card-description"
                    style="font-size: 0.75rem; color: var(--muted-foreground); margin: 0.125rem 0 0 0;">İşyerine ait
                    onaylanan raporların dönemsel trafiği</p>
            </div>
            <div class="tabs-list"
                style="display: flex; gap: 0.25rem; background: rgba(0,0,0,0.02); border: 1px solid var(--border); padding: 2px; border-radius: 6px;">
                <button onclick="switchChartTab(this, 'weekly')" class="tab-trigger active"
                    style="font-size: 0.75rem; font-weight: 500; padding: 0.25rem 0.5rem; border-radius: 4px; border: none; background: transparent; cursor: pointer; color: var(--muted-foreground); transition: all 0.2s;">Haftalık</button>
                <button onclick="switchChartTab(this, 'monthly')" class="tab-trigger"
                    style="font-size: 0.75rem; font-weight: 500; padding: 0.25rem 0.5rem; border-radius: 4px; border: none; background: transparent; cursor: pointer; color: var(--muted-foreground); transition: all 0.2s;">Aylık</button>
                <button onclick="switchChartTab(this, 'yearly')" class="tab-trigger"
                    style="font-size: 0.75rem; font-weight: 500; padding: 0.25rem 0.5rem; border-radius: 4px; border: none; background: transparent; cursor: pointer; color: var(--muted-foreground); transition: all 0.2s;">Yıllık</button>
            </div>
        </div>
        <div class="card-content" style="padding: 0;">
            <div class="chart-container" style="position: relative; height: 260px; width: 100%;">
                <svg viewBox="0 0 800 260" class="chart-svg" preserveAspectRatio="none"
                    style="width: 100%; height: 100%;">
                    <defs>
                        <linearGradient id="chart-gradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="hsl(var(--primary))" stop-opacity="0.2" />
                            <stop offset="100%" stop-color="hsl(var(--primary))" stop-opacity="0" />
                        </linearGradient>
                    </defs>

                    <!-- Kılavuz Çizgileri -->
                    <line x1="0" y1="40" x2="800" y2="40" class="grid-line"
                        style="stroke: var(--border); stroke-dasharray: 4, 4;" />
                    <line x1="0" y1="110" x2="800" y2="110" class="grid-line"
                        style="stroke: var(--border); stroke-dasharray: 4, 4;" />
                    <line x1="0" y1="180" x2="800" y2="180" class="grid-line"
                        style="stroke: var(--border); stroke-dasharray: 4, 4;" />
                    <line x1="0" y1="240" x2="800" y2="240" class="grid-line"
                        style="stroke: var(--border); stroke-dasharray: 4, 4;" />

                    <!-- SVG Çizgi Yolları -->
                    <path id="chart-path-fill" d="M0,240 Q150,220 300,200 T600,180 T800,200 L800,260 L0,260 Z"
                        fill="url(#chart-gradient)" />
                    <path id="chart-path-stroke" d="M0,240 Q150,220 300,200 T600,180 T800,200" fill="none"
                        stroke="hsl(var(--primary))" stroke-width="2.5" />
                </svg>

                <!-- Yüzen Değer Tooltip ve Daire Odak Noktası -->
                <div id="chart-tooltip"
                    style="position: absolute; display: none; background: #18181b; color: #ffffff; padding: 6px 10px; border-radius: 6px; font-size: 0.725rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.15); pointer-events: none; z-index: 100; transform: translate(-50%, -100%); margin-top: -10px; font-weight: 600; transition: left 0.1s, top 0.1s; white-space: nowrap;">
                </div>
                <div id="chart-focus-dot"
                    style="position: absolute; display: none; width: 10px; height: 10px; border-radius: 50%; background: hsl(var(--primary)); border: 2px solid #fff; box-shadow: 0 0 0 2px rgba(24, 24, 27, 0.1); pointer-events: none; z-index: 99; transform: translate(-50%, -50%); transition: left 0.1s, top 0.1s;">
                </div>

                <!-- Alt X Ekseni Etiketleri -->
                <div id="chart-x-labels"
                    style="position: relative; height: 20px; margin-top: 0.75rem; color: hsl(var(--muted-foreground)); font-size: 0.75rem; width: 100%;">
                </div>
            </div>
        </div>
    </div>

    <!-- Bilgi Panelleri Grid Düzeni -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">

        <!-- Sol Panel: Son Onaylanan Raporlar -->
        <div style="display: flex; flex-direction: column;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <h3 style="font-size: 0.9375rem; font-weight: 700; color: var(--foreground); margin: 0;">Son Onaylanan
                    Raporlar</h3>
                <a href="onayli-raporlar" class="btn btn-outline btn-sm nav-link" data-route="onayli-raporlar"
                    style="font-size: 0.75rem; height: 32px; padding: 0 0.75rem;">
                    <i data-lucide="external-link" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i> Tümünü
                    Gör
                </a>
            </div>

            <div class="card" style="padding: 0.5rem 0; border: 1px solid var(--border); border-radius: 12px; flex: 1;">
                <div class="table-container" style="overflow-x: auto;">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Sigortalı</th>
                                <th style="text-align: center;">Vaka</th>
                                <th style="text-align: center;">Tür</th>
                                <th style="text-align: center;">Tarih</th>
                                <th style="width: 60px; text-align: center;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentApproved as $rapor): 
                                $initials = '';
                                $nameParts = explode(' ', $rapor->SIGORTALIADSOYAD);
                                foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
                                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                                
                                $vakaClass = 'bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300';
                                if (stripos($rapor->VAKAADI, 'HASTALIK') !== false) {
                                    $vakaClass = 'background: rgba(37, 99, 235, 0.08); color: #2563eb;';
                                } elseif (stripos($rapor->VAKAADI, 'KAZASI') !== false) {
                                    $vakaClass = 'background: rgba(245, 158, 11, 0.08); color: #d97706;';
                                } elseif (stripos($rapor->VAKAADI, 'ANALIK') !== false) {
                                    $vakaClass = 'background: rgba(236, 72, 153, 0.08); color: #db2777;';
                                }
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div
                                            style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--border);">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span
                                                style="font-weight: 600; color: var(--foreground); font-size: 0.8125rem; line-height: 1.2;">
                                                <?php echo htmlspecialchars($rapor->SIGORTALIADSOYAD); ?>
                                            </span>
                                            <span
                                                style="font-size: 0.7rem; color: var(--muted-foreground); font-mono: monospace; margin-top: 0.125rem;"><?php echo htmlspecialchars($rapor->TCKIMLIKNO); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge"
                                        style="font-size: 0.7rem; padding: 0.125rem 0.375rem; font-weight: 600; border-radius: 4px; <?php echo $vakaClass; ?>">
                                        <?php echo htmlspecialchars($rapor->VAKAADI); ?>
                                    </span>
                                </td>
                                <td style="text-align: center; color: var(--foreground); font-size: 0.75rem;">
                                    <span
                                        style="font-weight: 500; font-size: 0.725rem; padding: 2px 6px; background: rgba(0,0,0,0.03); border: 1px solid var(--border); border-radius: 4px;"><?php echo htmlspecialchars($rapor->onay_turu); ?></span>
                                </td>
                                <td style="text-align: center; color: var(--muted-foreground); font-size: 0.75rem;">
                                    <?php 
                                    $onay_tarihi_val = (!empty($rapor->onay_tarihi) && $rapor->onay_tarihi != '0000-00-00 00:00:00') ? $rapor->onay_tarihi : $rapor->created_at;
                                    echo date('d.m.Y', strtotime($onay_tarihi_val)); 
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="rapor-onay-goster?id=<?php echo htmlspecialchars($rapor->MEDULARAPORID); ?>"
                                        target="_blank" class="btn btn-outline"
                                        style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;"
                                        title="Detay">
                                        <i data-lucide="eye" style="width: 14px; height: 14px; color: #0ea5e9;"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentApproved)): ?>
                            <tr>
                                <td colspan="5"
                                    style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                                    Henüz onaylanmış rapor bulunmamaktadır.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sağ Panel: Son Rapor Alan Personeller -->
        <div style="display: flex; flex-direction: column;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <h3 style="font-size: 0.9375rem; font-weight: 700; color: var(--foreground); margin: 0;">En Son Rapor
                    Alan Personeller</h3>
                <span style="font-size: 0.75rem; color: var(--muted-foreground);">Son 5 Sigortalı</span>
            </div>

            <div class="card" style="padding: 0.5rem 0; border: 1px solid var(--border); border-radius: 12px; flex: 1;">
                <div class="table-container" style="overflow-x: auto;">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Personel</th>
                                <th style="text-align: center;">Süre</th>
                                <th style="text-align: center;">Tarih Aralığı</th>
                                <th style="text-align: center;">Vaka Türü</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSick as $sick): 
                                $initials = '';
                                $nameParts = explode(' ', $sick->SIGORTALIADSOYAD);
                                foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
                                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                                
                                $vakaClass = 'bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300';
                                if (stripos($sick->VAKAADI, 'HASTALIK') !== false) {
                                    $vakaClass = 'background: rgba(37, 99, 235, 0.08); color: #2563eb;';
                                } elseif (stripos($sick->VAKAADI, 'KAZASI') !== false) {
                                    $vakaClass = 'background: rgba(245, 158, 11, 0.08); color: #d97706;';
                                } elseif (stripos($sick->VAKAADI, 'ANALIK') !== false) {
                                    $vakaClass = 'background: rgba(236, 72, 153, 0.08); color: #db2777;';
                                }
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div
                                            style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--border);">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span
                                                style="font-weight: 600; color: var(--foreground); font-size: 0.8125rem; line-height: 1.2;">
                                                <?php echo htmlspecialchars($sick->SIGORTALIADSOYAD); ?>
                                            </span>
                                            <span
                                                style="font-size: 0.7rem; color: var(--muted-foreground); font-mono: monospace; margin-top: 0.125rem;"><?php echo htmlspecialchars($sick->TCKIMLIKNO); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    style="text-align: center; font-weight: 600; color: var(--foreground); font-size: 0.8125rem;">
                                    <?php echo $sick->rapor_gun_sayisi; ?> Gün
                                </td>
                                <td style="text-align: center; color: var(--muted-foreground); font-size: 0.75rem;">
                                    <?php 
                                    $baslangic = !empty($sick->ABASTAR) && $sick->ABASTAR != '0000-00-00' ? $sick->ABASTAR : $sick->POLIKLINIKTAR;
                                    $bitis = !empty($sick->ABITTAR) && $sick->ABITTAR != '0000-00-00' ? $sick->ABITTAR : (!empty($sick->ISBASKONTTAR) && $sick->ISBASKONTTAR != '0000-00-00' ? $sick->ISBASKONTTAR : $sick->YATRAPBITTAR);
                                    
                                    $baslangic_formatted = (!empty($baslangic) && $baslangic != '0000-00-00') ? date('d.m.y', strtotime($baslangic)) : '-';
                                    $bitis_formatted = (!empty($bitis) && $bitis != '0000-00-00') ? date('d.m.y', strtotime($bitis)) : '-';
                                    echo $baslangic_formatted . ' - ' . $bitis_formatted;
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge"
                                        style="font-size: 0.7rem; padding: 0.125rem 0.375rem; font-weight: 600; border-radius: 4px; <?php echo $vakaClass; ?>">
                                        <?php echo htmlspecialchars($sick->VAKAADI); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentSick)): ?>
                            <tr>
                                <td colspan="4"
                                    style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                                    Henüz rapor alan personel bulunmamaktadır.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Hızlı İşlemler Alt Menüsü -->
    <div style="margin-bottom: 1rem;">
        <h3 style="font-size: 0.9375rem; font-weight: 700; color: var(--foreground); margin: 0 0 1rem 0;">Hızlı İşlemler
        </h3>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem;">
        <?php foreach ($quickActions as $action): ?>
        <a href="<?php echo $action['link']; ?>" class="card card-hover hover-card-premium nav-link"
            data-route="<?php echo $action['link']; ?>"
            style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid var(--border); border-radius: 12px; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s;">
            <div class="<?php echo $action['icon_color']; ?>"
                style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 8px; background: <?php echo $action['icon_bg']; ?>; flex-shrink: 0;">
                <i data-lucide="<?php echo $action['icon']; ?>" style="width: 18px; height: 18px;"></i>
            </div>
            <div style="min-width: 0; flex: 1;">
                <h4
                    style="font-size: 0.8125rem; font-weight: 600; color: var(--foreground); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo $action['title']; ?></h4>
            </div>
            <i data-lucide="chevron-right"
                style="width: 14px; height: 14px; color: var(--muted-foreground); flex-shrink: 0;"></i>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Tablo Tasarımı */
.dashboard-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 0.8125rem !important;
    /* 13px */
}

.dashboard-table th {
    padding: 0.75rem 1rem !important;
    font-weight: 600 !important;
    color: var(--muted-foreground) !important;
    border-bottom: 1px solid var(--border) !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}

.dashboard-table td {
    padding: 0.75rem 1rem !important;
    border-bottom: 1px solid var(--border) !important;
    vertical-align: middle !important;
    color: var(--foreground) !important;
}

.dashboard-table tr:last-child td {
    border-bottom: none !important;
}

.dashboard-table tr:hover {
    background-color: rgba(0, 0, 0, 0.015);
}

.dark .dashboard-table tr:hover {
    background-color: rgba(255, 255, 255, 0.015);
}

.hover-card-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
    border-color: hsl(var(--primary) / 0.3) !important;
}

.dark .hover-card-premium:hover {
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1) !important;
    border-color: hsl(var(--primary) / 0.5) !important;
}

/* Sıvı SVG Morf Animasyonları */
#chart-path-fill,
#chart-path-stroke {
    transition: d 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

// Aktif zaman dilimi takipçisi
let currentTab = 'weekly';
let currentActivePoints = [];

// Dinamik etiket ve yüzde verilerini PHP'den alalım
const weeklyLabelsData = [
    <?php foreach ($weeklyLabels as $index => $label): 
            $pct = ($index / 6) * 100;
        ?> {
        label: '<?php echo htmlspecialchars($label); ?>',
        pct: <?php echo $pct; ?>
    },
    <?php endforeach; ?>
];

const monthlyLabelsData = [
    <?php foreach ($monthlyLabels as $index => $label): 
            $pct = ($index / 5) * 100;
        ?> {
        label: '<?php echo htmlspecialchars($label); ?>',
        pct: <?php echo $pct; ?>
    },
    <?php endforeach; ?>
];

const yearlyLabelsData = [
    <?php foreach ($yearlyLabels as $index => $label): 
            $pct = ($index / 5) * 100;
        ?> {
        label: '<?php echo htmlspecialchars($label); ?>',
        pct: <?php echo $pct; ?>
    },
    <?php endforeach; ?>
];

// Raw count values from database
const rawData = {
    weekly: <?php echo json_encode($weeklyData); ?>,
    monthly: <?php echo json_encode($monthlyData); ?>,
    yearly: <?php echo json_encode($yearlyData); ?>
};

/**
 * Değerleri SVG Koordinatlarına ve Yüzdelere Dönüştüren Dinamik Fonksiyon
 */
function calculatePoints(dataValues, type) {
    const count = dataValues.length;
    const maxVal = Math.max(...dataValues, 5); // Prevent divide by zero / scaling down too much

    return dataValues.map((val, index) => {
        const pct = (index / (count - 1)) * 100;
        const x = (pct / 100) * 800; // SVG viewBox width 800

        // Y-axis scaling (range 160px, padding top 40px, max height 240px)
        const y = 220 - ((val / maxVal) * 160);

        let label = '';
        if (type === 'weekly') {
            label = weeklyLabelsData[index].label;
        } else if (type === 'monthly') {
            label = monthlyLabelsData[index].label;
        } else if (type === 'yearly') {
            label = yearlyLabelsData[index].label;
        }

        return {
            pct,
            x,
            y,
            label,
            value: val
        };
    });
}

/**
 * Bezier Curve Path Generator
 */
function getSvgPath(points, fill = false) {
    if (points.length === 0) return '';

    let d = `M ${points[0].x} ${points[0].y}`;

    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i];
        const p1 = points[i + 1];

        const cpX1 = p0.x + (p1.x - p0.x) / 3;
        const cpY1 = p0.y;
        const cpX2 = p1.x - (p1.x - p0.x) / 3;
        const cpY2 = p1.y;

        d += ` C ${cpX1} ${cpY1}, ${cpX2} ${cpY2}, ${p1.x} ${p1.y}`;
    }

    if (fill) {
        d += ` L 800 260 L 0 260 Z`;
    }

    return d;
}

/**
 * SVG Akıcı Morf (Wave Morph) Geçişi ve Etiket Güncellemesi
 */
function switchChartTab(btn, type) {
    currentTab = type;

    // Manage active classes
    const container = btn.closest('.tabs-list');
    if (container) {
        container.querySelectorAll('.tab-trigger').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'transparent';
            b.style.color = 'var(--muted-foreground)';
        });
    }
    btn.classList.add('active');
    btn.style.background = 'var(--card)';
    btn.style.color = 'var(--foreground)';
    btn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';

    const fillPath = document.getElementById('chart-path-fill');
    const strokePath = document.getElementById('chart-path-stroke');
    const focusDot = document.getElementById('chart-focus-dot');
    const tooltip = document.getElementById('chart-tooltip');

    if (!fillPath || !strokePath) return;

    if (focusDot) focusDot.style.display = 'none';
    if (tooltip) tooltip.style.display = 'none';

    const activeData = rawData[type];
    const activePoints = calculatePoints(activeData, type);
    currentActivePoints = activePoints;

    const fillD = getSvgPath(activePoints, true);
    const strokeD = getSvgPath(activePoints, false);

    fillPath.setAttribute('d', fillD);
    strokePath.setAttribute('d', strokeD);

    // Update X Axis Labels
    if (type === 'weekly') {
        renderXLabels(weeklyLabelsData);
    } else if (type === 'monthly') {
        renderXLabels(monthlyLabelsData);
    } else if (type === 'yearly') {
        renderXLabels(yearlyLabelsData);
    }
}

/**
 * Render X Axis Labels
 */
function renderXLabels(items) {
    const labelsContainer = document.getElementById('chart-x-labels');
    if (!labelsContainer) return;

    labelsContainer.style.opacity = 0;

    setTimeout(() => {
        labelsContainer.innerHTML = '';
        items.forEach(item => {
            const span = document.createElement('span');
            span.textContent = item.label;
            span.style.position = 'absolute';
            span.style.left = item.pct + '%';
            span.style.transform = 'translateX(-50%)';
            labelsContainer.appendChild(span);
        });
        labelsContainer.style.transition = 'opacity 0.3s ease';
        labelsContainer.style.opacity = 1;
    }, 200);
}

/**
 * Tooltip Hover Tracking
 */
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.chart-container');
    if (!container) return;

    const svg = container.querySelector('svg');
    const focusDot = document.getElementById('chart-focus-dot');
    const tooltip = document.getElementById('chart-tooltip');

    if (!svg || !focusDot || !tooltip) return;

    container.addEventListener('mousemove', (event) => {
        const rect = svg.getBoundingClientRect();
        const hoverPct = ((event.clientX - rect.left) / rect.width) * 100;

        if (currentActivePoints.length === 0) return;

        let closestPoint = null;
        let minDiff = Infinity;

        currentActivePoints.forEach(pt => {
            const diff = Math.abs(pt.pct - hoverPct);
            if (diff < minDiff) {
                minDiff = diff;
                closestPoint = pt;
            }
        });

        if (closestPoint) {
            const dotLeft = (closestPoint.pct / 100) * rect.width;
            const dotTop = (closestPoint.y / 260) * rect.height;

            focusDot.style.left = dotLeft + 'px';
            focusDot.style.top = dotTop + 'px';
            focusDot.style.display = 'block';

            tooltip.innerHTML = `<strong>${closestPoint.label}</strong>: ${closestPoint.value} Rapor`;
            tooltip.style.left = dotLeft + 'px';
            tooltip.style.top = (dotTop - 10) + 'px';
            tooltip.style.display = 'block';
        }
    });

    container.addEventListener('mouseleave', () => {
        focusDot.style.display = 'none';
        tooltip.style.display = 'none';
    });

    // Trigger first rendering
    const activeBtn = document.querySelector('.tabs-list .tab-trigger.active');
    if (activeBtn) {
        switchChartTab(activeBtn, 'weekly');
    }
});
</script>