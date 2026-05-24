<?php

use App\Helper\Security;
use Models\UserModel;
use Models\KullaniciAyarModel;
use Models\KvkkRizaModel;
use Models\KullaniciAbonelikModel;
use App\Helper\Date;


Security::checkFirma();
// Security::hasActiveSubscription();

$UserModel = new UserModel();
$KullaniciAyarModel = new KullaniciAyarModel();
$KvkkRizaModel = new KvkkRizaModel();


$title = "Profil Bilgileri";

$hataMesaji = '';
$basariMesaji = '';


$user = $UserModel->find($_SESSION['kullanici_id'] ?? null);
$aydinlatma_metni = $KvkkRizaModel->getKvkkRizaByUserId($_SESSION['kullanici_id'], 'aydinlatma_metni');
$gizlilik_sozlesmesi = $KvkkRizaModel->getKvkkRizaByUserId($_SESSION['kullanici_id'], 'gizlilik_sozlesmesi');
$acik_riza_beyani = $KvkkRizaModel->getKvkkRizaByUserId($_SESSION['kullanici_id'], 'acik_riza_beyani');

$saat_9_mail_bildirimi = $KullaniciAyarModel->getSetting('saat_9_mail_bildirimi');
$rapor_otomatik_onay_bildirim = $KullaniciAyarModel->getSetting('rapor_otomatik_onay_bildirim');

$giris_kayitlari = $UserModel->getLoginRecords($_SESSION['kullanici_id'], 15);

if (!$user) {
    $hataMesaji = 'Kullanıcı bulunamadı.';
}

$KullaniciAbonelikModel = new KullaniciAbonelikModel();
$db = \Core\Database::getInstance()->getConnection();
$bugun = date('Y-m-d');

