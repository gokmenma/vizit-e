<?php
// pages/bakim.php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Güncellemesi | SGK Vizite</title>
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/geist-sans/index.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --background: #030303;
            --foreground: #ededed;
            --card: rgba(10, 10, 10, 0.7);
            --border: rgba(255, 255, 255, 0.08);
            --primary: #3b82f6;
            --secondary: #8b5cf6;
            --muted-foreground: #888888;
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

        /* Ambient Animated Glows (Aurora Effect) */
        .glow-1 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
            top: -10%;
            left: -10%;
            z-index: 1;
            filter: blur(40px);
            animation: float1 20s infinite alternate;
            pointer-events: none;
        }

        .glow-2 {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.06) 0%, rgba(0, 0, 0, 0) 70%);
            bottom: -20%;
            right: -10%;
            z-index: 1;
            filter: blur(40px);
            animation: float2 25s infinite alternate;
            pointer-events: none;
        }

        @keyframes float1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(80px, 60px) scale(1.1); }
        }

        @keyframes float2 {
            0% { transform: translate(0, 0) scale(1.1); }
            100% { transform: translate(-60px, -80px) scale(0.9); }
        }

        /* Subtle Technical Grid Pattern */
        .grid-pattern {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.012) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.012) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 2;
            pointer-events: none;
        }

        .container {
            z-index: 3;
            max-width: 550px;
            width: 90%;
            animation: fadeIn 0.8s ease-out;
        }

        /* Glassmorphism Premium Card */
        .glass-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.03);
            text-align: center;
        }

        /* Double Rotating Ring Loader */
        .loader-wrapper {
            position: relative;
            width: 96px;
            height: 96px;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ring {
            position: absolute;
            border-radius: 50%;
            border: 2px solid transparent;
        }

        .ring-1 {
            width: 96px;
            height: 96px;
            border-top-color: var(--primary);
            border-bottom-color: rgba(59, 130, 246, 0.05);
            animation: spin1 4s linear infinite;
        }

        .ring-2 {
            width: 76px;
            height: 76px;
            border-left-color: var(--secondary);
            border-right-color: rgba(139, 92, 246, 0.05);
            animation: spin2 3s linear infinite reverse;
        }

        .inner-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            animation: pulse-icon 2s ease-in-out infinite alternate;
        }

        .inner-icon i {
            width: 18px;
            height: 18px;
            color: #ffffff;
        }

        @keyframes spin1 {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes spin2 {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse-icon {
            0% { transform: scale(0.95); box-shadow: 0 0 10px rgba(59, 130, 246, 0.1); }
            100% { transform: scale(1.05); box-shadow: 0 0 25px rgba(59, 130, 246, 0.2); }
        }

        /* Pill Status Badge */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.06);
            border: 1px solid rgba(16, 185, 129, 0.15);
            padding: 0.375rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #10b981;
            margin-bottom: 1.5rem;
            letter-spacing: 0.02em;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 12px #10b981;
            animation: dot-pulse 1.8s infinite;
        }

        @keyframes dot-pulse {
            0% { transform: scale(0.9); opacity: 0.6; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            50% { transform: scale(1.2); opacity: 1; box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.9); opacity: 0.6; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Title Gradient */
        h1 {
            font-size: 2.15rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin: 0 0 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #a1a1aa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.subtitle {
            font-size: 0.9rem;
            color: var(--muted-foreground);
            line-height: 1.6;
            margin: 0 auto 2.5rem;
            max-width: 440px;
        }

        /* Real-time Status Board */
        .status-timeline {
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.015);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 2.5rem;
            text-align: left;
        }

        .timeline-header {
            font-size: 0.8rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .timeline-header span {
            color: var(--muted-foreground);
            font-size: 0.75rem;
            font-weight: 400;
        }

        .timeline-steps {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }

        .step-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--muted-foreground);
            padding: 0.375rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        }

        .step-item:last-child {
            border: none;
            padding-bottom: 0;
        }

        .step-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step-label i {
            width: 14px;
            height: 14px;
        }

        .step-label.active {
            color: #ffffff;
            font-weight: 500;
        }

        .step-status {
            font-size: 0.6875rem;
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .status-running {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            animation: text-pulse 1.5s infinite alternate;
        }

        .status-pending {
            background: rgba(255, 255, 255, 0.04);
            color: var(--muted-foreground);
        }

        @keyframes text-pulse {
            0% { opacity: 0.75; }
            100% { opacity: 1; }
        }

        /* Action Buttons Grid */
        .actions-row {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 10px;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            height: 38px;
            padding: 0 1.25rem;
        }

        .btn-primary {
            background-color: #ffffff;
            color: #000000;
            border: none;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.08);
        }

        .btn-primary:hover {
            background-color: #e4e4e7;
            transform: translateY(-1px);
        }

        .btn-outline {
            background-color: transparent;
            color: #ffffff;
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .glass-card {
                padding: 2rem 1.5rem;
            }
            .actions-row {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="grid-pattern"></div>
    <div class="glow-1"></div>
    <div class="glow-2"></div>

    <div class="container">
        <div class="glass-card">
            <!-- Double Ring Geometric Loader -->
            <div class="loader-wrapper">
                <div class="ring ring-1"></div>
                <div class="ring ring-2"></div>
                <div class="inner-icon">
                    <i data-lucide="wrench"></i>
                </div>
            </div>

            <!-- Active Status Pill -->
            <span class="status-pill">
                <span class="pulse-dot"></span>
                Planlı Altyapı İyileştirmesi
            </span>

            <h1>Altyapı Optimizasyonu</h1>
            <p class="subtitle">
                Sizlere daha hızlı, güvenli ve kesintisiz bir SGK Rapor Takip hizmeti sunabilmek için sunucu ve veritabanı altyapımızı optimize ediyoruz.
            </p>

            <!-- Real-time Status Board -->
            <div class="status-timeline">
                <div class="timeline-header">
                    <span>İŞLEM ADIMLARI</span>
                    <span>Tahmini Kalan: 15-20 Dk</span>
                </div>
                <div class="timeline-steps">
                    <div class="step-item">
                        <div class="step-label">
                            <i data-lucide="check-circle-2" style="color: #10b981;"></i>
                            <span>Veritabanı Tablo Optimizasyonu</span>
                        </div>
                        <span class="step-status status-completed">Tamamlandı</span>
                    </div>
                    <div class="step-item">
                        <div class="step-label active">
                            <i data-lucide="refresh-cw" class="animate-spin-slow" style="color: var(--primary); animation-duration: 4s;"></i>
                            <span>API Entegrasyon & Önbellek Temizliği</span>
                        </div>
                        <span class="step-status status-running">Devam Ediyor</span>
                    </div>
                    <div class="step-item">
                        <div class="step-label">
                            <i data-lucide="clock" style="color: var(--muted-foreground);"></i>
                            <span>CDN Güncellemesi & Yayına Alma</span>
                        </div>
                        <span class="step-status status-pending">Bekliyor</span>
                    </div>
                </div>
            </div>

            <!-- Actions Row -->
            <div class="actions-row">
                <button onclick="location.reload();" class="btn btn-primary">
                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                    Sayfayı Yenile
                </button>
                <a href="mailto:bilgi@vizit-e.com" class="btn btn-outline">
                    <i data-lucide="mail" style="width: 14px; height: 14px;"></i>
                    Destek Talebi
                </a>
            </div>
        </div>
    </div>

    <script>
        // Init Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Auto reload after 20 seconds
        setTimeout(function() {
            location.reload();
        }, 20000);
    </script>
</body>
</html>
