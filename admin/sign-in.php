<?php 

require_once "vendor/autoload.php";
// Oturumu başlat

// Gerekli model dosyalarını dahil et
// Not: Dosya yollarının doğru olduğundan emin olun.

use Models\UserModel;
$UserModel = new UserModel();

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
        $user = $UserModel->adminLogin($kullanici_adi);

        // Fonksiyon bir kullanıcı döndürdüyse (giriş başarılıysa)
        if ($user && password_verify($password, $user->sifre)) {
            // Başarılı giriş
            // Kullanıcı bilgilerini session'a kaydet
            $_SESSION['admin_id'] = $user->id; // Veritabanındaki kullanıcı id'si
            $_SESSION['admin_kullanici_adi'] = $user->kullanici_adi;
            
            // Ana sayfaya yönlendir
            header('Location: /admin/');
            exit;
        } else {
            // Hatalı giriş
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
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">

<!-- Custom Css -->
<link rel="stylesheet" href="../assets/css/main.css">    
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-black">
<div class="authentication">
    <div class="container">
        <div class="col-md-12 content-center">
            <div class="row">
                <div class="col-lg-6 col-md-12">
                    <div class="company_detail">
                        <h4 class="logo"><img src="assets/images/logo.svg" alt=""> VİZİT-E</h4>
                        <h5>Admin Paneli</h5>
                        <p>Admin paneline giriş yapın</p>                        
                        
                    </div>                    
                </div>
                <div class="col-lg-5 col-md-12 offset-lg-1">
    <style>
        .alert{
            margin: 10px auto !important;
            font-size: 16px;
            font-weight: 600;
            border-radius: 0.35rem;
        }
        .authentication .card-plain {
            max-width: 550px !important;
        }
    </style>
     <?php 
                    // Eğer bir hata mesajı varsa, ekranda göster
                    if (!empty($error_message)) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                    }
                    ?>

                    <div class="card-plain">
                        <div class="header">
                            <h5>Giriş Yap</h5>
                        </div>
                        <form class="form" action="sign-in" method="post">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Kullanıcı Adı" name="kullanici_adi" />
                                <span class="input-group-addon"><i class="zmdi zmdi-account-circle"></i></span>
                            </div>
                            <div class="input-group">
                                <input type="password" placeholder="Şifre" class="form-control" name="password"  />
                                <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                            </div>                            
                            <div class="footer">
                                <button type="submit" class="btn btn-primary btn-block">GİRİŞ YAP</button>
                                <a href="sign-up" class="btn btn-primary btn-simple btn-block">KAYIT OL</a>
                            </div>
                        </form>
                        <a href="forgot-password.html" class="link">Şifremi Unuttum</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Jquery Core Js -->
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js -->
</body>
</html>