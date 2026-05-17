<?php
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}
require_once __DIR__ . '/../../autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Admin\Models\UserModel;

$userModel = new UserModel();
$user = $userModel->find($_SESSION['user_id'] ?? 0);

if (!$user) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

$initials = '';
$nameParts = explode(' ', $user->adi_soyadi ?? 'K');
foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
$initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
?>

<div class="animate-in flex flex-col gap-6" style="padding-bottom: 2rem;">
    <!-- Sayfa Başlığı ve Kaydet Butonu -->
    <div class="flex items-center justify-between" style="border-bottom: none; padding-bottom: 0;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0; color: var(--foreground);">
                Profil Ayarları
            </h1>
            <p style="color: var(--muted-foreground); font-size: 0.875rem; margin-top: 0.25rem;">
                Kişisel bilgilerinizi, güvenlik tercihlerinizi ve hesap detaylarınızı buradan yönetin.
            </p>
        </div>
        <div style="flex-shrink: 0;">
            <button onclick="document.getElementById('profile-form-submit-btn').click();" class="btn btn-primary" style="gap: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                Değişiklikleri Kaydet
            </button>
        </div>
    </div>

    <!-- Profil Grid Düzeni -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6" style="flex: 1; min-height: 0;">
        
        <!-- Sol Dikey Sekme Navigasyonu -->
        <div class="md:col-span-3 flex flex-col gap-1">
            <!-- Profil Avatar Gösterimi -->
            <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--primary); color: var(--primary-foreground); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 700; border: 3px solid var(--muted);">
                    <?php echo $initials; ?>
                </div>
                <div>
                    <h2 style="font-size: 0.875rem; font-weight: 700; margin: 0; color: var(--foreground);"><?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?></h2>
                    <p style="color: var(--muted-foreground); font-size: 0.75rem; margin: 0.125rem 0 0; word-break: break-all;"><?php echo htmlspecialchars($user->email); ?></p>
                </div>
            </div>

            <button onclick="switchTab('tab-personal')" id="nav-tab-personal" class="settings-nav-item active flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                Kişisel Bilgiler
            </button>
            <button onclick="switchTab('tab-password')" id="nav-tab-password" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                Şifre Değiştir
            </button>
            <button onclick="switchTab('tab-details')" id="nav-tab-details" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="info" style="width: 16px; height: 16px;"></i>
                Hesap Detayları
            </button>
            
            <!-- Hatırlatma Kartı (Cohesive Card Design) -->
            <div class="card" style="margin-top: 2rem; padding: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--foreground); font-size: 0.8125rem; margin-bottom: 0.375rem;">
                    <i data-lucide="info" style="width: 16px; height: 16px; color: #3b82f6;"></i>
                    Hatırlatma
                </div>
                <p style="font-size: 0.75rem; color: var(--muted-foreground); margin: 0; line-height: 1.4;">
                    Profil değişikliklerinizin kaydedilmesi için sağ üst köşedeki <b>"Değişiklikleri Kaydet"</b> butonuna tıklayınız.
                </p>
            </div>
        </div>

        <!-- Sağ Dinamik Form İçeriği -->
        <div class="md:col-span-9">
            <form id="profile-form" onsubmit="event.preventDefault(); submitProfileUpdate(this);">
                
                <!-- TAB 1: KİŞİSEL BİLGİLER -->
                <div id="tab-personal" class="settings-tab-content space-y-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2">
                                <i data-lucide="user" style="width: 18px; height: 18px;" class="text-primary"></i>
                                Kişisel Bilgiler
                            </h3>
                            <p class="card-description">Yönetici kimliğinizi ve sistemde görüntülenecek iletişim tercihlerinizi yapılandırın.</p>
                        </div>
                        <div class="card-content" style="display: flex; flex-direction: column; gap: 1.25rem; padding: 1.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" name="adi_soyadi" class="form-input" value="<?php echo htmlspecialchars($user->adi_soyadi); ?>" placeholder="Adınız ve Soyadınız" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Kullanıcı Adı</label>
                                    <input type="text" name="kullanici_adi" class="form-input" value="<?php echo htmlspecialchars($user->kullanici_adi); ?>" placeholder="kullaniciadi" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">E-posta Adresi</label>
                                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user->email); ?>" placeholder="ornek@mail.com" required>
                                <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">Giriş yapmak ve sistem kritik bildirimlerini almak için kullanılır.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ŞİFRE DEĞİŞTİR -->
                <div id="tab-password" class="settings-tab-content space-y-6 hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2">
                                <i data-lucide="lock" style="width: 18px; height: 18px;" class="text-primary"></i>
                                Şifre Değiştir
                            </h3>
                            <p class="card-description">Hesap güvenliğinizi artırmak amacıyla periyodik olarak şifrenizi yenileyebilirsiniz.</p>
                        </div>
                        <div class="card-content" style="display: flex; flex-direction: column; gap: 1.25rem; padding: 1.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Yeni Şifre</label>
                                    <input type="password" name="new_password" class="form-input" placeholder="••••••••">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Şifre Tekrar</label>
                                    <input type="password" name="confirm_password" class="form-input" placeholder="••••••••">
                                </div>
                            </div>
                            <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">Şifrenizi güncellemek istemiyorsanız bu alanları boş bırakabilirsiniz.</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: HESAP DETAYLARI -->
                <div id="tab-details" class="settings-tab-content space-y-6 hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2">
                                <i data-lucide="info" style="width: 18px; height: 18px;" class="text-primary"></i>
                                Hesap Detayları & İstatistikler
                            </h3>
                            <p class="card-description">Mevcut yöneticilik hesabınızın sistem veritabanındaki kayıt detayları.</p>
                        </div>
                        <div class="card-content" style="padding: 1.5rem;">
                             <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; padding: 0.5rem 0;">
                                <div>
                                    <span style="display: block; font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.25rem;">Kayıt Tarihi</span>
                                    <span style="font-weight: 600; color: var(--foreground); font-size: 0.875rem;"><?php echo date('d F Y', strtotime($user->kayit_tarihi)); ?></span>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.25rem;">Hesap Durumu</span>
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.08); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.15); display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; padding: 0.125rem 0.5rem; font-weight: 600;">
                                        <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981;"></span>
                                        Aktif
                                    </span>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.25rem;">Son Giriş</span>
                                    <span style="font-weight: 600; color: var(--foreground); font-size: 0.875rem;">Bugün</span>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Gizli Submit Butonu (JS tetiklemesi için) -->
                <button type="submit" id="profile-form-submit-btn" style="display: none;"></button>
            </form>
        </div>
    </div>
