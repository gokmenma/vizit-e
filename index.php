<?php
define('SPA_LAYOUT', true);

require_once "vendor/autoload.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// BAKIM MODU INTERCEPTOR (Süper Admin Hariç)
// ==========================================
try {
    require_once __DIR__ . '/Models/Model.php';
    require_once __DIR__ . '/Models/KullaniciAyarModel.php';
    
    $ayarModel = new \Models\KullaniciAyarModel();
    $maintenance_mode = $ayarModel->getSetting('maintenance_mode', 0);
    
    if ($maintenance_mode === '1') {
        // Süper Admin kontrolü (Oturumdaki rolden)
        $is_superadmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin') || 
                         (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') ||
                         (isset($_SESSION['user']) && is_object($_SESSION['user']) && isset($_SESSION['user']->role) && $_SESSION['user']->role === 'superadmin');
        
        if (!$is_superadmin) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $url_param = $_GET['url'] ?? '';
            
            // Bypass edilebilir rotalar (Giriş/Çıkış ekranı, Assets, reCAPTCHA vb.)
            $is_bypass_url = str_contains($request_uri, 'sign-in') || 
                             str_contains($request_uri, 'login.php') || 
                             str_contains($request_uri, 'logout') || 
                             str_contains($request_uri, '/assets/') ||
                             str_contains($request_uri, '/vendor/') ||
                             str_contains($request_uri, 'autologin') ||
                             str_contains($url_param, 'sign-in') ||
                             str_contains($url_param, 'logout');
            
            if (!$is_bypass_url) {
                http_response_code(503);
                require_once __DIR__ . '/pages/bakim.php';
                exit();
            }
        }
    }
} catch (\Throwable $e) {
    // Veritabanı veya model yükleme hatasını loglayalım
    @file_put_contents(__DIR__ . '/logs/maintenance_error.log', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
}

use App\Helper\Security;
use App\Helper\Date;
use Models\KullaniciAbonelikModel;
use Models\KullaniciIsyeriModel;

$currentRoute = $_GET['url'] ?? 'dashboard';

// Misafir (Giriş gerektirmeyen) rotaları bypass et
$guestRoutes = ['sign-in', 'sign-up', 'forgot-password', 'reset-password', 'logout', 'temizle'];
$isGuestRoute = false;
foreach ($guestRoutes as $route) {
    if ($currentRoute === $route || str_starts_with($currentRoute, $route . '/')) {
        $isGuestRoute = true;
        break;
    }
}

if ($isGuestRoute) {
    require_once __DIR__ . '/router.php';
    exit();
}

// ==========================================
// MOBİL YÖNLENDİRME (PWA Mobil Uygulaması İçin)
// ==========================================
$is_mobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $mobile_agents = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'opera mini', 'windows phone'];
    foreach ($mobile_agents as $agent) {
        if (strpos($user_agent, $agent) !== false) {
            $is_mobile = true;
            break;
        }
    }
}

// Masaüstü modu istek kontrolü
$wants_desktop = isset($_GET['desktop']) || (isset($_SESSION['desktop_mode']) && $_SESSION['desktop_mode']);
if (isset($_GET['desktop'])) {
    $_SESSION['desktop_mode'] = true;
} elseif (isset($_GET['mobile'])) {
    $_SESSION['desktop_mode'] = false;
}

if ($is_mobile && !$wants_desktop && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest')) {
    $config_redirect = require __DIR__ . '/config.php';
    $basePath_redirect = $config_redirect['base_path'] ?? '/';
    header("Location: " . rtrim($basePath_redirect, '/') . "/mobile/");
    exit();
}

Security::checkLogin();

// Firma seçimi zorunlu olmayan rotaları bypass et
$firmaBypassRoutes = ['isyerlerim', 'isyeri-sec', 'profile', 'logout', 'abonelik-paketleri'];
$isFirmaBypass = false;
foreach ($firmaBypassRoutes as $route) {
    if ($currentRoute === $route || str_starts_with($currentRoute, $route . '/')) {
        $isFirmaBypass = true;
        break;
    }
}

