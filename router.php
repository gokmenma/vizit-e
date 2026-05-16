<?php
file_put_contents('router_log.txt', date('H:i:s') . ' - ' . ($_SERVER['HTTP_HOST'] ?? 'no-host') . ' - ' . ($_SERVER['REQUEST_URI'] ?? 'no-uri') . "\n", FILE_APPEND);
require "vendor/autoload.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    private $basePath = 'pages/'; // Varsayılan base path

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
            $this->basePath = rtrim($basePath, '/') . '/';
        }
        return $this;
    }

    // Prefix'i sıfırla
    public function resetPrefix()
    {
        $this->prefix = '';
        $this->basePath = 'pages/'; // Varsayılan değere dön
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
        require 'pages/404.php';
    }
}

// Router başlat
$router = new Router();

// Subdomain tespiti
$is_admin_host = (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'admin.vizite.com' || $_SERVER['HTTP_HOST'] === 'admin.vizit-e.com'));

if ($is_admin_host) {
    // Admin subdomain'indeysek prefix yok, ana dizin gibi çalışır
    $router->prefix('', 'admin/');
    require_once 'admin/route.php';
    $router->resetPrefix();
} else {
    // Normal domaindeysek 'admin' prefix'li istekleri yönlendir
    if (isset($_GET['url']) && str_starts_with($_GET['url'], 'admin')) {
        $router->prefix('admin', 'admin/');
        require_once 'admin/route.php';
        $router->resetPrefix();
    }
}

// Ana Rotalar (Sadece subdomain değilsek veya admin rotası eşleşmezse çalışır)
$router->get('abonelik-paketleri', function () {
    require 'pages/abonelik-paketleri.php';
});

$router->get('index', function () {
    require 'index.php';
});

$router->get('isyeri-sec', function () {
    require 'pages/isyeri/isyeri_sec.php';
});
$router->get('isyerlerim', function () {
    require 'pages/isyeri/isyerlerim.php';
});
$router->get('excelden-yukle', function () {
    require 'pages/isyeri/excelden_yukle.php';
});

$router->get('kullanicilar', function () {
    require 'pages/kullanicilar/liste.php';
});

$router->get('iletisim-bilgileri', function () {
    require 'pages/iletisim-bilgileri.php';
});

$router->get('tarihe-gore-rapor-ara', function () {
    require 'pages/tarihe_gore_rapor_ara.php';
});

$router->get('mahsuplastirilacak-raporlar', function () {
    require 'pages/mahsuplastirma/mahsuplastirilacak_raporlar.php';
});

$router->get('mahsuplastirilan-raporlar', function () {
    require 'pages/mahsuplastirma/mahsuplastirilan_raporlar.php';
});

$router->get('prim-borcuna-mahsup-edilen-odemeler', function () {
    require 'pages/mahsuplastirma/prim-borcuna-mahsup-edilen-odemeler.php';
});

$router->get('onay-bekleyen-raporlar', function () {
    require 'pages/onay_bekleyen_raporlar.php';
});

$router->get('onayli-rapor-ara', function () {
    require 'pages/onayli_rapor_ara.php';
});

$router->get('onayli-raporlar', function () {
    require 'pages/onayli_raporlar.php';
});

$router->get('manuel-rapor-bildirimi', function () {
    require 'pages/manuel-rapor/manuel_rapor_bildirimi.php';
});

$router->get('manuel-rapor-goruntule', function () {
    require 'pages/manuel-rapor/manuel_rapor_goruntule.php';
});

$router->get('manuel-rapor-guncelleme', function () {
    require 'pages/manuel-rapor/manuel_rapor_guncelleme.php';
});

$router->get('is-kazasi-bildirimi', function () {
    require 'pages/is_kazasi_bildirimi.php';
});

$router->get('arsivlenmis-raporlar', function () {
    require 'pages/arsivlenmis_raporlar.php';
});

$router->get('odeme-sayfasi', function () {
    require 'pages/odeme_sayfasi.php';
});

$router->get('rapor-onay-goster', function () {
    require 'pages/rapor_onay_goster.php';
});

$router->get('onayli-rapor-goster', function () {
    require 'pages/onayli-raporlar/rapor_goster.php';
});

$router->get('profile', function () {
    require 'profile.php';
});

$router->get('forgot-password', function () {
    require 'forgot_password.php';
})->post('forgot-password', function () {
    require 'forgot_password.php';
});

$router->get('reset-password', function () {
    require 'reset_password.php';
})->post('reset-password', function () {
    require 'reset_password.php';
});

$router->get('temizle', function () {
    require 'temizle.php';
});

$router->get('sign-in', function () {
    require 'sign-in.php';
})->post('sign-in', function () {
    require 'sign-in.php';
});

$router->get('sign-up', function () {
    require 'sign-up.php';
})->post('sign-up', function () {
    require 'sign-up.php';
});

$router->get('sign-up/{davetid}', function ($davetid) {
    require 'sign-up.php';
})->post('sign-up/{davetid}', function ($davetid) {
    require 'sign-up.php';
});







//Çıkış yap
$router->get('logout', function () {
    session_destroy();
    header("Location: sign-in");
    exit();
});

// Yetkiniz yok sayfası
$router->get('unauthorize', function () {
    require 'pages/unauthorize.php';
});





// Parametreli örnek: /rapor/2025-08-17
$router->get('rapor/{tarih}', function ($tarih) {
    // $tarih değişkeni dinamik geliyor
    echo "Rapor tarihi: " . htmlspecialchars($tarih);
});

// Çalıştır
$url = $_GET['url'] ?? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$router->dispatch($url);
