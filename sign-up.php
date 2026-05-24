<?php

require_once "vendor/autoload.php";

use App\Helper\Security;
use Models\KvkkModel;
use Models\UserModel;

$KvkkModel = new KvkkModel();
$UserModel = new UserModel();

$config = require "config.php";
$base_path = rtrim($config['base_path'] ?? '', '/');

$aydinlatma_metni = $KvkkModel->getKvkkMetniByType('aydinlatma_metni') ?? 'Aydınlatma metni bulunamadı.';
$gizlilik_sozlesmesi = $KvkkModel->getKvkkMetniByType('gizlilik_sozlesmesi') ?? 'Gizlilik sözleşmesi bulunamadı.';
$acik_riza_beyani = $KvkkModel->getKvkkMetniByType('acik_riza_beyani') ?? 'Açık rıza beyanı bulunamadı.';

$aydinlatma_metni_metni = $aydinlatma_metni->metin_icerik ?? 'Aydınlatma metni bulunamadı.';
$aydinlatma_metni_id = Security::encrypt($aydinlatma_metni->id) ?? null;

$gizlilik_sozlesmesi_metni = $gizlilik_sozlesmesi->metin_icerik ?? 'Gizlilik sözleşmesi bulunamadı.';
$gizlilik_sozlesmesi_id = Security::encrypt($gizlilik_sozlesmesi->id) ?? null;

$acik_riza_beyani_metni = $acik_riza_beyani->metin_icerik ?? 'Açık rıza beyanı bulunamadı.';
$acik_riza_beyani_id = Security::encrypt($acik_riza_beyani->id) ?? null;


