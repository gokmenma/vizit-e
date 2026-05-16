<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
$config = require __DIR__ . '/../config.php';
$is_admin_host = ($_SERVER['HTTP_HOST'] === 'admin.vizite.com' || $_SERVER['HTTP_HOST'] === 'admin.vizit-e.com');
$basePath = $config['base_path'] ?? '/';

// Eğer bir AJAX isteği ise raw içeriği döndür ve çık
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    require_once __DIR__ . '/../router.php';
    exit();
}

// Doğrudan sayfa isteği ise (browser üzerinden), içeriği yakala ve layout içinde göster
$pageContent = '';
if (isset($_GET['url']) && $_GET['url'] !== 'index' && $_GET['url'] !== '') {
    ob_start();
    require_once __DIR__ . '/../router.php';
    $pageContent = ob_get_clean();
}

if ($is_admin_host) {
    $adminBase = '/';
    $assetBase = '/assets/';
} else {
    $adminBase = rtrim($basePath, '/') . '/admin/';
    $assetBase = rtrim($basePath, '/') . '/admin/assets/';
}
$currentRoute = $_GET['url'] ?? 'dashboard';
if ($currentRoute === '' || $currentRoute === 'index') $currentRoute = 'dashboard';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $adminBase; ?>">
    <title>Admin Panel | SGK Vizite</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <!-- Basecoat CSS (BaseUI) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">
    
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="<?php echo $assetBase; ?>css/app.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>css/datatable.custom.css?v=<?php echo time(); ?>">
    
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>css/flatpickr.custom.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="theme-light">
    <aside class="sidebar" data-side="left" aria-hidden="false">
        <nav aria-label="Sidebar navigation">
            <header class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-box">
                        <i data-lucide="sparkles"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-title">SGK Vizite</span>
                        <span class="logo-subtitle">Admin v1.0</span>
                    </div>
                </div>
            </header>

            <section class="scrollbar">
                <div role="group" aria-labelledby="group-label-menu">
                    <h3 id="group-label-menu">Menü</h3>
                    <ul>
                        <li>
                            <a href="/dashboard" class="nav-link <?php echo $currentRoute === 'dashboard' ? 'active' : ''; ?>" data-route="dashboard">
                                <i data-lucide="layout-dashboard"></i>
                                <span>Ana Sayfa</span>
                            </a>
                        </li>
                        <li>
                            <a href="kullanicilar" class="nav-link <?php echo $currentRoute === 'kullanicilar' ? 'active' : ''; ?>" data-route="kullanicilar">
                                <i data-lucide="users"></i>
                                <span>Kullanıcılar</span>
                            </a>
                        </li>
                        <li>
                            <a href="/alt-kullanicilar" class="nav-link <?php echo $currentRoute === 'alt-kullanicilar' ? 'active' : ''; ?>" data-route="alt-kullanicilar">
                                <i data-lucide="user-plus"></i>
                                <span>Alt Kullanıcılar</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div role="group" aria-labelledby="group-label-yönetim">
                    <h3 id="group-label-yönetim">Yönetim</h3>
                    <ul>
                        <li>
                            <a href="/paketler" class="nav-link <?php echo $currentRoute === 'paketler' ? 'active' : ''; ?>" data-route="paketler">
                                <i data-lucide="package"></i>
                                <span>Paket Tanımları</span>
                            </a>
                        </li>
                        <li>
                            <a href="/satinalmalar" class="nav-link <?php echo $currentRoute === 'satinalmalar' ? 'active' : ''; ?>" data-route="satinalmalar">
                                <i data-lucide="shopping-cart"></i>
                                <span>Satın Almalar</span>
                            </a>
                        </li>
                         <li>
                            <a href="/ayarlar" class="nav-link <?php echo $currentRoute === 'ayarlar' ? 'active' : ''; ?>" data-route="ayarlar">
                                <i data-lucide="settings"></i>
                                <span>Ayarlar</span>
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] === 'superadmin'): ?>
                        <li>
                            <a href="/aktiviteler" class="nav-link <?php echo $currentRoute === 'aktiviteler' ? 'active' : ''; ?>" data-route="aktiviteler">
                                <i data-lucide="activity"></i>
                                <span>Sistem Aktiviteleri</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>

            <footer class="sidebar-footer">
                <details class="user-dropdown">
                    <summary>
                        <div class="user-avatar">
                            <?php 
                            $nameParts = explode(' ', $_SESSION['user_ad'] ?? 'MA');
                            $initials = '';
                            foreach ($nameParts as $part) { $initials .= substr($part, 0, 1); }
                            echo strtoupper(substr($initials, 0, 2));
                            ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo $_SESSION['user_ad'] ?? 'Kullanıcı'; ?></span>
                            <span class="user-email"><?php echo $_SESSION['user_email'] ?? ''; ?></span>
                        </div>
                        <i data-lucide="chevrons-up-down" class="dropdown-icon"></i>
                    </summary>
                    <div class="dropdown-content">
                        <div class="dropdown-header">
                            <p class="dropdown-label">Hesabım</p>
                            <p class="dropdown-email"><?php echo $_SESSION['user_email'] ?? ''; ?></p>
                        </div>
                        <a href="profil" class="dropdown-item">
                            <i data-lucide="user"></i>
                            <span>Profil</span>
                        </a>
                        <hr>
                        <a href="logout.php" class="dropdown-item logout">
                            <i data-lucide="log-out"></i>
                            <span>Çıkış yap</span>
                        </a>
                    </div>
                </details>
            </footer>
        </nav>
    </aside>

    <main class="app-main">
        <header class="app-topbar" style="background: #fff; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button type="button" class="topbar-btn" onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar'))" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #71717a;">
                    <i data-lucide="menu"></i>
                </button>
                <div id="breadcrumb" class="breadcrumb" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.875rem; color: #71717a;">SGK Vizite</span>
                    <span style="font-size: 0.875rem; color: #d4d4d8;">/</span>
                    <span style="font-size: 0.875rem; font-weight: 500; color: #18181b;"><?php echo ucfirst($currentRoute); ?></span>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <button style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #71717a;"><i data-lucide="search" style="width: 18px;"></i></button>
                <button style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #71717a;"><i data-lucide="bell" style="width: 18px;"></i></button>
            </div>
        </header>

        <div id="app-content" class="content-area" style="display: flex; flex-direction: column; flex: 1; min-height: 0; padding: 1.5rem 2rem; overflow-y: auto;">
            <?php if ($pageContent): ?>
                <?php echo $pageContent; ?>
            <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; height: 200px; color: var(--muted-foreground);">
                    <div class="spinner" style="margin-right: 0.75rem;"></div> Yükleniyor...
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Basecoat JS -->
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/basecoat.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/toast.min.js" defer></script>
    
    <!-- Admin Scripts -->
    <script src="<?php echo $assetBase; ?>js/table.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $assetBase; ?>js/ui.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $assetBase; ?>js/admin.js?v=<?php echo time(); ?>"></script>

    <script>
        // Initial icon render
        lucide.createIcons();
    </script>
    <!-- Toaster Container for Basecoat -->
    <div id="toaster" class="toaster" data-align="end" popover="manual"></div>
</body>
</html>
