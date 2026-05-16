<?php
require_once __DIR__ . '/../../autoload.php';
$db = \Core\Database::getInstance()->getConnection();
$adminId = $_GET['admin_id'] ?? 0;

$sql = "SELECT k.*, 
               a.adi_soyadi as admin_ad, a.kullanici_adi as admin_username,
               e.adi_soyadi as ekleyen_ad, e.kullanici_adi as ekleyen_username
        FROM kullanicilar k 
        LEFT JOIN kullanicilar a ON k.admin_id = a.id 
        LEFT JOIN kullanicilar e ON k.ekleyen_id = e.id
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

// Ana kullanıcıları getir (modal için)
$stmtMain = $db->prepare("SELECT id, adi_soyadi, kullanici_adi FROM kullanicilar WHERE admin_id = 0 AND (silinme_tarihi IS NULL OR silinme_tarihi = '') ORDER BY adi_soyadi ASC");
$stmtMain->execute();
$mainUsers = $stmtMain->fetchAll(PDO::FETCH_OBJ);
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
                    Kullanıcılara bağlı çalışan alt kullanıcı hesapları.
                <?php endif; ?>
            </p>
        </div>
        <button class="btn" style="background: #18181b; color: white;" onclick="document.getElementById('add-alt-user-modal').showModal()">
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
                        <th class="sortable" onclick="sortAltTable(2)">Bağlı Olduğu Kullanıcı <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortAltTable(3)">Ekleyen Kullanıcı <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortAltTable(4)">Eklenme Tarihi <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($altKullanicilar as $user): ?>
                    <tr class="alt-user-row">
                        <td style="color: #71717a; font-family: monospace; font-size: 0.75rem;">#<?php echo $user->id; ?></td>
                        <td class="alt-user-name">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600; cursor: pointer; color: #18181b;" 
                                      onclick="openEditAltModal(this)"
                                      data-id="<?php echo $user->id; ?>"
                                      data-name="<?php echo htmlspecialchars($user->adi_soyadi); ?>"
                                      data-username="<?php echo htmlspecialchars($user->kullanici_adi); ?>"
                                      data-email="<?php echo htmlspecialchars($user->email); ?>"
                                      data-admin-id="<?php echo $user->admin_id; ?>"
                                      data-admin-name="<?php echo htmlspecialchars($user->admin_ad ?? $user->admin_username); ?>">
                                    <?php echo $user->adi_soyadi ?? '-'; ?>
                                </span>
                                <span style="color: #71717a; font-size: 0.75rem;">@<?php echo $user->kullanici_adi; ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.875rem; font-weight: 500; color: #18181b;"><?php echo $user->admin_ad ?? $user->admin_username; ?></span>
                                <span style="font-size: 0.75rem; color: #a1a1aa;">ID: #<?php echo $user->admin_id; ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.875rem; font-weight: 500; color: #18181b;"><?php echo $user->ekleyen_ad ?? ($user->ekleyen_id ? 'Admin' : 'Sistem'); ?></span>
                                <?php if ($user->ekleyen_id): ?>
                                <span style="font-size: 0.75rem; color: #a1a1aa;">@<?php echo $user->ekleyen_username; ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="color: #71717a; font-size: 0.8125rem;"><?php echo date('d.m.Y H:i', strtotime($user->kayit_tarihi)); ?></td>
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
            <div class="dt-info">Yükleniyor...</div>
            <div class="dt-pagination-actions"></div>
        </div>
    </div>
</div>

