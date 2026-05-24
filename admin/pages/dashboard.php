<?php
require_once __DIR__ . '/../../autoload.php';
$userModel = new \Models\UserModel();
$stats = $userModel->getAdminDashboardStats();

// Dinamik Türkçe ay etiketlerini hazırlayalım
$trMonths = ['Jan'=>'Oca', 'Feb'=>'Şub', 'Mar'=>'Mar', 'Apr'=>'Nis', 'May'=>'May', 'Jun'=>'Haz', 'Jul'=>'Tem', 'Aug'=>'Ağu', 'Sep'=>'Eyl', 'Oct'=>'Eki', 'Nov'=>'Kas', 'Dec'=>'Ara'];

$db = \Core\Database::getInstance()->getConnection();

// HAFTALIK ETİKETLER VE VERİLER (Son 7 Gün)
$weeklyLabels = [];
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime("-$i days"));
    $trDays = ['Mon' => 'Pzt', 'Tue' => 'Sal', 'Wed' => 'Çar', 'Thu' => 'Per', 'Fri' => 'Cum', 'Sat' => 'Cmt', 'Sun' => 'Paz'];
    $weeklyLabels[] = $trDays[$dayName] ?? $dayName;
    
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM kullanicilar WHERE DATE(kayit_tarihi) = ? AND admin_id = 0 AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
    $stmt->execute([$date]);
    $c = (int)$stmt->fetch(PDO::FETCH_OBJ)->c;
    $weeklyData[] = $c;
}

// AYLIK ETİKETLER VE VERİLER (Son 30 Günün 5'er Günlük Blokları)
$monthlyLabels = [];
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $startDays = $i * 5;
    $day = date('d', strtotime("-$startDays days"));
    $month = date('M', strtotime("-$startDays days"));
    $trMonth = $trMonths[$month] ?? $month;
    $monthlyLabels[] = "$day $trMonth";
    
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM kullanicilar WHERE kayit_tarihi >= DATE_SUB(NOW(), INTERVAL ? DAY) AND kayit_tarihi < DATE_SUB(NOW(), INTERVAL ? DAY) AND admin_id = 0 AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
    $stmt->execute([$startDays + 5, $startDays]);
    $c = (int)$stmt->fetch(PDO::FETCH_OBJ)->c;
    $monthlyData[] = $c;
}

// YILLIK ETİKETLER VE VERİLER (Son 12 Ayın Çift Ayları)
$yearlyLabels = [];
$yearlyData = [];
for ($i = 10; $i >= 0; $i -= 2) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd = date('Y-m-t', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $yearlyLabels[] = $trMonths[$monthLabel] ?? $monthLabel;
    
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM kullanicilar WHERE kayit_tarihi >= ? AND kayit_tarihi <= ? AND admin_id = 0 AND (silinme_tarihi IS NULL OR silinme_tarihi = '')");
    $stmt->execute([$monthStart, $monthEnd]);
    $c = (int)$stmt->fetch(PDO::FETCH_OBJ)->c;
    $yearlyData[] = $c;
}
?>

