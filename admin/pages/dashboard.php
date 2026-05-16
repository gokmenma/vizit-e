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
                <span class="trend-badge up"><i data-lucide="trending-up" style="width: 12px;"></i> +12.5%</span>
            </div>
            <div class="stat-value">₺12,250.00</div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Geçen aya göre artış</span>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Yeni Aboneler</span>
                <span class="trend-badge down"><i data-lucide="trending-down" style="width: 12px;"></i> -20%</span>
            </div>
            <div class="stat-value">1,234</div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Bu periyotta düşüş</span>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Aktif Kullanıcılar</span>
                <span class="trend-badge up"><i data-lucide="trending-up" style="width: 12px;"></i> +12.5%</span>
            </div>
            <div class="stat-value">45,678</div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Güçlü kullanıcı tutma oranı</span>
            </div>
        </div>
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Büyüme Oranı</span>
                <span class="trend-badge up"><i data-lucide="trending-up" style="width: 12px;"></i> +4.5%</span>
            </div>
            <div class="stat-value">4.5%</div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">İstikrarlı performans artışı</span>
            </div>
        </div>
    </div>

    <!-- Main Chart Section -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 class="card-title" style="font-size: 1rem;">Toplam Ziyaretçi</h3>
                <p class="card-description">Son 3 ayın ziyaretçi verileri</p>
            </div>
            <div class="tabs-list" style="margin-bottom: 0;">
                <button class="tab-trigger active">Son 3 ay</button>
                <button class="tab-trigger">Son 30 gün</button>
                <button class="tab-trigger">Son 7 gün</button>
            </div>
        </div>
        <div class="card-content">
            <div class="chart-container">
                <svg viewBox="0 0 800 300" class="chart-svg" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="chart-gradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#000" stop-opacity="0.2" />
                            <stop offset="100%" stop-color="#000" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Grid Lines -->
                    <line x1="0" y1="50" x2="800" y2="50" class="grid-line" />
                    <line x1="0" y1="125" x2="800" y2="125" class="grid-line" />
                    <line x1="0" y1="200" x2="800" y2="200" class="grid-line" />
                    <line x1="0" y1="275" x2="800" y2="275" class="grid-line" />
                    
                    <!-- Complex Path (mimicking the image) -->
                    <path d="M0,250 C50,220 100,180 150,210 S200,150 250,190 S300,100 350,180 S400,120 450,200 S500,80 550,170 S600,110 650,190 S700,90 750,180 L800,200 L800,300 L0,300 Z" fill="url(#chart-gradient)" />
                    <path d="M0,250 C50,220 100,180 150,210 S200,150 250,190 S300,100 350,180 S400,120 450,200 S500,80 550,170 S600,110 650,190 S700,90 750,180 L800,200" fill="none" stroke="#000" stroke-width="2.5" />
                    
                    <!-- Second Path -->
                    <path d="M0,270 C50,250 100,220 150,240 S200,200 250,230 S300,180 350,220 S400,190 450,230 S500,160 550,210 S600,180 650,220 S700,170 750,210 L800,230" fill="none" stroke="#666" stroke-width="1.5" stroke-dasharray="4" />
                </svg>
                
                <!-- X-Axis Labels -->
                <div style="display: flex; justify-content: space-between; margin-top: 1rem; color: hsl(var(--muted-foreground)); font-size: 0.75rem;">
                    <span>Nis 7</span>
                    <span>Nis 19</span>
                    <span>May 2</span>
                    <span>May 14</span>
                    <span>May 28</span>
                    <span>Haz 9</span>
                    <span>Haz 22</span>
                    <span>Haz 30</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div style="margin-top: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div class="tabs-list" style="background: transparent; border: 0; padding: 0;">
                <button class="tab-trigger active" style="padding-left: 0;">Genel Bakış</button>
                <button class="tab-trigger">Geçmiş Performans <span class="badge-secondary badge" style="margin-left: 0.5rem; padding: 0 0.3rem;">3</span></button>
                <button class="tab-trigger">Kilit Personel <span class="badge-secondary badge" style="margin-left: 0.5rem; padding: 0 0.3rem;">2</span></button>
                <button class="tab-trigger">Dosyalar</button>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-outline btn-sm"><i data-lucide="columns" style="width: 14px;"></i> Sütunları Düzenle</button>
                <button class="btn btn-sm" style="background: hsl(var(--primary)); color: hsl(var(--primary-foreground));"><i data-lucide="plus" style="width: 14px;"></i> Bölüm Ekle</button>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" style="border-radius: 4px;"></th>
                            <th>Başlık</th>
                            <th>Bölüm Türü</th>
                            <th>Durum</th>
                            <th>Hedef</th>
                            <th>Limit</th>
                            <th>Sorumlu</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td style="font-weight: 500;">Kapak Sayfası</td>
                            <td><span class="badge badge-outline">Kapak</span></td>
                            <td><span class="badge badge-secondary" style="color: #666;"><i data-lucide="loader-2" style="width: 12px; margin-right: 4px;"></i> İşleniyor</span></td>
                            <td>18</td>
                            <td>5</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #eee; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 600;">EL</div>
                                    Eddie Lake
                                </div>
                            </td>
                            <td><button class="btn btn-ghost btn-sm"><i data-lucide="more-vertical" style="width: 14px;"></i></button></td>
                        </tr>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td style="font-weight: 500;">İçindekiler</td>
                            <td><span class="badge badge-outline">Liste</span></td>
                            <td><span class="badge badge-success"><i data-lucide="check-circle-2" style="width: 12px; margin-right: 4px;"></i> Tamamlandı</span></td>
                            <td>29</td>
                            <td>24</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #eee; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 600;">EL</div>
                                    Eddie Lake
                                </div>
                            </td>
                            <td><button class="btn btn-ghost btn-sm"><i data-lucide="more-vertical" style="width: 14px;"></i></button></td>
                        </tr>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td style="font-weight: 500;">Yönetici Özeti</td>
                            <td><span class="badge badge-outline">Metin</span></td>
                            <td><span class="badge badge-success"><i data-lucide="check-circle-2" style="width: 12px; margin-right: 4px;"></i> Tamamlandı</span></td>
                            <td>10</td>
                            <td>13</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #eee; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 600;">EL</div>
                                    Eddie Lake
                                </div>
                            </td>
                            <td><button class="btn btn-ghost btn-sm"><i data-lucide="more-vertical" style="width: 14px;"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
