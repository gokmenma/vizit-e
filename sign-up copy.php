<?php

require_once "vendor/autoload.php";

use App\Helper\Security;
use Models\KvkkModel;
use Models\UserModel;

$KvkkModel = new KvkkModel();
$UserModel = new UserModel();


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
        $referred_id = null; // Geçersiz davet kodu
    }
}

?>

<!doctype html>
<html class="no-js " lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="description" content="Şifresiz Sgk vizite uygulaması">

    <title>:: VİZİT-E :: Kayıt Ol</title>
    <!-- Favicon-->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/plugins/bootstrap/css/bootstrap.min.css">

    <!-- Custom Css -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/color_skins.css">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17519625912"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-17519625912');
    </script>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        body {
            /* --- MEVCUT DİĞER STİLLERİNİZ --- */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin: 0;

            /* --- ARKA PLAN PATTERN AYARLARI --- */

            /* 1. Arka Plan Rengi: Resim yüklenemezse veya yavaş yüklenirse görünecek olan ana renk. */
            /* Sizin tasarımınızdaki renge benzer bir renk seçildi. */
            
            /* 2. Arka Plan Resmi: Kullanmak istediğiniz desen resminin yolu. */
            /* Yukarıdaki klasör yapısına göre yol './../images/pattern.png' şeklindedir. */
            /* Eğer CSS ve images klasörleri aynı dizindeyse yolu './images/pattern.png' yapabilirsiniz. */
            background-image: url('/assets/images/pattern.webp') !important;
            /* BURAYI KENDİ RESİM ADINIZLA GÜNCELLEYİN */
            background-color: #2c3440 !important;   

            /* 3. Tekrarlama Modu: Resmin hem yatay hem de dikey olarak tekrarlanarak tüm alanı kaplamasını sağlar. */
            background-repeat: repeat !important;
        }
        .theme-black .authentication {
            background: #313740;        
         }

        .logo {
            font-weight: 600;
            font-size: 24px;
            color: #ffffff;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 5px;
        }

        /* Swiper container'ını alanınıza göre konumlandırın ve boyutlandırın */
        .swiper {
            position: absolute;
            width: 100%;
            max-width: 450px;
            /* Genişliği kendi tasarımınıza göre ayarlayın */
            height: 150px;
            /* Yüksekliği kendi tasarımınıza göre ayarlayın */
            text-align: center;
            margin-top: 20px;
            /* Logonun altına boşluk bırakmak için */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Her bir slaytın içindeki metinleri ortalamak ve renklendirmek için */
        .swiper-slide {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #ffffff;
            /* Metin rengi */
        }

        .swiper-slide h2 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .swiper-slide p {
            font-size: 16px;
            line-height: 1.5;
            padding: 0 20px;
            /* Uzun metinlerin kenarlara yapışmasını engeller */
        }

        .swiper-pagination-bullet {
            width: 10px;
            /* Nokta boyutu */
            height: 10px;
            /* Nokta boyutu */
        }
    </style>
</head>

<body class="theme-black">
    <div class="authentication">
        <div class="container">
            <div class="col-md-12 content-center">
                <div class="row">
                    <div class="col-lg-6 col-md-12">
                        <!-- Slider main container -->
                        <img src="/assets/images/logo.svg" alt="" width="40px">
                        <h4 class="logo"> VİZİT-E</h4>
                        <div class="swiper">
                            <!-- Additional required wrapper -->
                            <div class="swiper-wrapper">
                                <!-- Slides -->

                                <div class="swiper-slide">
                                    <h2>Kolay Raporlama</h2>
                                    <p>SGK vizit-e rapor işlemlerinizi saniyeler içinde oluşturun.</p>
                                </div>
                                <div class="swiper-slide">
                                    <h2>Hızlı ve Güvenli</h2>
                                    <p>Verileriniz güvende tutulur ve işlemleriniz hızla gerçekleştirilir.</p>
                                </div>
                                <div class="swiper-slide">
                                    <h2>7/24 Erişim</h2>
                                    <p>İstediğiniz zaman, istediğiniz yerden sistemimize erişim sağlayın.</p>
                                </div>
                            </div>
                        </div>
                        <!-- Pagination elementini buraya ekleyin -->
                        <div class="swiper-pagination"></div>

                    </div>
                    <style>
                        .alert {
                            margin: 10px auto !important;
                            font-size: 16px;
                            font-weight: 600;
                            border-radius: 0.35rem;
                        }

                        .authentication .card-plain {
                            max-width: 550px !important;
                        }
                    </style>
                    <div class="col-lg-5 col-md-12 offset-lg-1">

                        <div class="alert alert-info d-none text-left"></div>
                        <div class="alert alert-danger d-none text-left"></div>
                        <div class="card-plain">


                            <div class="header">
                                <h6>Kayıt Ol</h6>
                                <span>Yeni bir üye olmak için lütfen aşağıdaki bilgileri doldurun</span>
                            </div>
                            <form class="form" id="signupForm">
                                <input type="hidden" name="aydinlatma_metni_id"
                                    value="<?php echo ($aydinlatma_metni_id); ?>">
                                <input type="hidden" name="gizlilik_sozlesmesi_id"
                                    value="<?php echo ($gizlilik_sozlesmesi_id); ?>">
                                <input type="hidden" name="acik_riza_beyani_id"
                                    value="<?php echo ($acik_riza_beyani_id); ?>">
                                <?php if (isset($referred_id)) : ?>
                                    <input type="hidden" name="referred_by" value="<?php echo htmlspecialchars($referred_id); ?>">
                                <?php endif; ?>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="kullanici_adi"
                                        placeholder="Kullanıcı Adı">
                                    <span class="input-group-addon"><i class="zmdi zmdi-account-circle"></i></span>
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="email" placeholder="Email">
                                    <span class="input-group-addon"><i class="zmdi zmdi-email"></i></span>
                                </div>
                                <div class="input-group">
                                    <input type="password" placeholder="Şifre" name="sifre" class="form-control" />
                                    <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                                </div>

                                <hr>
                                <div class="checkbox text-left">
                                    <input id="acik_riza_onay" type="checkbox" name="acik_riza_onay" required>
                                    <label for="acik_riza_onay"> SGK web servisleri üzerinden hizmet sunulabilmesi için verilerimin işlenmesine yönelik
                                        <strong><a href="#" id="btn_acik_riza_metni">açık rıza</a></strong> veriyorum.
                                </div>
                                <div class="checkbox text-left">

                                    <input id="aydinlatma_onay" type="checkbox" name="aydinlatma_onay" required>
                                    <label for="aydinlatma_onay">Kişisel verilerimin işlenmesine yönelik
                                        <a href="#" id="btn_aydinaltma_metni"><strong>aydınlatma
                                                metnini</strong></a> okudum onaylıyorum</label>
                                </div>
                                <div class="footer">
                                    <button type="button" class="btn btn-primary btn-block btn-submit" disabled>KAYDOL</button>
                                </div>
                            </form>
                            <a class="link" href="/sign-in">Zaten bir üye misiniz?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Large Size -->
    <div class="modal fade" id="largeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs">
                            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#aydinaltma_metni">AYDINLATMA
                                    METNİ</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#gizlilik_sozlesmesi">GİZLİLİK

                                    SÖZLEŞMESİ</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#acik_riza_metni">AÇIK RIZA
                                    METNİ</a></li>
                        </ul>
                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane in active" id="aydinaltma_metni">
                                <div role="tabpanel" class="tab-pane" id="home">
                                    <?php echo $aydinlatma_metni_metni; ?>

                                </div>


                            </div>
                            <div role="tabpanel" class="tab-pane" id="gizlilik_sozlesmesi">
                                <div role="tabpanel" class="tab-pane" id="profile">
                                    <?php echo $gizlilik_sozlesmesi_metni; ?>


                                </div>

                            </div>
                            <div role="tabpanel" class="tab-pane" id="acik_riza_metni">
                                <?php echo $acik_riza_beyani_metni; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-simple btn-round waves-effect"
                        data-dismiss="modal">KAPAT</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Jquery Core Js -->
    <script src="/assets/bundles/libscripts.bundle.js"></script>
    <script src="/assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js -->

    <script>
        // Sayfa tamamen yüklendiğinde bu kod çalışır.
        $(function() {

            // 1. Gerekli DOM elemanlarını jQuery ile seçelim
            const $form = $('#signupForm');
            const $kullaniciAdiInput = $('input[name="kullanici_adi"]');
            const $emailInput = $('input[name="email"]');
            const $sifreInput = $('input[name="sifre"]');
            const $rizaCheckbox = $('#acik_riza_onay');
            const $aydinlatmaCheckbox = $('#aydinlatma_onay');
            const $submitButton = $('.btn-submit');

            // Kontrol edilecek tüm alanları bir jQuery nesnesinde toplayalım
            const $fieldsToValidate = $kullaniciAdiInput.add($emailInput).add($sifreInput).add($rizaCheckbox).add($aydinlatmaCheckbox);

            /**
             * Tüm form alanlarının geçerli olup olmadığını kontrol eden fonksiyon.
             * @returns {boolean} - Form geçerliyse true, değilse false döner.
             */
            function validateForm() {
                // Text inputların dolu olup olmadığını kontrol et (boşlukları sayma)
                const textInputsValid = $.trim($kullaniciAdiInput.val()) !== '' &&
                    $.trim($emailInput.val()) !== '' &&
                    $.trim($sifreInput.val()) !== '';

                // Checkbox'ların işaretli olup olmadığını kontrol et
                const checkboxesValid = $rizaCheckbox.is(':checked') && $aydinlatmaCheckbox.is(':checked');

                // Tüm koşullar sağlanıyorsa true dön
                return textInputsValid && checkboxesValid;
            }

            /**
             * Formun geçerliliğine göre butonu aktif veya pasif yapan fonksiyon.
             */
            function updateButtonState() {
                // validateForm true dönerse disabled özelliği false (aktif),
                // false dönerse disabled özelliği true (pasif) olur.
                $submitButton.prop('disabled', !validateForm());
            }

            // 2. ANLIK KONTROL: Her bir form alanı değiştiğinde buton durumunu güncelle
            // 'input' olayı metin alanları için, 'change' olayı checkbox'lar içindir.
            $fieldsToValidate.on('input change', function() {
                updateButtonState();
            });

            // Sayfa ilk yüklendiğinde de butonun durumunu ayarla
            updateButtonState();


            // 3. GÜVENLİK KONTROLÜ VE AJAX GÖNDERİMİ
            // Butona tıklandığında bu fonksiyon çalışır.
            $submitButton.on('click', function() {

                // --- GÜVENLİK ADIMI ---
                // AJAX isteğini göndermeden ÖNCE formun geçerliliğini SON BİR KEZ kontrol et.
                // Eğer kullanıcı DOM'dan 'disabled' özelliğini kaldırdıysa, bu kontrol onu yakalar.
                if (!validateForm()) {
                    console.warn('Geçersiz form gönderimi engellendi. Lütfen tüm alanları doldurun.');
                    return; // Fonksiyonu burada sonlandır, AJAX isteği asla çalışmaz.
                }

                // --- AJAX GÖNDERİMİ (Sadece form geçerliyse bu kısım çalışır) ---
                var formData = new FormData($form[0]);
                formData.append('action', 'register');

                // Butonu gönderim süresince pasif yap ve metnini değiştir
                $submitButton.prop("disabled", true).text("Lütfen Bekleyiniz...");

                $.ajax({
                    url: '/App/Api/APIuser.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        let data = JSON.parse(response);
                        console.log(data);
                        if (data.status == "success") {
                            $('.alert.alert-info').removeClass('d-none').text(
                                "Kayıt işlemi başarılı! Giriş yapabilirsiniz.");

                            // Butonun tekrar tıklanmasını engellemek için pasif bırakabiliriz
                            // ve yönlendirme sonrası sayfa zaten yenilenecek.

                            setTimeout(() => {
                                window.location.href = "/sign-in";
                            }, 3000);

                        } else {
                            $('.alert.alert-danger').removeClass('d-none').html("Kayıt işlemi başarısız: <br>" + data.message);
                            setTimeout(() => {
                                $('.alert.alert-danger').addClass('d-none').text("");
                            }, 15000);

                            // Başarısızlık durumunda butonu tekrar aktif et ki kullanıcı düzeltebilsin
                            $submitButton.prop("disabled", false).text("KAYDOL");
                        }
                    },
                    error: function() {
                        // Ağ hatası veya sunucu hatası durumunda da butonu tekrar aktif et
                        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                        $submitButton.prop("disabled", false).text("KAYDOL");
                    }
                });
            });

        });
        $(document).on('click', '#btn_acik_riza_metni', function() {
            $("#largeModal").modal('show');

            // Doğru seçim: nav-link'e .tab('show')
            $('a[href="#acik_riza_metni"]').tab('show');
        });

        $(document).on('click', '#btn_aydinaltma_metni', function() {
            $("#largeModal").modal('show');

            // Doğru seçim: nav-link'e .tab('show')
            $('a[href="#aydinaltma_metni"]').tab('show');
        });
    </script>

    <script>
        const swiper = new Swiper('.swiper', {
            // İsteğe bağlı parametreler
            direction: 'horizontal', // Yatay geçiş
            loop: true, // Döngü (sondan başa dönme)
            speed: 1000, // Geçiş hızı (1 saniye)

            // Otomatik oynatma ayarları
            autoplay: {
                delay: 3000, // Slaytlar arası bekleme süresi (3 saniye)
                disableOnInteraction: false, // Kullanıcı etkileşiminden sonra durmasını engelle
            },

            // Daha şık bir görünüm için 'fade' efekti
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            // YENİ EKLENEN KISIM
            // Pagination (Noktalar)
            pagination: {
                el: '.swiper-pagination', // Pagination elemanının class'ı
                clickable: true, // Noktalara tıklanarak geçiş yapılabilmesini sağlar
            },
        });
    </script>

</body>

</html>