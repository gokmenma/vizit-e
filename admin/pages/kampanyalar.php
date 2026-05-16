<?php
require_once __DIR__ . '/../../autoload.php';
$campaignModel = new \Models\CampaignModel();
$campaigns = $campaignModel->getCampaigns();
$paketModel = new \Models\AbonelikPaketModel();
$paketler = $paketModel->all();
$userModel = new \Models\UserModel();
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Kampanya Yönetimi</h1>
            <p style="color: var(--muted-foreground); font-size: 0.875rem; margin-top: 0.25rem;">Kullanıcılara yönelik e-posta kampanyaları oluşturun ve yönetin.</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i data-lucide="plus" style="width: 16px;"></i> Yeni Kampanya Oluştur
        </button>
    </div>

    <div class="card dt-container" style="padding: 0; overflow: hidden; flex: 1; display: flex; flex-direction: column; min-height: 0;">
        <div class="dt-header">
            <div class="dt-tabs">
                <button class="dt-tab active">
                    Tüm Kampanyalar <span class="dt-tab-count"><?php echo count($campaigns); ?></span>
                </button>
            </div>
            <div class="dt-actions">
                <div class="dt-search-wrapper">
                    <i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; color: #71717a; z-index: 10;"></i>
                    <input type="text" id="campaign-search" class="dt-search-input" placeholder="Kampanya ara..." onkeyup="searchTable()">
                </div>
            </div>
        </div>

        <div class="table-container" style="flex: 1; min-height: 0;">
            <table class="data-table" id="campaigns-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Kampanya Başlığı</th>
                        <th>Kriterler</th>
                        <th>Hedef / Gönderilen / Hata</th>
                        <th>Durum</th>
                        <th>Oluşturma</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $camp): 
                        $criteria = json_decode($camp->criteria, true);
                        $statusBadge = 'badge-secondary';
                        if ($camp->status === 'completed') $statusBadge = 'badge-success';
                        elseif ($camp->status === 'sending') $statusBadge = 'badge-primary';
                        elseif ($camp->status === 'failed') $statusBadge = 'badge-destructive';
                    ?>
                    <tr class="campaign-row" data-id="<?php echo $camp->id; ?>">
                        <td style="color: var(--muted-foreground); font-family: monospace; font-size: 0.75rem;">#<?php echo $camp->id; ?></td>
                        <td>
                            <div class="campaign-title" style="font-weight: 600; color: var(--foreground);"><?php echo htmlspecialchars($camp->title); ?></div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                <?php if (!empty($criteria['user_ids'])): ?>
                                    <span class="badge" style="background: var(--muted); font-size: 0.7rem;">Seçili Kullanıcılar (<?php echo count($criteria['user_ids']); ?>)</span>
                                <?php endif; ?>
                                <?php if (!empty($criteria['manual_emails'])): ?>
                                    <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.7rem;">Manuel E-postalar</span>
                                <?php endif; ?>
                                <?php if (empty($criteria['user_ids']) && empty($criteria['manual_emails'])): ?>
                                    <?php if (!empty($criteria['status'])): ?>
                                        <span class="badge" style="background: var(--muted); font-size: 0.7rem;">Durum: <?php echo $criteria['status'] === 'active' ? 'Aktif' : 'Pasif'; ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($criteria['paket_id'])): 
                                        $pName = 'Bilinmiyor';
                                        foreach($paketler as $p) if($p->id == $criteria['paket_id']) $pName = $p->ad;
                                    ?>
                                        <span class="badge" style="background: var(--muted); font-size: 0.7rem;">Paket: <?php echo $pName; ?></span>
                                    <?php endif; ?>
                                    <?php if (empty($criteria['status']) && empty($criteria['paket_id'])): ?>
                                        <span class="badge" style="background: var(--muted); font-size: 0.7rem;">Tüm Kullanıcılar</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.875rem;">
                                <span title="Hedef" style="color: var(--foreground); font-weight: 600;"><?php echo $camp->total_recipients; ?></span> /
                                <span title="Gönderilen" style="color: #16a34a; font-weight: 600;"><?php echo $camp->sent_count; ?></span> /
                                <span title="Hata" style="color: #dc2626; font-weight: 600;"><?php echo $camp->failed_count; ?></span>
                            </div>
                            <?php if ($camp->total_recipients > 0): 
                                $percent = round(($camp->sent_count / $camp->total_recipients) * 100);
                            ?>
                            <div style="width: 100px; height: 4px; background: var(--muted); border-radius: 2px; margin-top: 4px;">
                                <div style="width: <?php echo $percent; ?>%; height: 100%; background: #16a34a; border-radius: 2px;"></div>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $statusBadge; ?>" style="font-size: 0.7rem;">
                                <?php 
                                    $statusLabels = ['draft' => 'Taslak', 'sending' => 'Gönderiliyor', 'completed' => 'Tamamlandı', 'failed' => 'Hata Oluştu'];
                                    echo $statusLabels[$camp->status] ?? $camp->status;
                                ?>
                            </span>
                        </td>
                        <td style="color: var(--muted-foreground); font-size: 0.8125rem;"><?php echo date('d.m.Y H:i', strtotime($camp->created_at)); ?></td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 0.25rem;">
                                <button class="btn btn-ghost btn-sm" title="Gönderim Logları" onclick="viewLogs(<?php echo $camp->id; ?>, '<?php echo htmlspecialchars($camp->title); ?>')">
                                    <i data-lucide="history" style="width: 14px;"></i>
                                </button>
                                <?php if ($camp->status === 'completed'): ?>
                                <button class="btn btn-ghost btn-sm" title="Yeniden Gönder (Aynı Hedef)" style="color: #6366f1;" onclick="confirmResend(<?php echo $camp->id; ?>)">
                                    <i data-lucide="rotate-ccw" style="width: 14px;"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-ghost btn-sm" title="Düzenle" onclick="editCampaign(<?php echo $camp->id; ?>)">
                                    <i data-lucide="edit-3" style="width: 14px;"></i>
                                </button>
                                <?php if ($camp->status === 'draft' || $camp->status === 'failed'): ?>
                                <button class="btn btn-ghost btn-sm" title="Gönderimi Başlat" style="color: #16a34a;" onclick="confirmSend(<?php echo $camp->id; ?>)">
                                    <i data-lucide="send" style="width: 14px;"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-ghost btn-sm" title="Sil" style="color: #ef4444;" onclick="confirmDelete(<?php echo $camp->id; ?>)">
                                    <i data-lucide="trash-2" style="width: 14px;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: var(--muted-foreground);">Henüz bir kampanya oluşturulmadı.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Campaign Modal -->
    <dialog id="add-campaign-modal" class="card modal-dialog" style="width: 850px;">
        <div class="modal-header">
            <h2 id="modal-title">
                <i data-lucide="megaphone" style="width: 20px;"></i> Yeni Kampanya Oluştur
            </h2>
            <button onclick="document.getElementById('add-campaign-modal').close()" class="modal-close-btn"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <form id="add-campaign-form" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;" onsubmit="event.preventDefault(); submitAddCampaign(this);">
            <input type="hidden" name="id" id="campaign-id" value="">
            <div class="form-group">
                <label class="form-label">Kampanya Başlığı (E-posta Konusu)</label>
                <input type="text" name="title" id="campaign-title" class="form-input" placeholder="Örn: Yeni Özelliklerimiz Hakkında" required>
            </div>
            
            <div class="dt-tabs" style="margin-bottom: 0.5rem; background: var(--muted); padding: 0.25rem; border-radius: 8px; align-self: flex-start;">
                <button type="button" class="dt-tab active" id="tab-filter" onclick="switchTargetMode('filter')" style="padding: 0.4rem 1rem; border-radius: 6px; font-size: 0.8125rem;">Filtreleme Kullan</button>
                <button type="button" class="dt-tab" id="tab-users" onclick="switchTargetMode('users')" style="padding: 0.4rem 1rem; border-radius: 6px; font-size: 0.8125rem;">Kullanıcı Seç</button>
                <button type="button" class="dt-tab" id="tab-manual" onclick="switchTargetMode('manual')" style="padding: 0.4rem 1rem; border-radius: 6px; font-size: 0.8125rem;">Manuel Giriş</button>
            </div>

            <div id="target-mode-filter" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Kullanıcı Durumu</label>
                    <div class="custom-select" id="criteria-status-select">
                        <input type="hidden" name="criteria[status]" value="">
                        <div class="select-trigger">
                            <span class="select-label">Tüm Kullanıcılar</span>
                            <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                        </div>
                        <div class="select-popover" popover="manual">
                            <div class="select-options">
                                <div class="select-option selected" data-value="">Hepsi</div>
                                <div class="select-option" data-value="active">Aktif Kullanıcılar</div>
                                <div class="select-option" data-value="passive">Pasif Kullanıcılar</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Paket Filtresi</label>
                    <div class="custom-select" id="criteria-paket-select">
                        <input type="hidden" name="criteria[paket_id]" value="">
                        <div class="select-trigger">
                            <span class="select-label">Tüm Paketler</span>
                            <i data-lucide="chevron-down" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                        </div>
                        <div class="select-popover" popover="manual">
                            <div class="select-options">
                                <div class="select-option selected" data-value="">Hepsi</div>
                                <?php foreach($paketler as $p): ?>
                                <div class="select-option" data-value="<?php echo $p->id; ?>"><?php echo $p->ad; ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="target-mode-users" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Tekil Kullanıcı Seçimi</label>
                    <div class="custom-select" id="user-multi-select">
                        <div class="select-trigger">
                            <span class="select-label">Kullanıcı ara ve ekle...</span>
                            <i data-lucide="search" style="width: 16px; color: #71717a; margin-left: auto;"></i>
                        </div>
                        <div class="select-popover" popover="manual">
                            <header style="padding: 0.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.5rem;">
                                <i data-lucide="search" style="width: 14px; color: #a1a1aa;"></i>
                                <input type="text" class="select-search" placeholder="İsim veya e-posta ara..." style="flex: 1; border: none; outline: none; background: transparent; font-size: 0.875rem;">
                            </header>
                            <div class="select-options" id="user-options-list" style="max-height: 200px; overflow-y: auto;">
                                <?php 
                                    $allUsers = $userModel->AktifKullanicilarAltKullanici();
                                    foreach($allUsers as $u): 
                                ?>
                                <div class="select-option" data-value="<?php echo $u->id; ?>" data-name="<?php echo htmlspecialchars($u->adi_soyadi ?: $u->kullanici_adi); ?>" onclick="addUserToSelection(this)">
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($u->adi_soyadi ?: $u->kullanici_adi); ?></span>
                                        <span style="font-size: 0.7rem; color: #71717a;"><?php echo $u->email; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div id="selected-users-tags" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem;">
                    </div>
                    <div id="user-ids-inputs">
                    </div>
                </div>
            </div>

            <div id="target-mode-manual" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Manuel E-posta Listesi</label>
                    <textarea name="criteria[manual_emails]" id="manual-emails" class="form-input" style="min-height: 100px; font-family: monospace; font-size: 0.875rem;" placeholder="Her satıra bir e-posta adresi yazın veya virgülle ayırın..."></textarea>
                    <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.5rem;">Sistemde kayıtlı olmayan kişilere de ulaşmak için burayı kullanabilirsiniz.</p>
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="form-label" style="margin: 0;">E-posta İçeriği</label>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="previewEmail()" style="height: 28px; padding: 0 0.5rem; font-size: 0.75rem;">
                        <i data-lucide="eye" style="width: 14px;"></i> Önizleme
                    </button>
                </div>
                <textarea name="content" id="campaign-content" class="summernote" required></textarea>
                <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.5rem;">İpucu: Değişkenleri kullanabilirsiniz: {adi_soyadi}</p>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('add-campaign-modal').close()">Vazgeç</button>
                <button type="submit" id="save-btn" class="btn btn-primary" style="flex: 1;">Kampanyayı Kaydet</button>
            </div>
        </form>
    </dialog>

    <!-- Logs Modal -->
    <dialog id="view-logs-modal" class="card modal-dialog" style="width: 850px;">
        <div class="modal-header">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="history" style="width: 20px;"></i> Gönderim Logları: <span id="log-campaign-title" style="color: var(--muted-foreground); font-weight: 400;"></span>
            </h2>
            <button onclick="document.getElementById('view-logs-modal').close()" class="modal-close-btn"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <div id="logs-container" class="modal-body-scrollable" style="padding: 1.5rem; background: var(--card);">
            <div style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: white;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f9fafb;">
                        <tr>
                            <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: left;">Alıcı</th>
                            <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: left;">E-posta</th>
                            <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: left;">Durum</th>
                            <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: left;">Tarih</th>
                            <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: left;">Hata</th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody">
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>

    <!-- Preview Modal -->
    <dialog id="preview-modal" class="card modal-dialog" style="width: 800px;">
        <div class="modal-header">
            <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="eye" style="width: 20px;"></i> E-posta Önizleme
            </h2>
            <button onclick="document.getElementById('preview-modal').close()" class="modal-close-btn"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <div class="modal-body-scrollable" style="background: #f8fafc; padding: 2rem;">
            <div id="preview-content-frame" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                <!-- Content goes here -->
            </div>
        </div>
    </dialog>

    <!-- Alert Dialog -->
    <dialog id="alert-dialog" class="card modal-dialog" style="width: 400px; border-radius: 16px;">
        <div style="padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; text-align: center;">
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <h3 id="alert-title" style="font-size: 1.25rem; font-weight: 700; color: var(--foreground);">Emin misiniz?</h3>
                <p id="alert-description" style="color: var(--muted-foreground); font-size: 0.875rem; line-height: 1.5;">Bu işlem geri alınamaz.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button onclick="document.getElementById('alert-dialog').close()" class="btn btn-outline" style="flex: 1; height: 44px; font-weight: 600; border-radius: 10px;">İptal</button>
                <button id="alert-confirm-btn" class="btn btn-primary" style="flex: 1; height: 44px; font-weight: 600; border-radius: 10px; background: #000; color: #fff; border: none;">Devam Et</button>
            </div>
        </div>
    </dialog>