// Aktif Abonelik
$aktif_stmt = $db->prepare("SELECT ka.*, ap.ad as paket_adi, ap.fiyat as paket_fiyat, ap.sure as paket_sure
                            FROM kullanici_abonelikleri ka 
                            LEFT JOIN abonelik_paketleri ap ON ka.paket_id = ap.id 
                            WHERE ka.kullanici_id = ? AND ka.durum = 'aktif' AND ka.baslangic_tarihi <= ? AND ka.bitis_tarihi >= ? 
                            ORDER BY ka.id DESC LIMIT 1");
$aktif_stmt->execute([$_SESSION['kullanici_id'], $bugun, $bugun]);
$aktif_abonelik = $aktif_stmt->fetch(PDO::FETCH_OBJ);

// Satın Alma Geçmişi (Tüm Abonelikleri)
$gecmis_stmt = $db->prepare("SELECT ka.*, ap.ad as paket_adi, ap.fiyat as paket_fiyat, ap.sure as paket_sure 
                             FROM kullanici_abonelikleri ka 
                             LEFT JOIN abonelik_paketleri ap ON ka.paket_id = ap.id 
                             WHERE ka.kullanici_id = ? 
                             ORDER BY ka.id DESC");
$gecmis_stmt->execute([$_SESSION['kullanici_id']]);
$abonelik_gecmisi = $gecmis_stmt->fetchAll(PDO::FETCH_OBJ);

$initials = '';
$nameParts = explode(' ', $user->adi_soyadi ?? 'K');
foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
$initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
?>
<!-- Head ve diğer layout include'ları -->
<?php include 'layouts/head.php'; ?>
<?php include 'layouts/preloader.php'; ?>
<?php include 'layouts/topbar.php'; ?>
<?php include 'layouts/navbar.php'; ?>

<!-- ANA İÇERİK BÖLÜMÜ -->
<div class="animate-in flex flex-col gap-6 w-full py-2 px-1">
    <!-- Sayfa Başlığı ve Kaydet Butonu -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800/80 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Profil Ayarları</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kişisel bilgilerinizi, güvenlik tercihlerinizi ve hesap detaylarınızı buradan yönetin.
            </p>
        </div>
        <div class="flex items-center gap-2 self-start md:self-auto flex-shrink-0">
            <!-- Ana Kaydet Butonu (Kişisel Bilgiler & Şifre Değiştir formunu kaydetmek için kullanılır) -->
            <button onclick="document.getElementById('kaydetButton').click();" class="btn btn-primary flex items-center gap-1.5 shadow">
                <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                <span>Değişiklikleri Kaydet</span>
            </button>
        </div>
    </div>

    <?php if ($hataMesaji): ?>
        <div class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Hata!</h4>
                <p class="text-xs mt-1 opacity-90"><?php echo htmlspecialchars($hataMesaji); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Profil Grid Düzeni -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 w-full">
        
        <!-- Sol Dikey Sekme Navigasyonu -->
        <div class="md:col-span-3 flex flex-col gap-1 w-full">
            <!-- Profil Avatar Gösterimi -->
            <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm" style="padding: 1.25rem; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: hsl(var(--primary)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 700; border: 3px solid var(--border);">
                    <?php echo $initials; ?>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-50" style="margin: 0;"><?php echo htmlspecialchars($user->adi_soyadi ?: $user->kullanici_adi); ?></h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400" style="margin: 0.125rem 0 0; word-break: break-all;"><?php echo htmlspecialchars($user->email); ?></p>
                </div>
            </div>

            <button onclick="switchTab('tab-personal')" id="nav-tab-personal" class="settings-nav-item active flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                Kişisel Bilgiler
            </button>
            
            <button onclick="switchTab('tab-password')" id="nav-tab-password" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="key-round" style="width: 16px; height: 16px;"></i>
                Şifre Değiştir
            </button>

            <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
                <button onclick="switchTab('tab-notifications')" id="nav-tab-notifications" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                    <i data-lucide="bell" style="width: 16px; height: 16px;"></i>
                    Bildirim Ayarları
                </button>
            <?php endif; ?>

            <button onclick="switchTab('tab-logins')" id="nav-tab-logins" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                <i data-lucide="history" style="width: 16px; height: 16px;"></i>
                Giriş Kayıtları
            </button>

            <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
                <button onclick="switchTab('tab-kvkk')" id="nav-tab-kvkk" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                    <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
                    KVKK Bilgileri
                </button>
                <button onclick="switchTab('tab-account')" id="nav-tab-account" class="settings-nav-item flex items-center gap-2.5 px-3 py-2 rounded-md text-left font-medium transition-all">
                    <i data-lucide="credit-card" style="width: 16px; height: 16px;"></i>
                    Hesap İşlemleri
                </button>
            <?php endif; ?>
            
            <!-- Hatırlatma Kartı -->
            <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm" style="margin-top: 2rem; padding: 1.25rem;">
                <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-50" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.375rem;">
                    <i data-lucide="info" style="width: 16px; height: 16px; color: #3b82f6;"></i>
                    Hatırlatma
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400" style="margin: 0; line-height: 1.4;">
                    Profil değişikliklerinizin kaydedilmesi için sağ üst köşedeki <b>"Değişiklikleri Kaydet"</b> butonuna tıklayınız.
                </p>
            </div>
        </div>

        <!-- Sağ Dinamik Form İçeriği -->
        <div class="md:col-span-9 w-full">
            <form id="profileForm" onsubmit="event.preventDefault();" style="display: contents;">
                
                <!-- TAB 1: KİŞİSEL BİLGİLER -->
                <div id="tab-personal" class="settings-tab-content space-y-6 w-full">
                    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm w-full">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
                            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                                <i data-lucide="user" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                                Kişisel Bilgiler
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Profilinizi ve iletişim tercihlerinizi yapılandırın.</p>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50">Kullanıcı Adı</label>
                                    <input type="text" id="kullanici_adi" name="kullanici_adi" value="<?php echo htmlspecialchars($user->kullanici_adi); ?>" readonly class="form-input opacity-70 cursor-not-allowed bg-zinc-50 dark:bg-zinc-900/50" placeholder="Kullanıcı Adı">
                                </div>
                                <div class="form-group">
                                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50">E-posta Adresi</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" readonly class="form-input opacity-70 cursor-not-allowed bg-zinc-50 dark:bg-zinc-900/50" placeholder="E-posta Adresi">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50">Ad Soyad</label>
                                    <input type="text" id="adi_soyadi" name="adi_soyadi" value="<?php echo htmlspecialchars($user->adi_soyadi); ?>" class="form-input" placeholder="Ad Soyad">
                                </div>
                                <div class="form-group">
                                    <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50">Telefon Numarası</label>
                                    <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($user->telefon); ?>" class="form-input" placeholder="Telefon(Opsiyonel)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ŞİFRE DEĞİŞTİR -->
                <div id="tab-password" class="settings-tab-content space-y-6 w-full hidden">
                    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm w-full">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
                            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                                <i data-lucide="key-round" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                                Şifre Değiştir
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Hesap güvenliğinizi artırmak amacıyla şifrenizi yenileyebilirsiniz.</p>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="form-group">
                                <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50">Mevcut Şifre</label>
                                <input type="password" id="mevcut_sifre" name="mevcut_sifre" autocomplete="new-password" class="form-input" placeholder="Mevcut Şifrenizi Girin">
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">Değişiklikleri kaydetmek için mevcut şifrenizi girmeniz zorunludur.</p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50">Yeni Şifre</label>
                                <input type="password" id="yeni_sifre" name="yeni_sifre" class="form-input" placeholder="Yeni Şifre">
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">Şifrenizi değiştirmek istemiyorsanız bu alanı boş bırakabilirsiniz.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gizli tetikleyici buton (kullanici.js kaydetButton olarak bu butonu dinler) -->
                <button type="button" id="kaydetButton" style="display: none;"></button>
            </form>

            <!-- TAB 3: BİLDİRİM AYARLARI -->
            <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
                <div id="tab-notifications" class="settings-tab-content space-y-6 w-full hidden">
                    <form id="bildirimForm" style="display: contents;">
                        <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm w-full">
                            <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
                                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                                    <i data-lucide="bell" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                                    Bildirim Ayarları
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">E-posta bildirimleri ve otomatik onay tercihlerini yönetin.</p>
                            </div>
                            
                            <div class="flex flex-col gap-6">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" id="saat_9_mail_bildirimi" name="saat_9_mail_bildirimi" value="1" <?php echo $saat_9_mail_bildirimi == 1 ? 'checked' : ''; ?> class="mt-1" style="width: 16px; height: 16px; accent-color: hsl(var(--primary));">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">Bekleyen Rapor Onayı Hatırlatma</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Hafta içi her gün saat 09:00'da bekleyen raporların hatırlatma bildirimi gönderilir.</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" id="rapor_otomatik_onay_bildirim" name="rapor_otomatik_onay_bildirim" value="1" <?php echo $rapor_otomatik_onay_bildirim == 1 ? 'checked' : ''; ?> class="mt-1" style="width: 16px; height: 16px; accent-color: hsl(var(--primary));">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">Otomatik Rapor Onayı</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Otomatik olarak onaylanan vizite raporları hakkında bilgilendirme e-postası gönderilir.</span>
                                    </div>
                                </label>

                                <div class="pt-2">
                                    <button type="button" id="bildirimKaydetButton" class="btn btn-primary flex items-center gap-1.5 shadow text-xs">
                                        <i data-lucide="save" style="width: 14px; height: 14px;"></i>
                                        <span>Tercihleri Kaydet</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- TAB 4: GİRİŞ KAYITLARI -->
            <div id="tab-logins" class="settings-tab-content space-y-6 w-full hidden">
                <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm w-full">
                    <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
                        <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                            <i data-lucide="history" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                            Giriş Kayıtları
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Hesabınıza yapılan son 15 başarılı giriş ve güvenlik kayıtları.</p>
                    </div>

                    <?php if (count($giris_kayitlari) > 0) { ?>
                        <div class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Giriş Tarihi</th>
                                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">IP Adresi</th>
                                        <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tarayıcı</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                    <?php foreach ($giris_kayitlari as $giris) { ?>
                                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                            <td class="py-3 px-4 text-xs font-medium text-zinc-700 dark:text-zinc-300"><?php echo $giris->created_at; ?></td>
                                            <td class="py-3 px-4 text-xs font-mono text-zinc-700 dark:text-zinc-300"><?php echo $giris->ip_address; ?></td>
                                            <td class="py-3 px-4 text-xs text-zinc-500 dark:text-zinc-400" title="<?php echo htmlspecialchars($giris->browser); ?>" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars($giris->browser); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-zinc-400">
                            <i data-lucide="history" class="w-8 h-8 opacity-45"></i>
                            <span class="text-xs font-medium">Henüz kayıtlı bir giriş bulunmamaktadır.</span>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- TAB 5: KVKK BİLGİLERİ -->
            <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
                <div id="tab-kvkk" class="settings-tab-content space-y-6 w-full hidden">
                    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm w-full">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
                            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                                <i data-lucide="file-text" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                                KVKK Bilgileri
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Kullanım koşulları ve kişisel verilerin korunması kanunu sözleşmeleri.</p>
                        </div>

                        <div class="flex flex-col gap-4">
                            <details class="group border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-4 [&_summary::-webkit-details-marker]:hidden cursor-pointer">
                                <summary class="flex items-center justify-between font-semibold text-xs text-zinc-900 dark:text-zinc-50 select-none">
                                    <span>Aydınlatma Metni</span>
                                    <span class="transition group-open:rotate-180"><i data-lucide="chevron-down" class="w-4 h-4"></i></span>
                                </summary>
                                <div class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400 border-t border-zinc-100 dark:border-zinc-800/80 pt-3 max-h-[300px] overflow-y-auto pr-2">
                                    <?php echo $aydinlatma_metni->icerik ?? 'Aydınlatma metni tanımlanmamış.'; ?>
                                </div>
                            </details>

                            <details class="group border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-4 [&_summary::-webkit-details-marker]:hidden cursor-pointer">
                                <summary class="flex items-center justify-between font-semibold text-xs text-zinc-900 dark:text-zinc-50 select-none">
                                    <span>Gizlilik Sözleşmesi</span>
                                    <span class="transition group-open:rotate-180"><i data-lucide="chevron-down" class="w-4 h-4"></i></span>
                                </summary>
                                <div class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400 border-t border-zinc-100 dark:border-zinc-800/80 pt-3 max-h-[300px] overflow-y-auto pr-2">
                                    <?php echo $gizlilik_sozlesmesi->icerik ?? 'Gizlilik sözleşmesi tanımlanmamış.'; ?>
                                </div>
                            </details>

                            <details class="group border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-4 [&_summary::-webkit-details-marker]:hidden cursor-pointer">
                                <summary class="flex items-center justify-between font-semibold text-xs text-zinc-900 dark:text-zinc-50 select-none">
                                    <span>Açık Rıza Metni</span>
                                    <span class="transition group-open:rotate-180"><i data-lucide="chevron-down" class="w-4 h-4"></i></span>
                                </summary>
                                <div class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400 border-t border-zinc-100 dark:border-zinc-800/80 pt-3 max-h-[300px] overflow-y-auto pr-2">
                                    <?php echo $acik_riza_beyani->icerik ?? 'Açık rıza metni tanımlanmamış.'; ?>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TAB 6: HESAP İŞLEMLERİ -->
            <?php if ($userRole == "admin" || $userRole == "superadmin") : ?>
                <div id="tab-account" class="settings-tab-content space-y-6 w-full hidden">
                    
                    <!-- AKTİF ABONELİK BİLGİLERİ -->
                    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm w-full">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                                    <i data-lucide="sparkles" style="width: 18px; height: 18px;" class="text-amber-500"></i>
                                    Aktif Abonelik Bilgileri
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Mevcut abonelik durumunuz, paket detayları ve kalan kullanım süreniz.</p>
                            </div>
                            <a href="abonelik-paketleri" class="btn btn-primary flex items-center gap-1.5 shadow text-xs font-semibold self-start sm:self-auto" style="text-decoration: none;">
                                <i data-lucide="shopping-bag" style="width: 14px; height: 14px;"></i>
                                <span>Yeni Abonelik Satın Al</span>
                            </a>
                        </div>

                        <?php if ($aktif_abonelik): ?>
                            <?php 
                                $kalan_gun = Date::getRemainingDays($aktif_abonelik->bitis_tarihi); 
                                $is_expired = $kalan_gun < 0;
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="p-4 rounded-xl border border-zinc-100 dark:border-zinc-800/50 bg-zinc-50/50 dark:bg-zinc-900/30 flex flex-col gap-1">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">Aktif Paket</span>
                                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200"><?php echo htmlspecialchars($aktif_abonelik->paket_adi); ?></span>
                                </div>
                                <div class="p-4 rounded-xl border border-zinc-100 dark:border-zinc-800/50 bg-zinc-50/50 dark:bg-zinc-900/30 flex flex-col gap-1">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">Başlangıç Tarihi</span>
                                    <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200"><?php echo Date::dmY($aktif_abonelik->baslangic_tarihi); ?></span>
                                </div>
                                <div class="p-4 rounded-xl border border-zinc-100 dark:border-zinc-800/50 bg-zinc-50/50 dark:bg-zinc-900/30 flex flex-col gap-1">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">Bitiş Tarihi</span>
                                    <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200"><?php echo Date::dmY($aktif_abonelik->bitis_tarihi); ?></span>
                                </div>
                                <div class="p-4 rounded-xl border border-zinc-100 dark:border-zinc-800/50 bg-zinc-50/50 dark:bg-zinc-900/30 flex flex-col gap-1">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">Kalan Süre</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <?php if ($is_expired): ?>
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400">Süresi Doldu</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400"><?php echo $kalan_gun; ?> Gün Kaldı</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center gap-3 py-6 px-4 text-center border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50/20 dark:bg-zinc-900/10">
                                <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400">
                                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-zinc-950 dark:text-zinc-50">Aktif Aboneliğiniz Bulunmuyor</h4>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 max-w-[400px] leading-relaxed">
                                        Sistem üzerinden otomatik vizite onaylama, anlık SMS/E-posta bildirimleri ve toplu sorgulama gibi özellikleri kullanabilmek için lütfen aktif bir paket satın alın.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SATIN ALMA GEÇMİŞİ -->
                    <div class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm w-full">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 pb-4 mb-5">
                            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-2">
                                <i data-lucide="receipt" style="width: 18px; height: 18px;" class="text-zinc-700 dark:text-zinc-300"></i>
                                Satın Alma Geçmişi
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Daha önce aldığınız tüm üyelik ve abonelik işlemlerinin detaylı dökümü.</p>
                        </div>

                        <?php if (count($abonelik_gecmisi) > 0): ?>
                            <div class="overflow-x-auto w-full border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-sm">
                                <table class="w-full border-collapse text-left">
                                    <thead>
                                        <tr class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
                                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Abonelik Paketi</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Başlangıç</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Bitiş</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tutar</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                        <?php foreach ($abonelik_gecmisi as $abonelik): ?>
                                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                                <td class="py-3 px-4 text-xs font-bold text-zinc-800 dark:text-zinc-200"><?php echo htmlspecialchars($abonelik->paket_adi); ?></td>
                                                <td class="py-3 px-4 text-xs text-zinc-600 dark:text-zinc-400"><?php echo Date::dmY($abonelik->baslangic_tarihi); ?></td>
                                                <td class="py-3 px-4 text-xs text-zinc-600 dark:text-zinc-400"><?php echo Date::dmY($abonelik->bitis_tarihi); ?></td>
                                                <td class="py-3 px-4 text-xs font-mono text-zinc-700 dark:text-zinc-300">
                                                    <?php echo isset($abonelik->paket_fiyat) ? number_format($abonelik->paket_fiyat, 2, ',', '.') . ' ₺' : '0,00 ₺'; ?>
                                                </td>
                                                <td class="py-3 px-4 text-xs">
                                                    <?php 
                                                        switch ($abonelik->durum) {
                                                            case 'aktif':
                                                                echo '<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400">Aktif</span>';
                                                                break;
                                                            case 'sona_erdi':
                                                                echo '<span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-50 text-zinc-600 dark:bg-zinc-800/40 dark:text-zinc-400">Süresi Doldu</span>';
                                                                break;
                                                            case 'iptal':
                                                                echo '<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-750 dark:bg-rose-950/20 dark:text-rose-400">İptal Edildi</span>';
                                                                break;
                                                            case 'onay_bekliyor':
                                                                echo '<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400">Onay Bekliyor</span>';
                                                                break;
                                                            default:
                                                                echo '<span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-50 text-zinc-600 dark:bg-zinc-800/40 dark:text-zinc-400">' . htmlspecialchars($abonelik->durum) . '</span>';
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center gap-2 py-8 text-zinc-400">
                                <i data-lucide="receipt" class="w-8 h-8 opacity-45"></i>
                                <span class="text-xs font-medium">Henüz kayıtlı bir satın alma işleminiz bulunmamaktadır.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- HESAP SİLME İŞLEMLERİ -->
                    <div class="card border border-red-200 dark:border-red-900/30 rounded-xl bg-red-50/10 dark:bg-red-950/5 p-6 shadow-sm w-full">
                        <div class="border-b border-red-100 dark:border-red-900/20 pb-4 mb-5">
                            <h3 class="font-bold text-sm text-red-650 dark:text-red-400 flex items-center gap-2">
                                <i data-lucide="shield-alert" style="width: 18px; height: 18px;" class="text-red-550"></i>
                                Hesabı Kapat / Sil
                            </h3>
                            <p class="text-xs text-red-500/80 dark:text-red-400/60 mt-1">Hesabınızı kalıcı olarak sonlandırma veya kaldırma işlemleri.</p>
                        </div>

                        <div class="flex flex-col gap-4">
                            <div class="border border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300 rounded-xl p-4 flex gap-3">
                                <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                                <div>
                                    <h4 class="font-bold text-sm">Hesabınızı Silmek Geri Alınamaz!</h4>
                                    <p class="text-xs mt-1 leading-relaxed opacity-90">
                                        Hesabınızı silmek istediğinizde, tüm kişisel verileriniz, işyerleriniz ve ödeme bilgileriniz sistemden kalıcı olarak silinecektir. Bu işlem geri alınamaz.
                                    </p>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="button" onclick="document.getElementById('hesapSilModal').showModal();" class="btn btn-danger flex items-center gap-1.5 shadow text-xs">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                    <span>Hesabımı Sil</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hesap Silme Modalı (Native HTML5 Dialog) -->
<dialog id="hesapSilModal" class="card border border-zinc-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 shadow-xl overflow-hidden p-0" style="width: 480px; max-width: 90%; border: none; outline: none; box-sizing: border-box; background: var(--card); color: var(--foreground);">
    <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-6 py-4 flex items-center justify-between">
        <h4 class="font-bold text-sm text-zinc-900 dark:text-zinc-50 flex items-center gap-1.5" id="defaultModalLabel">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i> Hesabımı Sil!
        </h4>
        <button type="button" onclick="document.getElementById('hesapSilModal').close();" class="close text-zinc-400 hover:text-zinc-500 dark:hover:text-zinc-300 border-none bg-transparent cursor-pointer" style="font-size: 1.25rem;">&times;</button>
    </div>
    <form action="" id="deleteAccountForm">
        <div class="modal-body px-6 py-4 flex flex-col gap-3">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                Hesabınızı silmek istediğinizde, tüm kişisel verileriniz, tanımlı işyerleriniz ve ödeme bilgileriniz sistemden kalıcı olarak silinecektir. Bu işlem geri alınamaz.
            </p>
            <div class="form-group mt-2">
                <label class="form-label text-xs font-semibold text-zinc-900 dark:text-zinc-50">Onaylamak için mevcut şifrenizi giriniz:</label>
                <input type="password" id="mevcut_sifre_modal" name="mevcut_sifre" autocomplete="new-password" class="form-input mt-1.5" placeholder="Mevcut şifreniz" required>
            </div>
        </div>
        <div class="modal-footer border-t border-zinc-100 dark:border-zinc-800/80 px-6 py-4 flex items-center justify-end gap-2">
            <button type="button" onclick="document.getElementById('hesapSilModal').close();" class="btn btn-secondary text-xs">VAZGEÇ</button>
            <button type="button" class="btn btn-danger text-xs delete-account">HESABIMI SİL</button>
        </div>
    </form>
</dialog>

<!-- Özel CSS Ayarları -->
<style>
    /* Native Dialog Backdrop Styling */
    dialog::backdrop {
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
    }
    
    /* Sekme Menüsü Butonları */
    .settings-nav-item {
        font-size: 13px !important;
        padding: 8px 12px !important;
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
        width: 100% !important;
        border-radius: 6px !important;
        border: 1px solid var(--border) !important;
        background: var(--background) !important;
        color: var(--foreground) !important;
        box-sizing: border-box !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .form-input:focus {
        outline: none !important;
        border-color: hsl(var(--primary)) !important;
    }
</style>

<!-- İstemci Tarafı JavaScript Kontrolleri -->
<script>
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
</script>

<!-- Script include'ları -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script src="App/Src/kullanici.js?<?php echo filemtime('App/Src/kullanici.js'); ?>"></script>
<?php include 'layouts/foot.php'; ?>