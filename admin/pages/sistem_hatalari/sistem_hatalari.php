<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo '<div class="alert alert-danger">Oturumunuz sonlanmış. Lütfen tekrar giriş yapın.</div>';
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
    echo '<div class="alert alert-danger">Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    exit;
}

$hataModel = new \Admin\Models\SistemHataModel();

$dbHatalari = $hataModel->veritabaniHatalari(200);
$cronCalismalari = $hataModel->cronCalismalari(50);
$cronOzetleri = $hataModel->cronOzetleri();
$basarisizOnaylar = $hataModel->basarisizOnaylar(200);
$dosyaKaynaklari = $hataModel->dosyaHatalari(300);
$sonOtomatikOnay = $hataModel->sonBasariliOtomatikOnay();

$simdi = time();
$sonYirmiDortSaat = 0;
foreach ($dbHatalari as $satir) {
    if ($satir->level === 'ERROR' && strtotime($satir->created_at) >= $simdi - 86400) {
        $sonYirmiDortSaat++;
    }
}

$dosyaHataSayisi = 0;
foreach ($dosyaKaynaklari as $kaynak) {
    foreach ($kaynak->satirlar as $satir) {
        if ($satir->seviye === 'ERROR') {
            $dosyaHataSayisi++;
        }
    }
}

$otomatikOnayCron = null;
foreach ($cronOzetleri as $ozet) {
    if ($ozet->cron_adi === 'otomatik_onay') {
        $otomatikOnayCron = $ozet;
        break;
    }
}

$gecenSaat = null;
if ($otomatikOnayCron) {
    $gecenSaat = (int)floor(($simdi - strtotime($otomatikOnayCron->baslangic)) / 3600);
}

function sh_tarih(?string $tarih): string
{
    if (empty($tarih) || $tarih === '0000-00-00 00:00:00') {
        return '-';
    }

    $zaman = strtotime($tarih);

    return $zaman ? date('d.m.Y H:i:s', $zaman) : '-';
}

function sh_kisalt(?string $metin, int $uzunluk = 220): string
{
    $metin = trim((string)$metin);
    if ($metin === '') {
        return '';
    }

    return mb_strlen($metin, 'UTF-8') > $uzunluk
        ? mb_substr($metin, 0, $uzunluk, 'UTF-8') . '…'
        : $metin;
}

$birlesikHatalar = [];

foreach ($dbHatalari as $satir) {
    $birlesikHatalar[] = [
        'zaman' => strtotime($satir->created_at),
        'seviye' => $satir->level,
        'kaynak' => 'Uygulama',
        'kanal' => $satir->channel,
        'mesaj' => $satir->message,
        'detay' => $satir->context,
        'ek' => trim(($satir->adi_soyadi ?? '') . ' ' . ($satir->ip_address ? '(' . $satir->ip_address . ')' : '')),
    ];
}

foreach ($cronCalismalari as $calisma) {
    if ($calisma->durum !== 'hata') {
        continue;
    }

    $birlesikHatalar[] = [
        'zaman' => strtotime($calisma->baslangic),
        'seviye' => 'ERROR',
        'kaynak' => 'Cron',
        'kanal' => $calisma->cron_adi,
        'mesaj' => $calisma->hata_mesaji ?: 'Cron çalışması hata ile sonlandı.',
        'detay' => null,
        'ek' => 'Onaylanan: ' . $calisma->onaylanan . ' / Başarısız: ' . $calisma->basarisiz,
    ];
}

foreach ($basarisizOnaylar as $onay) {
    $birlesikHatalar[] = [
        'zaman' => strtotime($onay->created_at),
        'seviye' => 'ERROR',
        'kaynak' => 'SGK Onay',
        'kanal' => $onay->onay_turu,
        'mesaj' => 'Rapor onaylanamadı: ' . ($onay->hata_mesaji ?: 'Hata metni kaydedilmemiş'),
        'detay' => 'Medula Rapor ID: ' . $onay->MEDULARAPORID . ' | TC: ' . $onay->TCKIMLIKNO,
        'ek' => trim(($onay->firma_adi ?? '') . ' ' . ($onay->SIGORTALIADSOYAD ?? '')),
    ];
}

