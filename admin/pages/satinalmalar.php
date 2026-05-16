<?php
require_once __DIR__ . '/../../autoload.php';
$db = \Core\Database::getInstance()->getConnection();

// Fetch Packages (for selection)
$paketModel = new \Models\AbonelikPaketModel();
$paketler = $paketModel->all();

// Fetch Purchases
$stmt = $db->prepare("SELECT ka.*, k.adi_soyadi as ad_soyad, k.kullanici_adi, k.email, ka.paket_id as current_package_id, ap.ad as paket_adi, ap.fiyat 
                      FROM kullanici_abonelikleri ka 
                      LEFT JOIN kullanicilar k ON ka.kullanici_id = k.id 
                      LEFT JOIN abonelik_paketleri ap ON ka.paket_id = ap.id 
                      ORDER BY ka.id DESC");
$stmt->execute();
$abonelikler = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch All Users (for Add Purchase modal)
$userStmt = $db->prepare("SELECT id, adi_soyadi, kullanici_adi, email FROM kullanicilar WHERE admin_id = 0 AND (silinme_tarihi IS NULL OR silinme_tarihi = '') ORDER BY adi_soyadi ASC");
$userStmt->execute();
$kullanicilar = $userStmt->fetchAll(PDO::FETCH_OBJ);

// Pre-process users to fix "0" or empty names
foreach ($kullanicilar as $user) {
    if (empty($user->adi_soyadi) || $user->adi_soyadi == '0') {
        $user->display_name = $user->kullanici_adi;
    } else {
        $user->display_name = $user->adi_soyadi;
    }
}
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Satın Almalar & Abonelikler</h1>
            <p style="color: #71717a; font-size: 0.875rem; margin-top: 0.25rem;">Tüm abonelik işlemlerini ve satın alma geçmişini buradan takip edebilirsiniz.</p>
        </div>
        <button class="btn" style="background: #18181b; color: white;" onclick="document.getElementById('add-purchase-modal').showModal()">
            <i data-lucide="shopping-bag" style="width: 16px;"></i> Yeni İşlem Ekle
        </button>
    </div>

    <div class="card dt-container" style="padding: 0; overflow: hidden; border-radius: 12px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); flex: 1; display: flex; flex-direction: column;">
        <div class="dt-header">
            <div style="font-weight: 600;">İşlem Listesi</div>
            <div class="dt-actions">
                <div class="dt-search-wrapper">
                    <i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; color: #71717a; z-index: 10;"></i>
                    <input type="text" id="purchase-search" class="dt-search-input" placeholder="Satın alma ara..." onkeyup="App.DataTable.search('purchase-search', '.purchase-row', '.user-cell', '.package-cell')">
                </div>
            </div>
        </div>
        
        <div class="table-container" style="flex: 1; min-height: 0;">
            <table class="data-table" id="purchases-table" style="min-width: 900px;">
                <thead style="background: #fafafa; border-bottom: 1px solid #e4e4e7;">
                    <tr>
                        <th class="sortable" style="width: 80px;">ID</th>
                        <th class="sortable" style="width: 250px;">Kullanıcı</th>
                        <th class="sortable" style="width: 150px;">Paket</th>
                        <th class="sortable" style="width: 180px;">Başlangıç / Bitiş</th>
                        <th class="sortable" style="width: 120px;">Tutar</th>
                        <th style="width: 120px;">Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($abonelikler as $row): ?>
                    <tr class="purchase-row">
                        <td style="color: #71717a; font-family: monospace; font-size: 0.75rem;">#<?php echo $row->id; ?></td>
                        <td class="user-cell">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600; color: #18181b; cursor: pointer;" 
                                      onclick="openEditSubscriberModal(this)"
                                      data-id="<?php echo $row->kullanici_id; ?>"
                                      data-name="<?php echo htmlspecialchars($row->ad_soyad ?? $row->kullanici_adi); ?>"
                                      data-email="<?php echo htmlspecialchars($row->email); ?>"
                                      data-package="<?php echo $row->current_package_id; ?>">
                                    <?php echo !empty($row->ad_soyad) ? $row->ad_soyad : $row->kullanici_adi; ?>
                                </span>
                                <span style="font-size: 0.75rem; color: #71717a;">@<?php echo $row->kullanici_adi; ?></span>
                            </div>
                        </td>
                        <td class="package-cell">
                            <span class="badge badge-secondary" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-weight: 600;">
                                <?php echo $row->paket_adi; ?>
                            </span>
                        </td>
                        <td style="font-size: 0.8125rem; color: #3f3f46;">
                            <div style="display: flex; flex-direction: column;">
                                <span><?php echo date('d.m.Y', strtotime($row->baslangic_tarihi)); ?></span>
                                <span style="font-size: 0.75rem; color: #a1a1aa;"><?php echo date('d.m.Y', strtotime($row->bitis_tarihi)); ?></span>
                            </div>
                        </td>
                        <td style="font-weight: 700; color: #18181b;">₺<?php echo number_format($row->fiyat ?? 0, 0); ?></td>
                        <td>
                            <?php if ($row->durum == 'aktif'): ?>
                                <span class="badge badge-success" style="background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; display: inline-flex; align-items: center; gap: 0.375rem;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e;"></span>
                                    Aktif
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary" style="background: #f4f4f5; color: #71717a; border: 1px solid #e4e4e7;"><?php echo ucfirst($row->durum); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 0.25rem;">
                                <button class="btn btn-ghost btn-sm" title="Sil" style="color: #71717a;" onclick="deletePurchase(<?php echo $row->id; ?>)"><i data-lucide="trash-2" style="width: 14px;"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="dt-footer">
            <div>Toplam <b><?php echo count($abonelikler); ?></b> işlem kayıtlı.</div>
            <div class="dt-pagination">
                <button class="dt-page-btn" disabled><i data-lucide="chevron-left" style="width: 14px;"></i></button>
                <button class="dt-page-btn active" style="background: #18181b; color: white; border-color: #18181b;">1</button>
                <button class="dt-page-btn"><i data-lucide="chevron-right" style="width: 14px;"></i></button>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <dialog id="add-purchase-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="shopping-cart" style="width: 18px;"></i> Yeni İşlem Ekle
            </h2>
            <button onclick="this.closest('dialog').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="add-purchase-form" style="padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem;" onsubmit="event.preventDefault(); submitAddPurchase(this);">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Kullanıcı Seçin</label>
                <div class="custom-select" id="subscriber-select">
                    <button type="button" class="select-trigger btn-outline">
                        <span class="select-label truncate">Kullanıcı seçiniz...</span>
                        <i data-lucide="chevrons-up-down" style="width: 14px; opacity: 0.5;"></i>
                    </button>
                    <div class="select-popover" popover="manual">
                        <header>
                            <i data-lucide="search" style="width: 14px; opacity: 0.5;"></i>
                            <input type="text" class="select-search" placeholder="Kullanıcı ara..." autocomplete="off">
                        </header>
                        <div class="select-options">
                            <?php foreach ($kullanicilar as $user): ?>
                                <div class="select-option" data-value="<?php echo $user->id; ?>">
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500;"><?php echo $user->display_name; ?></span>
                                        <span style="font-size: 0.75rem; color: #71717a;"><?php echo $user->email; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <input type="hidden" name="kullanici_id" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Paket Seçin</label>
                <div class="custom-select" id="package-select">
                    <button type="button" class="select-trigger btn-outline">
                        <span class="select-label truncate">Paket seçiniz...</span>
                        <i data-lucide="chevrons-up-down" style="width: 14px; opacity: 0.5;"></i>
                    </button>
                    <div class="select-popover" popover="manual">
                        <header>
                            <i data-lucide="search" style="width: 14px; opacity: 0.5;"></i>
                            <input type="text" class="select-search" placeholder="Paket ara..." autocomplete="off">
                        </header>
                        <div class="select-options">
                            <?php foreach ($paketler as $p): ?>
                                <div class="select-option" data-value="<?php echo $p->id; ?>"><?php echo $p->ad; ?> (₺<?php echo number_format($p->fiyat, 0); ?>)</div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <input type="hidden" name="paket_id" required onchange="handlePackageChange(this.value)">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Firma Hakkı</label>
                    <input type="number" name="firma_hakki" id="add-firma-hakki" class="form-input" placeholder="30" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kullanıcı Hakkı</label>
                    <input type="number" name="alt_kullanici_hakki" id="add-alt-kullanici-hakki" class="form-input" placeholder="3" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="date" name="baslangic_tarihi" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="date" name="bitis_tarihi" class="form-input" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="this.closest('dialog').close()">Vazgeç</button>
                <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">İşlemi Kaydet</button>
            </div>
        </form>
    </dialog>

    <dialog id="edit-subscriber-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="user-cog" style="width: 20px;"></i> Kullanıcı Düzenle
            </h2>
            <button onclick="this.closest('dialog').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="edit-subscriber-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); if(App.validateForm('edit-subscriber-form')) { App.toast('success', 'Güncellendi', 'Kullanıcı bilgileri başarıyla güncellendi.'); this.closest('dialog').close(); }">
            <input type="hidden" id="sub-edit-id">
            <div class="form-group">
                <label class="form-label">Ad Soyad / Firma</label>
                <input type="text" id="sub-edit-name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">E-posta Adresi</label>
                <input type="email" id="sub-edit-email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Abonelik Paketi</label>
                <select id="sub-edit-package" class="form-input" required>
                    <?php foreach ($paketler as $p): ?>
                        <option value="<?php echo $p->id; ?>"><?php echo $p->ad; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="this.closest('dialog').close()">Vazgeç</button>
                <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">Güncelle</button>
            </div>
        </form>
    </dialog>

    <dialog id="confirm-delete-modal" class="card" style="width: 400px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; text-align: center;">
            <div style="width: 48px; height: 48px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <i data-lucide="alert-triangle" style="width: 24px;"></i>
            </div>
            <h2 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem;">Kaydı Sil?</h2>
            <p style="color: #71717a; font-size: 0.875rem;">Bu satın alma kaydını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
        </div>
        <div style="padding: 1rem 1.5rem; background: #fafafa; border-top: 1px solid #e4e4e7; display: flex; gap: 0.75rem; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <button type="button" class="btn btn-outline" style="flex: 1;" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="button" id="confirm-delete-btn" class="btn" style="flex: 1; background: #ef4444; color: white;">Evet, Sil</button>
        </div>
    </dialog>
