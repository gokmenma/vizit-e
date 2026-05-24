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

        <div class="stats-grid animate-in" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
            <?php
            foreach ($paketler as $paket) {
                // Deneme paketini gösterme (Do not show trial package)
                if ($paket->fiyat == 0 || stripos($paket->ad, 'deneme') !== false) {
                    continue;
                }

                $enc_id = Security::encrypt($paket->id);
                $is_current_package = ($paket_id == $paket->id);
                $aktif_paket_border = $is_current_package ? "border: 2px solid var(--primary) !important;" : 'border: 1px solid var(--border) !important;';
            ?>
                <div class="card" style="display: flex; flex-direction: column; position: relative; overflow: hidden; padding: 1.5rem; <?php echo $aktif_paket_border; ?> min-height: 380px; border-radius: 12px; background: var(--card);">
                    <!-- Color Accent Bar on Top -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 5px; background: <?php echo $is_current_package ? 'var(--primary)' : '#e4e4e7'; ?>;"></div>
                    
                    <!-- Current Package Badge -->
                    <?php if ($is_current_package): ?>
                        <div style="position: absolute; top: 12px; right: 12px; background: var(--primary); color: var(--primary-foreground); font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.05em; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Mevcut Paket</div>
                    <?php endif; ?>

                    <!-- Package Title & Description -->
                    <div style="margin-top: 0.5rem; margin-bottom: 1.25rem; text-align: left;">
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem 0; color: var(--foreground);"><?php echo htmlspecialchars($paket->ad); ?></h3>
                        <p style="font-size: 0.825rem; color: var(--muted-foreground); margin: 0; line-height: 1.4;"><?php echo htmlspecialchars($paket->aciklama ?? 'Tüm temel özellikler dahildir.'); ?></p>
                    </div>

                    <!-- Pricing -->
                    <div style="display: flex; align-items: baseline; gap: 0.25rem; margin-bottom: 1.5rem; justify-content: flex-start;">
                        <span style="font-size: 2.25rem; font-weight: 800; color: var(--foreground); line-height: 1;">₺<?php echo number_format($paket->fiyat, 0, ',', '.'); ?></span>
                        <span style="font-size: 0.875rem; color: var(--muted-foreground); font-weight: 500;">/ <?php echo $paket->sure; ?> Gün</span>
                    </div>

                    <!-- Package Limits / Benefits Grid -->
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; padding: 1rem; background: var(--muted); border-radius: 8px; border: 1px solid var(--border); text-align: left;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.85rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.5rem;">
                                <i data-lucide="users" style="width: 14px; height: 14px; color: var(--muted-foreground);"></i> Firma (İşyeri) Limiti
                            </span>
                            <span style="font-weight: 600; font-size: 0.875rem; color: var(--foreground);"><?php echo $paket->firma_hakki; ?> Firma</span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.85rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.5rem;">
                                <i data-lucide="user-plus" style="width: 14px; height: 14px; color: var(--muted-foreground);"></i> Alt Kullanıcı Limiti
                            </span>
                            <span style="font-weight: 600; font-size: 0.875rem; color: var(--foreground);"><?php echo $paket->alt_kullanici_hakki ?? 0; ?> Kullanıcı</span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.85rem; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.5rem;">
                                <i data-lucide="calendar" style="width: 14px; height: 14px; color: var(--muted-foreground);"></i> Geçerlilik Süresi
                            </span>
                            <span style="font-weight: 600; font-size: 0.875rem; color: var(--foreground);"><?php echo $paket->sure; ?> Gün</span>
                        </div>
                    </div>

                    <!-- Extra features list (e.g. from ozellikler column) -->
                    <?php if (!empty($paket->ozellikler)): ?>
                        <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem 0; display: flex; flex-direction: column; gap: 0.5rem; text-align: left;">
                            <?php 
                            $ozellikler = explode(';', $paket->ozellikler);
                            foreach ($ozellikler as $ozellik): 
                                if (empty(trim($ozellik))) continue;
                            ?>
                                <li style="font-size: 0.85rem; color: var(--foreground); display: flex; align-items: center; gap: 0.5rem;">
                                    <i data-lucide="check" style="width: 14px; height: 14px; color: #10b981; flex-shrink: 0;"></i>
                                    <span><?php echo htmlspecialchars(trim($ozellik)); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <!-- Action Button -->
                    <div style="margin-top: auto; width: 100%;">
                        <?php if ($is_current_package): ?>
                            <button class="btn btn-secondary w-full" style="height: 2.75rem; border-radius: 8px; font-weight: 600; cursor: not-allowed; opacity: 0.65; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;" disabled>
                                <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Mevcut Paketiniz
                            </button>
                        <?php else: ?>
                            <button class="btn btn-primary w-full satin-al" style="height: 2.75rem; border-radius: 8px; font-weight: 600; width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;" data-id="<?php echo $enc_id; ?>">
                                <i data-lucide="shopping-cart" style="width: 16px; height: 16px;"></i> Satın Al
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        

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
    if (window.lucide) {
        lucide.createIcons();
    }

    $(document).on('click', '.satin-al', function() {
        var id = $(this).data('id');
        // AJAX isteği ile ödeme sayfasına yönlendiriyoruz
        window.location.href = 'odeme-sayfasi?paket_id=' + id;
    });
</script>

<!-- Body ve Html kapatmayı dahil ediyoruz -->
<?php include 'layouts/foot.php'; ?>