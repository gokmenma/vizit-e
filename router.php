<?php
file_put_contents('router_log.txt', date('H:i:s') . ' - ' . ($_SERVER['HTTP_HOST'] ?? 'no-host') . ' - ' . ($_SERVER['REQUEST_URI'] ?? 'no-uri') . "\n", FILE_APPEND);
require "vendor/autoload.php";

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
    @file_put_contents(__DIR__ . '/logs/maintenance_error.log', date('Y-m-d H:i:s') . ' - [Router] ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
}

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && file_exists($file) && is_file($file)) {
        return false;
    }
}


class Router
{
    private $routes = [];
    private $prefix = '';
    private $basePath = __DIR__ . '/pages/'; // Varsayılan base path (mutlak)

    // Base path ayarla
    public function setBasePath($path)
    {
        $this->basePath = rtrim($path, '/') . '/';
        return $this;
    }

    // Prefix ve base path ile birlikte
    public function prefix($prefix, $basePath = null)
    {
        $this->prefix = trim($prefix, '/');
        if ($basePath !== null) {
            $this->basePath = __DIR__ . '/' . rtrim($basePath, '/') . '/';
        }
        return $this;
    }

    // Prefix'i sıfırla
    public function resetPrefix()
    {
        $this->prefix = '';
        $this->basePath = __DIR__ . '/pages/'; // Varsayılan değere dön (mutlak)
        return $this;
    }
    // GET route ekle
    public function get($pattern, $callback)
    {
        $this->addRoute('GET', $pattern, $callback);
        return $this;
    }

    // POST route ekle
    public function post($pattern, $callback)
    {
        $this->addRoute('POST', $pattern, $callback);
        return $this;
    }

    private function addRoute($method, $pattern, $callback)
    {
        if (is_string($callback)) {
            // String callback'i base path ile birleştir
            $callback = $this->basePath . $callback;
            $actualCallback = function () use ($callback) {
                require $callback;
            };
        } else {
            $actualCallback = $callback;
        }

        $fullPattern = $this->prefix ? $this->prefix . '/' . ltrim($pattern, '/') : $pattern;
        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPattern,
            'callback' => $actualCallback
        ];
    }

    // Grup tanımlama metodu
    public function group($prefix, $basePath, $callback)
    {
        $oldPrefix = $this->prefix;
        $oldBasePath = $this->basePath;

        $this->prefix($prefix, $basePath);
        $callback($this);

        $this->prefix = $oldPrefix;
        $this->basePath = $oldBasePath;
        return $this;
    }

    public function dispatch($url)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = "@^" . preg_replace('/\{([^\/]+)\}/', '([^/]+)', $route['pattern']) . "$@";

            if (preg_match($pattern, $url, $matches)) {
                array_shift($matches); // ilk match (url) sil
                return call_user_func_array($route['callback'], $matches);
            }
        }

        // Hiçbir route eşleşmezse 404
        http_response_code(404);
        require __DIR__ . '/pages/404.php';
    }
}

// Router başlat
$router = new Router();

// Subdomain tespiti
$is_admin_host = (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'admin.vizite.com' || $_SERVER['HTTP_HOST'] === 'admin.vizit-e.com'));

if ($is_admin_host) {
    // Admin subdomain'indeysek prefix yok, ana dizin gibi çalışır
    $router->prefix('', 'admin/');
    require_once __DIR__ . '/admin/route.php';
    $router->resetPrefix();
} else {
    // Normal domaindeysek 'admin' prefix'li istekleri yönlendir
    if (isset($_GET['url']) && str_starts_with($_GET['url'], 'admin')) {
        $router->prefix('admin', 'admin/');
        require_once __DIR__ . '/admin/route.php';
        $router->resetPrefix();
    }
}

// Ana Rotalar (Sadece subdomain değilsek veya admin rotası eşleşmezse çalışır)
$router->get('abonelik-paketleri', function () {
    require __DIR__ . '/pages/abonelik-paketleri.php';
});

$router->get('index', function () {
    require __DIR__ . '/index.php';
});

$router->get('dashboard', function () {
    require __DIR__ . '/pages/dashboard.php';
});
$router->get('', function () {
    require __DIR__ . '/pages/dashboard.php';
});

$router->get('isyeri-sec', function () {
    require __DIR__ . '/pages/isyeri/isyeri_sec.php';
})->post('isyeri-sec', function () {
    require __DIR__ . '/pages/isyeri/isyeri_sec.php';
});
$router->get('isyerlerim', function () {
    require __DIR__ . '/pages/isyeri/isyerlerim.php';
});
$router->get('excelden-yukle', function () {
    require __DIR__ . '/pages/isyeri/excelden_yukle.php';
});

$router->get('kullanicilar', function () {
    require __DIR__ . '/pages/kullanicilar/liste.php';
});

$router->get('iletisim-bilgileri', function () {
    require __DIR__ . '/pages/iletisim-bilgileri.php';
});

$router->get('tarihe-gore-rapor-ara', function () {
    require __DIR__ . '/pages/tarihe_gore_rapor_ara.php';
})->post('tarihe-gore-rapor-ara', function () {
    require __DIR__ . '/pages/tarihe_gore_rapor_ara.php';
});

