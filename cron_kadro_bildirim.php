<?php
/**
 * Kadroya Geçiş Süreci Bildirim Cron Scripti
 * 
 * Her gün sabah 08:00'da o gün için kadroya geçiş sürecinde olan 
 * (yani göreve başlama tarihinden itibaren tam 3 yıl geçmiş) 
 * personelleri ilgili tenant kullanıcılarına mail olarak gönderir.
 * 
 * Kullanım:
 * CLI: php -f cron_kadro_bildirim.php
 * Test Modu: php -f cron_kadro_bildirim.php --test-date=2023-05-15
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php';




global $db;

$targetDate = "DATE_SUB(CURDATE(), INTERVAL 3 YEAR)";
$isTest = false;

if (php_sapi_name() === 'cli' && isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--test-date=') === 0) {
            $testDateVal = substr($arg, strlen('--test-date='));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $testDateVal)) {
                $targetDate = "'" . $testDateVal . "'";
                $isTest = true;
                echo "Test Modu Aktif: Hedef tarih $testDateVal olarak ayarlandı.\n";
            }
        }
    }
}

// Aktif tenantları alalım
$tenantsStmt = $db->query("SELECT id, name FROM tenants WHERE is_active = 1");
$tenants = $tenantsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($tenants)) {
    echo "Aktif tenant bulunamadı.\n";
    exit;
}

foreach ($tenants as $tenant) {
    $tenant_id = $tenant['id'];
    
    // Bu tenant için e-posta bildirimleri açık mı kontrol et
    $settingsStmt = $db->prepare("SELECT kadro_bildirim_aktif FROM tenant_settings WHERE tenant_id = ?");
    $settingsStmt->execute([$tenant_id]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Eğer kayıt varsa ve kapalıysa bu tenant'ı atla
    if ($settings && isset($settings['kadro_bildirim_aktif']) && (int)$settings['kadro_bildirim_aktif'] === 0) {
        if ($isTest) {
            echo "Tenant '{$tenant['name']}' için kadro bildirimleri kapalı. Atlanıyor.\n";
        }
        continue;
    }
    
    // Tam 3 yılını doldurmuş personelleri filtreleyelim
    $personnelsStmt = $db->prepare("
        SELECT * FROM personeller 
        WHERE tenant_id = ? 
          AND deleted_at IS NULL 
          AND durum = 'aktif' 
          AND goreve_baslama_tarihi = $targetDate
    ");
    $personnelsStmt->execute([$tenant_id]);
    $personnels = $personnelsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($personnels)) {
        if ($isTest) {
            echo "Tenant '{$tenant['name']}' için hedef tarihte personel bulunamadı.\n";
        }
        continue;
    }
    
    // Bu tenant ile ilişkili kullanıcıların benzersiz e-postalarını alalım
    $usersStmt = $db->prepare("
        SELECT DISTINCT email, name FROM (
            SELECT email, name FROM users WHERE tenant_id = ?
            UNION
            SELECT u.email, u.name FROM users u
            JOIN user_tenants ut ON u.id = ut.user_id
            WHERE ut.tenant_id = ?
        ) as tenant_users
        WHERE email IS NOT NULL AND email != ''
    ");
    $usersStmt->execute([$tenant_id, $tenant_id]);
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        if ($isTest) {
            echo "Tenant '{$tenant['name']}' için bildirim gönderilecek kullanıcı bulunamadı.\n";
        }
        continue;
    }
    
    // Premium tasarım e-posta içeriği
    $subject = "=?UTF-8?B?" . base64_encode("[Kadroya Geçiş Bildirimi] 3 Yılını Dolduran Personeller") . "?=";
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 32px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
            .header { border-bottom: 2px solid #3b82f6; padding-bottom: 16px; margin-bottom: 24px; }
            .header h2 { margin: 0; color: #1e293b; font-size: 20px; font-weight: 700; }
            .header p { margin: 4px 0 0; color: #64748b; font-size: 14px; }
            .table-container { overflow-x: auto; margin-top: 16px; }
            table { width: 100%; border-collapse: collapse; text-align: left; }
            th { background-color: #f1f5f9; color: #475569; font-size: 13px; font-weight: 600; padding: 12px; border-bottom: 1px solid #e2e8f0; }
            td { padding: 12px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
            .footer { margin-top: 32px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 16px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Kadroya Geçiş Bildirimi</h2>
                <p>" . htmlspecialchars($tenant['name']) . " bünyesinde bugün itibarıyla göreve başlama tarihinden itibaren 3 yıl geçmiş personellerin listesi aşağıdadır.</p>
            </div>
            <div class='table-container'>
                <table>
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>T.C. Kimlik No</th>
                            <th>Göreve Başlama Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>";
                    
    foreach ($personnels as $p) {
        $body .= "
                        <tr>
                            <td><strong>" . htmlspecialchars($p['ad_soyad']) . "</strong></td>
                            <td>" . htmlspecialchars($p['tc_kimlik']) . "</td>
                            <td>" . htmlspecialchars(date('d.m.Y', strtotime($p['goreve_baslama_tarihi']))) . "</td>
                        </tr>";
    }
    
    $body .= "
                    </tbody>
                </table>
            </div>
            <div class='footer'>
                Bu e-posta sistem tarafından otomatik olarak oluşturulmuştur. Lütfen yanıtlamayınız.
            </div>
        </div>
    </body>
    </html>";
    
    foreach ($users as $user) {
        $subjectText = "[Kadroya Geçiş Bildirimi] 3 Yılını Dolduran Personeller";
        if (MailGonderService::gonder($user['email'], $subjectText, $body, $user['name'])) {
            echo "E-posta başarıyla gönderildi: " . $user['email'] . "\n";
        } else {
            echo "E-posta gönderimi başarısız: " . $user['email'] . "\n";
        }
    }
}

