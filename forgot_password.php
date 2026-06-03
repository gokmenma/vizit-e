<?php

use Models\UserModel;
use Core\Services\MailGonderService;
use App\Helper\Security;

$UserModel = new UserModel();


$email = '';
$error_message = '';
$success_message = '';
$showForm = true; // Formun gösterilip gösterilmeyeceğini kontrol eden bayrak

// Sadece POST metodu ile istek geldiğinde formu işle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formdan gelen e-posta adresini alalım
    $email = $_POST['email'] ?? '';

    // E-posta boş mu kontrol et
    if (empty($email)) {
        $error_message = "E-posta adresi boş bırakılamaz.";
    }
    // E-posta formatı geçerli mi kontrol et
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Lütfen geçerli bir e-posta adresi girin.";
    } else {

        $user = $UserModel->findByEmail($email);

        if ($user) {
            // Kullanıcı bulundu, şifre sıfırlama işlemlerini başlat
            // 1. Güvenli bir token oluştur
          

            $token = bin2hex(random_bytes(16)); // Raw token - sadece email için
            $token_hash = hash('sha256', $token); // Hashed token - veritabanı için
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // 2. Token'ı ve son kullanma tarihini veritabanında sakla
            $data = [
                'id' => $user->id,
                'reset_token' => $token_hash,
                'token_expiry' => $token_expiry
            ];
            $UserModel->saveWithAttr($data);

            // 3. Şifre sıfırlama linkini oluştur
            $reset_link = $_ENV['APP_URL'] . '/reset-password?token=' . $token;
            // 4. Kullanıcıya e-posta gönder


            $subject = "Şifre Sıfırlama Talimatları";
            $body = "Merhaba " . htmlspecialchars($user->kullanici_adi) . ",<br><br>";
            $body .= "Şifrenizi sıfırlamak için lütfen aşağıdaki linke tıklayın:<br>";
            $body .= "<a href=\"$reset_link\">$reset_link</a><br><br>";
            $body .= "Link 1 saat içinde geçerli olacaktır. Eğer linki kullanmazsanız lütfen bu e-postayı dikkate alınmaksızın silin.<br><br>";
            $body .= "Teşekkürler,<br>Vizit-e";


            $body = <<<HTML
                <!DOCTYPE html>
                <html lang="tr">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Şifre Sıfırlama</title>
                </head>
                <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #1a1a1e; color: #E4DEFF; line-height: 1.6;">

                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #1a1a1e;">
                        <tr>
                            <td align="center">
                                <table width="600" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 20px auto;">

                                    <!-- Başlık -->
                                    <tr>
                                        <td align="center" style="padding: 30px 0;">
                                            <div style="font-size: 28px; font-weight: bold; color: #E4DEFF;">VİZİT-E</div>
                                        </td>
                                    </tr>

                                    <!-- Ana İçerik Kartı -->
                                    <tr>
                                        <td style="background-color: #2c2c2f; border-radius: 16px; border: 1px solid #444;">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding: 40px; color: #cccccc; font-size: 16px;">
                                                        
                                                        <!-- İkon -->
                                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td align="center" style="padding-bottom: 20px;">
                                                                    <img src="https://i.imgur.com/gwha12C.png" alt="Kilit İkonu" width="60" style="display: block;">
                                                                </td>
                                                            </tr>
                                                        </table>

                                                        <h1 style="font-size: 24px; font-weight: bold; margin: 0 0 20px 0; text-align: center; color: #FFFFFF;">Şifre Sıfırlama Talebi</h1>
                                                        <p style="margin: 0 0 20px 0;">Merhaba, <strong>{$user->kullanici_adi}</strong></p>
                                                        <p style="margin: 0 0 30px 0;">Hesabınız için bir şifre sıfırlama talebi aldık. Şifrenizi sıfırlamak için lütfen aşağıdaki butona tıklayın.</p>

                                                        <!-- Buton -->
                                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td align="center">
                                                                    <a href="{$reset_link}" target="_blank" style="background: linear-gradient(90deg, #8a2be2, #6a5acd); background-color: #8a2be2; color: #FFFFFF !important; padding: 16px 32px; border-radius: 30px; font-weight: bold; font-size: 16px; text-decoration: none; display: inline-block;">Şifremi Sıfırla</a>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                        <p style="margin: 30px 0 0 0; font-size: 14px; text-align: center; color: #888888;">
                                                            Bu talepte bulunmadıysanız, bu e-postayı görmezden gelebilirsiniz. Bu link 1 saat süreyle geçerlidir.
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <!-- Footer -->
                                    <tr>
                                        <td align="center" style="padding: 30px 0; color: #777; font-size: 12px;">
                                            © SENE VİZİT-E. Bu e-postayı, hesabınız için bir şifre sıfırlama talebinde bulunulduğu için aldınız.
                                        </td>
                                    </tr>

                                </table>
                            </td>
                        </tr>
                    </table>

                </body>
                </html>
                HTML;


            MailGonderService::gonder($email, $subject, $body);

            // 5. Başarılı mesajı göster    
            $success_message = "Şifre sıfırlama talimatları e-posta adresinize gönderildi. Lütfen gelen kutunuzu kontrol edin.";
            $showForm = false; // Başarılı olduğunda formu gizle
        } else {
            //Kullanıcı yok
            $error_message = "Bu e-posta adresine kayıtlı bir kullanıcı bulunamadı.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sgk Rapor işlemlerinizi sms şifresi olmadan yapın">
    <title>Şifre Sıfırlama | SGK Vizite</title>
    
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
                <h2>Şifre Sıfırlama</h2>
            </header>

            <?php if (!empty($error_message)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-banner"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($showForm): ?>
                <p style="font-size: 0.875rem; color: #71717a; text-align: center; margin-top: -1rem; margin-bottom: 2rem;" class="dark:text-zinc-400">
                    Kayıtlı e-posta adresinizi girdiğinizde size şifre sıfırlama talimatlarını içeren güvenli bir bağlantı göndereceğiz.
                </p>

                <form action="forgot-password" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email">E-Posta Adresi</label>
                        <input type="email" id="email" name="email" placeholder="isim@sirket.com" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                    </div>

                    <button type="submit" name="submitButton" class="btn-login">Sıfırlama Linki Gönder</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="footer-links">
            <a href="sign-in">Giriş Yap'a Geri Dön</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>```