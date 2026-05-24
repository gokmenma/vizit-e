<?php

use App\Helper\Security;
use Models\UserModel;
use Models\KullaniciAbonelikModel;


Security::checkLogin();



use Models\AbonelikPaketModel;


$AbonelikPaket = new AbonelikPaketModel();
$UserModel = new UserModel();

// Abonelik paketlerini çekiyoruz
$paketler = $AbonelikPaket->all();
$KullaniciAbonelikModel = new KullaniciAbonelikModel();
$title = 'Abonelik Paketleri';


//Kullanıcının abonelik paketini bul
$aktif_abonelik  = $KullaniciAbonelikModel->getSubscriptionByUserId($_SESSION['kullanici_id']);

$paket_id = $aktif_abonelik->paket_id ?? null;

if (isset($_SESSION["hata"])) {
    unset($_SESSION["hata"]);
}



//Benzersiz kod oluştur (WSFR5T6YH gibi)
$referral_code  = $_SESSION["user"]->referral_code ?? null;

?>


<!-- Head kısmını dahil ediyoruz -->
<?php include 'layouts/head.php'; ?>

<!-- Preloader'ı dahil ediyoruz -->
<?php include 'layouts/preloader.php'; ?>

<!--Topbarı dahil ediyoruz -->
<?php include 'layouts/topbar.php'; ?>

<!-- Navbarı dahil ediyoruz -->
<?php include 'layouts/navbar.php'; ?>
<!-- ANA İÇERİK BÖLÜMÜ -->
<section class="content">
    <div class="container">
        <div class="block-header">
            <div class="row clearfix">
                <div class="col-lg-5 col-md-5 col-sm-12">
                    <h2>Abonelik Paketleri</h2>
                </div>

            </div>
        </div>

        <?php if (isset($_SESSION["hata"])) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION["hata"]; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

<style>
    .current-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background-color: #313740;
    color: white;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    z-index: 10;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    pointer-events: none; /* Badge'in tıklanabilir olmasını engeller */
           
        }
