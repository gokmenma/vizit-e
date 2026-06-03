<?php
// Bu değişkenlerin önceden tanımlandığını varsayıyoruz
// $user->kullanici_adi = "magokmen";
// $reset_link = "https://vizit-e.com/reset-password?token=a1b2c3d4e5f6g7h8";

$subject = "Şifre Sıfırlama Talimatları";

// HEREDOC sözdizimi, HTML'i PHP içinde daha temiz yazmamızı sağlar
$body = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #1a1a1e; color: #E4DEFF; line-height: 1.6;">

    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #1a1a1e;">
        <tr>
            <td align="center">
                <table width="600" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 20px auto;">

                    <!-- Başlık -->
                    <tr>
                        <td align="center" style="padding: 30px 0;">
                            <div style="font-size: 28px; font-weight: bold; color: #E4DEFF;">VİZİT-E</div>
                        </td>
                    </tr>

                    <!-- Ana İçerik Kartı -->
                    <tr>
                        <td style="background-color: #2c2c2f; border-radius: 16px; border: 1px solid #444;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding: 40px; color: #cccccc; font-size: 16px;">
                                        
                                        <!-- İkon -->
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="padding-bottom: 20px;">
                                                    <img src="https://i.imgur.com/gwha12C.png" alt="Kilit İkonu" width="60" style="display: block;">
                                                </td>
                                            </tr>
                                        </table>

                                        <h1 style="font-size: 24px; font-weight: bold; margin: 0 0 20px 0; text-align: center; color: #FFFFFF;">Şifre Sıfırlama Talebi</h1>
                                        <p style="margin: 0 0 20px 0;">Merhaba, <strong>{$user->kullanici_adi}</strong></p>
                                        <p style="margin: 0 0 30px 0;">Hesabınız için bir şifre sıfırlama talebi aldık. Şifrenizi sıfırlamak için lütfen aşağıdaki butona tıklayın.</p>

                                        <!-- Buton -->
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td align="center">
                                                    <a href="{$reset_link}" target="_blank" style="background: linear-gradient(90deg, #8a2be2, #6a5acd); background-color: #8a2be2; color: #FFFFFF !important; padding: 16px 32px; border-radius: 30px; font-weight: bold; font-size: 16px; text-decoration: none; display: inline-block;">Şifremi Sıfırla</a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin: 30px 0 0 0; font-size: 14px; text-align: center; color: #888888;">
                                            Bu talepte bulunmadıysanız, bu e-postayı görmezden gelebilirsiniz. Bu link 1 saat süreyle geçerlidir.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 30px 0; color: #777; font-size: 12px;">
                            © SENE VİZİT-E. Bu e-postayı, hesabınız için bir şifre sıfırlama talebinde bulunulduğu için aldınız.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
HTML;

// SENE kısmını dinamik olarak güncelleyelim
$body = str_replace('SENE', date('Y'), $body);

// Mail gönderme fonksiyonunuzu burada çağırın
// ornek_mail_gonder("hedef@mail.com", $subject, $body);

?>