</div>

<script>
    const availablePackages = <?php echo json_encode($paketler); ?>;

    if (window.lucide) {
        lucide.createIcons();
    }

    function handlePackageChange(paketId) {
        const paket = availablePackages.find(p => p.id == paketId);
        if (paket) {
            document.getElementById('add-firma-hakki').value = paket.firma_hakki || 30;
            document.getElementById('add-alt-kullanici-hakki').value = paket.alt_kullanici_hakki || 3;
        }
    }

    async function submitAddPurchase(form) {
        if (!App.validateForm('add-purchase-form')) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner" style="width: 14px; height: 14px;"></div> İşleniyor...';

        try {
            const formData = new FormData(form);
            formData.append('action', 'admin-kullanici-satin-al');

            const response = await fetch('admin-kullanici-satin-al', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json();

            if (result.success || result.status === 'success') {
                App.toast('success', 'Başarılı', result.message || 'Satın alma işlemi başarıyla kaydedildi.');
                document.getElementById('add-purchase-modal').close();
                form.reset();
                setTimeout(() => App.refreshContent(), 500);
            } else {
                App.toast('error', 'Hata', result.message || 'Bir hata oluştu.');
            }
        } catch (error) {
            console.error('Error:', error);
            App.toast('error', 'Hata', 'Sunucuya bağlanılamadı.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function openEditSubscriberModal(el) {
        document.getElementById('sub-edit-id').value = el.dataset.id;
        document.getElementById('sub-edit-name').value = el.dataset.name;
        document.getElementById('sub-edit-email').value = el.dataset.email;
        document.getElementById('sub-edit-package').value = el.dataset.package;
        document.getElementById('edit-subscriber-modal').showModal();
    }

    function deletePurchase(id) {
        const modal = document.getElementById('confirm-delete-modal');
        const confirmBtn = document.getElementById('confirm-delete-btn');
        
        modal.showModal();
        
        confirmBtn.onclick = async () => {
            const originalText = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<div class="spinner" style="width: 14px; height: 14px;"></div>';

            try {
                const formData = new FormData();
                formData.append('id', id);

                const response = await fetch('satinalma-sil', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const result = await response.json();

                if (result.success) {
                    App.toast('success', 'Başarılı', result.message);
                    modal.close();
                    setTimeout(() => App.refreshContent(), 500);
                } else {
                    App.toast('error', 'Hata', result.message || 'Bir hata oluştu.');
                }
            } catch (error) {
                console.error('Error:', error);
                App.toast('error', 'Hata', 'Sunucuya bağlanılamadı.');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
        };
    }
</script>
