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
<!doctype html>
<html class="no-js" lang="tr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="description" content="Sgk Rapor işlemlerinizi sms şifresi olmadan yapın">

    <title>Vizit-e - Yeni Şifre Belirle</title>
    <!-- Favicon-->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/plugins/bootstrap/css/bootstrap.min.css">

    <!-- Custom Css -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/color_skins.css">
    <link rel="stylesheet" href="/assets/css/login.css?v=<?php echo filemtime(__DIR__ . '/assets/css/login.css') ?>">
    <style>
        .alert { margin-bottom: 20px !important; font-size: 15px; font-weight: 500; border-radius: 0.35rem; }
    </style>
</head>

<body class="theme-black">
    <div class="authentication">
        <div class="container">
            <div class="login-center">
                <div class="login-hero">
                    <img src="/assets/images/logo.svg" alt="Vizit-e" class="login-hero__logo">
                </div>
                <h3 class="center-title">Yeni Şifre Belirle</h3>
                <?php if ($showForm): ?>
                <p class="center-subtitle">Hesabınız için güvenli bir şifre oluşturun</p>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if ($showForm): ?>
                <div class="login-card">
                    <form class="login-form" method="POST" autocomplete="off">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="field">
                            <label class="form-label">Yeni Şifre</label>
                            <div class="input-group field-control">
                                <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                                <input type="password" class="form-control" name="sifre" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="form-label">Yeni Şifre (Tekrar)</label>
                            <div class="input-group field-control">
                                <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                                <input type="password" class="form-control" name="sifre_tekrar" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login btn-block">Şifreyi Güncelle</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="center-footer">
                    <?php if (!empty($success_message)): ?>
                        <a href="sign-in">Giriş Yap'a Dön</a>
                    <?php else: ?>
                        <a href="forgot-password">Yeni Sıfırlama Talebi Oluştur</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Jquery Core Js -->
    <script src="/assets/bundles/libscripts.bundle.js"></script>
    <script src="/assets/bundles/vendorscripts.bundle.js"></script>
</body>

</html>