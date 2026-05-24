<?php
require_once "../vendor/autoload.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/../config.php';
$basePath = $config['base_path'] ?? '/';

use App\Helper\Security;
use Models\KullaniciIsyeriModel;
use Models\KullaniciAbonelikModel;

// Verify user login session with absolute redirect route to prevent mobile subfolder loops
Security::checkLogin(rtrim($basePath, '/') . '/sign-in');

// ==============================================================
// MOBİL SAYFA AJAX YÖNLENDİRİCİ (Mobil Uyumlu Sayfalar İçin)
// ==============================================================
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // Rota ismini alalım
    $requestedUrl = $_GET['url'] ?? '';
    $route = str_replace(['mobile/views/', 'views/'], '', $requestedUrl);
    if (empty($route)) {
        $route = 'dashboard';
    }

    // Workplaces check for AJAX PWA routes to prevent redirects and provide high fidelity UX
    $workplaceDependentRoutes = [
        'dashboard',
        'onay-bekleyen-raporlar',
        'onayli-raporlar',
        'manuel-rapor-bildirimi',
        'is-kazasi-bildirimi',
        'arsivlenmis-raporlar',
        'mahsuplastirilacak-raporlar',
        'mahsuplastirilan-raporlar',
        'prim-borcuna-mahsup-edilen-odemeler',
        'iptal-edilen-raporlar'
    ];

    if (!isset($_SESSION['firma_adi']) && in_array($route, $workplaceDependentRoutes)) {
        echo "<div class='animate-in p-6 text-center flex flex-col items-center justify-center gap-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl m-4 shadow-sm'>
                <div class='w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950/20 text-amber-600 flex items-center justify-center'>
                    <i data-lucide='building-2' style='width:24px;height:24px;'></i>
                </div>
                <div class='flex flex-col gap-1'>
                    <h3 class='text-sm font-bold text-zinc-900 dark:text-zinc-50'>İşyeri Seçilmedi</h3>
                    <p class='text-xs text-zinc-500 dark:text-zinc-400'>İşlemlere devam edebilmek için lütfen yetkili olduğunuz işyerlerinden birini seçiniz.</p>
                </div>
                <button type='button' onclick=\"App.openBottomSheet('workplace-sheet')\" class='h-9 px-4 bg-zinc-900 dark:bg-zinc-50 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-zinc-50 dark:text-zinc-900 rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer'>
                    İşyeri Seçin
                </button>
              </div>
              <script>
                if (window.lucide) window.lucide.createIcons();
              </script>";
        exit();
    }

    $fileName = str_replace('-', '_', $route) . '.php';
    
    // Mobil ve masaüstü sayfa yolları
    $mobilePagePath = __DIR__ . '/pages/' . $fileName;
    $desktopPagePath = __DIR__ . '/../pages/' . $fileName;

    // Alt klasörler için rota eşleştirmeleri
    if ($route === 'mahsuplastirilacak-raporlar') {
        $mobilePagePath = __DIR__ . '/pages/mahsuplastirma/mahsuplastirilacak_raporlar.php';
        $desktopPagePath = __DIR__ . '/../pages/mahsuplastirma/mahsuplastirilacak_raporlar.php';
    } elseif ($route === 'mahsuplastirilan-raporlar') {
        $mobilePagePath = __DIR__ . '/pages/mahsuplastirma/mahsuplastirilan_raporlar.php';
        $desktopPagePath = __DIR__ . '/../pages/mahsuplastirma/mahsuplastirilan_raporlar.php';
    } elseif ($route === 'prim-borcuna-mahsup-edilen-odemeler') {
        $mobilePagePath = __DIR__ . '/pages/mahsuplastirma/prim-borcuna-mahsup-edilen-odemeler.php';
        $desktopPagePath = __DIR__ . '/../pages/mahsuplastirma/prim-borcuna-mahsup-edilen-odemeler.php';
    } elseif ($route === 'manuel-rapor-bildirimi') {
        $mobilePagePath = __DIR__ . '/pages/manuel_rapor_bildirimi.php';
        $desktopPagePath = __DIR__ . '/../pages/manuel-rapor/manuel_rapor_bildirimi.php';
    } elseif ($route === 'is-kazasi-bildirimi') {
        $mobilePagePath = __DIR__ . '/pages/is_kazasi_bildirimi.php';
        $desktopPagePath = __DIR__ . '/../pages/is_kazasi_bildirimi.php';
    } elseif ($route === 'arsivlenmis-raporlar') {
        $mobilePagePath = __DIR__ . '/pages/arsivlenmis_raporlar.php';
        $desktopPagePath = __DIR__ . '/../pages/arsivlenmis_raporlar.php';
    } elseif ($route === 'iletisim-bilgileri') {
        $mobilePagePath = __DIR__ . '/pages/iletisim_bilgileri.php';
        $desktopPagePath = __DIR__ . '/../pages/iletisim-bilgileri.php';
    } elseif ($route === 'profile') {
        $mobilePagePath = __DIR__ . '/pages/profile.php';
        $desktopPagePath = __DIR__ . '/../profile.php';
    } elseif ($route === 'kullanicilar') {
        $mobilePagePath = __DIR__ . '/pages/kullanicilar/liste.php';
        $desktopPagePath = __DIR__ . '/../pages/kullanicilar/liste.php';
    }

    if (file_exists($mobilePagePath)) {
        require_once $mobilePagePath;
    } elseif (file_exists($desktopPagePath)) {
        require_once $desktopPagePath;
    } else {
        http_response_code(404);
        echo "<div class='card p-4 text-center' style='background: var(--card); border: 1px solid var(--border); border-radius: 12px;'>
                <h3 class='font-bold text-sm text-rose-500'>Sayfa Bulunamadı</h3>
                <p class='text-[10px] text-zinc-500 mt-1'>Rota: <code>" . htmlspecialchars($route) . "</code></p>
              </div>";
    }
    exit();
}