foreach ($dosyaKaynaklari as $kaynak) {
    foreach ($kaynak->satirlar as $satir) {
        if ($satir->seviye === 'INFO') {
            continue;
        }

        $zaman = $kaynak->degisme;
        if (preg_match('/\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/', $satir->metin, $eslesme)) {
            $zaman = strtotime($eslesme[1]) ?: $kaynak->degisme;
        } elseif (preg_match('/\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2})/', $satir->metin, $eslesme)) {
            $zaman = strtotime($eslesme[1]) ?: $kaynak->degisme;
        }

        $birlesikHatalar[] = [
            'zaman' => $zaman,
            'seviye' => $satir->seviye,
            'kaynak' => 'Dosya',
            'kanal' => $kaynak->etiket,
            'mesaj' => $satir->metin,
            'detay' => $kaynak->yol,
            'ek' => '',
        ];
    }
}

usort($birlesikHatalar, static fn($a, $b) => $b['zaman'] <=> $a['zaman']);
$birlesikHatalar = array_slice($birlesikHatalar, 0, 500);

$sayaclar = ['ALL' => count($birlesikHatalar), 'ERROR' => 0, 'WARNING' => 0];
foreach ($birlesikHatalar as $hata) {
    if (isset($sayaclar[$hata['seviye']])) {
        $sayaclar[$hata['seviye']]++;
    }
}
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-shrink: 0;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Sistem Hataları</h1>
            <p style="color: var(--muted-foreground); font-size: 0.875rem; margin-top: 0.25rem;">Uygulama, cron ve SGK servis hatalarının tek ekrandan takibi.</p>
        </div>
        <button type="button" class="btn btn-outline" onclick="App.loadPage('sistem-hatalari', true)">
            <i data-lucide="refresh-cw" style="width: 16px;"></i> Yenile
        </button>
    </div>

    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Son 24 Saat Hatası</span>
                <span class="trend-badge <?php echo $sonYirmiDortSaat > 0 ? 'down' : 'up'; ?>">
                    <i data-lucide="<?php echo $sonYirmiDortSaat > 0 ? 'alert-triangle' : 'check'; ?>" style="width: 12px;"></i>
                    <?php echo $sonYirmiDortSaat > 0 ? 'Dikkat' : 'Temiz'; ?>
                </span>
            </div>
            <div class="stat-value"><?php echo $sonYirmiDortSaat; ?></div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Veritabanına yazılan kritik kayıt</span>
            </div>
        </div>

        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Otomatik Onay Cron</span>
                <?php if ($otomatikOnayCron === null): ?>
                    <span class="trend-badge down"><i data-lucide="x-circle" style="width: 12px;"></i> Kayıt yok</span>
                <?php elseif ($otomatikOnayCron->durum === 'hata'): ?>
                    <span class="trend-badge down"><i data-lucide="alert-octagon" style="width: 12px;"></i> Hata</span>
                <?php elseif ($gecenSaat !== null && $gecenSaat > 24): ?>
                    <span class="trend-badge down"><i data-lucide="clock" style="width: 12px;"></i> Gecikmiş</span>
                <?php else: ?>
                    <span class="trend-badge up"><i data-lucide="check" style="width: 12px;"></i> Çalışıyor</span>
                <?php endif; ?>
            </div>
            <div class="stat-value" style="font-size: 1.15rem;">
                <?php echo $otomatikOnayCron ? htmlspecialchars(sh_tarih($otomatikOnayCron->baslangic), ENT_QUOTES, 'UTF-8') : 'Hiç çalışmadı'; ?>
            </div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">
                    <?php
                    if ($otomatikOnayCron) {
                        echo 'Son çalışmada ' . (int)$otomatikOnayCron->onaylanan . ' onay, ' . (int)$otomatikOnayCron->basarisiz . ' hata';
                    } else {
                        echo 'Cron kaydı bulunamadı, zamanlayıcıyı kontrol edin';
                    }
                    ?>
                </span>
            </div>
        </div>

        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Son Başarılı Otomatik Onay</span>
                <span class="trend-badge neutral"><i data-lucide="file-check" style="width: 12px;"></i> SGK</span>
            </div>
            <div class="stat-value" style="font-size: 1.15rem;">
                <?php echo htmlspecialchars(sh_tarih($sonOtomatikOnay), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Veritabanındaki en son başarılı kayıt</span>
            </div>
        </div>

        <div class="card stat-card">
            <div class="stat-header">
                <span class="stat-label">Başarısız Onay</span>
                <span class="trend-badge <?php echo count($basarisizOnaylar) > 0 ? 'down' : 'up'; ?>">
                    <i data-lucide="file-x" style="width: 12px;"></i> Toplam
                </span>
            </div>
            <div class="stat-value"><?php echo count($basarisizOnaylar); ?></div>
            <div class="stat-trend">
                <span style="color: hsl(var(--muted-foreground));">Dosya günlüklerinde <?php echo $dosyaHataSayisi; ?> hata satırı</span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem; padding: 0; overflow: hidden;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);">
            <h2 style="font-size: 1rem; font-weight: 600; margin: 0;">Cron Çalışma Geçmişi</h2>
            <p style="color: var(--muted-foreground); font-size: 0.8125rem; margin: 0.25rem 0 0;">Zamanlanmış görevlerin son çalışmaları ve sonuçları.</p>
        </div>
        <div class="table-container" style="max-height: 320px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cron</th>
                        <th>Başlangıç</th>
                        <th>Bitiş</th>
                        <th>Durum</th>
                        <th>İşyeri</th>
                        <th>Onaylanan</th>
                        <th>Başarısız</th>
                        <th>Atlanan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cronCalismalari as $calisma):
                        $durumBadge = 'badge-outline';
                        if ($calisma->durum === 'tamamlandi') $durumBadge = 'badge-success';
                        if ($calisma->durum === 'hata') $durumBadge = 'badge-danger';
                        if ($calisma->durum === 'calisiyor') $durumBadge = 'badge-warning';
                    ?>
                    <tr>
                        <td style="font-weight: 600; font-size: 0.8125rem;"><?php echo htmlspecialchars(str_replace('_', ' ', $calisma->cron_adi), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="white-space: nowrap; font-size: 0.8125rem; color: var(--muted-foreground);"><?php echo htmlspecialchars(sh_tarih($calisma->baslangic), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="white-space: nowrap; font-size: 0.8125rem; color: var(--muted-foreground);"><?php echo htmlspecialchars(sh_tarih($calisma->bitis), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="badge <?php echo $durumBadge; ?>" style="font-weight: 700;"><?php echo htmlspecialchars($calisma->durum, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($calisma->hata_mesaji)): ?>
                                <div style="font-size: 0.7rem; color: var(--muted-foreground); margin-top: 0.25rem; font-family: monospace;">
                                    <?php echo htmlspecialchars(sh_kisalt($calisma->hata_mesaji, 160), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$calisma->isyeri_sayisi; ?></td>
                        <td style="color: #16a34a; font-weight: 600;"><?php echo (int)$calisma->onaylanan; ?></td>
                        <td style="color: <?php echo (int)$calisma->basarisiz > 0 ? '#dc2626' : 'inherit'; ?>; font-weight: 600;"><?php echo (int)$calisma->basarisiz; ?></td>
                        <td><?php echo (int)$calisma->atlanan; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cronCalismalari)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                            <i data-lucide="clock-alert" style="width: 28px; height: 28px; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                            <p style="margin: 0;">Hiç cron çalışma kaydı yok. Sunucudaki zamanlayıcının çalıştığından emin olun.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card dt-container" style="padding: 0; overflow: hidden; flex: 1; display: flex; flex-direction: column; min-height: 430px;">
        <div class="dt-header">
            <div class="dt-tabs">
                <button class="dt-tab active" onclick="filterHata('ALL', this)">Hepsi <span class="dt-tab-count"><?php echo $sayaclar['ALL']; ?></span></button>
                <button class="dt-tab" onclick="filterHata('ERROR', this)">Hata <span class="dt-tab-count"><?php echo $sayaclar['ERROR']; ?></span></button>
                <button class="dt-tab" onclick="filterHata('WARNING', this)">Uyarı <span class="dt-tab-count"><?php echo $sayaclar['WARNING']; ?></span></button>
            </div>

            <div class="dt-actions">
                <div class="dt-search-wrapper">
                    <i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; color: var(--muted-foreground); z-index: 10;"></i>
                    <input type="text" id="hata-search" class="dt-search-input" placeholder="Hata ara..." onkeyup="searchHatalar()">
                </div>
            </div>
        </div>

        <div class="table-container" style="flex: 1; min-height: 340px;">
            <table class="data-table" id="hatalar-table">
                <thead>
                    <tr>
                        <th class="sortable" onclick="sortHatalar(0)">Tarih <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th>Kaynak</th>
                        <th>Kanal</th>
                        <th>Seviye</th>
                        <th>Mesaj</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($birlesikHatalar as $hata):
                        $seviyeBadge = $hata['seviye'] === 'ERROR' ? 'badge-danger' : 'badge-warning';
                        $kaynakBadge = 'badge-secondary';
                        if ($hata['kaynak'] === 'Cron') $kaynakBadge = 'badge-primary';
                        if ($hata['kaynak'] === 'SGK Onay') $kaynakBadge = 'badge-danger';
                        if ($hata['kaynak'] === 'Dosya') $kaynakBadge = 'badge-outline';
                    ?>
                    <tr class="hata-row" data-level="<?php echo htmlspecialchars($hata['seviye'], ENT_QUOTES, 'UTF-8'); ?>">
                        <td style="white-space: nowrap; font-size: 0.8125rem; color: var(--muted-foreground);">
                            <?php echo $hata['zaman'] ? date('d.m.Y H:i:s', $hata['zaman']) : '-'; ?>
                        </td>
                        <td class="hata-kaynak">
                            <span class="badge <?php echo $kaynakBadge; ?>" style="font-size: 0.65rem; border-radius: 4px;">
                                <?php echo htmlspecialchars($hata['kaynak'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td style="font-size: 0.75rem; color: var(--muted-foreground);">
                            <?php echo htmlspecialchars(sh_kisalt($hata['kanal'], 40), ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $seviyeBadge; ?>" style="min-width: 70px; font-weight: 700;">
                                <?php echo htmlspecialchars($hata['seviye'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td class="hata-mesaj">
                            <span style="font-size: 0.8125rem; color: var(--foreground);">
                                <?php echo htmlspecialchars(sh_kisalt($hata['mesaj']), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <?php if (!empty($hata['detay'])): ?>
                                <div style="font-size: 0.7rem; color: var(--muted-foreground); margin-top: 0.25rem; font-family: monospace; background: var(--muted); padding: 0.25rem 0.4rem; border-radius: 4px; word-break: break-all;">
                                    <?php echo htmlspecialchars(sh_kisalt($hata['detay'], 300), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($hata['ek'])): ?>
                                <div style="font-size: 0.7rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars(sh_kisalt($hata['ek'], 120), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($birlesikHatalar)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 4rem; color: var(--muted-foreground);">
                            <i data-lucide="shield-check" style="width: 32px; height: 32px; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Kayıtlı sistem hatası bulunamadı.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

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

    function searchHatalar() {
        App.DataTable.search('hata-search', '.hata-row', '.hata-kaynak', '.hata-mesaj');
    }

    function sortHatalar(n) {
        App.DataTable.sort('hatalar-table', n);
    }

    function filterHata(level, btn) {
        if (btn) {
            btn.parentElement.querySelectorAll('.dt-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
        }

        document.querySelectorAll('.hata-row').forEach(row => {
            if (level === 'ALL' || row.dataset.level === level) {
                row.style.display = '';
                row.classList.remove('dt-filtered');
            } else {
                row.style.display = 'none';
                row.classList.add('dt-filtered');
            }
        });

        if (window.TablePagination && window.TablePagination['hatalar-table']) {
            window.TablePagination['hatalar-table'].currentPage = 1;
            App.DataTable.initPagination('hatalar-table');
        }
    }

    setTimeout(() => {
        App.DataTable.initPagination('hatalar-table');
    }, 100);
</script>