</style>



        <div class="row clearfix">
            <?php
           
            $i = 0; // Sıra numarasını başlatıyoruz
            foreach ($paketler as $paket) {
                

                $i++;
                $enc_id = Security::encrypt($paket->id); // Paket ID'sini şifreliyoruz

                $aktif_paket_border = $paket_id == $paket->id ? "border: 1px solid #313740;" : '';

               

                //1. ve 3. paketlerin butonları btn-simple olacak
                $buttonClass = ($i != 4) ? 'btn-simple' : 'btn-primary';

            ?>

                <div class="col-lg-3 cool-md-6 col-sm-12" >
                    <div class="card">
                        <?php if ($paket_id == $paket->id) { ?>
                            <div class="current-badge">Mevcut Paketiniz</div>
                        <?php } ?>
                        <ul class="pricing body active" style="<?php echo $aktif_paket_border; ?>">
                            <li><big><?php echo $paket->ad; ?></big></li>
                            <?php
                            //40 Adet Firma;Tüm Web Servisleri;Anlık Durum Takibi
                            $ozellikler = explode(';', $paket->ozellikler);
                            ?>

                            <?php foreach ($ozellikler as $ozellik) {

                            ?>
                                <li><?php echo $ozellik; ?></li>
                            <?php } ?>
                            <li>
                                <div class="d-flex justify-content-center align-items-center mb-3">

                                    <h3><?php echo $paket->fiyat; ?> ₺ /</h3> <b> <?php echo $paket->sure; ?> Gün</b>
                                </div>

                                <span><?php echo $paket->sure; ?> gün boyunca geçerlidir</span>
                            </li>
                            <li><button class="btn btn-primary btn-round satin-al <?php echo $buttonClass; ?>" data-id="<?php echo $enc_id; ?>">Satın Al</button></li>
                        </ul>
                    </div>
                </div>
            <?php } ?>
        

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
   
 
        .header {
            background: #22252B;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
       
        
        .benefits {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .benefit {
            text-align: center;
            flex: 1;
            padding: 20px;
        }
        
        .benefit i {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .benefit h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #91ADC8;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(to right, transparent, #22252B, transparent);
            margin: 20px 0;
        }
        
        .referral-section {
            text-align: center;
            margin: 30px 0;
        }
        
        .referral-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #22252B;
        }
        
        .referral-box {
            background: #f5f9ff;
            border: 1px dashed #22252B;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .referral-link {
            font-size: 1.1rem;
            color: #22252B;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 15px;
        }
 
        
        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .share-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .share-btn i {
            margin-right: 8px;
        }
        
        .whatsapp {
            background: #25D366;
        }
        
        .telegram {
            background: #0088cc;
        }
        
        .twitter {
            background: #1DA1F2;
        }
      
        
        @media (max-width: 902px) {
            .benefits {
                flex-direction: column;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .share-buttons {
                flex-direction: column;
                margin-bottom: 50px !important;
            }
            .footer {
                margin-top: 30px !important; /* Added margin to footer for better spacing */
            }
        }
    </style>
        <div class="header">
            <h1>Arkadaşını Davet Et</h1>
            <p>Bir ay ücretsiz kullan</p>
        </div>
        
            <div class="benefits">
                <div class="benefit">
                    <i class="zmdi zmdi-account-add"></i>

                    <h3>Davet Gönder</h3>
                    <p>Özel davet linkini arkadaşlarınla paylaş</p>
                </div>
                
                <div class="benefit">
                <i class="zmdi zmdi-card-giftcard"></i>

                    <h3>Hediye Kazan</h3>
                    <p>Arkadaşın kaydolduğunda o da bir ay ücretsiz kullanır</p>
                </div>
                
                <div class="benefit">
                    <i class="zmdi zmdi-star-circle"></i>

                    <h3>Avantajlı Ol</h3>
                    <p>Özel abonelik paketlerinden faydalanın</p>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="referral-section">
                <h2 class="referral-title">Kişisel Davet Linkin</h2>
                
                <div class="referral-box">
                    <div class="referral-link" id="referralLink">https://vizit-e.com/sign-up/<?php echo $referral_code; ?></div>
                    <button  class="btn btn-primary btn-round copy-btn" onclick="copyLink(this)">
                        <i class="fas fa-copy"></i> Kopyala
              
                    </button>
                </div>
                
                <p>Bu linki arkadaşlarına gönder, hem sen kazan hem de arkadaşın kazansın!</p>
                
                <div class="share-buttons">
                    <div class="share-btn whatsapp" onclick="shareOnWhatsApp()">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </div>
                    <div class="share-btn telegram" onclick="shareOnTelegram()">
                        <i class="fab fa-telegram"></i> Telegram
                    </div>
                    <div class="share-btn twitter" onclick="shareOnTwitter()">
                        <i class="fab fa-twitter"></i> Twitter
                    </div>
                </div>
            </div>
          
</div>
        </section>
    
    <script>
        function copyLink(button) {
            const link = document.getElementById('referralLink');
            const tempTextArea = document.createElement('textarea');
            tempTextArea.value = link.textContent;
            document.body.appendChild(tempTextArea);
            tempTextArea.select();
            document.execCommand('copy');
            document.body.removeChild(tempTextArea);
            button.innerHTML = '<i class="fas fa-check"></i> Kopyalandı!';
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
            }, 2000);
            
        }
        
        function shareOnWhatsApp() {
            const link = document.getElementById('referralLink').textContent;
            const text = "Merhaba! Seni özel bir indirim kazanmak için davet ediyorum: ";
            window.open(`https://wa.me/?text=${encodeURIComponent(text + link)}`, '_blank');
        }
        
        function shareOnTelegram() {
            const link = document.getElementById('referralLink').textContent;
            const text = "Merhaba! Seni özel bir indirim kazanmak için davet ediyorum: ";
            window.open(`https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent(text)}`, '_blank');
        }
        
        function shareOnTwitter() {
            const link = document.getElementById('referralLink').textContent;
            const text = "Arkadaşını davet et, %10 indirim kazan! ";
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(link)}`, '_blank');
        }
    </script>

           
   
<!-- Vendor Js -->
<?php include 'layouts/vendor-scripts.php'; ?>



<script>
    $(document).on('click', '.satin-al', function() {
        var id = $(this).data('id');
        // AJAX isteği ile ödeme sayfasına yönlendiriyoruz
        window.location.href = 'odeme-sayfasi?paket_id=' + id;


    });
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>