if (!$isFirmaBypass) {
    Security::checkFirma();
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
$aktif_abonelik = $KullaniciAbonelikModel->getSubscriptionByUserId($_SESSION['kullanici_id'] ?? 0);
$abonelik_varmi = $KullaniciAbonelikModel->hasActiveSubscription($_SESSION['kullanici_id'] ?? 0);
$abonelik_bitis_tarihi = $aktif_abonelik->bitis_tarihi ?? null;

$config = require __DIR__ . '/config.php';
$basePath = $config['base_path'] ?? '/';
$userRole = $_SESSION["role"] ?? "user";

// Logo SVG inline gömme
$rawSvgContent = @file_get_contents(__DIR__ . '/assets/images/logo.svg') ?: '';
$logoSvgContent = '';
$faviconDataUri = '';
if ($rawSvgContent) {
    $logoSvgContent = preg_replace('/(<svg[^>]*)\swidth="[^"]*"/', '$1 width="100%"', $rawSvgContent);
    $logoSvgContent = preg_replace('/(<svg[^>]*)\sheight="[^"]*"/', '$1 height="100%"', $logoSvgContent);
    $faviconDataUri = 'data:image/svg+xml;base64,' . base64_encode($rawSvgContent);
}

// Kullanıcı Bilgilerini Alalım
$userAd = $_SESSION['user_ad'] ?? $_SESSION['kullanici_adi'] ?? 'Kullanıcı';
$userEmail = '';
if (isset($_SESSION['kullanici_id'])) {
    require_once __DIR__ . '/Core/Database.php';
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
        // Database connection failed
    }
}

// Eğer bir AJAX isteği ise raw içeriği döndür ve çık
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    require_once __DIR__ . '/router.php';
    exit();
}

// Doğrudan sayfa isteği ise (browser üzerinden), içeriği yakala ve layout içinde göster
$pageContent = '';
$currentRoute = $_GET['url'] ?? 'dashboard';
if ($currentRoute === '' || $currentRoute === 'index' || $currentRoute === 'index.php') {
    $currentRoute = 'dashboard';
}

