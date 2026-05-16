<?php
require_once __DIR__ . '/../../autoload.php';
$userModel = new \Models\UserModel();
$stats = $userModel->getAdminDashboardStats();
?>
<div class="animate-in">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Dashboard</h1>
            <p style="color: hsl(var(--muted-foreground)); font-size: 0.875rem; margin-top: 0.25rem;">Sistem genel durumu ve istatistikler.</p>
        </div>
    </div>


    <!-- Stats Grid -->
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

    <!-- Main Chart Section -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 class="card-title" style="font-size: 1rem;">Kullanıcı Etkinliği</h3>
                <p class="card-description">Sistem genelindeki kayıt ve abonelik trafiği</p>
            </div>
            <div class="tabs-list" style="margin-bottom: 0;">
                <button class="tab-trigger active">Haftalık</button>
                <button class="tab-trigger">Aylık</button>
                <button class="tab-trigger">Yıllık</button>
            </div>
        </div>
        <div class="card-content">
            <div class="chart-container">
                <svg viewBox="0 0 800 300" class="chart-svg" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="chart-gradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="hsl(var(--primary))" stop-opacity="0.2" />
                            <stop offset="100%" stop-color="hsl(var(--primary))" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Grid Lines -->
                    <line x1="0" y1="50" x2="800" y2="50" class="grid-line" />
                    <line x1="0" y1="125" x2="800" y2="125" class="grid-line" />
                    <line x1="0" y1="200" x2="800" y2="200" class="grid-line" />
                    <line x1="0" y1="275" x2="800" y2="275" class="grid-line" />
                    
                    <!-- Complex Path (mimicking the image) -->
                    <path d="M0,250 C50,220 100,180 150,210 S200,150 250,190 S300,100 350,180 S400,120 450,200 S500,80 550,170 S600,110 650,190 S700,90 750,180 L800,200 L800,300 L0,300 Z" fill="url(#chart-gradient)" />
                    <path d="M0,250 C50,220 100,180 150,210 S200,150 250,190 S300,100 350,180 S400,120 450,200 S500,80 550,170 S600,110 650,190 S700,90 750,180 L800,200" fill="none" stroke="hsl(var(--primary))" stroke-width="2.5" />
                </svg>
                
                <!-- X-Axis Labels -->
                <div style="display: flex; justify-content: space-between; margin-top: 1rem; color: hsl(var(--muted-foreground)); font-size: 0.75rem;">
                    <span>Pzt</span>
                    <span>Sal</span>
                    <span>Çar</span>
                    <span>Per</span>
                    <span>Cum</span>
                    <span>Cmt</span>
                    <span>Paz</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Sections Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        
        <!-- Recent Users Section -->
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div class="tabs-list" style="background: transparent; border: 0; padding: 0;">
                    <button class="tab-trigger active" style="padding-left: 0;">Son Kayıtlar</button>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="kullanicilar" class="btn btn-outline btn-sm nav-link" data-route="kullanicilar">
                        <i data-lucide="external-link" style="width: 14px;"></i> Tümünü Gör
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>Paket</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_users'] as $user): 
                                $initials = mb_substr($user->adi_soyadi ?? $user->kullanici_adi, 0, 2, 'UTF-8');
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 28px; height: 28px; border-radius: 50%; background: #f4f4f5; color: #71717a; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; border: 1px solid #e4e4e7;">
                                            <?php echo strtoupper($initials); ?>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 600; color: #18181b; font-size: 0.8125rem;">
                                                <?php echo $user->adi_soyadi ?: $user->kullanici_adi; ?>
                                            </span>
                                            <span style="font-size: 0.7rem; color: #71717a;"><?php echo $user->email; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-outline" style="font-size: 0.7rem; color: #2563eb; border-color: #bfdbfe; background: #eff6ff;">
                                        <?php echo $user->paket_adi ?: 'Paketsiz'; ?>
                                    </span>
                                </td>
                                <td style="color: #71717a; font-size: 0.75rem;">
                                    <?php echo date('d.m.Y', strtotime($user->kayit_tarihi)); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['recent_users'])): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 2rem; color: #71717a;">
                                    Henüz kayıtlı kullanıcı bulunmuyor.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activities Section -->
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div class="tabs-list" style="background: transparent; border: 0; padding: 0;">
                    <button class="tab-trigger active" style="padding-left: 0;">Son Aktiviteler</button>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="aktiviteler" class="btn btn-outline btn-sm nav-link" data-route="aktiviteler">
                        <i data-lucide="activity" style="width: 14px;"></i> Tümünü Gör
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>İşlem / Kullanıcı</th>
                                <th>Kanal</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_activities'] as $activity): 
                                $colorClass = 'neutral';
                                $icon = 'info';
                                switch($activity->level) {
                                    case 'SUCCESS': $colorClass = 'up'; $icon = 'check-circle'; break;
                                    case 'WARNING': $colorClass = 'down'; $icon = 'alert-triangle'; break;
                                    case 'ERROR': $colorClass = 'down'; $icon = 'x-circle'; break;
                                }
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 600; color: #18181b; font-size: 0.8125rem;">
                                            <?php echo $activity->message; ?>
                                        </span>
                                        <span style="font-size: 0.7rem; color: #71717a;">
                                            <i data-lucide="user" style="width: 10px; display: inline-block; vertical-align: middle;"></i>
                                            <?php echo $activity->adi_soyadi ?: $activity->kullanici_adi ?: 'Sistem'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-outline" style="font-size: 0.7rem; text-transform: capitalize;">
                                        <?php echo str_replace('-', ' ', $activity->channel); ?>
                                    </span>
                                </td>
                                <td style="color: #71717a; font-size: 0.75rem;">
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
                                <td colspan="3" style="text-align: center; padding: 2rem; color: #71717a;">
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

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
