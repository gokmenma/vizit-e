<?php
$config = require __DIR__ . '/../config.php';
$basePath = rtrim($config['base_path'] ?? '', '/');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Sayfa Bulunamadı | SGK Vizite</title>
    <link rel="icon" href="<?php echo $basePath; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Basecoat CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    
    <!-- Fonts (Geist) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@latest/dist/fonts/geist/style.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --background: 0 0% 98%;
            --foreground: 240 10% 3.9%;
            --card: 0 0% 100%;
            --card-foreground: 240 10% 3.9%;
            --primary: 240 5.9% 10%;
            --primary-foreground: 0 0% 98%;
            --muted: 240 4.8% 95.9%;
            --muted-foreground: 240 3.8% 46.1%;
            --border: 240 5.9% 90%;
            --input: 240 5.9% 90%;
            --ring: 240 5.9% 10%;
            --radius: 0.75rem;
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
        }

        .error-container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            text-align: center;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 1rem;
            background: linear-gradient(to bottom, #18181b, #71717a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.05em;
        }

        .logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 3rem;
        }

        .logo-box {
            width: 44px;
            height: 44px;
            background: #18181b;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #18181b;
        }

        .error-card {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 16px;
            padding: 3rem 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 1rem 0;
            color: #18181b;
            letter-spacing: -0.01em;
        }

        p {
            color: #71717a;
            font-size: 1.0625rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .btn-primary {
            background: #18181b;
            color: #fff;
            border: none;
        }

        .btn-primary:hover {
            background: #27272a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .btn-outline {
            background: #fff;
            color: #18181b;
            border: 1px solid #e4e4e7;
        }

        .btn-outline:hover {
            background: #f4f4f5;
            border-color: #d4d4d8;
        }

        .illustration {
            margin-bottom: 2rem;
            color: #18181b;
            opacity: 0.9;
        }

        @media (min-width: 640px) {
            .actions {
                flex-direction: row;
            }
            .btn {
                flex: 1;
            }
        }
        
        /* Animasyon */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .floating {
            animation: float 4s ease-in-out infinite;
        }
    </style>
</head>
<body>

    <div class="error-container">
        <div class="logo-area">
            <div class="logo-box">
                <i data-lucide="sparkles" style="width: 24px; height: 24px;"></i>
            </div>
            <span class="logo-text">SGK Vizite</span>
        </div>

        <div class="error-card">
            <div class="illustration floating">
                <i data-lucide="ghost" style="width: 80px; height: 80px; stroke-width: 1.5;"></i>
            </div>
            
            <div class="error-code">404</div>
            
            <h1>Aradığınız sayfa bulunamadı</h1>
            <p>Maalesef ulaşmaya çalıştığınız sayfa taşınmış, silinmiş veya hiç var olmamış olabilir.</p>

            <div class="actions">
                <a href="<?php echo $basePath ?: '/'; ?>" class="btn btn-primary">
                    <i data-lucide="home" style="width: 18px; height: 18px;"></i>
                    Ana Sayfaya Dön
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    <i data-lucide="arrow-left" style="width: 18px; height: 18px;"></i>
                    Geri Git
                </a>
            </div>
        </div>

        <div style="margin-top: 3rem; font-size: 0.875rem; color: #a1a1aa;">
            &copy; <?php echo date('Y'); ?> SGK Vizite. Tüm hakları saklıdır.
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