if ($currentRoute !== 'dashboard') {
    ob_start();
    require_once __DIR__ . '/router.php';
    $pageContent = ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars($basePath); ?>">
    <title>Kullanıcı Paneli | SGK Vizite</title>
    <link rel="icon" href="<?php echo $faviconDataUri ?: rtrim($basePath, '/') . '/assets/images/logo.svg'; ?>" type="image/svg+xml">

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
    <!-- Basecoat CSS (BaseUI) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.min.css" />

    <!-- Custom Admin Styles & Custom Overrides -->
    <link rel="stylesheet" href="admin/assets/css/app.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="admin/assets/css/datatable.custom.css?v=<?php echo time(); ?>">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="admin/assets/css/flatpickr.custom.css">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- jQuery & SweetAlert2 JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    </script>
    <script>
    // Theme initialization to prevent flash
    (function() {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
    </script>

    <style>
    /* Details Dropdown styling for Sidebar */
    .sidebar-dropdown {
        margin: 0;
        padding: 0;
    }

    .sidebar-dropdown summary {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: var(--muted-foreground);
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 6px;
        cursor: pointer;
        list-style: none;
        transition: background-color 0.2s, color 0.2s;
        position: relative;
    }

    .sidebar-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .sidebar-dropdown summary:hover {
        background-color: var(--sidebar-accent);
        color: var(--sidebar-accent-foreground);
    }

    .sidebar-dropdown summary i:first-child {
        margin-right: 0.75rem;
        width: 18px;
        height: 18px;
    }

    .sidebar-dropdown summary .dropdown-arrow {
        margin-left: auto;
        width: 14px;
        height: 14px;
        transition: transform 0.2s;
    }

    .sidebar-dropdown[open] summary .dropdown-arrow {
        transform: rotate(180deg);
    }

    .sidebar-dropdown ul {
        padding-left: 1.5rem;
        margin-top: 0.25rem;
        list-style: none;
    }

    .sidebar-dropdown ul li a {
        display: flex;
        align-items: center;
        padding: 0.5rem 1rem;
        font-size: 0.8125rem;
        color: var(--muted-foreground);
        border-radius: 6px;
        transition: background-color 0.2s, color 0.2s;
        text-decoration: none;
    }

    .sidebar-dropdown ul li a:hover,
    .sidebar-dropdown ul li a.active {
        background-color: var(--sidebar-accent);
        color: var(--sidebar-accent-foreground);
    }

    /* Workplace Dropdown Styling */
    .isyeri-dropdown {
        margin: 0;
        padding: 0;
        display: inline-block;
    }

    .isyeri-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .isyeri-dropdown[open] .dropdown-arrow {
        transform: rotate(180deg);
    }

    .isyeri-dropdown .isyeri-item:hover {
        background: rgba(37, 99, 235, 0.04);
    }

    .dark .isyeri-dropdown .isyeri-item:hover {
        background: rgba(255, 255, 255, 0.04);
    }
    </style>
</head>

<body class="theme-light">
    <!-- Modern Top Loading Progress Bar (GitHub / Vercel style) -->
    <div id="top-loading-bar"
        style="position: fixed; top: 0; left: 0; height: 3px; width: 0%; z-index: 999999; transition: width 0.4s ease, opacity 0.3s ease;">
    </div>
    <style>
    #top-loading-bar {
        background-color: #000000;
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
    }

    .dark #top-loading-bar {
        background-color: #ffffff;
        box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
    }
    </style>

    <!-- Toaster Container for Basecoat -->
    <div id="toaster" class="toaster"></div>

    <aside class="sidebar" data-side="left" aria-hidden="false">
        <nav aria-label="Sidebar navigation">
            <header class="sidebar-header">
                <div class="sidebar-logo">
                    <div style="width: 28px; height: 28px; border-radius: 6px; overflow: hidden; flex-shrink: 0;"><?php echo $logoSvgContent; ?></div>
                    <div class="logo-text">
                        <span class="logo-title">Vizit-e</span>
                        <span class="logo-subtitle">Kullanıcı Paneli</span>
                    </div>
                </div>
            </header>
            <section class="scrollbar">
                <!-- Grup 1: Menü -->
                <div role="group" aria-labelledby="group-label-menu">
                    <h3 id="group-label-menu">Menü</h3>
                    <ul>
                        <li>
                            <a href="dashboard"
                                class="nav-link <?php echo $currentRoute === 'dashboard' ? 'active' : ''; ?>"
                                data-route="dashboard">
                                <i data-lucide="layout-dashboard"></i>
                                <span>Ana Sayfa</span>
                            </a>
                        </li>

                        <li>
                            <a href="isyerlerim"
                                class="nav-link <?php echo $currentRoute === 'isyerlerim' ? 'active' : ''; ?>"
                                data-route="isyerlerim">
                                <i data-lucide="building-2"></i>
                                <span>İşyerlerim</span>
                            </a>
                        </li>
                        <?php if ($userRole === 'admin' || $userRole === 'superadmin'): ?>
                        <li>
                            <a href="kullanicilar"
                                class="nav-link <?php echo $currentRoute === 'kullanicilar' ? 'active' : ''; ?>"
                                data-route="kullanicilar">
                                <i data-lucide="users"></i>
                                <span>Kullanıcılar</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Grup 2: Rapor İşlemleri -->
                <?php if ($abonelik_varmi): ?>
                <div role="group" aria-labelledby="group-label-rapor">
                    <h3 id="group-label-rapor">Rapor İşlemleri</h3>
                    <ul>
                        <li>
                            <a href="onay-bekleyen-raporlar"
                                class="nav-link <?php echo $currentRoute === 'onay-bekleyen-raporlar' ? 'active' : ''; ?>"
                                data-route="onay-bekleyen-raporlar">
                                <i data-lucide="clock"></i>
                                <span>Onay Bekleyen Raporlar</span>
                            </a>
                        </li>
                        <li>
                            <a href="onayli-raporlar"
                                class="nav-link <?php echo $currentRoute === 'onayli-raporlar' ? 'active' : ''; ?>"
                                data-route="onayli-raporlar">
                                <i data-lucide="check-circle-2"></i>
                                <span>Onaylı Raporlar</span>
                            </a>
                        </li>
                        <li>
                            <a href="manuel-rapor-bildirimi"
                                class="nav-link <?php echo $currentRoute === 'manuel-rapor-bildirimi' ? 'active' : ''; ?>"
                                data-route="manuel-rapor-bildirimi">
                                <i data-lucide="edit-3"></i>
                                <span>Manuel Rapor Bildirimi</span>
                            </a>
                        </li>
                        <li>
                            <a href="iptal-edilen-raporlar"
                                class="nav-link <?php echo $currentRoute === 'iptal-edilen-raporlar' ? 'active' : ''; ?>"
                                data-route="iptal-edilen-raporlar">
                                <i data-lucide="x-circle"></i>
                                <span>İptal Edilen Raporlar</span>
                            </a>
                        </li>
                        <li>
                            <a href="arsivlenmis-raporlar"
                                class="nav-link <?php echo $currentRoute === 'arsivlenmis-raporlar' ? 'active' : ''; ?>"
                                data-route="arsivlenmis-raporlar">
                                <i data-lucide="archive"></i>
                                <span>Arşive Alınan Raporlar</span>
                            </a>
                        </li>
                        <li>
                            <a href="is-kazasi-bildirimi"
                                class="nav-link <?php echo $currentRoute === 'is-kazasi-bildirimi' ? 'active' : ''; ?>"
                                data-route="is-kazasi-bildirimi">
                                <i data-lucide="shield-alert"></i>
                                <span>İş Kazası bildirimleri</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Grup 3: Mahsuplaşma İşlemleri -->
                <div role="group" aria-labelledby="group-label-mahsup">
                    <h3 id="group-label-mahsup">Mahsuplaşma İşlemleri</h3>
                    <ul>
                        <li>
                            <a href="mahsuplastirilacak-raporlar"
                                class="nav-link <?php echo $currentRoute === 'mahsuplastirilacak-raporlar' ? 'active' : ''; ?>"
                                data-route="mahsuplastirilacak-raporlar">
                                <i data-lucide="file-text"></i>
                                <span>Mahsuplaştırılacak Raporlar</span>
                            </a>
                        </li>
                        <li>
                            <a href="mahsuplastirilan-raporlar"
                                class="nav-link <?php echo $currentRoute === 'mahsuplastirilan-raporlar' ? 'active' : ''; ?>"
                                data-route="mahsuplastirilan-raporlar">
                                <i data-lucide="check-square"></i>
                                <span>Onaylanan Ödeme Listesi</span>
                            </a>
                        </li>
                        <li>
                            <a href="prim-borcuna-mahsup-edilen-odemeler"
                                class="nav-link <?php echo $currentRoute === 'prim-borcuna-mahsup-edilen-odemeler' ? 'active' : ''; ?>"
                                data-route="prim-borcuna-mahsup-edilen-odemeler">
                                <i data-lucide="coins"></i>
                                <span>Prim Borcuna Mahsup Edilenler</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </section>
            <footer class="sidebar-footer">
                <details class="user-dropdown">
                    <summary>
                        <div class="user-avatar" style="background: #f4f4f5; color: #18181b; border: 1px solid #e4e4e7; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; flex-shrink: 0;">
                            <?php 
                            $nameParts = explode(' ', $userAd);
                            $initials = '';
                            foreach ($nameParts as $part) { $initials .= mb_substr($part, 0, 1, 'UTF-8'); }
                            echo mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                            ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userAd); ?></span>
                            <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                        </div>
                        <i data-lucide="chevrons-up-down" class="dropdown-icon"></i>
                    </summary>
                    <div class="dropdown-content">
                        <div class="dropdown-header">
                            <p class="dropdown-label">Hesabım</p>
                            <p class="dropdown-email"><?php echo htmlspecialchars($userEmail); ?></p>
                        </div>
                        <a href="profile" class="dropdown-item nav-link" data-route="profile">
                            <i data-lucide="user"></i>
                            <span>Profil</span>
                        </a>
                        <a href="iletisim-bilgileri"
                            class="dropdown-item nav-link <?php echo $currentRoute === 'iletisim-bilgileri' ? 'active' : ''; ?>"
                            data-route="iletisim-bilgileri">
                            <i data-lucide="info"></i>
                            <span>İşyeri Bilgileri</span>
                        </a>
                        <hr>
                        <a href="logout" class="dropdown-item logout text-rose-500">
                            <i data-lucide="log-out"></i>
                            <span>Çıkış yap</span>
                        </a>
                    </div>
                </details>
            </footer>
        </nav>
    </aside>


    <main class="app-main">
        <header class="app-topbar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button type="button" class="topbar-btn"
                    onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar'))">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                        <path d="M9 3v18"></path>
                    </svg>
                </button>
                <div id="breadcrumb" class="breadcrumb">
                    <span class="breadcrumb-item">SGK Vizite</span>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-active"><?php echo ucfirst($currentRoute); ?></span>
                </div>
            </div>

            <!-- Sağ Üst Panel Bilgileri -->
            <div style="display: flex; align-items: center; gap: 1rem;">

                <!-- Isyeri Selector Dropdown -->
                <?php if (isset($header_isyerleri) && count($header_isyerleri) > 0): ?>
                <details class="isyeri-dropdown" style="position: relative;">
                    <summary
                        style="display: flex; align-items: center; gap: 0.375rem; font-size: 0.8125rem; font-weight: 600; color: var(--foreground); cursor: pointer; list-style: none; padding: 0.375rem 0.5rem; border-radius: 6px; border: none; background: transparent; transition: all 0.2s;">
                        <i data-lucide="building-2" style="width: 14px; height: 14px; color: hsl(var(--primary));"></i>
                        <span style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo htmlspecialchars($_SESSION['firma_adi'] ?? 'İşyeri Seçin'); ?>
                        </span>
                        <i data-lucide="chevron-down" class="dropdown-arrow"
                            style="width: 12px; height: 12px; margin-left: 0.25rem;"></i>
                    </summary>
                    <div class="dropdown-content"
                        style="position: absolute; top: calc(100% + 6px); right: 0; left: auto; width: 320px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 0.75rem; z-index: 999;">
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; width: 100%;">
                            <span
                                style="font-size: 0.7rem; font-weight: 700; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">İŞYERLERİM</span>
                            <button type="button" id="isyeri-search-toggle"
                                style="background: transparent; border: none; padding: 2px; color: var(--muted-foreground); cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: all 0.2s;"
                                title="İşyeri Ara">
                                <i data-lucide="search" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>

                        <div id="isyeri-search-container"
                            style="position: relative; margin-bottom: 0.625rem; display: none; transition: all 0.2s;">
                            <i data-lucide="search"
                                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 12px; height: 12px; color: var(--muted-foreground);"></i>
                            <input type="text" id="isyeri-search" placeholder="İşyeri ara..." autocomplete="off"
                                style="width: 100%; padding: 0.375rem 0.5rem 0.375rem 1.875rem; font-size: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--background); color: var(--foreground); outline: none;">
                        </div>

                        <div class="isyeri-list-scroll"
                            style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.25rem; padding-right: 0.25rem;">
                            <?php foreach ($header_isyerleri as $isyeri): 
                                    $is_selected = ((int)$isyeri->id === (int)($_SESSION['isyeri_id'] ?? 0));
                                    $enc_id = \App\Helper\Security::encrypt($isyeri->id);
                                ?>
                            <a href="isyeri-sec?isyeri_id=<?php echo $enc_id; ?>" class="isyeri-item"
                                data-name="<?php echo htmlspecialchars(mb_strtolower($isyeri->firma_adi, 'UTF-8')); ?>"
                                style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.625rem; border-radius: 6px; text-decoration: none; color: var(--foreground); transition: background 0.2s; <?php echo $is_selected ? 'background: rgba(37, 37, 37, 0.08); font-weight: 600;' : ''; ?>">
                                <div
                                    style="display: flex; flex-direction: column; gap: 0.125rem; text-align: left; min-width: 0; flex: 1;">
                                    <span
                                        style="font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: <?php echo $is_selected ? 'hsl(var(--primary))' : 'var(--foreground)'; ?>;">
                                        <?php echo htmlspecialchars($isyeri->firma_adi); ?>
                                    </span>
                                    <span
                                        style="font-size: 0.65rem; color: var(--muted-foreground); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        Kod: <?php echo htmlspecialchars($isyeri->isyeri_kodu); ?>
                                    </span>
                                </div>
                                <?php if ($is_selected): ?>
                                <i data-lucide="check"
                                    style="width: 14px; height: 14px; color: hsl(var(--primary)); flex-shrink: 0; margin-left: 0.5rem;"></i>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </details>
                <?php endif; ?>

                <button id="theme-toggle" class="topbar-btn" title="Tema Değiştir">
                    <i data-lucide="sun" class="sun-icon" style="width: 18px;"></i>
                    <i data-lucide="moon" class="moon-icon" style="width: 18px; display: none;"></i>
                </button>
                <a href="https://api.whatsapp.com/send?phone=905079432723" target="_blank" class="topbar-btn"
                    title="Destek"><i data-lucide="phone-call" style="width: 18px;"></i></a>
            </div>
        </header>

        <div id="app-content" class="content-area"
            style="display: flex; flex-direction: column; flex: 1; min-height: 0; padding: 1.5rem 2rem; overflow-y: auto;">
            <?php if ($pageContent): ?>
            <?php echo $pageContent; ?>
            <?php else: ?>
            <!-- Default loader to run dynamic fetch -->
            <div
                style="display: flex; align-items: center; justify-content: center; height: 200px; color: var(--muted-foreground);">
                <div class="spinner" style="margin-right: 0.75rem;"></div> Yükleniyor...
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Basecoat JS -->
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/basecoat.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/toast.min.js" defer></script>

    <!-- jQuery Core & Vendor Plugins (Loaded globally for SPA sub-pages) -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="App/Src/button-loading.js"></script>

    <!-- User Panel Scripts -->
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>

    <script>
    lucide.createIcons();

    // Workplace search filtering and dropdown click-outside closing
    document.addEventListener('DOMContentLoaded', () => {
        const isyeriSearch = document.getElementById('isyeri-search');
        const isyeriSearchToggle = document.getElementById('isyeri-search-toggle');
        const isyeriSearchContainer = document.getElementById('isyeri-search-container');
        const isyeriDropdown = document.querySelector('.isyeri-dropdown');

        if (isyeriSearch) {
            isyeriSearch.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                const items = document.querySelectorAll('.isyeri-item');
                items.forEach(item => {
                    const name = item.getAttribute('data-name') || '';
                    if (name.includes(query)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        if (isyeriSearchToggle && isyeriSearchContainer) {
            isyeriSearchToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (isyeriSearchContainer.style.display === 'none') {
                    isyeriSearchContainer.style.display = 'block';
                    if (isyeriSearch) {
                        isyeriSearch.focus();
                    }
                } else {
                    isyeriSearchContainer.style.display = 'none';
                    if (isyeriSearch) {
                        isyeriSearch.value = '';
                        isyeriSearch.dispatchEvent(new Event('input'));
                    }
                }
            });
        }

        if (isyeriDropdown) {
            isyeriDropdown.addEventListener('toggle', () => {
                if (!isyeriDropdown.open) {
                    if (isyeriSearchContainer) isyeriSearchContainer.style.display = 'none';
                    if (isyeriSearch) {
                        isyeriSearch.value = '';
                        isyeriSearch.dispatchEvent(new Event('input'));
                    }
                }
            });
        }

        // Close the details dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            const isyeriDropdown = document.querySelector('.isyeri-dropdown');
            if (isyeriDropdown && isyeriDropdown.hasAttribute('open') && !isyeriDropdown.contains(e
                    .target)) {
                isyeriDropdown.removeAttribute('open');
            }

            const userDropdown = document.querySelector('.user-dropdown');
            if (userDropdown && userDropdown.hasAttribute('open') && !userDropdown.contains(e.target)) {
                userDropdown.removeAttribute('open');
            }
        });

        // Close dropdowns when a link inside is clicked
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.isyeri-dropdown a, .user-dropdown a');
            if (link) {
                const details = link.closest('details');
                if (details) {
                    details.removeAttribute('open');
                }
            }
        });
    });
    </script>
</body>

</html>