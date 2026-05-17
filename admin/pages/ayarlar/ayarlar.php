<?php
// admin/pages/ayarlar/ayarlar.php

require_once __DIR__ . '/../../../Models/Model.php';
require_once __DIR__ . '/../../../Models/KullaniciAyarModel.php';

// Oturum kontrolü ve yetkilendirme
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$ayarModel = new \Models\KullaniciAyarModel();

// Veritabanından mevcut sistem ayarlarını çekiyoruz, yoksa varsayılanlara düşüyoruz
$site_title = $ayarModel->getSetting('site_title', 0) ?: 'SGK Vizite Rapor Takip Sistemi';
$admin_email = $ayarModel->getSetting('admin_email', 0) ?: 'admin@vizit-e.com';
$default_language = $ayarModel->getSetting('default_language', 0) ?: 'tr';
$maintenance_mode = $ayarModel->getSetting('maintenance_mode', 0) ?: '0';
$kvkk_consent = $ayarModel->getSetting('kvkk_consent', 0) ?: '0';

$smtp_host = $ayarModel->getSetting('smtp_host', 0);
if (!$smtp_host || $smtp_host === '0') $smtp_host = $_ENV['SMTP_HOST'] ?? 'mail.vizit-e.com';

$smtp_port = $ayarModel->getSetting('smtp_port', 0);
if (!$smtp_port || $smtp_port === '0') $smtp_port = $_ENV['SMTP_PORT'] ?? '587';

$smtp_user = $ayarModel->getSetting('smtp_user', 0);
if (!$smtp_user || $smtp_user === '0') $smtp_user = $_ENV['SMTP_USER'] ?? 'bilgi@vizit-e.com';

$smtp_password = $ayarModel->getSetting('smtp_password', 0);
if (!$smtp_password || $smtp_password === '0') $smtp_password = $_ENV['SMTP_PASSWORD'] ?? '';

$smtp_encryption = $ayarModel->getSetting('smtp_encryption', 0);
if (!$smtp_encryption || $smtp_encryption === '0') $smtp_encryption = 'tls';

$smtp_from_name = $ayarModel->getSetting('smtp_from_name', 0);
if (!$smtp_from_name || $smtp_from_name === '0') $smtp_from_name = 'SGK Vizit-e Rapor Sistemi';

$google_recaptcha_site_key = $ayarModel->getSetting('google_recaptcha_site_key', 0) ?: '';
$google_recaptcha_secret_key = $ayarModel->getSetting('google_recaptcha_secret_key', 0) ?: '';
$two_factor_auth = $ayarModel->getSetting('two_factor_auth', 0) ?: '0';

$api_enabled = $ayarModel->getSetting('api_enabled', 0) ?: '0';
$api_secret_key = $ayarModel->getSetting('api_secret_key', 0);
if (!$api_secret_key || $api_secret_key === '0') {
    $api_secret_key = bin2hex(random_bytes(16)); // Eğer anahtar yoksa varsayılan rastgele üretilsin
}

// Veritabanı boyutunu kabaca hesaplama
$dbSize = '0 KB';
try {
    require_once __DIR__ . '/../../../Core/Database.php';
    $db = \Core\Database::getInstance()->getConnection();
    $dbName = $_ENV['DB_NAME'] ?? 'sgk_vizite';
    $sizeQuery = $db->prepare("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?");
    $sizeQuery->execute([$dbName]);
    $dbSizeBytes = $sizeQuery->fetch(PDO::FETCH_OBJ)->size ?? 0;
    if ($dbSizeBytes > 1048576) {
        $dbSize = round($dbSizeBytes / 1048576, 2) . ' MB';
    } else {
        $dbSize = round($dbSizeBytes / 1024, 2) . ' KB';
    }
} catch (\Exception $e) {
    $dbSize = 'Ölçülemedi';
}
?>