<!-- Add Alt User Modal -->
<dialog id="add-alt-user-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
    <div style="padding: 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="user-plus" style="width: 20px; color: #18181b;"></i> Yeni Alt Kullanıcı Ekle
        </h2>
        <button onclick="document.getElementById('add-alt-user-modal').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
    </div>
    <form id="add-alt-user-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); submitAddAltUser(this);">
        <div class="form-group">
            <label class="form-label">Ad Soyad</label>
            <div class="input-icon-wrapper">
                <i data-lucide="user" class="input-icon"></i>
                <input type="text" name="adi_soyadi" class="form-input" placeholder="Örn: Ahmet Yılmaz" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Kullanıcı Adı</label>
            <div class="input-icon-wrapper">
                <i data-lucide="at-sign" class="input-icon"></i>
                <input type="text" name="kullanici_adi" class="form-input" placeholder="ahmet_yilmaz" required onkeyup="sanitizeUsername(this)">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">E-posta Adresi</label>
            <div class="input-icon-wrapper">
                <i data-lucide="mail" class="input-icon"></i>
                <input type="email" name="email" class="form-input" placeholder="ahmet@example.com" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Şifre</label>
            <div class="input-icon-wrapper">
                <i data-lucide="key" class="input-icon"></i>
                <input type="password" name="sifre" class="form-input" placeholder="••••••••" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Bağlı Olacağı Kullanıcı (Ana Hesap)</label>
            <div class="custom-select" id="parent-user-select" data-placement="top">
                <input type="hidden" name="admin_id" value="<?php echo $adminId; ?>" required>
                <div class="select-trigger">
                    <i data-lucide="users" style="width: 16px; color: #71717a;"></i>
                    <span class="select-label">
                        <?php 
                        if ($adminId > 0) {
                            foreach ($mainUsers as $u) {
                                if ($u->id == $adminId) {
                                    echo htmlspecialchars($u->adi_soyadi ?: $u->kullanici_adi);
                                    break;
                                }
                            }
                        } else {
                            echo "Ana kullanıcı seçin...";
                        }
                        ?>
                    </span>
                    <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                </div>
                <div class="select-popover" popover="manual">
                    <header>
                        <i data-lucide="search" style="width: 14px; color: #a1a1aa;"></i>
                        <input type="text" class="select-search" placeholder="Kullanıcı ara...">
                    </header>
                    <div class="select-options">
                        <?php foreach ($mainUsers as $u): ?>
                        <div class="select-option <?php echo ($adminId == $u->id) ? 'selected' : ''; ?>" data-value="<?php echo $u->id; ?>">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($u->adi_soyadi ?: $u->kullanici_adi); ?></span>
                                <span style="font-size: 0.7rem; color: #71717a;">@<?php echo $u->kullanici_adi; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
            <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('add-alt-user-modal').close()">Vazgeç</button>
            <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">Alt Kullanıcı Oluştur</button>
        </div>
    </form>
</dialog>

<!-- Edit Alt User Modal -->
<dialog id="edit-alt-user-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
    <div style="padding: 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="edit-3" style="width: 20px; color: #18181b;"></i> Alt Kullanıcı Düzenle
        </h2>
        <button onclick="document.getElementById('edit-alt-user-modal').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
    </div>
    <form id="edit-alt-user-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); submitEditAltUser(this);">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-group">
            <label class="form-label">Ad Soyad</label>
            <div class="input-icon-wrapper">
                <i data-lucide="user" class="input-icon"></i>
                <input type="text" name="adi_soyadi" id="edit-name" class="form-input" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Kullanıcı Adı</label>
            <div class="input-icon-wrapper">
                <i data-lucide="at-sign" class="input-icon"></i>
                <input type="text" name="kullanici_adi" id="edit-username" class="form-input" required onkeyup="sanitizeUsername(this)">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">E-posta Adresi</label>
            <div class="input-icon-wrapper">
                <i data-lucide="mail" class="input-icon"></i>
                <input type="email" name="email" id="edit-email" class="form-input" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Şifre (Boş bırakılırsa değişmez)</label>
            <div class="input-icon-wrapper">
                <i data-lucide="key" class="input-icon"></i>
                <input type="password" name="sifre" class="form-input" placeholder="••••••••">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Bağlı Olacağı Kullanıcı (Ana Hesap)</label>
            <div class="custom-select" id="edit-parent-user-select" data-placement="top">
                <input type="hidden" name="admin_id" id="edit-admin-id" required>
                <div class="select-trigger">
                    <i data-lucide="users" style="width: 16px; color: #71717a;"></i>
                    <span class="select-label" id="edit-admin-label">Ana kullanıcı seçin...</span>
                    <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                </div>
                <div class="select-popover" popover="manual">
                    <header>
                        <i data-lucide="search" style="width: 14px; color: #a1a1aa;"></i>
                        <input type="text" class="select-search" placeholder="Kullanıcı ara...">
                    </header>
                    <div class="select-options">
                        <?php foreach ($mainUsers as $u): ?>
                        <div class="select-option" data-value="<?php echo $u->id; ?>">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($u->adi_soyadi ?: $u->kullanici_adi); ?></span>
                                <span style="font-size: 0.7rem; color: #71717a;">@<?php echo $u->kullanici_adi; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
            <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('edit-alt-user-modal').close()">Vazgeç</button>
            <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">Değişiklikleri Kaydet</button>
        </div>
    </form>
