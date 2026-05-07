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
            // Sunucu Ayarları
            $mail->isSMTP();

            $mail->Host       = $_ENV['SMTP_HOST']; // Kendi SMTP sunucunuz (örn: smtp.gmail.com)
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER']; // SMTP kullanıcı adınız

            $mail->Password   = $_ENV['SMTP_PASSWORD'];           // SMTP şifreniz
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Veya ENCRYPTION_SMTPS
            $mail->Port       = $_ENV['SMTP_PORT']; // Veya 465

            // KARAKTER SETİ AYARI (ÇOK ÖNEMLİ)
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64'; // İçeriği base64 ile kodlamak uyumluluğu artırır

            // Gönderen ve Alıcı Bilgileri
            $mail->setFrom('bilgi@vizit-e.com', 'SGK Vizit-e Rapor Sistemi');
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