</div>

<!-- Özel CSS Ayarları -->
<style>
    /* Sekme Menüsü Butonları */
    .settings-nav-item {
        font-size: 13px !important;
        padding: 6px 12px !important;
        border-radius: 6px !important;
        color: var(--muted-foreground);
        background: transparent;
        border: 1px solid transparent;
        cursor: pointer;
    }
    .settings-nav-item:hover {
        background-color: var(--muted);
        color: var(--foreground);
    }
    .settings-nav-item.active {
        background-color: var(--primary);
        color: var(--primary-foreground);
    }

    /* Yazı Boyutları Dengelemesi (Visual Continuity Overrides) */
    .card-title {
        font-size: 0.9375rem !important; /* 15px */
        font-weight: 600 !important;
        margin: 0 !important;
    }
    .card-description {
        font-size: 0.75rem !important; /* 12px */
        color: var(--muted-foreground) !important;
        margin-top: 0.125rem !important;
        font-weight: 400 !important;
    }
    .form-label {
        font-size: 0.8125rem !important; /* 13px */
        font-weight: 500 !important;
        margin-bottom: 0.375rem !important;
        color: var(--foreground) !important;
    }
    .form-input {
        font-size: 0.8125rem !important; /* 13px */
        height: 36px !important; /* Standart compact 36px */
        padding-top: 0.375rem !important;
        padding-bottom: 0.375rem !important;
    }
</style>

<!-- İstemci Tarafı JavaScript Kontrolleri -->
<script>
    if (window.lucide) {
        lucide.createIcons();
    }

    /**
     * Dikey Sekmeler Arasında Geçiş Yapma Fonksiyonu
     */
    function switchTab(tabId) {
        // Tüm içerik bloklarını gizle
        document.querySelectorAll('.settings-tab-content').forEach(el => {
            el.classList.add('hidden');
        });
        
        // İlgili bloğu göster
        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.remove('hidden');
            targetTab.classList.add('animate-in', 'fade-in', 'duration-300');
        }
        
        // Tüm navigasyon butonlarındaki aktif sınıflarını temizle
        document.querySelectorAll('.settings-nav-item').forEach(el => {
            el.classList.remove('active');
        });
        
        // Aktif butona 'active' sınıfı ekle
        const btnId = 'nav-' + tabId;
        const activeBtn = document.getElementById(btnId);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }

    async function submitProfileUpdate(form) {
        const newPass = form.querySelector('input[name="new_password"]').value;
        const confirmPass = form.querySelector('input[name="confirm_password"]').value;

        if (newPass && newPass !== confirmPass) {
            App.toast('error', 'Hata', 'Şifreler uyuşmuyor!');
            return;
        }

        // Üst sağdaki butonu da devre dışı bırakıp durumunu gösterelim
        const headerSaveBtn = document.querySelector('.btn-primary');
        const originalText = headerSaveBtn.innerHTML;
        
        headerSaveBtn.disabled = true;
        headerSaveBtn.innerHTML = '<i class="spinner-loader animate-spin" style="width: 14px; height: 14px; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; display: inline-block; animation: spin 1s linear infinite;"></i> Güncelleniyor...';

        try {
            const formData = new FormData(form);
            
            const response = await fetch('profil-guncelle', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                App.toast('success', 'Başarılı', result.message);
                // Sidebar avatar ve kullanıcı adının yenilenmesi için 1.5 sn sonra sayfayı tazele
                setTimeout(() => location.reload(), 1500);
            } else {
                App.toast('error', 'Hata', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            App.toast('error', 'Hata', 'Bir hata oluştu.');
        } finally {
            headerSaveBtn.disabled = false;
            headerSaveBtn.innerHTML = originalText;
        }
    }
</script>
