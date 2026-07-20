<?php
// Gerekli sınıfları dahil edelim

use App\Helper\Security;
use Models\UserModel;

// Modeli başlatalım
$UserModel = new UserModel();

// Değişkenleri başlangıçta tanımlayalım
$token = $_GET['token'] ?? '';

$error_message = '';
$success_message = '';
$showForm = false; // Formu sadece token geçerliyse göstereceğiz

// 1. TOKEN KONTROLÜ (Sayfa Yüklendiğinde)
if (empty($token)) {
    $error_message = "Geçersiz sıfırlama linki. Lütfen tekrar deneyin.";
} else {
    // Gelen token'ın hash'ini alarak veritabanında arama yapalım
    $token_hash = hash('sha256', $token);

    // NOT: UserModel'ınıza bu fonksiyonu eklemeniz gerekecek.
    $user = $UserModel->findByResetToken($token_hash);
   
    // Kullanıcı bulundu mu ve token'ın süresi dolmuş mu kontrol et
    if ($user && strtotime($user->token_expiry) > time()) {
        // Token geçerli ve süresi dolmamış, formu göster
        $showForm = true;
    } else {
        // Token geçersiz veya süresi dolmuş
        $error_message = "Bu şifre sıfırlama linki geçersiz veya süresi dolmuş. Lütfen yeni bir talep oluşturun.";
    }
}

// 2. FORM GÖNDERİMİ (Yeni Şifre Belirlendiğinde)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_from_form = $_POST['token'] ?? '';
    $password = $_POST['sifre'] ?? '';
    $password_confirm = $_POST['sifre_tekrar'] ?? '';

    // Formdan gelen token ile ilk token'ın hala aynı olduğunu teyit edelim
    if (empty($token_from_form) || $token_from_form !== $token) {
        $error_message = "Güvenlik hatası. Lütfen sayfayı yenileyip tekrar deneyin.";
        $showForm = false;
    }
    // Şifre alanları boş mu kontrol et
    else if (empty($password) || empty($password_confirm)) {
        $error_message = "Şifre alanları boş bırakılamaz.";
        $showForm = true; // Hata var, formu tekrar göster
    }
    // Şifreler uyuşuyor mu kontrol et
    else if ($password !== $password_confirm) {
        $error_message = "Girdiğiniz şifreler uyuşmuyor.";
        $showForm = true; // Hata var, formu tekrar göster
    }
    // Şifre uzunluğu kontrolü (isteğe bağlı ama önerilir)
    else if (strlen($password) < 6) {
        $error_message = "Şifreniz en az 6 karakter olmalıdır.";
        $showForm = true; // Hata var, formu tekrar göster
    }
    // Tüm kontroller başarılıysa
    else {
        // Yeni şifreyi güvenli bir şekilde hash'leyelim
        $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Veritabanında şifreyi güncelle ve token'ı temizle
        $UserModel->updatePasswordAndClearToken($user->id, $new_password_hash);
        
        $success_message = "Şifreniz başarıyla güncellendi. Artık yeni şifrenizle giriş yapabilirsiniz.";
        $showForm = false; // İşlem bitti, formu gizle
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sgk Rapor işlemlerinizi sms şifresi olmadan yapın">
    <title>Yeni Şifre Belirle | SGK Vizite</title>
    
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

        .success-banner {
            background-color: #f0fdf4;
            color: #16a34a;
            font-size: 0.8125rem;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #dcfce7;
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

        .dark .success-banner {
            background-color: rgba(22, 163, 74, 0.1);
            border-color: rgba(22, 163, 74, 0.2);
            color: #4ade80;
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
                <h2>Yeni Şifre Belirle</h2>
            </header>

            <?php if (!empty($error_message)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-banner"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($showForm): ?>
                <p style="font-size: 0.875rem; color: #71717a; text-align: center; margin-top: -1rem; margin-bottom: 2rem;" class="dark:text-zinc-400">
                    Hesabınız için en az 6 karakterden oluşan güvenli yeni şifrenizi belirleyin.
                </p>

                <form method="POST" autocomplete="off">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="sifre">Yeni Şifre</label>
                        <input type="password" id="sifre" name="sifre" placeholder="••••••••" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="sifre_tekrar">Yeni Şifre (Tekrar)</label>
                        <input type="password" id="sifre_tekrar" name="sifre_tekrar" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-login">Şifreyi Güncelle</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="footer-links">
            <?php if (!empty($success_message)): ?>
                <a href="sign-in">Giriş Yap'a Dön</a>
            <?php else: ?>
                <a href="forgot-password">Yeni Sıfırlama Talebi Oluştur</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>