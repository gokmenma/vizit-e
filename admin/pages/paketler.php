<?php
require_once __DIR__ . '/../../autoload.php';
$db = \Core\Database::getInstance()->getConnection();

// Fetch Packages
$paketModel = new \Models\AbonelikPaketModel();
$paketler = $paketModel->all();
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Paket Tanımları</h1>
            <p style="color: #71717a; font-size: 0.875rem; margin-top: 0.25rem;">Abonelik paketlerini oluşturun ve özelliklerini belirleyin.</p>
        </div>
        <button id="add-btn" class="btn btn-primary" onclick="document.getElementById('add-package-modal').showModal()">
            <i data-lucide="plus" style="width: 16px;"></i> Yeni Paket Oluştur
        </button>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <?php foreach ($paketler as $paket): ?>
        <div class="card" style="display: flex; flex-direction: column; position: relative; overflow: hidden; padding: 1.5rem;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary);"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--foreground);"><?php echo $paket->ad; ?></h3>
                    <p style="font-size: 0.875rem; color: var(--muted-foreground);"><?php echo $paket->aciklama ?? 'Tüm temel özellikler dahil.'; ?></p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--foreground);">₺<?php echo number_format($paket->fiyat, 0); ?></div>
                    <div style="font-size: 0.75rem; color: var(--muted-foreground);"><?php echo $paket->sure ?? 1; ?> Ay</div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin: 1.5rem 0; padding: 1rem; background: var(--muted); border-radius: 8px; border: 1px solid var(--border);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.875rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="users" style="width: 14px;"></i> Firma (İşyeri) Limiti
                    </span>
                    <span style="font-weight: 600;"><?php echo $paket->firma_hakki; ?></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.875rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="user-plus" style="width: 14px;"></i> Alt Kullanıcı Limiti
                    </span>
                    <span style="font-weight: 600;"><?php echo $paket->alt_kullanici_hakki ?? 0; ?></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.875rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="calendar" style="width: 14px;"></i> Abonelik Süresi
                    </span>
                    <span style="font-weight: 600;"><?php echo $paket->sure ?? 1; ?> Ay</span>
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; margin-top: auto;">
                <button class="btn btn-outline" style="flex: 1; height: 2.25rem;" onclick="openEditPackageModal(this)" 
                        data-id="<?php echo $paket->id; ?>"
                        data-ad="<?php echo htmlspecialchars($paket->ad); ?>"
                        data-fiyat="<?php echo $paket->fiyat; ?>"
                        data-hak="<?php echo $paket->firma_hakki; ?>"
                        data-alt-hak="<?php echo $paket->alt_kullanici_hakki; ?>"
                        data-sure="<?php echo $paket->sure; ?>">
                    Düzenle
                </button>
                <button class="btn btn-secondary" style="flex: 1; height: 2.25rem;">Pasife Al</button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Add New Package Card -->
        <div class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed var(--border); background: transparent; cursor: pointer; min-height: 250px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" 
                onclick="document.getElementById('add-package-modal').showModal()">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--muted); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <i data-lucide="plus" style="width: 24px; color: var(--muted-foreground);"></i>
            </div>
            <span style="font-weight: 600; color: var(--foreground);">Yeni Paket Ekle</span>
        </div>
    </div>

    <!-- Modals -->
    <dialog id="add-package-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="package-plus" style="width: 20px;"></i> Yeni Paket Ekle
            </h2>
            <button onclick="this.closest('dialog').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="add-package-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="handlePackageSubmit(event, 'add-package-form')">
            <div class="form-group">
                <label class="form-label">Paket Adı</label>
                <input type="text" name="ad" class="form-input" placeholder="Örn: Profesyonel Paket" required>
                <span class="form-error">Paket adı gereklidir.</span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" name="fiyat" class="form-input" placeholder="0" required>
                    <span class="form-error">Geçerli bir fiyat giriniz.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Süre (Ay)</label>
                    <input type="number" name="sure" class="form-input" value="12" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Firma (İşyeri) Limiti</label>
                    <input type="number" name="firma_hakki" class="form-input" value="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alt Kullanıcı Limiti</label>
                    <input type="number" name="alt_kullanici_hakki" class="form-input" value="3" required>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="this.closest('dialog').close()">Vazgeç</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Paketi Oluştur</button>
            </div>
        </form>
    </dialog>

    <dialog id="edit-package-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="edit-3" style="width: 20px;"></i> Paketi Düzenle
            </h2>
            <button onclick="this.closest('dialog').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="edit-package-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="handlePackageSubmit(event, 'edit-package-form')">
            <input type="hidden" id="edit-id" name="id">
            <div class="form-group">
                <label class="form-label">Paket Adı</label>
                <input type="text" id="edit-ad" name="ad" class="form-input" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" id="edit-fiyat" name="fiyat" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Süre (Ay)</label>
                    <input type="number" id="edit-sure" name="sure" class="form-input" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Firma (İşyeri) Limiti</label>
                    <input type="number" id="edit-hak" name="firma_hakki" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alt Kullanıcı Limiti</label>
                    <input type="number" id="edit-alt-hak" name="alt_kullanici_hakki" class="form-input" required>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="this.closest('dialog').close()">Vazgeç</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Güncelle</button>
            </div>
        </form>
    </dialog>
</div>

<script>
    async function handlePackageSubmit(e, formId) {
        e.preventDefault();
        if (!App.validateForm(formId)) return;

        const form = document.getElementById(formId);
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner" style="width: 16px; height: 16px;"></div> İşleniyor...';

        try {
            const response = await fetch('admin-paket-kaydet', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                App.toast('success', 'Başarılı', result.message);
                form.closest('dialog').close();
                App.refreshContent();
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            App.toast('error', 'Hata', 'Bir sistem hatası oluştu.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    }

    function openEditPackageModal(el) {
        document.getElementById('edit-id').value = el.dataset.id;
        document.getElementById('edit-ad').value = el.dataset.ad;
        document.getElementById('edit-fiyat').value = el.dataset.fiyat;
        document.getElementById('edit-hak').value = el.dataset.hak;
        document.getElementById('edit-alt-hak').value = el.dataset.altHak;
        document.getElementById('edit-sure').value = el.dataset.sure;
        document.getElementById('edit-package-modal').showModal();
    }
</script>
