<?php
// pages/bakim.php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Bakımda | SGK Vizite</title>
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --background: #09090b;
            --foreground: #f4f4f5;
            --card: #18181b;
            --muted: #27272a;
            --muted-foreground: #a1a1aa;
            --primary: #f4f4f5;
            --primary-foreground: #09090b;
            --border: #27272a;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Geist', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Gradient Glow Background */
        .glow {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            pointer-events: none;
        }

        .container {
            z-index: 2;
            text-align: center;
            padding: 2rem;
            max-width: 480px;
            width: 100%;
        }

        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background-color: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .icon-box i {
            width: 36px;
            height: 36px;
            color: var(--foreground);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0 0 1rem;
        }

        p {
            font-size: 0.9375rem;
            color: var(--muted-foreground);
            line-height: 1.6;
            margin: 0 0 2rem;
        }

        .card {
            background-color: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: left;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .card-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.8125rem;
            color: var(--muted-foreground);
            line-height: 1.5;
        }

        .card-item b {
            color: var(--foreground);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: var(--primary);
            color: var(--primary-foreground);
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.05);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Pulse and Spin Animations */
        .animate-spin-slow {
            animation: spin 6s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            background-color: #f59e0b;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.6; }
        }
    </style>
</head>
<body>
    <div class="glow"></div>

    <div class="container">
        <!-- Icon Wrapper with Spinning Wrench -->
        <div class="icon-box">
            <i data-lucide="wrench" class="animate-spin-slow"></i>
        </div>

        <br>
        <span class="badge">
            <span class="pulse-dot"></span>
            Planlı Altyapı Çalışması
        </span>

        <h1>Sistemimiz Bakımdadır</h1>
        <p>
            Sizlere daha hızlı, güvenli ve pürüzsüz bir SGK Rapor Takip deneyimi sunabilmek amacıyla altyapımızı optimize ediyoruz. Çalışmamız tamamlandığında otomatik olarak yönlendirileceksiniz.
        </p>

        <!-- Bilgilendirme Kartı -->
        <div class="card">
            <div class="card-item">
                <i data-lucide="info" style="width: 18px; height: 18px; color: #f59e0b; flex-shrink: 0; margin-top: 2px;"></i>
                <div>
                    <b>Tahmini Süre:</b> Altyapı ve veritabanı optimizasyon çalışmaları ortalama 15-30 dakika sürmektedir. Anlayışınız için teşekkür ederiz.
                </div>
            </div>
        </div>

        <!-- İletişim / Durum Butonu -->
        <a href="mailto:bilgi@vizit-e.com" class="btn">
            <i data-lucide="mail" style="width: 16px; height: 16px;"></i>
            Destek E-Postası Gönder
        </a>
    </div>

    <script>
        // Lucide İkonlarını Başlat
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Sayfayı her 15 saniyede bir otomatik yenile (bakım modu bitmiş mi diye kontrol etmek için)
        setTimeout(function() {
            location.reload();
        }, 15000);
    </script>
</body>
</html>