</div>

<style>
    /* Standard Modal UI */
    .modal-dialog { 
        padding: 0; 
        border: none; 
        border-radius: 12px; 
        box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); 
        overflow: hidden; 
        background: var(--card);
    }
    .modal-header { 
        padding: 1.5rem; 
        border-bottom: 1px solid var(--border); 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        background: var(--card); 
    }
    .modal-close-btn { background: none; border: none; cursor: pointer; color: #71717a; display: flex; align-items: center; }
    .modal-body-scrollable { 
        max-height: 600px; 
        overflow-y: auto; 
        overflow-x: hidden;
        border-bottom-left-radius: 12px; 
        border-bottom-right-radius: 12px; 
    }

    .dt-tab { border: none; background: transparent; cursor: pointer; color: var(--muted-foreground); transition: all 0.2s; }
    .dt-tab.active { background: var(--card) !important; color: var(--foreground) !important; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); }
    
    .user-tag { 
        display: inline-flex; 
        align-items: center; 
        gap: 0.25rem; 
        padding: 0.25rem 0.6rem; 
        background: var(--primary); 
        color: var(--primary-foreground); 
        border-radius: 4px; 
        font-size: 0.75rem; 
        font-weight: 500;
        transition: all 0.2s;
    }
    .user-tag button { background: none; border: none; cursor: pointer; padding: 0; color: inherit; opacity: 0.7; display: flex; align-items: center; }
    .user-tag button:hover { opacity: 1; }
    
    #user-options-list .select-option.hidden { display: none !important; }

    .modal-body-scrollable table { width: 100% !important; table-layout: fixed; }
    .modal-body-scrollable td, .modal-body-scrollable th { 
        overflow: hidden; 
        text-overflow: ellipsis; 
        white-space: nowrap; 
    }