<div class="animate-in" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
    <!-- Sayfa Başlığı ve Açıklaması (kullanicilar.php ile Birebir Uyumlu) -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="cog" style="width: 28px; height: 28px;" class="animate-spin-slow"></i>
                Sistem Ayarları
            </h1>
            <p style="color: var(--muted-foreground); font-size: 0.875rem; margin-top: 0.25rem;">
                SGK Vizite Rapor Takip Platformunun genel, SMTP, güvenlik ve sistem yedekleme tercihlerini yönetin.
            </p>
        </div>
        <button onclick="saveAllSettings(event)" id="globalSaveBtn" class="btn btn-primary" style="gap: 0.5rem; display: flex; align-items: center;">
            <i data-lucide="save" style="width: 16px; height: 16px;"></i>
            Değişiklikleri Kaydet
        </button>
    </div>

    <!-- Ayarlar Ana Gövde Grid Düzeni -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6" style="flex: 1; min-height: 0;">
        
        <!-- Sol Dikey Sekme Navigasyonu -->
        <div class="md:col-span-3 flex flex-col gap-1">
            <button onclick="switchTab('tab-general')" id="nav-tab-general" class="settings-nav-item active flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="globe" style="width: 16px; height: 16px;"></i>
                Genel Ayarlar
            </button>
            <button onclick="switchTab('tab-smtp')" id="nav-tab-smtp" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="mail" style="width: 16px; height: 16px;"></i>
                SMTP E-Posta
            </button>
            <button onclick="switchTab('tab-security')" id="nav-tab-security" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
                Güvenlik & API
            </button>
            <button onclick="switchTab('tab-maintenance')" id="nav-tab-maintenance" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="database" style="width: 16px; height: 16px;"></i>
                Yedekleme & Bakım
            </button>
            
            <!-- Hatırlatma Kartı (Cohesive Card Design) -->
            <div class="card" style="margin-top: 2rem; padding: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--foreground); font-size: 0.8125rem; margin-bottom: 0.375rem;">
                    <i data-lucide="info" style="width: 16px; height: 16px; color: #3b82f6;"></i>
                    Hatırlatma
                </div>
                <p style="font-size: 0.75rem; color: var(--muted-foreground); margin: 0; line-height: 1.4;">
                    Yapılan değişikliklerin yürürlüğe girmesi için sağ üst köşedeki <b>"Değişiklikleri Kaydet"</b> butonuna tıklamalısınız.
                </p>
            </div>
        </div>

        <!-- Sağ Dinamik Form İçeriği -->
        <div class="md:col-span-9">
            <form id="systemSettingsForm" onsubmit="event.preventDefault();">
                
                <!-- TAB 1: GENEL AYARLAR -->
                <div id="tab-general" class="settings-tab-content space-y-6">
                    
                    <!-- Genel Platform Ayarları Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="globe" style="width: 18px; height: 18px;" class="text-primary"></i>
                                Genel Platform Ayarları
                            </h3>
                            <p class="card-description">Platformun genel tanımlarını ve yerelleştirme dil tercihlerini yapılandırın.</p>
                        </div>
                        <div class="card-content" style="display: flex; flex-direction: column; gap: 1.25rem; padding: 1.5rem;">
                            <!-- Sistem Başlığı -->
                            <div class="form-group">
                                <label for="site_title" class="form-label">Sistem Başlığı (Site Title)</label>
                                <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($site_title); ?>" class="form-input" required>
                                <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">Platformun tarayıcı sekmesinde ve genel arayüz başlıklarında kullanılacak isim.</p>
                            </div>

                            <!-- Yönetici E-Postası -->
                            <div class="form-group">
                                <label for="admin_email" class="form-label">Yönetici Bildirim E-Postası</label>
                                <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($admin_email); ?>" class="form-input" required>
                                <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">Sistem kritik hataları ve rapor bildirim uyarılarının gönderileceği adres.</p>
                            </div>

                            <!-- Sistem Dili -->
                            <div class="form-group">
                                <label for="default_language" class="form-label">Varsayılan Sistem Dili</label>
                                <div style="position: relative; display: flex; width: 100%;">
                                    <select id="default_language" name="default_language" class="form-input" style="appearance: none; cursor: pointer; padding-right: 2.5rem; width: 100%;">
                                        <option value="tr" <?php echo $default_language === 'tr' ? 'selected' : ''; ?>>Türkçe (TR)</option>
                                        <option value="en" <?php echo $default_language === 'en' ? 'selected' : ''; ?>>English (EN)</option>
                                    </select>
                                    <div style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--muted-foreground); display: flex; align-items: center;">
                                        <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                                    </div>
                                </div>
                                <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">Yeni oluşturulan kullanıcılar ve oturum dışı alanlar için varsayılan dil.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sistem Mod Anahtarları Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="sliders" style="width: 18px; height: 18px;" class="text-primary"></i>
                                Sistem Anahtarları (Mod Toggles)
                            </h3>
                            <p class="card-description">Platformun operasyonel modlarını ve yasal uyumluluk pencerelerini anlık yönetin.</p>
                        </div>
                        <div class="card-content" style="padding: 0 1.5rem 1.5rem;">
                            <!-- Bakım Modu -->
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 0; border-bottom: 1px solid var(--border);">
                                <div style="padding-right: 1.5rem;">
                                    <label class="form-label" style="margin-bottom: 0.125rem;">Bakım Modu (Maintenance Mode)</label>
                                    <p style="margin: 0; font-size: 0.75rem; color: var(--muted-foreground);">Aktif edildiğinde, süper adminler haricindeki tüm kullanıcılar "Bakım Yapılıyor" ekranı ile karşılaşır.</p>
                                </div>
                                <div style="display: flex; align-items: center;">
                                    <input type="hidden" name="maintenance_mode" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="maintenance_mode_chk" value="1" <?php echo $maintenance_mode === '1' ? 'checked' : ''; ?> class="sr-only peer">
                                        <div class="w-11 h-6 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary" style="background: var(--muted); border: 1px solid var(--border);"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- KVKK Çerez Bildirimi -->
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 0;">
                                <div style="padding-right: 1.5rem;">
                                    <label class="form-label" style="margin-bottom: 0.125rem;">KVKK ve Çerez Rıza Bildirimi</label>
                                    <p style="margin: 0; font-size: 0.75rem; color: var(--muted-foreground);">Kullanıcı girişi ve kayıt sayfalarında KVKK aydınlatma metnini ve çerez izin barını gösterir.</p>
                                </div>
                                <div style="display: flex; align-items: center;">
                                    <input type="hidden" name="kvkk_consent" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="kvkk_consent_chk" value="1" <?php echo $kvkk_consent === '1' ? 'checked' : ''; ?> class="sr-only peer">
                                        <div class="w-11 h-6 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary" style="background: var(--muted); border: 1px solid var(--border);"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: SMTP E-POSTA AYARLARI -->
                <div id="tab-smtp" class="settings-tab-content space-y-6 hidden">
                    
                    <!-- SMTP Sunucu Bağlantı Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="mail" style="width: 18px; height: 18px;" class="text-primary"></i>
                                SMTP Sunucu Bağlantısı
                            </h3>
                            <p class="card-description">E-posta bildirimlerinin gönderileceği posta sunucusu parametrelerini yapılandırın.</p>
                        </div>
                        <div class="card-content grid grid-cols-1 md:grid-cols-2 gap-4" style="padding: 1.5rem;">
                            <!-- SMTP Host -->
                            <div class="form-group col-span-2 md:col-span-1">
                                <label for="smtp_host" class="form-label">SMTP Sunucu Adresi</label>
                                <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" placeholder="mail.alanadi.com" class="form-input">
                            </div>

                            <!-- SMTP Port -->
                            <div class="form-group col-span-2 md:col-span-1">
                                <label for="smtp_port" class="form-label">SMTP Port Numarası</label>
                                <input type="text" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" placeholder="587" class="form-input">
                            </div>

                            <!-- SMTP User -->
                            <div class="form-group col-span-2 md:col-span-1">
                                <label for="smtp_user" class="form-label">SMTP Kullanıcı Adı (E-Posta)</label>
                                <input type="email" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($smtp_user); ?>" placeholder="bilgi@alanadi.com" class="form-input">
                            </div>

                            <!-- SMTP Password -->
                            <div class="form-group col-span-2 md:col-span-1">
                                <label for="smtp_password" class="form-label">SMTP Şifresi</label>
                                <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($smtp_password); ?>" placeholder="••••••••••••" class="form-input">
                            </div>

                            <!-- SMTP Encryption -->
                            <div class="form-group col-span-2 md:col-span-1">
                                <label for="smtp_encryption" class="form-label">Şifreleme Türü</label>
                                <div style="position: relative; display: flex; width: 100%;">
                                    <select id="smtp_encryption" name="smtp_encryption" class="form-input" style="appearance: none; cursor: pointer; padding-right: 2.5rem; width: 100%;">
                                        <option value="none" <?php echo $smtp_encryption === 'none' ? 'selected' : ''; ?>>Şifreleme Yok (None)</option>
                                        <option value="tls" <?php echo $smtp_encryption === 'tls' ? 'selected' : ''; ?>>STARTTLS (Tavsiye Edilen)</option>
                                        <option value="ssl" <?php echo $smtp_encryption === 'ssl' ? 'selected' : ''; ?>>SSL/TLS (Port 465)</option>
                                    </select>
                                    <div style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--muted-foreground); display: flex; align-items: center;">
                                        <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Sender Name -->
                            <div class="form-group col-span-2 md:col-span-1">
                                <label for="smtp_from_name" class="form-label">Gönderen Adı (From Name)</label>
                                <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp_from_name); ?>" placeholder="SGK Vizit-e Bildirim" class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- SMTP Bağlantı Test Modül Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="send" style="width: 18px; height: 18px;" class="text-indigo-500"></i>
                                E-Posta Gönderim Testi (Real-time Test)
                            </h3>
                            <p class="card-description">Girdiğiniz SMTP ayarlarını kaydetmeden önce e-posta gönderim yeteneğini test edebilirsiniz.</p>
                        </div>
                        <div class="card-content" style="padding: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; width: 100%;">
                                <div style="flex: 1; min-width: 0;">
                                    <input type="email" id="test_email" placeholder="test@gmail.com" class="form-input" style="width: 100% !important; min-width: 0;">
                                </div>
                                <div style="flex-shrink: 0;">
                                    <button type="button" onclick="testSmtpConnection(event)" id="testSmtpBtn" class="btn btn-primary" style="gap: 0.5rem; display: flex; align-items: center; justify-content: center; height: 36px; padding: 0 1rem; white-space: nowrap;">
                                        <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                        Bağlantıyı Test Et
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: GÜVENLİK VE API AYARLARI -->
                <div id="tab-security" class="settings-tab-content space-y-6 hidden">
                    
                    <!-- Güvenlik Korumaları Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="shield" style="width: 18px; height: 18px;" class="text-primary"></i>
                                Güvenlik Korumaları
                            </h3>
                            <p class="card-description">reCAPTCHA bot koruması ve iki faktörlü oturum açma (2FA) tercihlerini belirleyin.</p>
                        </div>
                        <div class="card-content" style="display: flex; flex-direction: column; gap: 1.25rem; padding: 1.5rem;">
                            <!-- Google Recaptcha Site Key -->
                            <div class="form-group">
                                <label for="google_recaptcha_site_key" class="form-label">Google reCAPTCHA v3 Site Key</label>
                                <input type="text" id="google_recaptcha_site_key" name="google_recaptcha_site_key" value="<?php echo htmlspecialchars($google_recaptcha_site_key); ?>" placeholder="6Ld..." class="form-input">
                                <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">Bot saldırılarını engellemek amacıyla üye giriş ve şifre sıfırlama formlarında kullanılır.</p>
                            </div>

                            <!-- Google Recaptcha Secret Key -->
                            <div class="form-group">
                                <label for="google_recaptcha_secret_key" class="form-label">Google reCAPTCHA v3 Secret Key</label>
                                <input type="password" id="google_recaptcha_secret_key" name="google_recaptcha_secret_key" value="<?php echo htmlspecialchars($google_recaptcha_secret_key); ?>" placeholder="••••••••••••••••••••••••••••••••" class="form-input">
                                <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">reCAPTCHA doğrulama istekleri için arka planda sunucunun kullandığı gizli anahtar.</p>
                            </div>

                            <!-- 2FA Toggle -->
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                                <div style="padding-right: 1.5rem;">
                                    <label class="form-label" style="margin-bottom: 0.125rem;">Çift Faktörlü Doğrulama (2FA)</label>
                                    <p style="margin: 0; font-size: 0.75rem; color: var(--muted-foreground);">Aktif edildiğinde, süper adminler ve yöneticiler her girişte e-posta onay kodu girmek zorundadır.</p>
                                </div>
                                <div style="display: flex; align-items: center;">
                                    <input type="hidden" name="two_factor_auth" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="two_factor_auth_chk" value="1" <?php echo $two_factor_auth === '1' ? 'checked' : ''; ?> class="sr-only peer">
                                        <div class="w-11 h-6 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary" style="background: var(--muted); border: 1px solid var(--border);"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Geliştirici Entegrasyon API Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="key" style="width: 18px; height: 18px;" class="text-emerald-500"></i>
                                Geliştirici Entegrasyon API
                            </h3>
                            <p class="card-description">Harici otomasyon ve ERP sistem entegrasyonu için gizli API erişim anahtarınızı yönetin.</p>
                        </div>
                        <div class="card-content" style="display: flex; flex-direction: column; gap: 1.25rem; padding: 0 1.5rem 1.5rem;">
                            <!-- API Erişim Modu -->
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
                                <div style="padding-right: 1.5rem;">
                                    <label class="form-label" style="margin-bottom: 0.125rem;">Genel API Erişimi</label>
                                    <p style="margin: 0; font-size: 0.75rem; color: var(--muted-foreground);">API isteklerinin kabul edilmesini sağlar. Pasif duruma getirildiğinde tüm API uçları engellenir.</p>
                                </div>
                                <div style="display: flex; align-items: center;">
                                    <input type="hidden" name="api_enabled" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="api_enabled_chk" value="1" <?php echo $api_enabled === '1' ? 'checked' : ''; ?> class="sr-only peer">
                                        <div class="w-11 h-6 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary" style="background: var(--muted); border: 1px solid var(--border);"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- API Gizli Anahtarı -->
                            <div class="form-group">
                                <label for="api_secret_key" class="form-label">API Secret Key (Gizli Erişim Şifresi)</label>
                                <div style="display: flex; gap: 0.75rem;">
                                    <div style="position: relative; flex: 1;">
                                        <input type="text" id="api_secret_key" name="api_secret_key" value="<?php echo htmlspecialchars($api_secret_key); ?>" readonly class="form-input" style="font-family: monospace; background: var(--muted); padding-right: 2.5rem; select-all: true;">
                                        <button type="button" onclick="copyApiSecret(event)" style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted-foreground);" class="hover:text-foreground">
                                            <i data-lucide="copy" style="width: 16px; height: 16px;"></i>
                                        </button>
                                    </div>
                                    <button type="button" onclick="generateNewApiKey(event)" class="btn btn-outline" style="gap: 0.5rem; display: flex; align-items: center;">
                                        <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                        Yenile
                                    </button>
                                </div>
                                <p style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 0.25rem;">Tüm API isteklerinizin Header bloğunda <code>X-API-KEY</code> anahtarı ile bu gizli değeri göndermelisiniz.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: BAKIM VE VERİTABANI YEDEĞİ -->
                <div id="tab-maintenance" class="settings-tab-content space-y-6 hidden">
                    
                    <!-- Veritabanı Yönetimi Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="database" style="width: 18px; height: 18px;" class="text-primary"></i>
                                Veritabanı Yönetimi & Yedekleme
                            </h3>
                            <p class="card-description">Veritabanı tablolarının tam yedeğini içeren SQL dosyasını indirerek sisteminizi yedekleyin.</p>
                        </div>
                        <div class="card-content" style="padding: 1.5rem;">
                            <div style="display: flex; flex-direction: column; md-flex-direction: row; justify-content: space-between; align-items: center; gap: 1rem; padding: 1.25rem; border: 1px dashed var(--border); border-radius: 8px; background: rgba(var(--muted), 0.1);">
                                <div style="flex: 1; width: 100%;">
                                    <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600;">Aktif Veritabanı Yedeği</h4>
                                    <p style="margin: 0.25rem 0 0; font-size: 0.75rem; color: var(--muted-foreground);">Tüm tablo yapılarını ve kayıtları içeren sıkıştırılmış SQL dökümü.</p>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.75rem; font-weight: 600;">
                                        <span style="padding: 0.125rem 0.5rem; border-radius: 4px; background: var(--muted); border: 1px solid var(--border);">Veritabanı Boyutu: <?php echo $dbSize; ?></span>
                                        <span style="padding: 0.125rem 0.5rem; border-radius: 4px; background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <span style="width: 5px; height: 5px; border-radius: 50%; background: #22c55e;"></span> Sunucu Aktif
                                        </span>
                                    </div>
                                </div>
                                <div style="width: 100%; sm:width: auto;">
                                    <button type="button" onclick="downloadDatabaseBackup(event)" class="btn btn-primary" style="gap: 0.5rem; display: flex; align-items: center; justify-content: center; width: 100%;">
                                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                                        Veritabanı Yedeği İndir (.SQL)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sistem Optimizasyon Kartı -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title flex items-center gap-2" style="font-size: 1rem; margin: 0;">
                                <i data-lucide="sparkles" style="width: 18px; height: 18px;" class="text-amber-500"></i>
                                Sistem Optimizasyonu & Önbellek
                            </h3>
                            <p class="card-description">Platform performansını artırmak amacıyla geçici önbellekleri ve eski günlükleri temizleyin.</p>
                        </div>
                        <div class="card-content grid grid-cols-1 md:grid-cols-2 gap-4" style="padding: 1.5rem;">
                            <!-- Önbellek Temizleme -->
                            <div style="padding: 1.25rem; border: 1px solid var(--border); border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between; gap: 1.25rem;">
                                <div>
                                    <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600;">Sistem Önbelleği</h4>
                                    <p style="margin: 0.25rem 0 0; font-size: 0.75rem; color: var(--muted-foreground); line-height: 1.4;">Arayüz şablonlarını ve geçici sorgu önbelleklerini temizler.</p>
                                </div>
                                <button type="button" onclick="clearSystemCache(event)" id="cacheClearBtn" class="btn btn-outline" style="gap: 0.5rem; display: flex; align-items: center; justify-content: center; width: 100%;">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                    Önbelleği Temizle
                                </button>
                            </div>

                            <!-- Log Temizleme -->
                            <div style="padding: 1.25rem; border: 1px solid var(--border); border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between; gap: 1.25rem;">
                                <div>
                                    <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600;">Log Veritabanı Temizliği</h4>
                                    <p style="margin: 0.25rem 0 0; font-size: 0.75rem; color: var(--muted-foreground); line-height: 1.4;">Son 6 aydan eski işlem günlüklerini arşiv sunucusuna taşır.</p>
                                </div>
                                <button type="button" onclick="optimizeLogTables(event)" id="logClearBtn" class="btn btn-outline" style="gap: 0.5rem; display: flex; align-items: center; justify-content: center; width: 100%;">
                                    <i data-lucide="archive" style="width: 14px; height: 14px;"></i>
                                    Eski Logları Arşivle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

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
    
    /* Toggle Anahtarı Stilleri */
    .peer:checked ~ div {
        background-color: var(--primary) !important;
    }
    
    /* Dönme Animasyonu */
    .animate-spin-slow {
        animation: spin 8s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
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
    // Lucide ikonlarını sıfırdan oluştur
    if (typeof lucide !== 'undefined') {
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
        
        // İlgili navigasyon butonunu aktif yap
        const navId = 'nav-' + tabId;
        const targetNav = document.getElementById(navId);
        if (targetNav) {
            targetNav.classList.add('active');
        }
        
        // İkonları yeniden render et
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    /**
     * Yeni API Erişim Anahtarı Oluşturma
     */
    function generateNewApiKey(event) {
        event.preventDefault();
        
        const array = new Uint8Array(16);
        window.crypto.getRandomValues(array);
        let randomKey = '';
        for (let i = 0; i < array.length; i++) {
            randomKey += ('0' + array[i].toString(16)).slice(-2);
        }
        
        const apiKeyField = document.getElementById('api_secret_key');
        if (apiKeyField) {
            apiKeyField.value = randomKey;
            
            if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                App.toast('info', 'API Anahtarı Değişti', 'Yeni anahtar üretildi! Aktif olması için "Değişiklikleri Kaydet" butonuna tıklayınız.');
            }
        }
    }

    /**
     * API Erişim Anahtarını Panoya Kopyalama
     */
    function copyApiSecret(event) {
        event.preventDefault();
        const apiKeyField = document.getElementById('api_secret_key');
        if (apiKeyField) {
            apiKeyField.select();
            apiKeyField.setSelectionRange(0, 99999); // Mobil için
            navigator.clipboard.writeText(apiKeyField.value);
            
            if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                App.toast('success', 'Panoya Kopyalandı', 'API gizli anahtarı başarıyla kopyalandı.');
            }
        }
    }

    /**
     * SMTP Bağlantı Testi Gönderimi
     */
    function testSmtpConnection(event) {
        event.preventDefault();
        
        const testEmail = document.getElementById('test_email').value;
        const smtpHost = document.getElementById('smtp_host').value;
        const smtpPort = document.getElementById('smtp_port').value;
        const smtpUser = document.getElementById('smtp_user').value;
        const smtpPass = document.getElementById('smtp_password').value;
        const smtpEnc = document.getElementById('smtp_encryption').value;
        const smtpFromName = document.getElementById('smtp_from_name').value;
        
        if (!testEmail) {
            if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                App.toast('warning', 'Hata', 'Lütfen test e-postasının gönderileceği alıcı adresini giriniz!');
            }
            return;
        }

        const btn = document.getElementById('testSmtpBtn');
        const originalHtml = btn.innerHTML;
        
        // Yükleniyor Durumu
        btn.disabled = true;
        btn.innerHTML = '<i class="animate-spin mr-1 border-2 border-white border-t-transparent rounded-full w-4 h-4 inline-block"></i> Gönderiliyor...';
        
        // AJAX isteği başlat
        fetch('smtp-test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                test_email: testEmail,
                smtp_host: smtpHost,
                smtp_port: smtpPort,
                smtp_user: smtpUser,
                smtp_password: smtpPass,
                smtp_encryption: smtpEnc,
                smtp_from_name: smtpFromName
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            if (data.status === 'success') {
                if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                    App.toast('success', 'Bağlantı Başarılı', data.message);
                }
            } else {
                if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                    App.toast('error', 'SMTP Doğrulama Başarısız', data.message);
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                App.toast('error', 'Bağlantı Hatası', 'Sunucuya bağlanırken beklenmeyen bir ağ hatası oluştu.');
            }
        });
    }

    /**
     * Veritabanı Yedeği İndirme Tetikleyicisi
     */
    function downloadDatabaseBackup(event) {
        event.preventDefault();
        
        if (typeof App !== 'undefined' && typeof App.toast === 'function') {
            App.toast('info', 'Yedek Alınıyor', 'Veritabanı tabloları derleniyor, indirme birazdan başlayacaktır...');
        }
        
        window.location.href = 'sistem-yedek-indir';
    }

    /**
     * Sistem Önbelleği Temizleme (Mock Aksiyon)
     */
    function clearSystemCache(event) {
        event.preventDefault();
        const btn = document.getElementById('cacheClearBtn');
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="animate-spin mr-1 border-2 border-foreground border-t-transparent rounded-full w-3.5 h-3.5 inline-block"></i> Temizleniyor...';
        
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                App.toast('success', 'Önbellek Sıfırlandı', 'Tüm derlenmiş şablonlar ve arabellek verileri başarıyla temizlendi.');
            }
        }, 1500);
    }

    /**
     * İşlem Loglarını Optimize Etme (Mock Aksiyon)
     */
    function optimizeLogTables(event) {
        event.preventDefault();
        const btn = document.getElementById('logClearBtn');
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="animate-spin mr-1 border-2 border-foreground border-t-transparent rounded-full w-3.5 h-3.5 inline-block"></i> Optimize Ediliyor...';
        
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                App.toast('success', 'Arşivleme Başarılı', 'Son 6 ay dışındaki eski işlem ve bildirim günlükleri arşiv veri havuzuna taşındı.');
            }
        }, 1800);
    }

    /**
     * Tüm Sistem Ayarlarını AJAX ile Kaydetme
     */
    function saveAllSettings(event) {
        if (event) event.preventDefault();
        
        const saveBtn = document.getElementById('globalSaveBtn');
        const originalHtml = saveBtn.innerHTML;
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="animate-spin mr-1 border-2 border-white border-t-transparent rounded-full w-4 h-4 inline-block"></i> Kaydediliyor...';
        
        const maintenanceMode = document.getElementById('maintenance_mode_chk').checked ? '1' : '0';
        const kvkkConsent = document.getElementById('kvkk_consent_chk').checked ? '1' : '0';
        const twoFactorAuth = document.getElementById('two_factor_auth_chk').checked ? '1' : '0';
        const apiEnabled = document.getElementById('api_enabled_chk').checked ? '1' : '0';

        const payload = {
            site_title: document.getElementById('site_title').value,
            admin_email: document.getElementById('admin_email').value,
            default_language: document.getElementById('default_language').value,
            maintenance_mode: maintenanceMode,
            kvkk_consent: kvkkConsent,
            
            smtp_host: document.getElementById('smtp_host').value,
            smtp_port: document.getElementById('smtp_port').value,
            smtp_user: document.getElementById('smtp_user').value,
            smtp_password: document.getElementById('smtp_password').value,
            smtp_encryption: document.getElementById('smtp_encryption').value,
            smtp_from_name: document.getElementById('smtp_from_name').value,
            
            google_recaptcha_site_key: document.getElementById('google_recaptcha_site_key').value,
            google_recaptcha_secret_key: document.getElementById('google_recaptcha_secret_key').value,
            two_factor_auth: twoFactorAuth,
            api_enabled: apiEnabled,
            api_secret_key: document.getElementById('api_secret_key').value
        };
        
        fetch('ayarlar-guncelle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            if (data.status === 'success') {
                if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                    App.toast('success', 'Başarılı', data.message);
                }
            } else {
                if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                    App.toast('error', 'Hata', data.message);
                }
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            if (typeof App !== 'undefined' && typeof App.toast === 'function') {
                App.toast('error', 'Hata', 'Ayarlar kaydedilirken sunucu hatası oluştu.');
            }
        });
    }
</script>