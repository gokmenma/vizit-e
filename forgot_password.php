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
            $reset_link = 'https://vizit-e.com/reset-password?token=' . $token;
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
<!doctype html>
<html class="no-js " lang="tr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="description" content="Sgk Rapor işlemlerinizi sms şifresi olmadan yapın">
    <title>Vizit-e - Şifre Sıfırlama</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/color_skins.css">
    <link rel="stylesheet" href="/assets/css/login.css?v=<?php echo filemtime(__DIR__ . '/assets/css/login.css') ?>">
    <style>
        .alert {
            margin-bottom: 20px !important;
            font-size: 15px;
            font-weight: 500;
            border-radius: 0.35rem;
        }

        .authentication .card-plain {
            max-width: 550px !important;
        }
    </style>
</head>

<body class="theme-black">
    <div class="authentication">
        <div class="container">
            <div class="login-center">
                <div class="login-hero">
                    <img src="/assets/images/logo.svg" alt="Vizit-e" class="login-hero__logo">
                </div>
                <h3 class="center-title">Şifre Sıfırlama</h3>
                <p class="center-subtitle">Kayıtlı e-posta adresinizi girin</p>
                <?php if (!empty($error_message)) { echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>'; } ?>
                <?php if (!empty($success_message)) { echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>'; } ?>
                <div class="login-card">
                    <?php if ($showForm): ?>
                    <form class="login-form" action="forgot-password" method="POST" autocomplete="off">
                        <div class="field">
                            <label class="form-label">E-Posta Adresi</label>
                            <div class="input-group field-control">
                                <span class="input-group-addon"><i class="zmdi zmdi-email"></i></span>
                                <input type="email" class="form-control" name="email" placeholder="isim@sirket.com" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="submitButton" class="btn btn-login btn-block">Sıfırlama Linki Gönder</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="center-footer"><a href="sign-in">Giriş Yap'a Geri Dön</a></div>
            </div>
        </div>
    </div>

    <!-- Jquery Core Js -->
    <script src="/assets/bundles/libscripts.bundle.js"></script>
    <script src="/assets/bundles/vendorscripts.bundle.js"></script>
</body>

</html>```