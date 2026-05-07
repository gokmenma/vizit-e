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
<html class="no-js " lang="tr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title>Vizit-e - Yeni Şifre Belirle</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/color_skins.css">
    <style>
        .alert { margin-bottom: 20px !important; font-size: 15px; font-weight: 500; border-radius: 0.35rem; }
        .authentication .card-plain { max-width: 550px !important; }
    </style>
</head>
<body class="theme-black">
    <div class="authentication">
        <div class="container">
            <div class="col-md-12 content-center">
                <div class="row">
                    <div class="col-lg-6 col-md-12">
                        <div class="company_detail">
                            <h4 class="logo"><img src="assets/images/logo.svg" alt=""> <strong>VİZİT-E</strong></h4>
                            <h5>YENİ ŞİFRE BELİRLEME</h5>
                            <p>Lütfen hesabınız için yeni bir şifre belirleyin.</p>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12 offset-lg-1">
                        
                        <?php
                            if (!empty($error_message)) {
                                echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                            }
                            if (!empty($success_message)) {
                                echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                            }
                            ?>

                            <div class="card-plain">
                            <div class="header">
                                <h5>Yeni Şifre Oluştur</h5>
                            </div>

                          

                            <?php if ($showForm): // Sadece token geçerliyse formu göster ?>
                                <form class="form" method="POST" autocomplete="off">
                                    <!-- Token'ı formla birlikte tekrar göndermek için gizli input -->
                                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                    
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="sifre" placeholder="Yeni Şifre" required>
                                        <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                                    </div>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="sifre_tekrar" placeholder="Yeni Şifre (Tekrar)" required>
                                        <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                                    </div>
                                    <div class="footer">
                                        <button type="submit" class="btn btn-primary btn-block">ŞİFREYİ GÜNCELLE</button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <div class="text-center" style="margin-top: 20px;">
                                <!-- Başarılı mesajı varsa Giriş Yap linkini göster, yoksa talep sayfasına yönlendir -->
                                <?php if (!empty($success_message)): ?>
                                    <a href="sign-in">Giriş Yap</a>
                                <?php else: ?>
                                    <a href="forgot-password">Yeni Sıfırlama Talebi Oluştur</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/bundles/libscripts.bundle.js"></script>
    <script src="assets/bundles/vendorscripts.bundle.js"></script>
</body>
</html>