<div class="animate-in">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Dashboard</h1>
            <p style="color: hsl(var(--muted-foreground)); font-size: 0.875rem; margin-top: 0.25rem;">Sistem genel durumu ve istatistikler.</p>
        </div>
    </div>

    <!-- İstatistik Kartları Grid Düzeni -->
    <div class="stats-grid">
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Toplam Gelir</span>
                <span class="trend-badge <?php echo $stats['total_revenue'] > 0 ? 'up' : 'neutral'; ?>">
                    <i data-lucide="<?php echo $stats['total_revenue'] > 0 ? 'trending-up' : 'minus'; ?>" style="width: 12px;"></i> 
                    Aktif
                </span>
            </div>
            <div class="stat-value">₺<?php echo number_format($stats['total_revenue'], 2, ',', '.'); ?></div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Toplam onaylı abonelik tutarı</span>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Yeni Kullanıcılar</span>
                <?php if ($stats['growth_rate'] > 0): ?>
                    <span class="trend-badge up"><i data-lucide="trending-up" style="width: 12px;"></i> +<?php echo $stats['growth_rate']; ?>%</span>
                <?php elseif ($stats['growth_rate'] < 0): ?>
                    <span class="trend-badge down"><i data-lucide="trending-down" style="width: 12px;"></i> <?php echo $stats['growth_rate']; ?>%</span>
                <?php else: ?>
                    <span class="trend-badge neutral"><i data-lucide="minus" style="width: 12px;"></i> 0%</span>
                <?php endif; ?>
            </div>
            <div class="stat-value"><?php echo number_format($stats['new_users'], 0, ',', '.'); ?></div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Son 30 gün içindeki kayıtlar</span>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Aktif Kullanıcılar</span>
                <span class="trend-badge up"><i data-lucide="users" style="width: 12px;"></i> Toplam</span>
            </div>
            <div class="stat-value"><?php echo number_format($stats['active_users'], 0, ',', '.'); ?></div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Sistemdeki toplam ana kullanıcı sayısı</span>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Büyüme Oranı</span>
                <span class="trend-badge <?php echo $stats['growth_rate'] >= 0 ? 'up' : 'down'; ?>">
                    <i data-lucide="<?php echo $stats['growth_rate'] >= 0 ? 'trending-up' : 'trending-down'; ?>" style="width: 12px;"></i> 
                    <?php echo $stats['growth_rate']; ?>%
                </span>
            </div>
            <div class="stat-value"><?php echo $stats['growth_rate']; ?>%</div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Geçen aya göre kayıt artış oranı</span>
            </div>
        </div>
    </div>

    <!-- Özgün ve Sıvı SVG Alan Grafik Kartı -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: flex-start; padding: 1.25rem 1.5rem 0.5rem 1.5rem;">
            <div>
                <h3 class="card-title" style="font-size: 0.9375rem; font-weight: 600;">Kullanıcı Etkinliği</h3>
                <p class="card-description" style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.125rem;">Sistem genelindeki kayıt ve abonelik trafiği</p>
            </div>
            <div class="tabs-list" style="margin-bottom: 0;">
                <button onclick="switchChartTab(this, 'weekly')" class="tab-trigger active">Haftalık</button>
                <button onclick="switchChartTab(this, 'monthly')" class="tab-trigger">Aylık</button>
                <button onclick="switchChartTab(this, 'yearly')" class="tab-trigger">Yıllık</button>
            </div>
        </div>
        <div class="card-content" style="padding: 1.5rem;">
            <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                <svg viewBox="0 0 800 300" class="chart-svg" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                    <defs>
                        <linearGradient id="chart-gradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="hsl(var(--primary))" stop-opacity="0.2" />
                            <stop offset="100%" stop-color="hsl(var(--primary))" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Kılavuz Çizgileri -->
                    <line x1="0" y1="50" x2="800" y2="50" class="grid-line" style="stroke: var(--border); stroke-dasharray: 4, 4;" />
                    <line x1="0" y1="125" x2="800" y2="125" class="grid-line" style="stroke: var(--border); stroke-dasharray: 4, 4;" />
                    <line x1="0" y1="200" x2="800" y2="200" class="grid-line" style="stroke: var(--border); stroke-dasharray: 4, 4;" />
                    <line x1="0" y1="275" x2="800" y2="275" class="grid-line" style="stroke: var(--border); stroke-dasharray: 4, 4;" />
                    
                    <!-- Sıvı Morf Efektli Çizgiler -->
                    <path id="chart-path-fill" d="M0,250 C50,220 100,180 150,210 S200,150 250,190 S300,100 350,180 S400,120 450,200 S500,80 550,170 S600,110 650,190 S700,90 750,180 L800,200 L800,300 L0,300 Z" fill="url(#chart-gradient)" />
                    <path id="chart-path-stroke" d="M0,250 C50,220 100,180 150,210 S200,150 250,190 S300,100 350,180 S400,120 450,200 S500,80 550,170 S600,110 650,190 S700,90 750,180 L800,200" fill="none" stroke="hsl(var(--primary))" stroke-width="2.5" />
                </svg>
                
                <!-- Yüzen Değer Penceresi (HTML Tooltip) -->
                <div id="chart-tooltip" style="position: absolute; display: none; background: #18181b; color: #ffffff; padding: 6px 10px; border-radius: 6px; font-size: 0.725rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.15); pointer-events: none; z-index: 100; transform: translate(-50%, -100%); margin-top: -10px; font-weight: 600; transition: left 0.1s, top 0.1s; white-space: nowrap;"></div>
                
                <!-- Kusursuz Dairesel Odak Noktası (HTML Div) -->
                <div id="chart-focus-dot" style="position: absolute; display: none; width: 10px; height: 10px; border-radius: 50%; background: hsl(var(--primary)); border: 2px solid #fff; box-shadow: 0 0 0 2px rgba(24, 24, 27, 0.1); pointer-events: none; z-index: 99; transform: translate(-50%, -50%); transition: left 0.1s, top 0.1s;"></div>
                
                <!-- Alt X Ekseni Etiketleri (Mutlak Konumlu) -->
                <div id="chart-x-labels" style="position: relative; height: 20px; margin-top: 1rem; color: hsl(var(--muted-foreground)); font-size: 0.75rem; width: 100%;">
                </div>
            </div>
        </div>
    </div>

    <!-- Bilgi Panelleri Grid Düzeni -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        
        <!-- Son Kayıt Olan Kullanıcılar (Sadeleştirilmiş Tablo) -->
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div class="tabs-list" style="background: transparent; border: 0; padding: 0;">
                    <button class="tab-trigger active" style="padding-left: 0; font-size: 0.875rem !important; font-weight: 700; cursor: default; background: transparent !important; color: var(--foreground) !important;">Son Kayıtlar</button>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="kullanicilar" class="btn btn-outline btn-sm nav-link" data-route="kullanicilar" style="font-size: 0.75rem; height: 32px; padding: 0 0.75rem;">
                        <i data-lucide="external-link" style="width: 12px; height: 12px;"></i> Tümünü Gör
                    </a>
                </div>
            </div>

            <div class="card" style="padding: 0.5rem 0;">
                <div class="table-container" style="overflow-x: auto;">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>Paket</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_users'] as $user): 
                                $initials = '';
                                $nameParts = explode(' ', $user->adi_soyadi ?? $user->kullanici_adi);
                                foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
                                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: var(--primary-foreground); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 1px solid var(--border);">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 600; color: var(--foreground); font-size: 0.8125rem;">
                                                <?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?>
                                            </span>
                                            <span style="font-size: 0.7rem; color: var(--muted-foreground);"><?php echo htmlspecialchars($user->email); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background: rgba(var(--primary-rgb, 37, 99, 235), 0.06); color: var(--primary); border: 1px solid rgba(var(--primary-rgb, 37, 99, 235), 0.12); font-size: 0.7rem; padding: 0.125rem 0.5rem; font-weight: 600;">
                                        <?php echo htmlspecialchars($user->paket_adi ?: 'Paketsiz'); ?>
                                    </span>
                                </td>
                                <td style="color: var(--muted-foreground); font-size: 0.75rem;">
                                    <?php echo date('d.m.Y', strtotime($user->kayit_tarihi)); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['recent_users'])): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 2rem; color: var(--muted-foreground);">
                                    Henüz kayıtlı kullanıcı bulunmuyor.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Son Sistem Aktiviteleri (Sadeleştirilmiş Tablo) -->
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div class="tabs-list" style="background: transparent; border: 0; padding: 0;">
                    <button class="tab-trigger active" style="padding-left: 0; font-size: 0.875rem !important; font-weight: 700; cursor: default; background: transparent !important; color: var(--foreground) !important;">Son Aktiviteler</button>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="aktiviteler" class="btn btn-outline btn-sm nav-link" data-route="aktiviteler" style="font-size: 0.75rem; height: 32px; padding: 0 0.75rem;">
                        <i data-lucide="activity" style="width: 12px; height: 12px;"></i> Tümünü Gör
                    </a>
                </div>
            </div>

            <div class="card" style="padding: 0.5rem 0;">
                <div class="table-container" style="overflow-x: auto;">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>İşlem / Kullanıcı</th>
                                <th>Kanal</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_activities'] as $activity): 
                                $badgeColor = 'rgba(113, 113, 122, 0.08)';
                                $textColor = '#71717a';
                                if ($activity->level === 'SUCCESS') {
                                    $badgeColor = 'rgba(16, 185, 129, 0.08)';
                                    $textColor = '#10b981';
                                } elseif ($activity->level === 'WARNING') {
                                    $badgeColor = 'rgba(245, 158, 11, 0.08)';
                                    $textColor = '#f59e0b';
                                } elseif ($activity->level === 'ERROR') {
                                    $badgeColor = 'rgba(239, 68, 68, 0.08)';
                                    $textColor = '#ef4444';
                                }
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.125rem;">
                                        <span style="font-weight: 600; color: var(--foreground); font-size: 0.8125rem; line-height: 1.3;">
                                            <?php echo htmlspecialchars($activity->message); ?>
                                        </span>
                                        <span style="font-size: 0.7rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.25rem;">
                                            <i data-lucide="user" style="width: 10px; height: 10px;"></i>
                                            <?php echo htmlspecialchars($activity->adi_soyadi ?: $activity->kullanici_adi ?: 'Sistem'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background: <?php echo $badgeColor; ?>; color: <?php echo $textColor; ?>; border: 1px solid rgba(0,0,0,0.02); font-size: 0.7rem; padding: 0.125rem 0.5rem; font-weight: 600; text-transform: capitalize;">
                                        <?php echo htmlspecialchars(str_replace('-', ' ', $activity->channel)); ?>
                                    </span>
                                </td>
                                <td style="color: var(--muted-foreground); font-size: 0.75rem;">
                                    <?php 
                                        $diff = time() - strtotime($activity->created_at);
                                        if ($diff < 60) echo 'Şimdi';
                                        elseif ($diff < 3600) echo floor($diff/60) . ' dk önce';
                                        elseif ($diff < 86400) echo floor($diff/3600) . ' sa önce';
                                        else echo date('d.m.Y', strtotime($activity->created_at));
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['recent_activities'])): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 2rem; color: var(--muted-foreground);">
                                    Henüz aktivite bulunmuyor.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Özel CSS overrides -->
