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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">

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
            padding: 0 0.75rem 0 2.25rem !important; /* make space for the icon */
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

        /* Custom Input Icon Styling */
        .input-icon-wrapper {
            position: relative;
            display: block;
            width: 100%;
        }

        .input-icon-wrapper i,
        .input-icon-wrapper svg {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #a1a1aa; /* text-zinc-400 */
            pointer-events: none;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .form-group input:focus + i,
        .form-group input:focus + svg {
            color: #18181b; /* zinc-900 */
        }

        .dark .form-group input:focus + i,
        .dark .form-group input:focus + svg {
            color: #f4f4f5; /* zinc-100 */
        }

        .forgot-password-link {
            font-size: 0.8125rem;
            color: #71717a;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-password-link:hover {
            text-decoration: underline;
            color: #18181b;
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
        .dark .form-group label {
            color: #f4f4f5;
        }

        .dark .form-group input {
            background: #09090b;
            border-color: #27272a;
            color: #f4f4f5;
        }

        .dark .form-group input:focus {
            border-color: #f4f4f5;
        }

        .dark .forgot-password-link {
            color: #a1a1aa;
        }

        .dark .forgot-password-link:hover {
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
        <div class="logo-area">
            <img src="assets/images/logo.svg?v=<?= filemtime(__DIR__ . '/assets/images/logo.svg') ?>" alt="Vizit-e" style="width: 40px; height: 40px; border-radius: 10px;">
            <span class="logo-text">SGK Vizite</span>
        </div>

        <div class="login-card">
            <header class="card-header">
                <h2>Giriş Yap</h2>
            </header>

            <?php if (!empty($error_message)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form id="loginForm" action="sign-in" method="POST">
                <div class="form-group">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <div class="input-icon-wrapper">
                        <input type="text" id="kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı adınızı giriniz" required autofocus>
                        <i data-lucide="user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label for="password">Şifre</label>
                        <a href="forgot-password" class="forgot-password-link">Şifremi Unuttum?</a>
                    </div>
                    <div class="input-icon-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <i data-lucide="lock"></i>
                    </div>
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
    </script>
</body>
</html>
