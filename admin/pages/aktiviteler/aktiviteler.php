<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

// Yetki kontrolü (Sadece superadmin)
if ($_SESSION['user_role'] !== 'superadmin') {
    echo '<div class="alert alert-danger">Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    exit;
}

$userModel = new \Models\UserModel();
$activities = $userModel->getRecentActivities(100); // Son 100 aktiviteyi getir

$counts = [
    'ALL' => count($activities),
    'SUCCESS' => 0,
    'WARNING' => 0,
    'ERROR' => 0
];

foreach ($activities as $a) {
    if (isset($counts[$a->level])) $counts[$a->level]++;
}
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-shrink: 0;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Sistem Aktiviteleri</h1>
            <p style="color: var(--muted-foreground); font-size: 0.875rem; margin-top: 0.25rem;">Tüm kritik sistem işlemleri ve günlükleri.</p>
        </div>
    </div>

    <div class="card dt-container" style="padding: 0; overflow: hidden; flex: 1; display: flex; flex-direction: column; min-height: 0;">
        <!-- Data Table Header -->
        <div class="dt-header">
            <div class="dt-tabs">
                <button class="dt-tab active" onclick="filterByLevel('ALL', this)">Hepsi <span class="dt-tab-count"><?php echo $counts['ALL']; ?></span></button>
                <button class="dt-tab" onclick="filterByLevel('SUCCESS', this)">Başarılı <span class="dt-tab-count"><?php echo $counts['SUCCESS']; ?></span></button>
                <button class="dt-tab" onclick="filterByLevel('WARNING', this)">Uyarı <span class="dt-tab-count"><?php echo $counts['WARNING']; ?></span></button>
                <button class="dt-tab" onclick="filterByLevel('ERROR', this)">Hata <span class="dt-tab-count"><?php echo $counts['ERROR']; ?></span></button>
            </div>

            <div class="dt-actions">
                <div class="dt-search-wrapper">
                    <i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; color: var(--muted-foreground); z-index: 10;"></i>
                    <input type="text" id="activity-search" class="dt-search-input" placeholder="Aktivite ara..." onkeyup="searchActivities()">
                </div>
            </div>
        </div>

        <div class="table-container" style="flex: 1; min-height: 0;">
            <table class="data-table" id="activities-table">
                <thead>
                    <tr>
                        <th class="sortable" onclick="sortActivities(0)">Tarih <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortActivities(1)">Kullanıcı <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th>İşlem</th>
                        <th>Kanal</th>
                        <th>Seviye</th>
                        <th>IP Adresi</th>
                        <th>Tarayıcı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): 
                        $levelBadge = 'badge-outline';
                        if ($activity->level === 'SUCCESS') $levelBadge = 'badge-success';
                        if ($activity->level === 'WARNING') $levelBadge = 'badge-warning';
                        if ($activity->level === 'ERROR') $levelBadge = 'badge-danger';
                    ?>
                    <tr class="activity-row" data-level="<?php echo $activity->level; ?>">
                        <td style="white-space: nowrap; font-size: 0.8125rem; color: var(--muted-foreground);">
                            <?php echo date('d.m.Y H:i:s', strtotime($activity->created_at)); ?>
                        </td>
                        <td class="activity-user">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600; color: var(--foreground); font-size: 0.8125rem;">
                                    <?php echo $activity->adi_soyadi ?: ($activity->kullanici_adi ?: 'Sistem'); ?>
                                </span>
                                <span style="font-size: 0.7rem; color: var(--muted-foreground);">ID: <?php echo $activity->user_id ?: '-'; ?></span>
                            </div>
                        </td>
                        <td class="activity-message">
                            <span style="font-size: 0.875rem; color: var(--foreground);"><?php echo $activity->message; ?></span>
                            <?php if ($activity->context): ?>
                                <div style="font-size: 0.7rem; color: var(--muted-foreground); margin-top: 0.25rem; font-family: monospace; background: var(--muted); padding: 0.25rem; border-radius: 4px;">
                                    <?php echo htmlspecialchars($activity->context); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $channel = $activity->channel;
                                $channelClass = 'badge-secondary';
                                if (strpos($channel, 'admin') !== false) $channelClass = 'badge-primary';
                                if (strpos($channel, 'api') !== false) $channelClass = 'badge-success';
                                if (strpos($channel, 'auth') !== false) $channelClass = 'badge-danger';
                                if (strpos($channel, 'system') !== false) $channelClass = 'badge-outline';
                            ?>
                            <span class="badge <?php echo $channelClass; ?>" style="text-transform: capitalize; font-size: 0.65rem; border-radius: 4px;">
                                <?php echo str_replace('-', ' ', $activity->channel); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $levelBadge; ?>" style="min-width: 70px; font-weight: 700;">
                                <?php echo $activity->level; ?>
                            </span>
                        </td>
                        <td style="font-size: 0.75rem; color: var(--muted-foreground);"><?php echo $activity->ip_address; ?></td>
                        <td style="font-size: 0.75rem; color: var(--muted-foreground);" title="<?php echo htmlspecialchars($activity->browser); ?>">
                            <?php 
                                $ua = $activity->browser;
                                if (strpos($ua, 'Chrome') !== false) echo 'Chrome';
                                elseif (strpos($ua, 'Firefox') !== false) echo 'Firefox';
                                elseif (strpos($ua, 'Safari') !== false) echo 'Safari';
                                elseif (strpos($ua, 'Edge') !== false) echo 'Edge';
                                else echo 'Bilinmiyor';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 4rem; color: var(--muted-foreground);">
                            <i data-lucide="info" style="width: 32px; height: 32px; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Henüz sistem aktivitesi kaydedilmemiş.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Data Table Footer -->
        <div class="dt-footer">
            <div class="dt-info">Yükleniyor...</div>
            <div class="dt-pagination-actions"></div>
        </div>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    function searchActivities() {
        App.DataTable.search('activity-search', '.activity-row', '.activity-user', '.activity-message');
    }

    function sortActivities(n) {
        App.DataTable.sort('activities-table', n);
    }

    function filterByLevel(level, btn) {
        // Tab buttons update
        if (btn) {
            btn.parentElement.querySelectorAll('.dt-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
        }

        const rows = document.querySelectorAll('.activity-row');
        rows.forEach(row => {
            if (level === 'ALL' || row.dataset.level === level) {
                row.style.display = '';
                row.classList.remove('dt-filtered');
            } else {
                row.style.display = 'none';
                row.classList.add('dt-filtered');
            }
        });
        
        // Refresh pagination if active
        if (window.TablePagination && window.TablePagination['activities-table']) {
            window.TablePagination['activities-table'].currentPage = 1;
            App.DataTable.initPagination('activities-table');
        }
    }

    // Initialize Pagination
    setTimeout(() => {
        App.DataTable.initPagination('activities-table');
    }, 100);
</script>