$router->get('mahsuplastirilacak-raporlar', function () {
    require __DIR__ . '/pages/mahsuplastirma/mahsuplastirilacak_raporlar.php';
})->post('mahsuplastirilacak-raporlar', function () {
    require __DIR__ . '/pages/mahsuplastirma/mahsuplastirilacak_raporlar.php';
});

$router->get('mahsuplastirilan-raporlar', function () {
    require __DIR__ . '/pages/mahsuplastirma/mahsuplastirilan_raporlar.php';
})->post('mahsuplastirilan-raporlar', function () {
    require __DIR__ . '/pages/mahsuplastirma/mahsuplastirilan_raporlar.php';
});

$router->get('prim-borcuna-mahsup-edilen-odemeler', function () {
    require __DIR__ . '/pages/mahsuplastirma/prim-borcuna-mahsup-edilen-odemeler.php';
})->post('prim-borcuna-mahsup-edilen-odemeler', function () {
    require __DIR__ . '/pages/mahsuplastirma/prim-borcuna-mahsup-edilen-odemeler.php';
});

$router->get('onay-bekleyen-raporlar', function () {
    require __DIR__ . '/pages/onay_bekleyen_raporlar.php';
})->post('onay-bekleyen-raporlar', function () {
    require __DIR__ . '/pages/onay_bekleyen_raporlar.php';
});

$router->get('onayli-rapor-ara', function () {
    require __DIR__ . '/pages/onayli_rapor_ara.php';
});

$router->get('onayli-raporlar', function () {
    require __DIR__ . '/pages/onayli_raporlar.php';
})->post('onayli-raporlar', function () {
    require __DIR__ . '/pages/onayli_raporlar.php';
});

$router->get('iptal-edilen-raporlar', function () {
    require __DIR__ . '/pages/iptal_edilen_raporlar.php';
})->post('iptal-edilen-raporlar', function () {
    require __DIR__ . '/pages/iptal_edilen_raporlar.php';
});

$router->get('manuel-rapor-bildirimi', function () {
    require __DIR__ . '/pages/manuel-rapor/manuel_rapor_bildirimi.php';
});

$router->get('manuel-rapor-goruntule', function () {
    require __DIR__ . '/pages/manuel-rapor/manuel_rapor_goruntule.php';
});

$router->get('manuel-rapor-guncelleme', function () {
    require __DIR__ . '/pages/manuel-rapor/manuel_rapor_guncelleme.php';
});

$router->get('is-kazasi-bildirimi', function () {
    require __DIR__ . '/pages/is_kazasi_bildirimi.php';
})->post('is-kazasi-bildirimi', function () {
    require __DIR__ . '/pages/is_kazasi_bildirimi.php';
});

$router->get('arsivlenmis-raporlar', function () {
    require __DIR__ . '/pages/arsivlenmis_raporlar.php';
})->post('arsivlenmis-raporlar', function () {
    require __DIR__ . '/pages/arsivlenmis_raporlar.php';
});

$router->get('odeme-sayfasi', function () {
    require __DIR__ . '/pages/odeme_sayfasi.php';
});

$router->get('rapor-onay-goster', function () {
    require __DIR__ . '/pages/rapor_onay_goster.php';
});

$router->get('onayli-rapor-goster', function () {
    require __DIR__ . '/pages/onayli-raporlar/rapor_goster.php';
});

$router->get('profile', function () {
    require __DIR__ . '/profile.php';
});

$router->get('forgot-password', function () {
    require __DIR__ . '/forgot_password.php';
})->post('forgot-password', function () {
    require __DIR__ . '/forgot_password.php';
});

$router->get('reset-password', function () {
    require __DIR__ . '/reset_password.php';
})->post('reset-password', function () {
    require __DIR__ . '/reset_password.php';
});

$router->get('temizle', function () {
    require __DIR__ . '/temizle.php';
});

$router->get('sign-in', function () {
    require __DIR__ . '/sign-in.php';
})->post('sign-in', function () {
    require __DIR__ . '/sign-in.php';
});

$router->get('sign-up', function () {
    require __DIR__ . '/sign-up.php';
})->post('sign-up', function () {
    require __DIR__ . '/sign-up.php';
});

$router->get('sign-up/{davetid}', function ($davetid) {
    require __DIR__ . '/sign-up.php';
})->post('sign-up/{davetid}', function ($davetid) {
    require __DIR__ . '/sign-up.php';
});







//Çıkış yap
$router->get('logout', function () {
    session_destroy();
    header("Location: sign-in");
    exit();
});

// Yetkiniz yok sayfası
$router->get('unauthorize', function () {
    require __DIR__ . '/pages/unauthorize.php';
});





// Parametreli örnek: /rapor/2025-08-17
$router->get('rapor/{tarih}', function ($tarih) {
    // $tarih değişkeni dinamik geliyor
    echo "Rapor tarihi: " . htmlspecialchars($tarih);
});

// Çalıştır
$url = $_GET['url'] ?? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$router->dispatch($url);