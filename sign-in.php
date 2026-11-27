<?php

require_once "vendor/autoload.php";


use Models\UserModel;
use Core\Services\DatabaseLogger as Logger;

$UserModel = new UserModel();

$logger = new Logger('sign-in'); // Loglama için Logger sınıfını kullanıyoruz

// Hata mesajı için bir değişken tanımla
$error_message = '';

// Formun POST metodu ile gönderilip gönderilmediğini kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $password = $_POST['password'] ?? '';

    // Gelen verilerin boş olup olmadığını kontrol et
    if (!empty($kullanici_adi) && !empty($password)) {
        // UserModel'den bir nesne oluştur

        // Modeldeki login fonksiyonunu çağır
        $user = $UserModel->login($kullanici_adi);

              // Fonksiyon bir kullanıcı döndürdüyse (giriş başarılıysa)
        if ($user && password_verify($password, $user->sifre)) {

       
            //Eğer kullanıcı pasif ise giriş yapamasın
            if ($user->durum == "Pasif") {
                $logger->warning("Pasif kullanıcı giriş denemesi: " . $user->kullanici_adi, [
                    'user_id' => $user->id,
                    'identifier' => $kullanici_adi,
                    'reason' => 'passive_user'
                ]);
                $error_message = 'Kullanıcı pasif! Lütfen yöneticiniz ile iletişime geçin.';
            } else {


                // Başarılı giriş
                // Kullanıcı bilgilerini session'a kaydet
                $_SESSION['kullanici_id'] = $user->id; // Veritabanındaki kullanıcı id'si
                $_SESSION['kullanici_adi'] = $user->kullanici_adi;
                $_SESSION["role"] = $user->role; // Kullanıcı rolü (admin veya user)
                $_SESSION["yetkiler"] = $user->yetkiler ?? ""; // Kullanıcı yetkileri
                $_SESSION["user"] = $user; // Tüm kullanıcı bilgileri

                // Varsa varsayılan işyerini otomatik olarak seç
                try {
                    require_once __DIR__ . '/Models/KullaniciIsyeriModel.php';
                    $isyeriModelForLogin = new \Models\KullaniciIsyeriModel();
                    
                    $defaultIsyeri = null;
                    if ($user->role === 'user') {
                        $isyeri_ids = $user->yetkili_oldugu_isyeri_ids ?? '';
                        if (!empty($isyeri_ids)) {
                            $db = \Core\Database::getInstance()->getConnection();
                            $placeholders = implode(',', array_fill(0, count(explode(',', $isyeri_ids)), '?'));
                            $stmt = $db->prepare("SELECT * FROM kullanici_isyerleri WHERE id IN ($placeholders) AND varsayilan_mi = 1 AND aktif_mi = 1 LIMIT 1");
                            $stmt->execute(explode(',', $isyeri_ids));
                            $defaultIsyeri = $stmt->fetch(PDO::FETCH_OBJ);
                        }
                    } else {
                        $defaultIsyeri = $isyeriModelForLogin->whereRaw('kullanici_id = ? AND varsayilan_mi = 1 AND aktif_mi = 1', [$user->id]);
                        if (!empty($defaultIsyeri)) {
                            $defaultIsyeri = $defaultIsyeri[0];
                        }
                    }

                    if ($defaultIsyeri) {
                        $_SESSION['isyeri_id'] = $defaultIsyeri->id;
                        $_SESSION['firma_adi'] = $defaultIsyeri->firma_adi;
                        $_SESSION['kullaniciAdi'] = $defaultIsyeri->kullanici_kodu;
                        $_SESSION['isyeriKodu'] = $defaultIsyeri->isyeri_kodu;
                        $_SESSION['wsSifre'] = $defaultIsyeri->isyeri_sifre;
                    }
                } catch (\Exception $loginIsyeriEx) {
                }

                //echo "kullanıcı id: " . $_SESSION['kullanici_id'];
                $logger->success("Kullanıcı giriş yaptı: " . $user->kullanici_adi, [
                    'user_id' => $user->id,
                    'identifier' => $kullanici_adi
                ]);

                // Ana sayfaya yönlendir
                header('Location: ' . $_ENV['BASE_PATH']);

                exit;
            }
        } else {
            // Hatalı giriş
            $logger->warning("Başarısız kullanıcı giriş denemesi: " . $kullanici_adi, [
                'user_id' => $user->id ?? 0,
                'identifier' => $kullanici_adi,
                'reason' => $user ? 'invalid_password' : 'user_not_found'
            ]);
            $error_message = 'Kullanıcı adı veya şifre yanlış!';
        }
    } else {
        $error_message = 'Lütfen tüm alanları doldurun.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sgk Rapor işlemlerinizi sms şifresi olmadan yapın">
    <title>Giriş Yap | SGK Vizite</title>
    
    <!-- Basecoat CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    
    <!-- Fonts (Geist) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/geist-sans/index.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

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
            max-width: 440px;
            padding: 1rem;
        }

        .login-card {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 16px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .card-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .card-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            margin: 0 auto 1.25rem;
            display: block;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin: 0;
            color: #18181b;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: #71717a;
            margin: 0.375rem 0 0;
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

        .form-group input[type="text"],
        .form-group input[type="password"] {
            height: 2.625rem;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #e4e4e7;
            background: #fff;
            padding: 0 0.75rem;
            font-size: 0.875rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #18181b;
            box-shadow: 0 0 0 1px #18181b;
        }

        .password-wrapper {
            position: relative;
            display: block;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 2.5rem !important;
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            border: none;
            background: transparent;
            color: #a1a1aa;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: color 0.2s, background-color 0.2s;
        }

        .password-toggle-btn:hover {
            color: #18181b;
            background-color: rgba(0, 0, 0, 0.05);
        }

        .password-toggle-btn:focus-visible {
            outline: 2px solid #18181b;
            outline-offset: 1px;
        }

        .password-toggle-btn svg {
            width: 1rem;
            height: 1rem;
        }

        .dark .password-toggle-btn:hover {
            color: #f4f4f5;
            background-color: rgba(255, 255, 255, 0.08);
        }

        .dark .password-toggle-btn:focus-visible {
            outline-color: #f4f4f5;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #18181b;
            cursor: pointer;
            user-select: none;
        }

        .remember-me input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            border-radius: 4px;
            border: 1px solid #d4d4d8;
            accent-color: #18181b;
            cursor: pointer;
            margin: 0;
        }

        .forgot-password-link {
            font-size: 0.875rem;
            color: #18181b;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .forgot-password-link:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            height: 2.75rem;
            background: #18181b;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 0.2s;
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

        /* Dark Mode Overrides */
        .dark body {
            background-color: #09090b;
        }

        .dark .logo-text {
            color: #f4f4f5;
        }

        .dark .login-card {
            background: #18181b;
            border-color: #27272a;
        }

        .dark .card-header h2,
        .dark .form-group label,
        .dark .remember-me {
            color: #f4f4f5;
        }

        .dark .card-subtitle {
            color: #a1a1aa;
        }

        .dark .form-group input[type="text"],
        .dark .form-group input[type="password"] {
            background: #09090b;
            border-color: #27272a;
            color: #f4f4f5;
        }

        .dark .form-group input[type="text"]:focus,
        .dark .form-group input[type="password"]:focus {
            border-color: #f4f4f5;
            box-shadow: 0 0 0 1px #f4f4f5;
        }

        .dark .remember-me input[type="checkbox"] {
            accent-color: #f4f4f5;
        }

        .dark .forgot-password-link {
            color: #f4f4f5;
        }

        .dark .btn-login {
            background: #f4f4f5;
            color: #18181b;
        }

        .dark .footer-links {
            color: #a1a1aa;
        }

        .dark .footer-links a {
            color: #f4f4f5;
        }

        .dark .error-banner {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <header class="card-header">
                <img src="assets/images/logo.svg?v=<?= filemtime(__DIR__ . '/assets/images/logo.svg') ?>" alt="SGK Vizite" class="card-logo">
                <h2>Tekrar hoş geldiniz</h2>
                <p class="card-subtitle">Devam etmek için hesabınıza giriş yapın</p>
            </header>

            <?php if (!empty($error_message)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form id="loginForm" action="sign-in" method="POST">
                <div class="form-group">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <input type="text" id="kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı adınızı giriniz" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Şifreyi göster" title="Şifreyi göster" aria-pressed="false">
                            <i data-lucide="eye" id="togglePasswordIconShow"></i>
                            <i data-lucide="eye-off" id="togglePasswordIconHide" style="display: none;"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me" for="remember_me">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <span>Beni hatırla</span>
                    </label>
                    <a href="forgot-password" class="forgot-password-link">Şifremi unuttum</a>
                </div>

                <button type="submit" class="btn-login">Giriş Yap</button>
            </form>
        </div>

        <div class="footer-links">
            Hesabınız yok mu? <a href="sign-up">Hemen Kayıt Olun</a>
        </div>
    </div>

    <script>
        lucide.createIcons();

        (function() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const iconShow = document.getElementById('togglePasswordIconShow');
            const iconHide = document.getElementById('togglePasswordIconHide');

            if (passwordInput && toggleBtn && iconShow && iconHide) {
                toggleBtn.addEventListener('click', function() {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    iconShow.style.display = isPassword ? 'none' : '';
                    iconHide.style.display = isPassword ? '' : 'none';
                    toggleBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                    toggleBtn.setAttribute('aria-label', isPassword ? 'Şifreyi gizle' : 'Şifreyi göster');
                    toggleBtn.setAttribute('title', isPassword ? 'Şifreyi gizle' : 'Şifreyi göster');
                });
            }

            const usernameInput = document.getElementById('kullanici_adi');
            const rememberCheckbox = document.getElementById('remember_me');
            const loginForm = document.getElementById('loginForm');
            const REMEMBER_KEY = 'sgk_vizite_remembered_user';

            if (usernameInput && rememberCheckbox && loginForm) {
                const savedUser = localStorage.getItem(REMEMBER_KEY);
                if (savedUser) {
                    usernameInput.value = savedUser;
                    rememberCheckbox.checked = true;
                }

                loginForm.addEventListener('submit', function() {
                    if (rememberCheckbox.checked && usernameInput.value.trim() !== '') {
                        localStorage.setItem(REMEMBER_KEY, usernameInput.value.trim());
                    } else {
                        localStorage.removeItem(REMEMBER_KEY);
                    }
                });
            }
        })();
    </script>
</body>
</html>
