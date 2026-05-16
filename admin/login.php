<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$config = require __DIR__ . '/../config.php';
$basePath = $config['base_path'] ?? '/';

// Giriş kontrolü
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? ''; // Email veya Kullanıcı Adı
    $password = $_POST['password'] ?? '';

    try {
        require_once __DIR__ . '/../Core/Database.php';
        $db = \Core\Database::getInstance()->getConnection();
        
        // Hem email hem de kullanıcı adı ile kontrol et
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE (email = ? OR kullanici_adi = ?) AND durum = 'Aktif'");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['sifre'])) {
            if ($user['role'] === 'superadmin') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_ad'] = $user['adi_soyadi'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['kullanici_id'] = $user['id']; // DatabaseLogger uses this

                require_once __DIR__ . '/../Core/Services/DatabaseLogger.php';
                $logger = new \Core\Services\DatabaseLogger('admin-auth');
                $logger->info("Süper Admin giriş yaptı: " . $user['adi_soyadi']);
                
                header("Location: dashboard");
                exit();
            } else {
                $error = 'Bu alana sadece Süper Admin yetkisi olanlar girebilir!';
            }
        } else {
            $error = 'Giriş bilgileri hatalı!';
        }
    } catch (Exception $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | SGK Vizite</title>
    
    <!-- Basecoat CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    
    <!-- Fonts (Geist) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --background: 0 0% 98%;
            --foreground: 240 10% 3.9%;
            --card: 0 0% 100%;
            --card-foreground: 240 10% 3.9%;
            --primary: 240 5.9% 10%;
            --primary-foreground: 0 0% 98%;
            --border: 240 5.9% 90%;
            --input: 240 5.9% 90%;
            --ring: 240 5.9% 10%;
            --radius: 0.5rem;
        }

        body {
            font-family: 'Geist', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f4f5;
            color: hsl(var(--foreground));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .logo-box {
            width: 40px;
            height: 40px;
            background: #000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #18181b;
        }

        .login-card {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: #18181b;
        }

        .form-group {
            display: grid;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #18181b;
        }

        .form-group input {
            height: 2.5rem;
            width: 100%;
            border-radius: 6px;
            border: 1px solid #e4e4e7;
            background: #fff;
            padding: 0 0.75rem;
            font-size: 0.875rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #18181b;
            ring: 1px solid #18181b;
        }

        .btn-login {
            width: 100%;
            height: 2.5rem;
            background: #18181b;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .error-banner {
            background-color: #fef2f2;
            color: #ef4444;
            font-size: 0.8125rem;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #fee2e2;
            text-align: center;
            font-weight: 500;
        }

        .footer-links {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.875rem;
            color: #71717a;
        }

        .footer-links a {
            color: #18181b;
            font-weight: 600;
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-area">
            <div class="logo-box">
                <i data-lucide="sparkles" style="width: 22px; height: 22px;"></i>
            </div>
            <span class="logo-text">SGK Vizite</span>
        </div>

        <div class="login-card">
            <header class="card-header">
                <h2>Giriş Yap</h2>
            </header>

            <?php if ($error): ?>
                <div class="error-banner"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="identifier">E-posta veya Kullanıcı Adı</label>
                    <input type="text" id="identifier" name="identifier" placeholder="Kullanıcı adı veya e-posta" required autofocus>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label for="password">Şifre</label>
                    </div>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-login">Giriş Yap</button>
            </form>
        </div>

        <div class="footer-links">
            Henüz bir hesabınız yok mu? <a href="#">Kayıt Ol</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
