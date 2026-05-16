<?php
require_once __DIR__ . '/../../autoload.php';
$userModel = new \Models\UserModel();
$kullanicilar = $userModel->AktifKullanicilar();
$paketModel = new \Models\AbonelikPaketModel();
$paketler = $paketModel->all();
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Kullanıcılar</h1>
            <p style="color: #71717a; font-size: 0.875rem; margin-top: 0.25rem;">Sisteme kayıtlı ana kullanıcılar ve firma yöneticileri.</p>
        </div>
        <button class="btn" style="background: #18181b; color: white;" onclick="document.getElementById('add-subscriber-modal').showModal()">
            <i data-lucide="user-plus" style="width: 16px;"></i> Yeni Kullanıcı Ekle
        </button>
    </div>

    <div class="card dt-container" style="padding: 0; overflow: hidden; border-radius: 12px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
        <!-- Data Table Header -->
        <div class="dt-header">
            <div class="dt-tabs">
                <button class="dt-tab active" onclick="filterByStatus('all')">
                    Hepsi <span class="dt-tab-count"><?php echo count($kullanicilar); ?></span>
                </button>
                <button class="dt-tab" onclick="filterByStatus('active')">
                    <i data-lucide="check-circle" style="width: 14px;"></i> Aktif <span class="dt-tab-count"><?php echo count($kullanicilar); ?></span>
                </button>
                <button class="dt-tab" onclick="filterByStatus('passive')">
                    <i data-lucide="clock" style="width: 14px;"></i> Pasif <span class="dt-tab-count">0</span>
                </button>
            </div>

            <div class="dt-actions">
                <div class="dt-search-wrapper">
                    <i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; color: #71717a; z-index: 10;"></i>
                    <input type="text" id="subscriber-search" class="dt-search-input" placeholder="Her şeyi ara..." onkeyup="searchTable()">
                </div>
            </div>
        </div>

        <!-- Table Body -->
        <div class="table-container">
            <table class="data-table" id="subscribers-table">
                <thead style="background: #fafafa; border-bottom: 1px solid #e4e4e7;">
                    <tr>
                        <th class="sortable" onclick="sortTable(0)" style="width: 80px;">ID <i data-lucide="chevron-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortTable(1)">Kullanıcı / Firma <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortTable(2)">Paket / İletişim <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortTable(3)" style="width: 120px;">Yetki <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th>İşlem Yetkileri</th>
                        <th class="sortable" onclick="sortTable(5)" style="text-align: center;">Alt Kullanıcı <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th class="sortable" onclick="sortTable(6)">Kayıt Tarihi <i data-lucide="chevrons-up-down" class="sort-icon" style="width: 12px;"></i></th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kullanicilar as $user): 
                        $initials = mb_substr($user->ad_soyad ?? $user->kullanici_adi, 0, 2, 'UTF-8');
                        $roleBadge = 'badge-secondary';
                        $roleLabel = 'Kullanıcı';
                        
                        if ($user->role === 'superadmin') {
                            $roleBadge = 'badge-primary';
                            $roleLabel = 'Süper Admin';
                        } elseif ($user->role === 'admin') {
                            $roleBadge = 'badge-secondary';
                            $roleLabel = 'Admin';
                        }
                    ?>
                    <tr class="subscriber-row" data-id="<?php echo $user->id; ?>" data-status="active">
                        <td style="color: #71717a; font-family: monospace; font-size: 0.75rem;">#<?php echo $user->id; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #f4f4f5; color: #71717a; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; border: 1px solid #e4e4e7;">
                                    <?php echo strtoupper($initials); ?>
                                </div>
                                <div style="display: flex; flex-direction: column;">
                                    <span class="subscriber-name" style="font-weight: 600; color: #18181b; cursor: pointer;" 
                                          onclick="openEditModal(this)"
                                          data-id="<?php echo \App\Helper\Security::encrypt($user->id); ?>"
                                          data-name="<?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?>"
                                          data-username="<?php echo htmlspecialchars($user->kullanici_adi); ?>"
                                          data-email="<?php echo htmlspecialchars($user->email); ?>"
                                          data-role="<?php echo $user->role; ?>"
                                          data-package="<?php echo $user->current_paket_id; ?>"
                                          data-yetkiler="<?php echo htmlspecialchars($user->yetkiler ?? ''); ?>">
                                        <?php echo $user->adi_soyadi  ?: $user->kullanici_adi; ?>
                                    </span>
                                    <span style="font-size: 0.75rem; color: #71717a;">@<?php echo $user->kullanici_adi; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="subscriber-email-cell">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.875rem; font-weight: 600; color: #2563eb;"><?php echo $user->paket_adi ?? 'Paketsiz'; ?></span>
                                <span style="font-size: 0.75rem; color: #71717a;"><?php echo $user->email; ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo $roleBadge; ?>" style="font-size: 0.7rem;">
                                <?php echo $roleLabel; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                <?php 
                                    $yetkiler = (isset($user->yetkiler) && $user->yetkiler) ? explode(',', $user->yetkiler) : [];
                                    foreach ($yetkiler as $yetki) {
                                        $label = ($yetki == 'rapor_onay') ? 'Rapor Onay' : (($yetki == 'manuel_bildirim') ? 'Manuel Bildirim' : $yetki);
                                        echo '<span class="badge" style="background: #f4f4f5; color: #18181b; font-size: 0.7rem; border: 1px solid #e4e4e7;">' . $label . '</span>';
                                    }
                                    if (empty($yetkiler)) echo '<span style="color: #a1a1aa; font-size: 0.75rem;">-</span>';
                                ?>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <?php $encryptedId = \App\Helper\Security::encrypt($user->id); ?>
                            <a href="alt-kullanicilar?user=<?php echo $encryptedId; ?>" class="btn btn-ghost btn-sm nav-link" data-route="alt-kullanicilar?user=<?php echo $encryptedId; ?>" style="gap: 0.375rem; color: #18181b; font-weight: 600;">
                                <i data-lucide="users" style="width: 16px;"></i>
                                <?php echo $user->alt_kullanici_sayisi; ?>
                            </a>
                        </td>
                        <td style="color: #71717a; font-size: 0.8125rem;"><?php echo date('d.m.Y', strtotime($user->kayit_tarihi)); ?></td>
                        <td>
                            <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 0.375rem; background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e;"></span>
                                Aktif
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 0.25rem;">
                                <button class="btn btn-ghost btn-sm" title="Düzenle" 
                                        onclick="openEditModal(this)"
                                          data-id="<?php echo \App\Helper\Security::encrypt($user->id); ?>"
                                          data-name="<?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?>"
                                          data-username="<?php echo htmlspecialchars($user->kullanici_adi); ?>"
                                          data-email="<?php echo htmlspecialchars($user->email); ?>"
                                          data-role="<?php echo $user->role; ?>"
                                          data-package="<?php echo $user->current_paket_id; ?>"
                                          data-yetkiler="<?php echo htmlspecialchars($user->yetkiler ?? ''); ?>">
                                    <i data-lucide="edit-3" style="width: 14px;"></i>
                                </button>
                                <button class="btn btn-ghost btn-sm" title="Şifre Değiştir"><i data-lucide="key" style="width: 14px;"></i></button>
                                <button class="btn btn-ghost btn-sm" title="Sil" style="color: #ef4444;" 
                                        onclick="confirmDelete(<?php echo $user->id; ?>, '<?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?>')">
                                    <i data-lucide="trash-2" style="width: 14px;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Data Table Footer -->
        <div class="dt-footer">
            <div class="dt-info" style="font-size: 0.8125rem; color: #71717a;">
                Yükleniyor...
            </div>
            <div class="dt-pagination-actions"></div>
        </div>
    </div>
    
    <!-- Modals -->
    <dialog id="add-subscriber-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="user-plus" style="width: 20px; color: #18181b;"></i> Yeni Kullanıcı Ekle
            </h2>
            <button onclick="document.getElementById('add-subscriber-modal').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="add-subscriber-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); submitAddSubscriber(this);">
            <div class="form-group">
                <label class="form-label">Ad Soyad / Firma</label>
                <div class="input-icon-wrapper">
                    <i data-lucide="user" class="input-icon"></i>
                    <input type="text" name="adi_soyadi" class="form-input" placeholder="Örn: Mehmet Ali Gökmen" required>
                    <span class="form-error">Bu alan zorunludur.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kullanıcı Adı</label>
                <div class="input-icon-wrapper">
                    <i data-lucide="at-sign" class="input-icon"></i>
                    <input type="text" name="kullanici_adi" class="form-input" placeholder="kullaniciadi" onkeyup="sanitizeUsername(this)" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">E-posta Adresi</label>
                <div class="input-icon-wrapper">
                    <i data-lucide="mail" class="input-icon"></i>
                    <input type="email" name="email" class="form-input" placeholder="ornek@mail.com" required>
                    <span class="form-error">Geçerli bir e-posta adresi giriniz.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Abonelik Paketi</label>
                <div class="custom-select" id="add-package-select">
                    <input type="hidden" name="paket_id" required>
                    <div class="select-trigger">
                        <i data-lucide="package" style="width: 16px; color: #71717a;"></i>
                        <span class="select-label">Paket seçin...</span>
                        <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                    </div>
                    <div class="select-popover" popover="manual">
                        <header>
                            <i data-lucide="search" style="width: 14px; color: #a1a1aa;"></i>
                            <input type="text" class="select-search" placeholder="Paket ara...">
                        </header>
                        <div class="select-options">
                            <?php foreach ($paketler as $paket): ?>
                            <div class="select-option" data-value="<?php echo $paket->id; ?>">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;"><?php echo $paket->ad; ?></span>
                                    <span style="font-size: 0.7rem; color: #71717a;"><?php echo $paket->firma_hakki; ?> Firma / <?php echo $paket->sure; ?> Ay</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kullanıcı Rolü</label>
                <div class="custom-select" id="add-role-select">
                    <input type="hidden" name="role" value="admin" required>
                    <div class="select-trigger">
                        <i data-lucide="shield" style="width: 16px; color: #71717a;"></i>
                        <span class="select-label">Kullanıcı (Admin)</span>
                        <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                    </div>
                    <div class="select-popover" popover="manual">
                        <div class="select-options">
                            <div class="select-option selected" data-value="admin">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;">Kullanıcı (Admin)</span>
                                    <span style="font-size: 0.7rem; color: #71717a;">Standart ana kullanıcı</span>
                                </div>
                            </div>
                            <div class="select-option" data-value="user">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;">Standart Kullanıcı</span>
                                    <span style="font-size: 0.7rem; color: #71717a;">Kısıtlı yetkili kullanıcı</span>
                                </div>
                            </div>
                            <div class="select-option" data-value="superadmin">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;">Süper Admin</span>
                                    <span style="font-size: 0.7rem; color: #71717a;">Tüm paneli yönetebilir</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('add-subscriber-modal').close()">Vazgeç</button>
                <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">Kullanıcı Oluştur</button>
            </div>
        </form>
    </dialog>

    <dialog id="edit-subscriber-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="edit-3" style="width: 20px; color: #18181b;"></i> Kullanıcı Düzenle
            </h2>
            <button onclick="document.getElementById('edit-subscriber-modal').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="edit-subscriber-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); submitEditSubscriber(this);">
            <input type="hidden" id="edit-id" name="id">
            <div class="form-group">
                <label class="form-label">Ad Soyad / Firma</label>
                <div class="input-icon-wrapper">
                    <i data-lucide="user" class="input-icon"></i>
                    <input type="text" id="edit-name" name="adi_soyadi" class="form-input" required>
                    <span class="form-error">Bu alan zorunludur.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kullanıcı Adı</label>
                <div class="input-icon-wrapper">
                    <i data-lucide="at-sign" class="input-icon"></i>
                    <input type="text" id="edit-username" name="kullanici_adi" class="form-input" onkeyup="sanitizeUsername(this)" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">E-posta Adresi</label>
                <div class="input-icon-wrapper">
                    <i data-lucide="mail" class="input-icon"></i>
                    <input type="email" id="edit-email" name="email" class="form-input" required>
                    <span class="form-error">Geçerli bir e-posta adresi giriniz.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Abonelik Paketi</label>
                <div class="custom-select" id="package-select">
                    <input type="hidden" name="paket_id" id="edit-package">
                    <div class="select-trigger">
                        <i data-lucide="package" style="width: 16px; color: #71717a;"></i>
                        <span class="select-label">Paket seçin...</span>
                        <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                    </div>
                    <div class="select-popover" popover="manual">
                        <header>
                            <i data-lucide="search" style="width: 14px; color: #a1a1aa;"></i>
                            <input type="text" class="select-search" placeholder="Paket ara...">
                        </header>
                        <div class="select-options">
                            <?php foreach ($paketler as $paket): ?>
                            <div class="select-option" data-value="<?php echo $paket->id; ?>">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;"><?php echo $paket->ad; ?></span>
                                    <span style="font-size: 0.7rem; color: #71717a;"><?php echo $paket->firma_hakki; ?> Firma / <?php echo $paket->sure; ?> Ay</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kullanıcı Rolü</label>
                <div class="custom-select" id="edit-role-select">
                    <input type="hidden" name="role" required>
                    <div class="select-trigger">
                        <i data-lucide="shield" style="width: 16px; color: #71717a;"></i>
                        <span class="select-label">Rol seçin...</span>
                        <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                    </div>
                    <div class="select-popover" popover="manual">
                        <div class="select-options">
                            <div class="select-option" data-value="admin">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;">Kullanıcı (Admin)</span>
                                    <span style="font-size: 0.7rem; color: #71717a;">Standart ana kullanıcı</span>
                                </div>
                            </div>
                            <div class="select-option" data-value="user">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;">Standart Kullanıcı</span>
                                    <span style="font-size: 0.7rem; color: #71717a;">Kısıtlı yetkili kullanıcı</span>
                                </div>
                            </div>
                            <div class="select-option" data-value="superadmin">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;">Süper Admin</span>
                                    <span style="font-size: 0.7rem; color: #71717a;">Tüm paneli yönetebilir</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('edit-subscriber-modal').close()">Vazgeç</button>
                <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">Güncelle</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="alert-dialog" class="card" style="width: 400px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <header>
                <h2 id="alert-dialog-title" style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; color: #ef4444;">
                    <i data-lucide="alert-triangle" style="width: 20px;"></i> Emin misiniz?
                </h2>
                <p id="alert-dialog-description" style="font-size: 0.875rem; color: #71717a; line-height: 1.5;">
                    <b id="delete-user-name"></b> isimli kullanıcıyı silmek istediğinize emin misiniz? <br><br>
                    Bu işlem geri alınamaz. Bu kullanıcı ile birlikte tüm <b>alt kullanıcılar</b> ve <b>işyerleri</b> de kalıcı olarak silinecektir.
                </p>
            </header>

            <footer style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('alert-dialog').close()">Vazgeç</button>
                <button class="btn" id="confirm-delete-btn" style="flex: 1; background: #ef4444; color: white; border-color: #ef4444; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    Silmeyi Onayla
                </button>
            </footer>
        </div>
    </dialog>
