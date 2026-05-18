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



<!doctype html>
<html class="no-js " lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="description" content="Sgk Rapor işlemlerinizi sms şifresi olmadan yapın">

    <title>Vizit-e - Giriş Yap</title>
    <!-- Favicon-->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/plugins/bootstrap/css/bootstrap.min.css">

    <!-- Custom Css -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/color_skins.css">
    <link rel="stylesheet" href="/assets/css/login.css?v=<?php echo filemtime(__DIR__ . '/assets/css/login.css') ?>">
</head>

<body class="theme-black">
    <div class="authentication">
        <div class="container">
            <div class="login-center">
                <div class="login-hero">
                    <img src="/assets/images/logo.svg" alt="Vizit-e" class="login-hero__logo">
                </div>
                <h3 class="center-title">Vizit-E'ye Hoşgeldiniz</h3>
                <p class="center-subtitle">Devam etmek için hesabınıza giriş yapın</p>
                <?php if (!empty($error_message)) { echo '<div class="alert alert-danger login-alert">' . htmlspecialchars($error_message) . '</div>'; } ?>
                <div class="login-card">
                    <form class="login-form" action="sign-in" method="post">
                        <div class="field">
                            <label class="form-label">E-Posta Adresi</label>
                            <div class="input-group field-control">
                                <span class="input-group-addon"><i class="zmdi zmdi-email"></i></span>
                                <input type="text" class="form-control" placeholder="Kullanıcı adınızı giriniz" name="kullanici_adi" />
                            </div>
                        </div>
                        <div class="field">
                            <div class="field-top">
                                <label class="form-label">Şifre</label>
                                <a href="forgot-password" class="link-sm">Şifremi Unuttum?</a>
                            </div>
                            <div class="input-group field-control">
                                <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                                <input type="password" placeholder="••••••••" class="form-control" name="password" />
                            </div>
                        </div>
                        <button type="submit" class="btn btn-login btn-block">Giriş Yap</button>
                    </form>
                </div>
                <div class="center-footer">Hesabınız yok mu? <a href="sign-up">Hemen Kayıt Olun</a></div>
            </div>
        </div>
    </div>
    
    <!-- Jquery Core Js -->
    <script src="/assets/bundles/libscripts.bundle.js"></script>
    <script src="/assets/bundles/vendorscripts.bundle.js"></script>
</body>

</html>
