<?php
require_once __DIR__ . '/../../autoload.php';
$db = \Core\Database::getInstance()->getConnection();
$adminId = $_GET['admin_id'] ?? 0;

$sql = "SELECT k.*, a.adi_soyadi as admin_ad, a.kullanici_adi as admin_username 
        FROM kullanicilar k 
        LEFT JOIN kullanicilar a ON k.admin_id = a.id 
        WHERE k.admin_id > 0 AND (k.silinme_tarihi IS NULL OR k.silinme_tarihi = '')";

if ($adminId > 0) {
    $sql .= " AND k.admin_id = :admin_id";
}

$stmt = $db->prepare($sql);
if ($adminId > 0) {
    $stmt->bindParam(':admin_id', $adminId);
}
$stmt->execute();
$altKullanicilar = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Alt Kullanıcılar</h1>
            <p style="color: #71717a; font-size: 0.875rem; margin-top: 0.25rem;">
                <?php if ($adminId > 0 && !empty($altKullanicilar)): ?>
                    <b><?php echo $altKullanicilar[0]->admin_ad ?? $altKullanicilar[0]->admin_username; ?></b> kullanıcısına bağlı alt hesaplar.
                    <a href="alt-kullanicilar" class="nav-link" data-route="alt-kullanicilar" style="color: #2563eb; margin-left: 0.5rem; text-decoration: underline;">Tümünü Gör</a>
                <?php else: ?>
                    Abonelere bağlı çalışan alt kullanıcı hesapları.
                <?php endif; ?>
            </p>
        </div>
        <button class="btn" style="background: #18181b; color: white;">
            <i data-lucide="user-plus" style="width: 16px;"></i> Yeni Alt Kullanıcı
        </button>
    </div>

    <div class="card dt-container" style="padding: 0; overflow: hidden; border-radius: 12px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
        <!-- Data Table Header -->
        <div class="dt-header">
            <div class="dt-tabs">
                <button class="dt-tab active">Hepsi <span class="dt-tab-count"><?php echo count($altKullanicilar); ?></span></button>
                <button class="dt-tab">Aktif <span class="dt-tab-count"><?php echo count($altKullanicilar); ?></span></button>
                <button class="dt-tab">Pasif <span class="dt-tab-count">0</span></button>
            </div>

            <div class="dt-actions">
                <div class="dt-search-wrapper">
                    <i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; color: #71717a; z-index: 10;"></i>
                    <input type="text" id="alt-user-search" class="dt-search-input" placeholder="Alt kullanıcı ara..." onkeyup="searchAltTable()">
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="table-container">
            <table class="data-table" id="alt-users-table">
                <thead style="background: #fafafa; border-bottom: 1px solid #e4e4e7;">
                    <tr>
                        <th class="sortable" onclick="sortAltTable(0)" style="width: 80px;">ID <i data-lucide="chevron-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortAltTable(1)">Alt Kullanıcı <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortAltTable(2)">Kullanıcı Adı <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortAltTable(3)">Bağlı Olduğu Abone <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortAltTable(4)">Kayıt Tarihi <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($altKullanicilar as $user): ?>
                    <tr class="alt-user-row">
                        <td style="color: #71717a; font-family: monospace; font-size: 0.75rem;">#<?php echo $user->id; ?></td>
                        <td class="alt-user-name" style="font-weight: 600;"><?php echo $user->adi_soyadi ?? '-'; ?></td>
                        <td class="alt-user-username" style="color: #3f3f46; font-size: 0.8125rem;">@<?php echo $user->kullanici_adi; ?></td>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.875rem; font-weight: 500; color: #18181b;"><?php echo $user->admin_ad ?? $user->admin_username; ?></span>
                                <span style="font-size: 0.75rem; color: #a1a1aa;">ID: #<?php echo $user->admin_id; ?></span>
                            </div>
                        </td>
                        <td style="color: #71717a; font-size: 0.8125rem;"><?php echo date('d.m.Y', strtotime($user->kayit_tarihi)); ?></td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 0.25rem;">
                                <button class="btn btn-ghost btn-sm" title="Giriş Yap" style="color: #2563eb;"><i data-lucide="external-link" style="width: 14px;"></i></button>
                                <button class="btn btn-ghost btn-sm" title="Sil" style="color: #ef4444;"><i data-lucide="trash-2" style="width: 14px;"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($altKullanicilar)): ?>
                    <tr>
                        <td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">Henüz kayıtlı alt kullanıcı bulunmuyor.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Data Table Footer -->
        <div class="dt-footer">
            <div>
                Toplam <b><?php echo count($altKullanicilar); ?></b> kayıttan 1-<?php echo count($altKullanicilar); ?> arası gösteriliyor
            </div>
            <div class="dt-pagination">
                <button class="dt-page-btn" disabled><i data-lucide="chevron-left" style="width: 14px;"></i></button>
                <button class="dt-page-btn active" style="background: #18181b; color: white; border-color: #18181b;">1</button>
                <button class="dt-page-btn"><i data-lucide="chevron-right" style="width: 14px;"></i></button>
            </div>
        </div>
    </div>
</div>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }

    function searchAltTable() {
        App.DataTable.search('alt-user-search', '.alt-user-row', '.alt-user-name', '.alt-user-username');
    }

    function sortAltTable(n) {
        App.DataTable.sort('alt-users-table', n);
    }
</script>