$isyeriModel = new KullaniciIsyeriModel();
$isyeriKullaniciId = $_SESSION['kullanici_id'] ?? 0;
$header_isyerleri = [];

if (isset($_SESSION['kullanici_id'])) {
    $userRole = $_SESSION["role"] ?? "user";
    if ($userRole === 'user') {
        $isyeri_ids = '';
        if (isset($_SESSION['user']) && is_object($_SESSION['user'])) {
            $isyeri_ids = $_SESSION['user']->yetkili_oldugu_isyeri_ids ?? '';
        } else {
            try {
                $db = \Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT yetkili_oldugu_isyeri_ids FROM kullanicilar WHERE id = ?");
                $stmt->execute([$_SESSION['kullanici_id']]);
                $isyeri_ids = $stmt->fetchColumn() ?: '';
            } catch (\Exception $e) {
            }
        }
        $header_isyerleri = $isyeriModel->AltKullaniciİsyerleri($isyeri_ids);
    } else {
        $header_isyerleri = $isyeriModel->whereRaw('kullanici_id = ? AND aktif_mi = ?', [$isyeriKullaniciId, 1]);
    }
}

$KullaniciAbonelikModel = new KullaniciAbonelikModel();
$abonelik_varmi = $KullaniciAbonelikModel->hasActiveSubscription($_SESSION['kullanici_id'] ?? 0);

$userAd = $_SESSION['user_ad'] ?? $_SESSION['kullanici_adi'] ?? 'Kullanıcı';
$userEmail = '';