</style>

<script>
    function initSummernote() {
        if (typeof $ === 'undefined' || !$.fn.summernote) {
            setTimeout(initSummernote, 100);
            return;
        }
        $('#campaign-content').summernote({
            placeholder: 'E-posta içeriğini buraya yazın...',
            tabsize: 2,
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }

    initSummernote();

    if (window.lucide) {
        lucide.createIcons();
    }

    function searchTable() {
        App.DataTable.search('campaign-search', '.campaign-row', '.campaign-title');
    }

    var selectedUsers = window.selectedUsers || new Map();
    window.selectedUsers = selectedUsers;
    var currentTargetMode = 'filter';

    function openAddModal() {
        document.getElementById('add-campaign-form').reset();
        document.getElementById('campaign-id').value = '';
        $('#campaign-content').summernote('code', '');
        document.getElementById('modal-title').innerHTML = '<i data-lucide="megaphone" style="width: 20px;"></i> Yeni Kampanya Oluştur';
        selectedUsers.clear();
        renderUserTags();
        switchTargetMode('filter');
        document.getElementById('add-campaign-modal').showModal();
        if (window.lucide) lucide.createIcons();
    }

    function switchTargetMode(mode) {
        currentTargetMode = mode;
        document.getElementById('tab-filter').classList.toggle('active', mode === 'filter');
        document.getElementById('tab-users').classList.toggle('active', mode === 'users');
        document.getElementById('tab-manual').classList.toggle('active', mode === 'manual');
        
        document.getElementById('target-mode-filter').style.display = (mode === 'filter' ? 'grid' : 'none');
        document.getElementById('target-mode-users').style.display = (mode === 'users' ? 'block' : 'none');
        document.getElementById('target-mode-manual').style.display = (mode === 'manual' ? 'block' : 'none');

        if (mode === 'users' && !App.initializedCustomSelects_Users) {
            App.initCustomSelects(document.getElementById('target-mode-users'));
            App.initializedCustomSelects_Users = true;
        }
    }

    function addUserToSelection(option) {
        const id = option.dataset.value;
        const name = option.dataset.name;
        if (selectedUsers.has(id)) return;
        selectedUsers.set(id, name);
        option.classList.add('hidden');
        renderUserTags();
    }

    function removeUserFromSelection(id) {
        selectedUsers.delete(id.toString());
        const option = document.querySelector(`#user-options-list .select-option[data-value="${id}"]`);
        if (option) option.classList.remove('hidden');
        renderUserTags();
    }

    function renderUserTags() {
        const container = document.getElementById('selected-users-tags');
        const inputsContainer = document.getElementById('user-ids-inputs');
        container.innerHTML = '';
        inputsContainer.innerHTML = '';
        
        selectedUsers.forEach((name, id) => {
            const tag = document.createElement('div');
            tag.className = 'user-tag';
            tag.innerHTML = `<span>${name}</span><button type="button" onclick="removeUserFromSelection(${id})"><i data-lucide="x" style="width:12px;"></i></button>`;
            container.appendChild(tag);
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'criteria[user_ids][]';
            input.value = id;
            inputsContainer.appendChild(input);
        });
        
        if (window.lucide) lucide.createIcons();
    }

    async function editCampaign(id) {
        try {
            const response = await fetch(`kampanya-detay?id=${id}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                const data = result.data;
                document.getElementById('campaign-id').value = data.id;
                document.getElementById('campaign-title').value = data.title;
                $('#campaign-content').summernote('code', data.content);
                document.getElementById('modal-title').innerHTML = '<i data-lucide="edit-3" style="width: 20px;"></i> Kampanyayı Düzenle';
                
                selectedUsers.clear();
                document.querySelectorAll('#user-options-list .select-option').forEach(opt => opt.classList.remove('hidden'));

                if (data.criteria.user_ids) {
                    switchTargetMode('users');
                    if (data.selected_users_data) {
                        data.selected_users_data.forEach(u => {
                            selectedUsers.set(u.id.toString(), u.name);
                            const opt = document.querySelector(`#user-options-list .select-option[data-value="${u.id}"]`);
                            if (opt) opt.classList.add('hidden');
                        });
                    }
                    renderUserTags();
                } else if (data.criteria.manual_emails) {
                    switchTargetMode('manual');
                    document.getElementById('manual-emails').value = data.criteria.manual_emails;
                } else {
                    switchTargetMode('filter');
                    updateSelect('criteria-status-select', data.criteria.status || '');
                    updateSelect('criteria-paket-select', data.criteria.paket_id || '');
                }
                
                document.getElementById('add-campaign-modal').showModal();
                if (window.lucide) lucide.createIcons();
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (e) {
            App.toast('error', 'Hata', 'Kampanya detayları yüklenemedi.');
        }
    }

    function updateSelect(id, value) {
        const select = document.getElementById(id);
        const input = select.querySelector('input[type="hidden"]');
        const label = select.querySelector('.select-label');
        const options = select.querySelectorAll('.select-option');
        
        input.value = value;
        options.forEach(opt => {
            if (opt.dataset.value == value) {
                label.textContent = opt.textContent;
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
    }

    async function viewLogs(id, title) {
        document.getElementById('log-campaign-title').textContent = title;
        const tbody = document.getElementById('logs-tbody');
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Yükleniyor...</td></tr>';
        document.getElementById('view-logs-modal').showModal();

        try {
            const response = await fetch(`kampanya-logs?id=${id}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                tbody.innerHTML = '';
                if (result.logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:2rem;">Henüz gönderim kaydı yok.</td></tr>';
                    return;
                }
                result.logs.forEach(log => {
                    const row = document.createElement('tr');
                    const statusClass = log.status === 'sent' ? 'badge-success' : (log.status === 'failed' ? 'badge-destructive' : 'badge-secondary');
                    const name = (log.name == '0' || !log.name || log.name == 'Dış Alıcı') ? log.email.split('@')[0] : log.name;
                    row.innerHTML = `
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;">${name}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;">${log.email}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;"><span class="badge ${statusClass}">${log.status}</span></td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; font-size: 0.8rem;">${log.sent_at || '-'}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; font-size: 0.75rem; color:#ef4444;">${log.error_message || '-'}</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#ef4444;">Hata oluştu.</td></tr>';
        }
    }

    function previewEmail() {
        const content = $('#campaign-content').summernote('code');
        const previewContent = content.replace(/{adi_soyadi}/g, '<strong>[Örnek Kullanıcı]</strong>');
        document.getElementById('preview-content-frame').innerHTML = previewContent;
        document.getElementById('preview-modal').showModal();
    }

    function showConfirm(title, description, onConfirm) {
        document.getElementById('alert-title').textContent = title;
        document.getElementById('alert-description').textContent = description;
        const confirmBtn = document.getElementById('alert-confirm-btn');
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        newConfirmBtn.onclick = () => {
            document.getElementById('alert-dialog').close();
            onConfirm();
        };
        document.getElementById('alert-dialog').showModal();
    }

    async function submitAddCampaign(form) {
        const submitBtn = document.getElementById('save-btn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> Kaydediliyor...';

        try {
            const formData = new FormData(form);
            formData.set('content', $('#campaign-content').summernote('code'));

            if (currentTargetMode === 'filter') {
                formData.delete('criteria[user_ids][]');
                formData.delete('criteria[manual_emails]');
            } else if (currentTargetMode === 'users') {
                formData.delete('criteria[status]');
                formData.delete('criteria[paket_id]');
                formData.delete('criteria[manual_emails]');
            } else {
                formData.delete('criteria[status]');
                formData.delete('criteria[paket_id]');
                formData.delete('criteria[user_ids][]');
            }

            const response = await fetch('kampanya-kaydet', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const resultStr = await response.text();
            let result;
            try {
                const jsonMatch = resultStr.match(/{.*}/s);
                result = JSON.parse(jsonMatch ? jsonMatch[0] : resultStr);
            } catch(e) { throw new Error("Sunucu yanıtı anlaşılamadı."); }

            if (result.status === 'success') {
                App.toast('success', 'Başarılı', result.message);
                document.getElementById('add-campaign-modal').close();
                setTimeout(() => App.refreshContent(), 500);
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (error) {
            App.toast('error', 'Hata', error.message || 'Bir hata oluştu.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function confirmResend(id) {
        showConfirm(
            'Yeniden Gönderilsin mi?', 
            'Aynı hedef kitleye gönderim süreci tekrar başlatılacaktır. Eski loglar korunur.',
            async () => {
                App.toast('info', 'Bilgi', 'Gönderim hazırlanıyor...');
                try {
                    const formData = new FormData();
                    formData.append('id', id);
                    const response = await fetch('kampanya-kaydet', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const result = await response.json();
                    if (result.status === 'success') startSending(id, true);
                } catch (e) { App.toast('error', 'Hata', 'İşlem başarısız.'); }
            }
        );
    }

    function confirmSend(id) {
        showConfirm(
            'Gönderimi Başlat?', 
            'Kampanya e-postaları belirlenen alıcılara gönderilmeye başlanacaktır.',
            () => startSending(id)
        );
    }

    async function startSending(id, isSkipConfirm = false) {
        App.toast('info', 'Bilgi', 'Gönderim başlatılıyor, lütfen bekleyin...');
        try {
            const formData = new FormData();
            formData.append('id', id);
            const response = await fetch('kampanya-gonder', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                App.toast('success', 'Başarılı', result.message);
                App.refreshContent();
            } else { App.toast('error', 'Hata', result.message); }
        } catch (error) { App.toast('error', 'Hata', 'Bir hata oluştu.'); }
    }

    function confirmDelete(id) {
        showConfirm(
            'Silmek istediğinize emin misiniz?', 
            'Bu kampanya ve tüm gönderim kayıtları kalıcı olarak silinecektir.',
            () => deleteCampaign(id)
        );
    }

    async function deleteCampaign(id) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            const response = await fetch('kampanya-sil', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                App.toast('success', 'Başarılı', result.message);
                App.refreshContent();
            } else { App.toast('error', 'Hata', result.message); }
        } catch (error) { App.toast('error', 'Hata', 'Bir hata oluştu.'); }
    }
</script>
