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

<div class="animate-in" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0;">Profil Ayarları</h1>
        <p style="color: var(--muted-foreground); font-size: 0.875rem; margin-top: 0.25rem;">Kişisel bilgilerinizi ve hesap ayarlarınızı buradan yönetebilirsiniz.</p>
    </div>

    <div style="display: grid; gap: 2rem;">
        <!-- Profil Kartı -->
        <div class="card" style="padding: 2rem;">
            <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border);">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: var(--primary-foreground); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; border: 4px solid var(--muted);">
                    <?php echo $initials; ?>
                </div>
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--foreground);"><?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?></h2>
                    <p style="color: var(--muted-foreground); font-size: 0.875rem; margin: 0.25rem 0 0.5rem;"><?php echo htmlspecialchars($user->email); ?></p>
                    <span class="badge badge-secondary" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        <?php echo $user->role === 'superadmin' ? 'Süper Admin' : 'Yönetici'; ?>
                    </span>
                </div>
            </div>

            <form id="profile-form" onsubmit="event.preventDefault(); submitProfileUpdate(this);" style="display: grid; gap: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; color: var(--foreground);">Ad Soyad</label>
                        <div class="input-icon-wrapper">
                            <i data-lucide="user" class="input-icon"></i>
                            <input type="text" name="adi_soyadi" class="form-input" value="<?php echo htmlspecialchars($user->adi_soyadi); ?>" placeholder="Adınız ve Soyadınız" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; color: var(--foreground);">Kullanıcı Adı</label>
                        <div class="input-icon-wrapper">
                            <i data-lucide="at-sign" class="input-icon"></i>
                            <input type="text" name="kullanici_adi" class="form-input" value="<?php echo htmlspecialchars($user->kullanici_adi); ?>" placeholder="kullaniciadi" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; color: var(--foreground);">E-posta Adresi</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="mail" class="input-icon"></i>
                        <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user->email); ?>" placeholder="ornek@mail.com" required>
                    </div>
                    <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.5rem;">Giriş yapmak ve bildirim almak için kullanılır.</p>
                </div>

                <div style="padding: 1.5rem; background: var(--muted); border-radius: 8px; border: 1px dashed var(--border); margin-top: 0.5rem;">
                    <h3 style="font-size: 0.875rem; font-weight: 700; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="lock" style="width: 16px;"></i> Şifre Değiştir (Opsiyonel)
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" name="new_password" class="form-input" placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Şifre Tekrar</label>
                            <input type="password" name="confirm_password" class="form-input" placeholder="••••••••">
                        </div>
                    </div>
                    <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.75rem;">Şifrenizi değiştirmek istemiyorsanız bu alanları boş bırakın.</p>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.625rem 2rem;">
                        <i data-lucide="save" style="width: 16px;"></i> Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>

        <!-- Hesap Bilgileri -->
        <div class="card" style="padding: 1.5rem; background: var(--card);">
             <h3 style="font-size: 0.875rem; font-weight: 700; margin: 0 0 1.25rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Hesap Detayları</h3>
             <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div>
                    <span style="display: block; font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.25rem;">Kayıt Tarihi</span>
                    <span style="font-weight: 600; color: var(--foreground);"><?php echo date('d F Y', strtotime($user->kayit_tarihi)); ?></span>
                </div>
                <div>
                    <span style="display: block; font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.25rem;">Hesap Durumu</span>
                    <span class="badge badge-success" style="background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e; display: inline-block; margin-right: 4px;"></span>
                        Aktif
                    </span>
                </div>
                <div>
                    <span style="display: block; font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.25rem;">Son Giriş</span>
                    <span style="font-weight: 600; color: var(--foreground);">Bugün</span>
                </div>
             </div>
        </div>
    </div>
</div>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }

    async function submitProfileUpdate(form) {
        const newPass = form.querySelector('input[name="new_password"]').value;
        const confirmPass = form.querySelector('input[name="confirm_password"]').value;

        if (newPass && newPass !== confirmPass) {
            App.toast('error', 'Hata', 'Şifreler uyuşmuyor!');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> Güncelleniyor...';

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
                // Sidebar'ı güncellemek için sayfayı yenilemek en temizi
                setTimeout(() => location.reload(), 1500);
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
</script>