</div>

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

    // Search Table using Global App Helper
    function searchTable() {
        App.DataTable.search('subscriber-search', '.subscriber-row', '.subscriber-name', '.subscriber-email-cell');
    }

    // Sort Table using Global App Helper
    function sortTable(n) {
        App.DataTable.sort('subscribers-table', n);
    }

    // Submit Add Subscriber
    async function submitAddSubscriber(form) {
        if (!App.validateForm('add-subscriber-form')) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> İşleniyor...';

        try {
            const formData = new FormData(form);
            formData.append('action', 'admin-kullanici-ekle');

            const apiPath = 'admin-kullanici-ekle';

            const response = await fetch(apiPath, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('Server response was not JSON:', text);
                throw new Error('Sunucu geçersiz bir yanıt döndürdü.');
            }

            if (result.status === 'success') {
                App.toast('success', 'Başarılı', result.message);
                document.getElementById('add-subscriber-modal').close();
                form.reset();
                setTimeout(() => App.refreshContent(), 500);
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            App.toast('error', 'Hata', error.message || 'Bir ağ hatası oluştu.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // Submit Edit Subscriber
    async function submitEditSubscriber(form) {
        if (!App.validateForm('edit-subscriber-form')) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> Güncelleniyor...';

        try {
            const formData = new FormData(form);
            formData.append('action', 'admin-kullanici-guncelle');

            const apiPath = 'admin-kullanici-ekle'; 

            const response = await fetch(apiPath, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('Server response was not JSON:', text);
                throw new Error('Sunucu geçersiz bir yanıt döndürdü.');
            }

            if (result.status === 'success') {
                App.toast('success', 'Başarılı', result.message);
                document.getElementById('edit-subscriber-modal').close();
                setTimeout(() => App.refreshContent(), 500);
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            App.toast('error', 'Hata', error.message || 'Bir ağ hatası oluştu.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // Filter by Status
    function filterByStatus(status) {
        document.querySelectorAll('.dt-tab').forEach(tab => tab.classList.remove('active'));
        event.currentTarget.classList.add('active');

        const rows = document.querySelectorAll('.subscriber-row');
        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = "";
            } else {
                row.style.display = (row.getAttribute('data-status') === status) ? "" : "none";
            }
        });
    }

    // Open Edit Modal with Data
    function openEditModal(el) {
        const id = el.getAttribute('data-id');
        const name = el.getAttribute('data-name');
        const username = el.getAttribute('data-username');
        const email = el.getAttribute('data-email');
        const packageId = el.getAttribute('data-package');
        const role = el.getAttribute('data-role') || 'admin';

        document.getElementById('edit-id').value = id;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-username').value = username;
        document.getElementById('edit-email').value = email;
        
        // Custom Select'i güncelle (Paket)
        const select = document.getElementById('package-select');
        if (select) {
            const hiddenInput = select.querySelector('input[type="hidden"]');
            const label = select.querySelector('.select-label');
            const options = select.querySelectorAll('.select-option');
            
            hiddenInput.value = packageId;
            options.forEach(opt => {
                if (opt.dataset.value == packageId) {
                    label.textContent = opt.querySelector('span').textContent;
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        }
        
        // Role Select'i güncelle
        const roleSelect = document.getElementById('edit-role-select');
        if (roleSelect) {
            const hiddenInput = roleSelect.querySelector('input[type="hidden"]');
            const label = roleSelect.querySelector('.select-label');
            const options = roleSelect.querySelectorAll('.select-option');
            
            hiddenInput.value = role;
            options.forEach(opt => {
                if (opt.dataset.value === role) {
                    label.textContent = opt.querySelector('span').textContent;
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        }

        document.getElementById('edit-subscriber-modal').showModal();
    }

    // Delete Logic
    var userIdToDelete = null;

    function confirmDelete(id, name) {
        userIdToDelete = id;
        document.getElementById('delete-user-name').innerText = name;
        document.getElementById('alert-dialog').showModal();
    }

    document.getElementById('confirm-delete-btn').addEventListener('click', function() {
        if (!userIdToDelete) return;

        const btn = this;
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner" style="width: 14px; height: 14px;"></div> İşleniyor...';

        const formData = new FormData();
        formData.append('id', userIdToDelete);

        fetch('kullanici-sil', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            if (!response.ok) {
                const text = await response.text();
                throw new Error(text || 'Sunucu hatası');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                App.toast('success', 'Başarılı', data.message);
                document.getElementById('alert-dialog').close();
                const row = document.querySelector(`.subscriber-row[data-id="${userIdToDelete}"]`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        row.remove();
                        updateTableStats();
                    }, 300);
                } else {
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                App.toast('error', 'Hata', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            App.toast('error', 'Hata', 'Bir hata oluştu.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            userIdToDelete = null;
        });
    });

    function updateTableStats() {
        const tableId = 'subscribers-table';
        const table = document.getElementById(tableId);
        if (!table) return;

        // Update pagination & Info text
        if (App.TablePagination) {
            App.TablePagination.init(tableId);
        }

        // Update Tab Counts
        const rows = table.querySelectorAll('tbody tr.subscriber-row');
        const totalCount = rows.length;
        
        // Update Hepsi and Aktif tab counts (assuming deleted users are removed from both)
        const tabs = document.querySelectorAll('.dt-tab-count');
        if (tabs.length >= 2) {
            tabs[0].textContent = totalCount; // Hepsi
            tabs[1].textContent = totalCount; // Aktif
        }
    }

</script>