// Davet ID'si varsa al
if (isset($davetid)) {
    // Güvenlik kontrolleri yapılabilir
    $referredBy = $UserModel->getUserByReferralCode($davetid);
    if ($referredBy) {
        $referred_id = Security::encrypt($referredBy->id);
    } else {
        $_SESSION["hata"] = "Geçersiz davet kodu"; // Geçersiz davet kodu
        header("Location: " . $base_path . "/sign-up");
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Şifresiz Sgk vizite uygulaması">
    <title>Kayıt Ol | SGK Vizite</title>
    
    <!-- Basecoat CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    
    <!-- Tailwind CSS (V4) -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <!-- Fonts (Geist) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17519625912"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'AW-17519625912');
    </script>

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
            padding: 2rem 0;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
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
            transition: all 0.2s;
            margin-top: 0.5rem;
        }

        .btn-login:hover:not(:disabled) {
            opacity: 0.9;
        }

        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .error-banner {
            background-color: #fef2f2;
            color: #ef4444;
            font-size: 0.8125rem;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #fee2e2;
            text-align: left;
            font-weight: 500;
        }

        .info-banner {
            background-color: #f0f9ff;
            color: #0284c7;
            font-size: 0.8125rem;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #e0f2fe;
            text-align: center;
            font-weight: 500;
        }

        .d-none {
            display: none !important;
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

        .dark .info-banner {
            background-color: rgba(2, 132, 199, 0.1);
            border-color: rgba(2, 132, 199, 0.2);
            color: #38bdf8;
        }

        /* Shadcn Style Modal Overrides & Premium Dialog */
        .dialog > div {
            background-color: #ffffff !important;
            color: #18181b !important;
            border-radius: 0.5rem !important;
            border: 1px solid #e4e4e7 !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            padding: 1.5rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .dark .dialog > div {
            background-color: #09090b !important;
            color: #f4f4f5 !important;
            border-color: #27272a !important;
        }

        dialog::backdrop {
            background-color: rgba(9, 9, 11, 0.45) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
        }

        .nav-tabs {
            display: flex;
            border-bottom: 1px solid #e4e4e7;
            margin-bottom: 1.5rem;
            gap: 1.25rem;
            padding: 0;
            list-style: none;
        }

        .dark .nav-tabs {
            border-color: #27272a;
        }

        .nav-tabs .nav-link {
            display: inline-block;
            padding: 0.5rem 0.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #71717a;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
        }

        .dark .nav-tabs .nav-link {
            color: #a1a1aa;
        }

        .nav-tabs .nav-link.active {
            color: #18181b;
            border-bottom: 2px solid #18181b;
            font-weight: 700;
        }

        .dark .nav-tabs .nav-link.active {
            color: #f4f4f5;
            border-bottom: 2px solid #f4f4f5;
        }

        .tab-content {
            font-size: 0.875rem;
            line-height: 1.6;
            color: #3f3f46;
        }

        .dark .tab-content {
            color: #d4d4d8;
        }

        .btn-simple {
            background-color: #18181b;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-simple:hover {
            opacity: 0.9;
        }

        .dark .btn-simple {
            background-color: #f4f4f5;
            color: #18181b;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-area">
            <img src="<?php echo $base_path; ?>/assets/images/logo.svg?v=<?= filemtime(__DIR__ . '/assets/images/logo.svg') ?>" alt="Vizit-e" style="width: 40px; height: 40px; border-radius: 10px;">
            <span class="logo-text">SGK Vizite</span>
        </div>

        <div class="login-card">
            <header class="card-header">
                <h2>Kayıt Ol</h2>
            </header>

            <div class="info-banner d-none"></div>

            <div class="error-banner <?php echo isset($_SESSION["hata"]) ? '' : 'd-none'; ?>">
                <?php echo $_SESSION["hata"] ?? ''; unset($_SESSION["hata"]); ?>
            </div>

            <form id="signupForm" autocomplete="off">
                <input type="hidden" name="aydinlatma_metni_id" value="<?php echo ($aydinlatma_metni_id); ?>">
                <input type="hidden" name="gizlilik_sozlesmesi_id" value="<?php echo ($gizlilik_sozlesmesi_id); ?>">
                <input type="hidden" name="acik_riza_beyani_id" value="<?php echo ($acik_riza_beyani_id); ?>">
                <?php if (isset($referred_id)) : ?>
                    <input type="hidden" name="referred_by" value="<?php echo htmlspecialchars($referred_id); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <input type="text" id="kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı adı giriniz" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">E-Posta</label>
                    <input type="email" id="email" name="email" placeholder="isim@sirket.com" required>
                </div>

                <div class="form-group">
                    <label for="sifre">Şifre</label>
                    <input type="password" id="sifre" name="sifre" placeholder="••••••••" required>
                </div>

                <hr style="border: 0; border-top: 1px solid #e4e4e7; margin: 1.5rem 0;" class="dark:border-zinc-800">

                <div style="display: flex; gap: 0.75rem; align-items: flex-start; margin-bottom: 1rem; text-align: left;">
                    <input id="acik_riza_onay" type="checkbox" name="acik_riza_onay" required style="width: 16px; height: 16px; margin-top: 2px; accent-color: #18181b; cursor: pointer;">
                    <label for="acik_riza_onay" style="font-size: 0.8125rem; color: #71717a; line-height: 1.4; font-weight: 400; cursor: pointer;" class="dark:text-zinc-400">
                        SGK web servisleri üzerinden hizmet sunulabilmesi için verilerimin işlenmesine yönelik <strong><a href="javascript:void(0)" id="btn_acik_riza_metni" style="color: #18181b; text-decoration: underline;" class="dark:text-zinc-100">açık rıza</a></strong> veriyorum.
                    </label>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: flex-start; margin-bottom: 1.5rem; text-align: left;">
                    <input id="aydinlatma_onay" type="checkbox" name="aydinlatma_onay" required style="width: 16px; height: 16px; margin-top: 2px; accent-color: #18181b; cursor: pointer;">
                    <label for="aydinlatma_onay" style="font-size: 0.8125rem; color: #71717a; line-height: 1.4; font-weight: 400; cursor: pointer;" class="dark:text-zinc-400">
                        Kişisel verilerimin işlenmesine yönelik <a href="javascript:void(0)" id="btn_aydinaltma_metni" style="color: #18181b; text-decoration: underline;" class="dark:text-zinc-100"><strong>aydınlatma metnini</strong></a> okudum onaylıyorum.
                    </label>
                </div>

                <button type="button" class="btn-login btn-submit" disabled>Kaydol</button>
            </form>
        </div>

        <div class="footer-links">
            Zaten üye misiniz? <a href="<?php echo $base_path; ?>/sign-in">Giriş Yapın</a>
        </div>
    </div>

    <!-- KVKK Modals in Premium Dialog styling -->
    <dialog id="largeModal" class="dialog w-full sm:max-w-[850px]" aria-labelledby="largeModal-title" onclick="if(event.target === this) this.close()">
        <div>
            <header>
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 1rem;">
                    <h2 id="largeModal-title" style="display: flex; align-items: center; gap: 0.5rem; margin: 0; font-size: 1.125rem;">
                        <i data-lucide="shield-check" class="w-5 h-5 text-emerald-500"></i> KVKK ve Gizlilik Metinleri
                    </h2>
                    <button type="button" onclick="document.getElementById('largeModal').close();" aria-label="Kapat" class="border-none bg-transparent cursor-pointer text-zinc-400 hover:text-zinc-500 dark:hover:text-zinc-300" style="font-size: 1.5rem; line-height: 1; padding: 0 0.5rem;">&times;</button>
                </div>
                
                <!-- Nav tabs -->
                <ul class="nav-tabs">
                    <li><a class="nav-link active" data-tab="aydinaltma_metni">AYDINLATMA METNİ</a></li>
                    <li><a class="nav-link" data-tab="gizlilik_sozlesmesi">GİZLİLİK SÖZLEŞMESİ</a></li>
                    <li><a class="nav-link" data-tab="acik_riza_metni">AÇIK RIZA METNİ</a></li>
                </ul>
                
                <!-- Tab panes -->
                <div class="tab-content" style="max-height: 45vh; overflow-y: auto; padding-right: 0.5rem; text-align: left;">
                    <div class="tab-pane active" id="aydinaltma_metni">
                        <?php echo $aydinlatma_metni_metni; ?>
                    </div>
                    <div class="tab-pane d-none" id="gizlilik_sozlesmesi">
                        <?php echo $gizlilik_sozlesmesi_metni; ?>
                    </div>
                    <div class="tab-pane d-none" id="acik_riza_metni">
                        <?php echo $acik_riza_beyani_metni; ?>
                    </div>
                </div>
            </header>
            
            <footer>
                <button type="button" class="btn-primary" onclick="document.getElementById('largeModal').close();">Kapat</button>
            </footer>
        </div>
    </dialog>

    <!-- Jquery Core Js & Bootstrap Modal Triggers -->
    <script src="<?php echo $base_path; ?>/assets/bundles/libscripts.bundle.js"></script>
    <script src="<?php echo $base_path; ?>/assets/bundles/vendorscripts.bundle.js"></script>

    <script>
        $(function() {
            const $form = $('#signupForm');
            const $kullaniciAdiInput = $('#kullanici_adi');
            const $emailInput = $('#email');
            const $sifreInput = $('#sifre');
            const $rizaCheckbox = $('#acik_riza_onay');
            const $aydinlatmaCheckbox = $('#aydinlatma_onay');
            const $submitButton = $('.btn-submit');

            const $fieldsToValidate = $kullaniciAdiInput.add($emailInput).add($sifreInput).add($rizaCheckbox).add($aydinlatmaCheckbox);

            function validateForm() {
                const textInputsValid = $.trim($kullaniciAdiInput.val()) !== '' &&
                    $.trim($emailInput.val()) !== '' &&
                    $.trim($sifreInput.val()) !== '';
                const checkboxesValid = $rizaCheckbox.is(':checked') && $aydinlatmaCheckbox.is(':checked');
                return textInputsValid && checkboxesValid;
            }

            function updateButtonState() {
                const isValid = validateForm();
                $submitButton.prop('disabled', !isValid);
            }

            $fieldsToValidate.on('input change', function() {
                updateButtonState();
            });

            updateButtonState();

            $submitButton.on('click', function() {
                if (!validateForm()) {
                    console.warn('Geçersiz form gönderimi engellendi.');
                    return;
                }

                var formData = new FormData($form[0]);
                formData.append('action', 'register');

                $submitButton.prop("disabled", true).text("Lütfen Bekleyiniz...");

                $.ajax({
                    url: '<?php echo $base_path; ?>/App/Api/APIuser.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        let data = JSON.parse(response);
                        if (data.status == "success") {
                            $('.info-banner').removeClass('d-none').text("Kayıt işlemi başarılı! Giriş sayfasına yönlendiriliyorsunuz...");
                            $('.error-banner').addClass('d-none');
                            setTimeout(() => {
                                window.location.href = "<?php echo $base_path; ?>/sign-in";
                            }, 2500);
                        } else {
                            $('.error-banner').removeClass('d-none').html("Kayıt işlemi başarısız: <br>" + data.message);
                            $submitButton.prop("disabled", false).text("Kaydol");
                        }
                    },
                    error: function() {
                        alert('Bir ağ hatası oluştu. Lütfen tekrar deneyin.');
                        $submitButton.prop("disabled", false).text("Kaydol");
                    }
                });
            });
        });

        // Tab switching function
        function switchTab(tabId) {
            // Deactivate all tab links
            $('.nav-tabs .nav-link').removeClass('active');
            // Hide all tab panes
            $('.tab-content .tab-pane').addClass('d-none').removeClass('active');
            
            // Activate target tab link
            $(`.nav-tabs .nav-link[data-tab="${tabId}"]`).addClass('active');
            // Show target tab pane
            $(`#${tabId}`).removeClass('d-none').addClass('active');
        }

        // Bind click event to nav links inside tabs
        $(document).on('click', '.nav-tabs .nav-link', function(e) {
            e.preventDefault();
            const tabId = $(this).data('tab');
            switchTab(tabId);
        });

        // Trigger opening functions
        $(document).on('click', '#btn_acik_riza_metni', function() {
            const modal = document.getElementById('largeModal');
            if (modal) {
                switchTab('acik_riza_metni');
                modal.showModal();
                if (window.lucide) lucide.createIcons();
            }
        });

        $(document).on('click', '#btn_aydinaltma_metni', function() {
            const modal = document.getElementById('largeModal');
            if (modal) {
                switchTab('aydinaltma_metni');
                modal.showModal();
                if (window.lucide) lucide.createIcons();
            }
        });
    </script>
    

</body>

</html>