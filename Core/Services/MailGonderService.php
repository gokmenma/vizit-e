<?php

namespace Core\Services;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;



class MailGonderService
{
    public static function gonder(string $kime, string $konu, string $icerik, array $ekler = []): bool
    {
        $mail = new PHPMailer(true);


        try {
            // Get settings from database, fall back to .env configurations
            $ayarModel = new \Models\KullaniciAyarModel();
            
            $smtp_host = $ayarModel->getSetting('smtp_host', 0);
            if (!$smtp_host || $smtp_host === '0') $smtp_host = $_ENV['SMTP_HOST'] ?? '';
            
            $smtp_user = $ayarModel->getSetting('smtp_user', 0);
            if (!$smtp_user || $smtp_user === '0') $smtp_user = $_ENV['SMTP_USER'] ?? '';
            
            $smtp_pass = $ayarModel->getSetting('smtp_password', 0);
            if (!$smtp_pass || $smtp_pass === '0') $smtp_pass = $_ENV['SMTP_PASSWORD'] ?? '';
            
            $smtp_port = $ayarModel->getSetting('smtp_port', 0);
            if (!$smtp_port || $smtp_port === '0') $smtp_port = $_ENV['SMTP_PORT'] ?? '587';
            
            $smtp_enc = $ayarModel->getSetting('smtp_encryption', 0);
            if (!$smtp_enc || $smtp_enc === '0') $smtp_enc = 'tls';
            
            $smtp_from_name = $ayarModel->getSetting('smtp_from_name', 0);
            if (!$smtp_from_name || $smtp_from_name === '0') $smtp_from_name = 'SGK Vizit-e Rapor Sistemi';

            // Sunucu Ayarları
            $mail->isSMTP();

            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;

            $mail->Password   = $smtp_pass;
            
            if (strtolower($smtp_enc) === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else if (strtolower($smtp_enc) === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAuth   = false; // No encryption / authentication
            }
            
            $mail->Port       = $smtp_port;

            // KARAKTER SETİ AYARI (ÇOK ÖNEMLİ)
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64'; // İçeriği base64 ile kodlamak uyumluluğu artırır

            // Gönderen ve Alıcı Bilgileri
            $mail->setFrom($smtp_user, $smtp_from_name);
            $mail->addAddress($kime); // Alıcı e-posta adresi

            // İçerik
            $mail->isHTML(true);
            $mail->Subject = $konu;
            $mail->Body    = $icerik;
            $mail->AltBody = strip_tags($icerik); // HTML desteklemeyen istemciler için
            
            //eğer ekler boş değilse foreach ile ekleri ekle
            if (!empty($ekler)) {
                foreach ($ekler as $ek) {
                    $mail->addAttachment($ek);
                }
            }


            $mail->send();
            return true;
        } catch (Exception $e) {
            // Hata olursa, loglayıp false döndür
            error_log("E-posta gönderilemedi. Hata: {$mail->ErrorInfo}");
            return false;
        }
    }
}
