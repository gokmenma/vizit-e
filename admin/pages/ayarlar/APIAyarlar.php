<?php
// admin/pages/ayarlar/APIAyarlar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum ve Yetki Kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['user_role'] !== 'superadmin') {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Bu işlem için Süper Admin yetkisi gerekmektedir!']);
    exit();
}

// GET - Sistem Veritabanı Yedeği İndirme
if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], 'sistem-yedek-indir') !== false) {
    try {
        require_once __DIR__ . '/../../../Core/Database.php';
        $db = \Core\Database::getInstance()->getConnection();
        
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $sqlBackup = "-- SGK Vizite Veritabanı Yedeği\n";
        $sqlBackup .= "-- Oluşturan: " . $_SESSION['user_ad'] . " (" . $_SESSION['user_email'] . ")\n";
        $sqlBackup .= "-- Tarih: " . date('d.m.Y H:i:s') . "\n\n";
        $sqlBackup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Tablo Yapısı
            $structResult = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $sqlBackup .= "-- --------------------------------------------------------\n";
            $sqlBackup .= "-- Tablo Yapısı: `$table`\n";
            $sqlBackup .= "-- --------------------------------------------------------\n";
            $sqlBackup .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlBackup .= $structResult['Create Table'] . ";\n\n";
            
            // Tablo Verileri
            $dataResult = $db->query("SELECT * FROM `$table`");
            $rows = $dataResult->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $sqlBackup .= "-- Tablo Verisi: `$table`\n";
                foreach ($rows as $row) {
                    $columns = array_map(function($c) { return "`$c`"; }, array_keys($row));
                    $values = array_map(function($v) use ($db) {
                        if ($v === null) return "NULL";
                        return $db->quote($v);
                    }, array_values($row));
                    $sqlBackup .= "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlBackup .= "\n";
            }
        }
        
        $sqlBackup .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Sistem günlüğü logla
        require_once __DIR__ . '/../../../Core/Services/DatabaseLogger.php';
        $logger = new \Core\Services\DatabaseLogger('admin-backup');
        $logger->info("Veritabanı yedeği indirildi. İndiren: " . $_SESSION['user_ad']);

        // Download Header'ları gönder
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="sgk_vizite_yedek_' . date('Ymd_His') . '.sql"');
        header('Content-Length: ' . strlen($sqlBackup));
        header('Cache-Control: no-cache, must-revalidate');
        echo $sqlBackup;
        exit();
    } catch (\Exception $e) {
        header('HTTP/1.1 500 Server Error');
        echo "Veritabanı yedekleme hatası: " . $e->getMessage();
        exit();
    }
}

// POST - SMTP Bağlantısı ve Gönderim Testi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'smtp-test') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $test_email = $data['test_email'] ?? '';
    if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen geçerli bir test alıcı e-postası giriniz!']);
        exit();
    }
    
    $host = $data['smtp_host'] ?? '';
    $user = $data['smtp_user'] ?? '';
    $pass = $data['smtp_password'] ?? '';
    $port = $data['smtp_port'] ?? '587';
    $enc = $data['smtp_encryption'] ?? 'tls';
    $from_name = $data['smtp_from_name'] ?? 'SGK Vizite';
    
    if (empty($host) || empty($user) || empty($pass)) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen SMTP Sunucu, Kullanıcı ve Şifre alanlarını eksiksiz doldurunuz!']);
        exit();
    }
    
    // PHPMailer'ı yükle
    require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/Exception.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;
        
        if (strtolower($enc) === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else if (strtolower($enc) === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAuth = false;
        }
        
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        $mail->setFrom($user, $from_name);
        $mail->addAddress($test_email);
        
        $mail->isHTML(true);
        $mail->Subject = "SGK Vizite - SMTP Bağlantı Test Mesajı";
        
        $htmlBody = "
        <div style='font-family: sans-serif; max-width: 550px; margin: 0 auto; border: 1px solid #e4e4e7; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
            <div style='background: #18181b; color: #ffffff; padding: 1.5rem; text-align: center;'>
                <h2 style='margin: 0; font-size: 1.25rem; font-weight: bold;'>SMTP Bağlantı Doğrulaması</h2>
            </div>
            <div style='padding: 2rem; background: #ffffff; color: #18181b; line-height: 1.6;'>
                <h3 style='margin-top: 0; color: #10b981;'>✓ Tebrikler! E-Posta Sunucusu Çalışıyor.</h3>
                <p>Bu doğrulama mesajı, <b>SGK Vizite</b> yönetim panelinde yeni SMTP ayarlarını test etmek amacıyla tetiklenmiştir.</p>
                <p>E-posta gönderim altyapınız başarıyla çalışır durumdadır. Artık sistem genelinde ve e-posta kampanyalarında bu ayarlar kullanılacaktır.</p>
                <hr style='border: none; border-top: 1px solid #e4e4e7; margin: 1.5rem 0;'>
                <p style='font-size: 0.75rem; color: #71717a;'>Tarih Damgası: " . date('d.m.Y H:i:s') . "<br>IP Adresi: " . $_SERVER['REMOTE_ADDR'] . "</p>
            </div>
        </div>";
        
        $mail->Body = $htmlBody;
        $mail->AltBody = "Tebrikler! SGK Vizite SMTP bağlantı testi başarıyla tamamlandı. Zaman damgası: " . date('d.m.Y H:i:s');
        
        $mail->send();
        
        // Sisteme log yazalım
        require_once __DIR__ . '/../../../Core/Services/DatabaseLogger.php';
        $logger = new \Core\Services\DatabaseLogger('smtp-test');
        $logger->info("SMTP Test E-postası başarıyla gönderildi: " . $test_email);
        
        echo json_encode(['status' => 'success', 'message' => 'SMTP Bağlantı testi başarılı! ' . $test_email . ' adresine doğrulama e-postası gönderildi.']);
        exit();
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'SMTP Gönderim Hatası: ' . $mail->ErrorInfo]);
        exit();
    }
}

// POST - Ayarları Veritabanına Kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'ayarlar-guncelle') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    try {
        require_once __DIR__ . '/../../../Models/Model.php';
        require_once __DIR__ . '/../../../Models/KullaniciAyarModel.php';
        
        $ayarModel = new \Models\KullaniciAyarModel();
        
        // Kaydedilecek tüm anahtarlar
        $allowedKeys = [
            'site_title', 'admin_email', 'default_language', 'maintenance_mode', 'kvkk_consent',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_encryption', 'smtp_from_name',
            'google_recaptcha_site_key', 'google_recaptcha_secret_key', 'two_factor_auth', 'api_enabled', 'api_secret_key'
        ];
        
        $settingsToUpdate = [];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) {
                $settingsToUpdate[$key] = $data[$key];
            }
        }
        
        if (!empty($settingsToUpdate)) {
            // kullanici_id = 0 ile global sistem ayarlarını güncelliyoruz
            $ayarModel->updateSettings(0, $settingsToUpdate);
        }
        
        // Sisteme log yazalım
        require_once __DIR__ . '/../../../Core/Services/DatabaseLogger.php';
        $logger = new \Core\Services\DatabaseLogger('admin-settings');
        $logger->info("Sistem genel ayarları güncellendi");
        
        echo json_encode(['status' => 'success', 'message' => 'Tüm sistem ayarları başarıyla kaydedildi ve yürürlüğe alındı!']);
        exit();
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Veritabanı güncellenirken hata oluştu: ' . $e->getMessage()]);
        exit();
    }
}
