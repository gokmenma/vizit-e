<?php
require_once __DIR__ . '/../../autoload.php';

// Yetki kontrolü (Sadece superadmin)
if ($_SESSION['user_role'] !== 'superadmin') {
    echo '<div class="alert alert-danger">Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    exit;
}

$userModel = new \Models\UserModel();
$activities = $userModel->getRecentActivities(100); // Son 100 aktiviteyi getir
?>

<div class="animate-in">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Sistem Aktiviteleri</h1>
            <p style="color: hsl(var(--muted-foreground)); font-size: 0.875rem; margin-top: 0.25rem;">Tüm kritik sistem işlemleri ve günlükleri.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Kullanıcı</th>
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
                    <tr>
                        <td style="white-space: nowrap; font-size: 0.8125rem; color: #71717a;">
                            <?php echo date('d.m.Y H:i:s', strtotime($activity->created_at)); ?>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600; color: #18181b; font-size: 0.8125rem;">
                                    <?php echo $activity->adi_soyadi ?: ($activity->kullanici_adi ?: 'Sistem'); ?>
                                </span>
                                <span style="font-size: 0.7rem; color: #71717a;">ID: <?php echo $activity->user_id ?: '-'; ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.875rem; color: #18181b;"><?php echo $activity->message; ?></span>
                            <?php if ($activity->context): ?>
                                <div style="font-size: 0.7rem; color: #71717a; margin-top: 0.25rem; font-family: monospace; background: #f4f4f5; padding: 0.25rem; border-radius: 4px;">
                                    <?php echo htmlspecialchars($activity->context); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-outline" style="text-transform: capitalize;">
                                <?php echo str_replace('-', ' ', $activity->channel); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $levelBadge; ?>">
                                <?php echo $activity->level; ?>
                            </span>
                        </td>
                        <td style="font-size: 0.75rem; color: #71717a;"><?php echo $activity->ip_address; ?></td>
                        <td style="font-size: 0.75rem; color: #71717a;" title="<?php echo htmlspecialchars($activity->browser); ?>">
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
                        <td colspan="7" style="text-align: center; padding: 4rem; color: #71717a;">
                            <i data-lucide="info" style="width: 32px; height: 32px; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Henüz sistem aktivitesi kaydedilmemiş.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