if (isset($_SESSION['kullanici_id'])) {
    require_once __DIR__ . '/../Core/Database.php';
    try {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT adi_soyadi, email FROM kullanicilar WHERE id = ?");
        $stmt->execute([$_SESSION['kullanici_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentUser) {
            $userAd = !empty($currentUser['adi_soyadi']) ? $currentUser['adi_soyadi'] : $userAd;
            $userEmail = $currentUser['email'] ?? '';
        }
    } catch (\Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <base href="<?php echo htmlspecialchars(rtrim($basePath, '/') . '/'); ?>">
    <title>Vizit-e Mobil Kullanıcı Paneli</title>

    <!-- PWA Configurations -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#09090b">
    <link rel="manifest" href="mobile/manifest.json">
    <link rel="apple-touch-icon" href="mobile/assets/icons/apple-touch-icon.png">
    <link rel="icon" href="assets/images/logo.svg" type="image/svg+xml">

    <!-- Styling Frameworks -->
    <!-- Force Tailwind v4 dark: variant to use .dark class instead of OS media query -->
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="admin/assets/css/app.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="mobile/assets/css/mobile.css?v=<?php echo time(); ?>">

    <!-- Lucide Icons & jQuery -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body class="theme-light">

    <!-- PWA Offline status banner -->
    <div id="offline-banner" class="offline-banner">
        İnternet bağlantınız koptu. Çevrimdışı modda çalışıyorsunuz.
    </div>

    <!-- Mobile Top Header -->
    <header class="mobile-header">
        <div class="mobile-logo-text">
            <img src="assets/images/logo.svg" alt="Vizit-e" style="width: 26px; height: 26px;">
            <span>Vizit-e</span>
        </div>

        <div class="mobile-header-actions">
            <!-- Active Workplace Select Trigger -->
            <?php if (count($header_isyerleri) > 0): ?>
            <button type="button" class="mobile-workplace-selector-btn"
                onclick="App.openBottomSheet('workplace-sheet')">
                <i data-lucide="building-2" style="width: 14px; height: 14px;"></i>
                <span><?php echo htmlspecialchars($_SESSION['firma_adi'] ?? 'İşyeri Seçin'); ?></span>
                <i data-lucide="chevron-down" style="width: 10px; height: 10px;"></i>
            </button>
            <?php endif; ?>

            <!-- PWA Install Button (hidden by default, shown when prompt available) -->
            <button id="pwa-install-btn" class="topbar-btn" title="Uygulamayı Yükle" style="display:none;">
                <i data-lucide="download" style="width: 18px; height: 18px;"></i>
            </button>

            <!-- Theme Toggle Trigger -->
            <button id="theme-toggle" class="topbar-btn" title="Tema Değiştir">
                <i data-lucide="sun" class="sun-icon" style="width: 18px; height: 18px;"></i>
                <i data-lucide="moon" class="moon-icon" style="width: 18px; height: 18px; display: none;"></i>
            </button>
        </div>
    </header>

    <!-- Mobile Scroll Viewport -->
    <main id="mobile-viewport" class="mobile-viewport">
        <div id="mobile-app-content" class="content-area flex flex-col flex-1">
            <!-- Dynamic SPA contents loaded here -->
        </div>
    </main>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-nav">
        <!-- Home Page Tab -->
        <button id="tab-home" class="mobile-bottom-nav__item active" onclick="window.location.hash = '#dashboard'">
            <i data-lucide="house"></i>
            <span>Ana Sayfa</span>
        </button>

        <!-- Sgk Panel Menu Trigger Tab -->
        <button id="tab-sgk" class="mobile-bottom-nav__item" onclick="App.openBottomSheet('sgk-sheet')">
            <i data-lucide="folder-kanban"></i>
            <span>Sgk Panel</span>
        </button>

        <!-- Workplaces Switcher Tab -->
        <button id="tab-isyerleri" class="mobile-bottom-nav__item" onclick="App.openBottomSheet('workplace-sheet')">
            <i data-lucide="building-2"></i>
            <span>İşyerlerim</span>
        </button>

        <!-- Other Operations Menu Trigger Tab -->
        <button id="tab-diger" class="mobile-bottom-nav__item" onclick="App.openBottomSheet('diger-sheet')">
            <i data-lucide="menu"></i>
            <span>Diğer</span>
        </button>
    </nav>

    <!-- Bottom Sheet Backdrop Cover -->
    <div id="bottom-sheet-overlay" class="bottom-sheet-overlay"></div>

    <!-- Bottom Sheet 1: SGK Panel Menu -->
    <div id="sgk-sheet" class="bottom-sheet">
        <div class="bottom-sheet-drag-handle"></div>
        <div class="bottom-sheet-header">
            <h3 class="bottom-sheet-title">Sgk Panel Rapor İşlemleri</h3>
            <button class="bottom-sheet-close"><i data-lucide="x" style="width: 16px; height: 16px;"></i></button>
        </div>
        <div class="bottom-sheet-content">
            <nav class="bottom-sheet-menu-list">
                <?php if ($abonelik_varmi): ?>
                <div class="bottom-sheet-menu-section">Mahsuplaşma İşlemleri</div>
                <a href="#prim-borcuna-mahsup-edilen-odemeler" class="bottom-sheet-menu-item">
                    <i data-lucide="coins"></i>
                    <span>Prim Borcuna Mahsup Edilenler</span>
                </a>
                <a href="#mahsuplastirilan-raporlar" class="bottom-sheet-menu-item">
                    <i data-lucide="check-square"></i>
                    <span>Onaylanan Ödeme Listesi</span>
                </a>
                <a href="#mahsuplastirilacak-raporlar" class="bottom-sheet-menu-item">
                    <i data-lucide="file-text"></i>
                    <span>Mahsuplaştırılacak Raporlar</span>
                </a>

                <div class="bottom-sheet-menu-section">Rapor İşlemleri</div>
                <a href="#is-kazasi-bildirimi" class="bottom-sheet-menu-item">
                    <i data-lucide="shield-alert"></i>
                    <span>İş Kazası bildirimleri</span>
                </a>
                <a href="#arsivlenmis-raporlar" class="bottom-sheet-menu-item">
                    <i data-lucide="archive"></i>
                    <span>Arşive Alınan Raporlar</span>
                </a>
                <a href="#iptal-edilen-raporlar" class="bottom-sheet-menu-item">
                    <i data-lucide="x-circle"></i>
                    <span>İptal Edilen Raporlar</span>
                </a>
                <a href="#manuel-rapor-bildirimi" class="bottom-sheet-menu-item">
                    <i data-lucide="edit-3"></i>
                    <span>Manuel Rapor Bildirimi</span>
                </a>
                <a href="#onayli-raporlar" class="bottom-sheet-menu-item">
                    <i data-lucide="check-circle-2"></i>
                    <span>Onaylı Raporlar</span>
                </a>
                <a href="#onay-bekleyen-raporlar" class="bottom-sheet-menu-item">
                    <i data-lucide="clock"></i>
                    <span>Onay Bekleyen Raporlar</span>
                </a>
                <?php else: ?>
                <div class="p-4 text-center text-xs text-rose-500">
                    Sgk Panel işlemlerini görüntülemek için aktif bir aboneliğinizin bulunması gerekmektedir.
                </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- Bottom Sheet 2: Workplaces List -->
    <div id="workplace-sheet" class="bottom-sheet">
        <div class="bottom-sheet-drag-handle"></div>
        <div class="bottom-sheet-header">
            <h3 class="bottom-sheet-title">Aktif İşyeri Seçin</h3>
            <button class="bottom-sheet-close"><i data-lucide="x" style="width: 16px; height: 16px;"></i></button>
        </div>
        <div class="bottom-sheet-content">
            <div class="flex flex-col gap-1">
                <?php if (count($header_isyerleri) > 0): ?>
                <?php foreach ($header_isyerleri as $isyeri): 
                        $is_selected = ((int)$isyeri->id === (int)($_SESSION['isyeri_id'] ?? 0));
                        $enc_id = \App\Helper\Security::encrypt($isyeri->id);
                    ?>
                <button type="button" class="workplace-item <?php echo $is_selected ? 'active' : ''; ?>"
                    onclick="App.selectWorkplace('<?php echo $enc_id; ?>')">
                    <div class="flex flex-col text-left gap-0.5 min-w-0 flex-1">
                        <span class="text-xs font-bold <?php echo $is_selected ? 'text-primary' : 'text-foreground'; ?>"
                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                            <?php echo htmlspecialchars($isyeri->firma_adi); ?>
                        </span>
                        <span class="text-[10px] text-zinc-500">
                            Kod: <?php echo htmlspecialchars($isyeri->isyeri_kodu); ?>
                        </span>
                    </div>
                    <?php if ($is_selected): ?>
                    <i data-lucide="check" class="text-primary" style="width: 16px; height: 16px; flex-shrink: 0;"></i>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-center text-xs text-zinc-500 py-4">Yetkili olduğunuz işyeri bulunamadı.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Sheet 3: Other Actions List -->
    <div id="diger-sheet" class="bottom-sheet">
        <div class="bottom-sheet-drag-handle"></div>
        <div class="bottom-sheet-header">
            <h3 class="bottom-sheet-title">Diğer İşlemler</h3>
            <button class="bottom-sheet-close"><i data-lucide="x" style="width: 16px; height: 16px;"></i></button>
        </div>
        <div class="bottom-sheet-content">
            <nav class="bottom-sheet-menu-list">
                <?php if ($userRole === 'admin' || $userRole === 'superadmin'): ?>
                <a href="#kullanicilar" class="bottom-sheet-menu-item">
                    <i data-lucide="users"></i>
                    <span>Kullanıcılar</span>
                </a>
                <a href="#isyerlerim" class="bottom-sheet-menu-item">
                    <i data-lucide="building-2"></i>
                    <span>İşyeri Yönetimi</span>
                </a>
                <?php endif; ?>

                <a href="#profile" class="bottom-sheet-menu-item">
                    <i data-lucide="user"></i>
                    <span>Hesabım (Profil)</span>
                </a>

                <a href="#iletisim-bilgileri" class="bottom-sheet-menu-item">
                    <i data-lucide="info"></i>
                    <span>İşyeri Bilgileri</span>
                </a>

                <a href="https://api.whatsapp.com/send?phone=905079432723" target="_blank"
                    class="bottom-sheet-menu-item">
                    <i data-lucide="phone-call" style="color: #16a34a;"></i>
                    <span>Teknik Destek (WhatsApp)</span>
                </a>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 0.5rem 0;">

                <a href="logout" class="bottom-sheet-menu-item text-rose-500">
                    <i data-lucide="log-out" class="text-rose-500"></i>
                    <span>Çıkış Yap</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Toaster Container for Basecoat Toasts -->
    <div id="toaster" class="toaster"></div>

    <!-- Framework scripts -->
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/basecoat.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/toast.min.js" defer></script>

    <!-- Select2 & Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

    <script>
    // Polyfill standard App object components for sub-pages
    window.App = window.App || {};

    App.initGlobalSelect2 = (container = document) => {
        if (window.jQuery && window.jQuery.fn.select2) {
            const $ = window.jQuery;
            $(container).find('select.select2, select.custom-select').each(function() {
                const $select = $(this);
                if (!$select.data('select2')) {
                    $select.select2({
                        placeholder: $select.attr('placeholder') || 'Seçiniz',
                        width: '100%'
                    });
                }
            });
        }
    };

    App.initGlobalFlatpickr = (container = document) => {
        if (window.flatpickr) {
            const dateInputs = container.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                const val = input.value;
                input.setAttribute('type', 'text');
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    locale: 'tr',
                    defaultDate: val || undefined,
                    allowInput: true
                });
            });
        }
    };

    // Theme initialization to prevent white flash in dark mode
    (function() {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
    </script>
    <script>
    // PHP-injected absolute URL bases — avoids <base href> resolution ambiguity on production servers
    window.MOBILE_AJAX_BASE = '<?php echo htmlspecialchars(rtrim($basePath, '/') . '/mobile/index.php'); ?>';
    window.MOBILE_ROOT_BASE = '<?php echo htmlspecialchars(rtrim($basePath, '/')); ?>';
    </script>
    <script src="mobile/assets/js/mobile.js?v=<?php echo time(); ?>"></script>

    <script>
    // Start Lucide Icons and register sw
    lucide.createIcons();

    // PWA Install Prompt Handler
    let _pwaInstallPrompt = null;
    const pwaInstallBtn = document.getElementById('pwa-install-btn');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        _pwaInstallPrompt = e;
        if (pwaInstallBtn) pwaInstallBtn.style.display = 'flex';
    });

    if (pwaInstallBtn) {
        pwaInstallBtn.addEventListener('click', async () => {
            if (!_pwaInstallPrompt) return;
            _pwaInstallPrompt.prompt();
            const { outcome } = await _pwaInstallPrompt.userChoice;
            if (outcome === 'accepted') {
                pwaInstallBtn.style.display = 'none';
            }
            _pwaInstallPrompt = null;
        });
    }

    window.addEventListener('appinstalled', () => {
        if (pwaInstallBtn) pwaInstallBtn.style.display = 'none';
        _pwaInstallPrompt = null;
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('mobile/sw.js')
                .then(reg => console.log('PWA ServiceWorker successfully registered:', reg))
                .catch(err => console.warn('PWA ServiceWorker registration skipped:', err));
        });
    }

    // Theme Switcher Syncing
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        const sunIcon = themeToggle.querySelector('.sun-icon');
        const moonIcon = themeToggle.querySelector('.moon-icon');

        const updateIcons = () => {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        };

        updateIcons();

        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateIcons();
        });
    }

    // Catch form submissions to redirect inside Mobile SPA
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        const action = form.getAttribute('action');
        if (form.hasAttribute('data-bypass')) return;

        e.preventDefault();
        const container = document.getElementById('mobile-app-content');
        if (!container) return;

        container.style.opacity = '0.6';

        try {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const formData = new FormData(form);
            if (e.submitter && e.submitter.name) {
                formData.append(e.submitter.name, e.submitter.value || '');
            }

            let fetchUrl = action || (window.location.hash.substring(1) || 'dashboard');
            if (!action) {
                fetchUrl = (window.MOBILE_AJAX_BASE || 'mobile/index.php') + '?url=views/' + fetchUrl;
            }
            
            let options = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            if (method === 'POST') {
                options.body = formData;
            } else {
                const params = new URLSearchParams(formData).toString();
                fetchUrl = fetchUrl.split('?')[0] + '?' + params;
            }

            const response = await fetch(fetchUrl, options);
            if (!response.ok) throw new Error('Form gönderimi başarısız oldu.');

            const html = await response.text();

            document.querySelectorAll('script[data-spa-page-script]').forEach(s => s.remove());

            container.innerHTML = html;
            container.style.opacity = '1';

            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name,
                    attr.value));
                newScript.setAttribute('data-spa-page-script', 'true');
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            if (window.lucide) window.lucide.createIcons();
            if (window.App && App.initGlobalSelect2) App.initGlobalSelect2(container);
            if (window.App && App.initGlobalFlatpickr) App.initGlobalFlatpickr(container);

        } catch (err) {
            console.error('Mobile SPA Form Submission error:', err);
            container.style.opacity = '1';
            if (window.showToast) window.showToast(err.message, 'error');
        }
    });
    </script>
</body>

</html>