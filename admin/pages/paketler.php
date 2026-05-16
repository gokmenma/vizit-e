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
        <button id="add-btn" class="btn" style="background: #18181b; color: white;" onclick="document.getElementById('add-package-modal').showModal()">
            <i data-lucide="plus" style="width: 16px;"></i> Yeni Paket Oluştur
        </button>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <?php foreach ($paketler as $paket): ?>
        <div class="card" style="display: flex; flex-direction: column; position: relative; overflow: hidden; padding: 1.5rem; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: #18181b;"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem;"><?php echo $paket->ad; ?></h3>
                    <p style="font-size: 0.875rem; color: #71717a;"><?php echo $paket->aciklama ?? 'Tüm temel özellikler dahil.'; ?></p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: #18181b;">₺<?php echo number_format($paket->fiyat, 0); ?></div>
                    <div style="font-size: 0.75rem; color: #71717a;"><?php echo $paket->sure ?? 1; ?> Ay</div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin: 1.5rem 0; padding: 1rem; background: #f9fafb; border-radius: 8px; border: 1px solid #f1f1f4;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.875rem; color: #71717a; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="users" style="width: 14px;"></i> Kullanıcı Limiti
                    </span>
                    <span style="font-weight: 600;"><?php echo $paket->firma_hakki; ?></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.875rem; color: #71717a; display: flex; align-items: center; gap: 0.5rem;">
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
                        data-sure="<?php echo $paket->sure; ?>">
                    Düzenle
                </button>
                <button class="btn" style="background: #18181b; color: white; flex: 1; height: 2.25rem;">Pasife Al</button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Add New Package Card -->
        <div class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed #e4e4e7; background: transparent; cursor: pointer; min-height: 250px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" 
                onclick="document.getElementById('add-package-modal').showModal()"
                onmouseover="this.style.borderColor='#18181b'; this.style.backgroundColor='#f9fafb';" 
                onmouseout="this.style.borderColor='#e4e4e7'; this.style.backgroundColor='transparent';">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #f4f4f5; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <i data-lucide="plus" style="width: 24px; color: #71717a;"></i>
            </div>
            <span style="font-weight: 600; color: #18181b;">Yeni Paket Ekle</span>
        </div>
    </div>

    <!-- Modals -->
    <dialog id="add-package-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="package-plus" style="width: 20px;"></i> Yeni Paket Ekle
            </h2>
            <button onclick="this.closest('dialog').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="add-package-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); if(App.validateForm('add-package-form')) { App.toast('success', 'Başarılı', 'Yeni paket başarıyla tanımlandı.'); this.closest('dialog').close(); }">
            <div class="form-group">
                <label class="form-label">Paket Adı</label>
                <input type="text" class="form-input" placeholder="Örn: Profesyonel Paket" required>
                <span class="form-error">Paket adı gereklidir.</span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" class="form-input" placeholder="0" required>
                    <span class="form-error">Geçerli bir fiyat giriniz.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Süre (Ay)</label>
                    <input type="number" class="form-input" value="12" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kullanıcı (İşyeri) Limiti</label>
                <input type="number" class="form-input" value="1" required>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="this.closest('dialog').close()">Vazgeç</button>
                <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">Paketi Oluştur</button>
            </div>
        </form>
    </dialog>

    <dialog id="edit-package-modal" class="card" style="width: 480px; padding: 0; border: none; border-radius: 12px; box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e4e4e7; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="edit-3" style="width: 20px;"></i> Paketi Düzenle
            </h2>
            <button onclick="this.closest('dialog').close()" style="background: none; border: none; cursor: pointer; color: #71717a;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="edit-package-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); if(App.validateForm('edit-package-form')) { App.toast('success', 'Güncellendi', 'Paket bilgileri başarıyla güncellendi.'); this.closest('dialog').close(); }">
            <input type="hidden" id="edit-id">
            <div class="form-group">
                <label class="form-label">Paket Adı</label>
                <input type="text" id="edit-ad" class="form-input" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" id="edit-fiyat" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Süre (Ay)</label>
                    <input type="number" id="edit-sure" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kullanıcı (İşyeri) Limiti</label>
                <input type="number" id="edit-hak" class="form-input" required>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="this.closest('dialog').close()">Vazgeç</button>
                <button type="submit" class="btn" style="flex: 1; background: #18181b; color: white;">Güncelle</button>
            </div>
        </form>
    </dialog>
</div>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }

    function openEditPackageModal(el) {
        document.getElementById('edit-id').value = el.dataset.id;
        document.getElementById('edit-ad').value = el.dataset.ad;
        document.getElementById('edit-fiyat').value = el.dataset.fiyat;
        document.getElementById('edit-hak').value = el.dataset.hak;
        document.getElementById('edit-sure').value = el.dataset.sure;
        document.getElementById('edit-package-modal').showModal();
    }
</script>
