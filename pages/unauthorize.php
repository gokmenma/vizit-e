<?php 
//hata mesajını temizle
    unset($_SESSION['hata']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Yetkisiz Erişim</title>
    
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap");

        /* Sayfa açılışında transition efekti */
        body {
            opacity: 0;
            animation: fadeIn 0.3s ease-in-out forwards;
        }

        @keyframes fadeIn {
            from {
            opacity: 0;
            }

            to {
            opacity: 1;
            }
        }
        /* --- TEMEL SAYFA AYARLARI --- */
        body {
            margin: 0;
  font-family: "Source Sans 3", sans-serif;

            background-color: #1F222D;
            color: #E4DEFF;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            overflow: hidden;
            position: relative;
            background-image: radial-gradient(rgba(228, 222, 255, 0.07) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* Ana konteyner, kartı ve 403 yazısını dikey olarak hizalar */
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* --- ANA KART --- */
        .card {
            position: relative;
            width: 100vw; /* Mobil cihazlarda daha iyi görünür */
            max-width: 600px; /* Maksimum genişlik */
            padding: 50px; /* İçerik için boşluk */
            box-sizing: border-box; /* Padding'i genişliğe dahil et */
            color: #E4DEFF;
            border-radius: 24px;
            background-color: #1f222d;
            border: 1px solid rgba(255, 255, 255, 0.3);
            
            /* 1. KART İÇİ DÜZENLEMESİ (EN ÖNEMLİ DÜZELTME) */
            /* line-height kaldırıldı, yerine flexbox kullanıldı */
            display: flex;
            flex-direction: column;   /* Elementleri dikey olarak sırala */
            justify-content: center; /* Dikeyde ortala */
            align-items: center;     /* Yatayda ortala */
        }
        
        /* 2. NEON EFEKTİ İYİLEŞTİRMESİ */
        .card::after {
            position: absolute;
            content: "";
            top: 48%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: -1;
            width: 100%;
            height: 100%;
            filter: blur(20px); /* Daha yumuşak bir parlama */
            background: linear-gradient(to right, #7e0fff, #0fffc1); /* Mor -> Yeşil/Mavi geçişi */
            opacity: 0.4;
        }

        /* --- KART İÇERİĞİ --- */
        .lock-icon svg {
            width: 48px; /* Biraz daha zarif bir boyut */
            height: 48px;
            stroke: rgba(255, 255, 255, 0.5);
            margin-bottom: 20px;
        }

        h1 {
            font-size: 1.8rem;
            margin: 0 0 10px 0;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        p {
            font-size: 1rem;
            line-height: 1.5; /* Sadece paragraflar için line-height */
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
        }
        
        /* 3. "403" YAZISI KARTIN DIŞINA ALINDI */
        .error-code {
            font-size: 9rem;
            font-weight: 700;
            line-height: 1;
            color: rgba(255, 255, 255, 0.15);
            margin-top: 20px; /* Kart ile arasına boşluk koy */
        }

        /* --- BUTONLAR --- */
        .actions a {
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 1rem;
            margin: 0 8px;
            transition: all 0.3s ease;
            color: #fff;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: rgba(138, 43, 226, 0.5);
            border-color: rgba(138, 43, 226, 0.8);
        }
        .btn-primary:hover {
            background-color: rgba(138, 43, 226, 0.7);
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>


    <div class="container">
        <!-- KART BÖLÜMÜ -->
        <div class="card">
            <div class="lock-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="error-code">403</div>
            <!-- Not: 403 yazısı buradan kaldırıldı -->
            <h1>Erişim Engellendi</h1>
            <p>Bu sayfayı görüntülemek için gerekli yetkilere sahip değilsiniz.</p>
            <div class="actions">
                <a href="javascript:history.back()" class="btn-secondary">Geri Dön</a>
                <a href="/" class="btn-primary">Ana Sayfa'ya Git</a>
            </div>
        </div>

        <!-- 403 YAZISI ARTIK KARTIN DIŞINDA -->
    </div>
    
</body>
</html>