<style>
    /* Tablo Tasarımı */
    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.8125rem !important; /* 13px */
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

    /* Sıvı SVG Morf Animasyonları */
    #chart-path-fill, #chart-path-stroke {
        transition: d 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Aktif zaman dilimi takipçisi
    var currentTab = 'weekly';
    var currentActivePoints = [];

    // Dinamik etiket ve yüzde verilerini PHP'den alalım
    var weeklyLabelsData = [
        <?php foreach ($weeklyLabels as $index => $label): 
            $pct = ($index / 6) * 100;
        ?>
        { label: '<?php echo htmlspecialchars($label); ?>', pct: <?php echo $pct; ?> },
        <?php endforeach; ?>
    ];

    var monthlyLabelsData = [
        <?php foreach ($monthlyLabels as $index => $label): 
            $pct = ($index / 5) * 100;
        ?>
        { label: '<?php echo htmlspecialchars($label); ?>', pct: <?php echo $pct; ?> },
        <?php endforeach; ?>
    ];

    var yearlyLabelsData = [
        <?php foreach ($yearlyLabels as $index => $label): 
            $pct = ($index / 5) * 100;
        ?>
        { label: '<?php echo htmlspecialchars($label); ?>', pct: <?php echo $pct; ?> },
        <?php endforeach; ?>
    ];

    // Raw count values from PHP veritabanı (Rastgele paylar olmadan %100 gerçek veriler)
    var rawData = {
        weekly: <?php echo json_encode($weeklyData); ?>,
        monthly: <?php echo json_encode($monthlyData); ?>,
        yearly: <?php echo json_encode($yearlyData); ?>
    };

    /**
     * Değerleri SVG Koordinatlarına ve Yüzdelere Dönüştüren Dinamik Fonksiyon
     */
    function calculatePoints(dataValues, type) {
        const count = dataValues.length;
        // Ölçeklendirme limiti (bölünme hatasını önlemek için min 5)
        const maxVal = Math.max(...dataValues, 5); 
        
        return dataValues.map((val, index) => {
            const pct = (index / (count - 1)) * 100;
            const x = (pct / 100) * 800; // SVG viewBox genişliği 800
            
            // Y ekseni ölçeklendirme (margin top 50, range 180px, margin bottom 70)
            const y = 250 - ((val / maxVal) * 180); 
            
            let label = '';
            if (type === 'weekly') {
                label = weeklyLabelsData[index].label;
            } else if (type === 'monthly') {
                label = monthlyLabelsData[index].label;
            } else if (type === 'yearly') {
                label = yearlyLabelsData[index].label;
            }
            
            return { pct, x, y, label, value: val };
        });
    }

    /**
     * Noktaları Birleştiren Premium Bezier Dalga Yolu (SVG Path Generator)
     */
    function getSvgPath(points, fill = false) {
        if (points.length === 0) return '';
        
        let d = `M ${points[0].x} ${points[0].y}`;
        
        for (let i = 0; i < points.length - 1; i++) {
            const p0 = points[i];
            const p1 = points[i + 1];
            
            // Yumuşak Bezier dalgası için kontrol noktaları hesaplama (Stripe tarzı)
            const cpX1 = p0.x + (p1.x - p0.x) / 3;
            const cpY1 = p0.y;
            const cpX2 = p1.x - (p1.x - p0.x) / 3;
            const cpY2 = p1.y;
            
            d += ` C ${cpX1} ${cpY1}, ${cpX2} ${cpY2}, ${p1.x} ${p1.y}`;
        }
        
        if (fill) {
            d += ` L 800 300 L 0 300 Z`;
        }
        
        return d;
    }

    /**
     * SVG Akıcı Morf (Wave Morph) Geçişi ve Etiket Güncellemesi
     */
    function switchChartTab(btn, type) {
        currentTab = type;

        // Tab butonlarındaki aktif sınıfını yönet
        const container = btn.closest('.tabs-list');
        if (container) {
            container.querySelectorAll('.tab-trigger').forEach(b => b.classList.remove('active'));
        }
        btn.classList.add('active');

        const fillPath = document.getElementById('chart-path-fill');
        const strokePath = document.getElementById('chart-path-stroke');
        const focusDot = document.getElementById('chart-focus-dot');
        const tooltip = document.getElementById('chart-tooltip');
        
        if (!fillPath || !strokePath) return;

        // Geçiş sırasında koordinatların sıçramaması için gizle
        if (focusDot) focusDot.style.display = 'none';
        if (tooltip) tooltip.style.display = 'none';

        // Dinamik değer ve noktaları hesapla
        const activeData = rawData[type];
        const activePoints = calculatePoints(activeData, type);
        currentActivePoints = activePoints;

        // Dynamic yollar oluştur ve uygula
        const fillD = getSvgPath(activePoints, true);
        const strokeD = getSvgPath(activePoints, false);

        fillPath.setAttribute('d', fillD);
        strokePath.setAttribute('d', strokeD);

        // X Ekseni Etiketlerini güncelle
        if (type === 'weekly') {
            renderXLabels(weeklyLabelsData);
        } else if (type === 'monthly') {
            renderXLabels(monthlyLabelsData);
        } else if (type === 'yearly') {
            renderXLabels(yearlyLabelsData);
        }
    }

    /**
     * X Ekseni Etiketlerini Yumuşak Geçişle Yenileme
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
     * Hassas Mouse Hover / Tooltip Takip Mekanizması
     */
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.querySelector('.chart-container');
        if (!container) return;

        const svg = container.querySelector('svg');
        const focusDot = document.getElementById('chart-focus-dot');
        const tooltip = document.getElementById('chart-tooltip');

        if (!svg || !focusDot || !tooltip) return;

        // Mouse hareket ettikçe en yakın koordinata kilitlen
        container.addEventListener('mousemove', (event) => {
            const rect = svg.getBoundingClientRect();
            // Mouse X pozisyonunu yüzdelik oran olarak al (0-100)
            const hoverPct = ((event.clientX - rect.left) / rect.width) * 100;

            if (currentActivePoints.length === 0) return;

            let closestPoint = null;
            let minDiff = Infinity;

            // En yakın veri noktasını yatay eksende yüzdelik farkla ara
            currentActivePoints.forEach(pt => {
                const diff = Math.abs(pt.pct - hoverPct);
                if (diff < minDiff) {
                    minDiff = diff;
                    closestPoint = pt;
                }
            });

            if (closestPoint) {
                // Yüzdesel konumlandırma ile HTML Focus Dot'u tam dalga tepe noktasına konumlandır
                const dotLeft = (closestPoint.pct / 100) * rect.width;
                const dotTop = (closestPoint.y / 300) * rect.height;

                focusDot.style.left = dotLeft + 'px';
                focusDot.style.top = dotTop + 'px';
                focusDot.style.display = 'block';

                // Tooltip'i hemen nokta üzerine hizala
                tooltip.innerHTML = `<strong>${closestPoint.label}</strong>: ${closestPoint.value} Kayıt`;
                tooltip.style.left = dotLeft + 'px';
                tooltip.style.top = (dotTop - 10) + 'px';
                tooltip.style.display = 'block';
            }
        });

        // Mouse grafikten çıkınca tooltip ve noktayı gizle
        container.addEventListener('mouseleave', () => {
            focusDot.style.display = 'none';
            tooltip.style.display = 'none';
        });

        // Sayfa ilk yüklendiğinde haftalık veriyi tetikle
        const activeBtn = document.querySelector('.tabs-list .tab-trigger.active');
        if (activeBtn) {
            switchChartTab(activeBtn, 'weekly');
        }
    });
</script>