</dialog>

<script>
    // Username Sanitization
    function sanitizeUsername(input) {
        let val = input.value;
        val = val.toLowerCase()
                 .replace(/ı/g, 'i')
                 .replace(/ğ/g, 'g')
                 .replace(/ü/g, 'u')
                 .replace(/ş/g, 's')
                 .replace(/ö/g, 'o')
                 .replace(/ç/g, 'c')
                 .replace(/[^a-z0-9_.]/g, '');
        input.value = val;
    }

    document.querySelectorAll('input[name="username"]').forEach(input => {
        input.addEventListener('keyup', () => sanitizeUsername(input));
        input.addEventListener('blur', () => sanitizeUsername(input));
    });

    if (window.lucide) {
        lucide.createIcons();
    }

    function searchAltTable() {
        App.DataTable.search('alt-user-search', '.alt-user-row', '.alt-user-name', '.alt-user-username');
    }

    function sortAltTable(n) {
        App.DataTable.sort('alt-users-table', n);
    }

    async function submitAddAltUser(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> İşleniyor...';

        try {
            const formData = new FormData(form);
            // Action is handled by the script itself now

            const response = await fetch('ajax_alt_kullanici_kaydet.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                App.toast('success', 'Başarılı', result.message);
                document.getElementById('add-alt-user-modal').close();
                form.reset();
                setTimeout(() => App.refreshContent(), 500);
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            App.toast('error', 'Hata', 'Bir hata oluştu.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function openEditAltModal(el) {
        const data = el.dataset;
        document.getElementById('edit-id').value = data.id;
        document.getElementById('edit-name').value = data.name;
        document.getElementById('edit-username').value = data.username;
        document.getElementById('edit-email').value = data.email;
        document.getElementById('edit-admin-id').value = data.adminId;
        document.getElementById('edit-admin-label').innerText = data.adminName;

        // Sync custom select
        const select = document.getElementById('edit-parent-user-select');
        const options = select.querySelectorAll('.select-option');
        options.forEach(opt => {
            if (opt.dataset.value == data.adminId) opt.classList.add('selected');
            else opt.classList.remove('selected');
        });

        document.getElementById('edit-alt-user-modal').showModal();
    }

    async function submitEditAltUser(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> Kaydediliyor...';

        try {
            const formData = new FormData(form);

            const response = await fetch('ajax_alt_kullanici_kaydet.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                App.toast('success', 'Başarılı', result.message);
                document.getElementById('edit-alt-user-modal').close();
                setTimeout(() => App.refreshContent(), 500);
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            App.toast('error', 'Hata', 'Bir hata oluştu.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // Close on Outside Click
    window.onclick = function(event) {
        const addModal = document.getElementById('add-alt-user-modal');
        const editModal = document.getElementById('edit-alt-user-modal');
        if (event.target == addModal) addModal.close();
        if (event.target == editModal) editModal.close();
    }
</script>
