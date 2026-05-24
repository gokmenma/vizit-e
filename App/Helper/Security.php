<?php

namespace App\Helper;

use Models\KullaniciAbonelikModel;
use Models\KullaniciIsyeriModel;

class Security
{
    public static function escape($data)
    {
        return htmlentities ($data, ENT_QUOTES, 'UTF-8');
    }

    // CSRF Token
    public static function csrf()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $token = bin2hex(random_bytes(48));
            $_SESSION['csrf_token'] = $token;
        } else {
            $token = $_SESSION['csrf_token'];
        }
        return $token;
    }

    public static function checkCsrfToken()
    {
        //kullaNıcının session_token alanı ile Session'daki csrf_token alanını karşılaştırır
        $token = $_SESSION['user']->session_token ?? null;
        return hash_equals($_SESSION['csrf_token'], $token);

   
    }


    /**
     * Admin Sayfasına erişim kontrolü yapar
     * Eğer kullanıcı admin değilse hata mesajı gösterir ve admin sayfasına yönlendirir
     * @param string $redirect Yönlendirilecek sayfa
     */
    public static function checkAdmin($redirect = "sign-in")
    {

        //SESSION yoksa session başlatılır
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        if (!isset($_SESSION['admin_id']) ) {
            $_SESSION['hata'] = 'Bu sayfaya erişim izniniz yok.';
            header('Location: ' . $redirect);
            exit();
        }
    }

   
    /*
    *Login Kontrolü yapılır,api sayfalarına erişim kontrolü için kullanılır
    */
    public static function checkLogin($redirect = "sign-in")
    {
        if (!isset($_SESSION['kullanici_id'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(401);
                exit('SESSION_EXPIRED');
            }
            header('Location: ' . $redirect);
            exit;
        }
    }



    /**
     * firma Adı kontrolü yapılır, sessionda yoksa veya tanımlı işyeri yoksa firma seçme sayfasına yönlendirir
     * 
     */
    public static function checkFirma()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['kullanici_id'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo "<script>window.location.href = 'sign-in';</script>";
                exit();
            }
            header('Location: sign-in');
            exit();
        }

        // Detect Mobile browser mode to redirect correctly
        $is_mobile = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $mobile_agents = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'opera mini', 'windows phone'];
            foreach ($mobile_agents as $agent) {
                if (strpos($user_agent, $agent) !== false) {
                    $is_mobile = true;
                    break;
                }
            }
        }
        $wants_desktop = isset($_GET['desktop']) || (isset($_SESSION['desktop_mode']) && $_SESSION['desktop_mode']);
        $isMobileMode = $is_mobile && !$wants_desktop;

        $config = require __DIR__ . '/../../config.php';
        $basePath = rtrim($config['base_path'] ?? '', '/');
        $redirectUrl = $isMobileMode ? ($basePath . '/mobile/#isyerlerim') : ($basePath . '/isyerlerim');

        $kullaniciId = $_SESSION['kullanici_id'];
        $userRole = $_SESSION["role"] ?? "user";
        
        $isyeriModel = new KullaniciIsyeriModel();
        $isyerleri = [];

        if ($userRole === 'user') {
            $isyeri_ids = '';
            if (isset($_SESSION['user']) && is_object($_SESSION['user'])) {
                $isyeri_ids = $_SESSION['user']->yetkili_oldugu_isyeri_ids ?? '';
            } else {
                try {
                    $db = \Core\Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT yetkili_oldugu_isyeri_ids FROM kullanicilar WHERE id = ?");
                    $stmt->execute([$kullaniciId]);
                    $isyeri_ids = $stmt->fetchColumn() ?: '';
                } catch (\Exception $e) {
                }
            }
            $isyerleri = $isyeriModel->AltKullaniciİsyerleri($isyeri_ids);
        } else {
            $isyerleri = $isyeriModel->whereRaw('kullanici_id = ? AND aktif_mi = ?', [$kullaniciId, 1]);
        }

        // 1. Eğer kullanıcının tanımlı hiçbir işyeri yoksa, isyerlerim sayfasına yönlendir
        if (empty($isyerleri)) {
            $_SESSION['hata'] = 'İşlem yapabilmek için lütfen önce en az bir işyeri ekleyiniz.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo "<script>window.location.href = '" . $redirectUrl . "';</script>";
                exit();
            }
            header('Location: ' . $redirectUrl);
            exit();
        }

        // 2. Eğer session'da işyeri seçili değilse, isyerlerim sayfasına yönlendir
        if (!isset($_SESSION['isyeri_id']) || empty($_SESSION['isyeri_id']) || !isset($_SESSION['firma_adi'])) {
            if ($userRole !== "user") {
                $_SESSION['hata'] = 'Lütfen önce işlem yapacağınız firmayı seçiniz.';
            }
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo "<script>window.location.href = '" . $redirectUrl . "';</script>";
                exit();
            }
            header('Location: ' . $redirectUrl);
            exit();
        }

        // 3. Eğer session'da seçili olan işyeri kullanıcının yetkili olduğu işyerleri arasında değilse (silinmiş/yetkisi kalkmışsa)
        $isyeriAuthorized = false;
        $activeIsyeri = null;
        foreach ($isyerleri as $isyeri) {
            if ((int)$isyeri->id === (int)$_SESSION['isyeri_id']) {
                $isyeriAuthorized = true;
                $activeIsyeri = $isyeri;
                break;
            }
        }

        if ($isyeriAuthorized && $activeIsyeri) {
            // Kendi kendini onarma: Session'daki eksik veya uçmuş işyeri bilgilerini otomatik olarak doldur
            $_SESSION['firma_adi'] = $activeIsyeri->firma_adi;
            $_SESSION['kullaniciAdi'] = $activeIsyeri->kullanici_kodu;
            $_SESSION['isyeriKodu'] = $activeIsyeri->isyeri_kodu;
            $_SESSION['wsSifre'] = $activeIsyeri->isyeri_sifre;
        }

        if (!$isyeriAuthorized) {
            unset($_SESSION['isyeri_id']);
            unset($_SESSION['firma_adi']);
            $_SESSION['hata'] = 'Seçili işyerine erişim yetkiniz bulunmamaktadır. Lütfen listeden başka bir işyeri seçiniz.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo "<script>window.location.href = '" . $redirectUrl . "';</script>";
                exit();
            }
            header('Location: ' . $redirectUrl);
            exit();
        }
    }

/*
    Kullanıcının Role kontrolü yapılır
    * Eğer kullanıcı admin değilse hata mesajı gösterir ve admin sayfasına yönlendirir
*/
    public static function checkUserRole()
    {
        if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'superadmin') {
            $_SESSION['hata'] = 'Bu sayfaya erişim izniniz yok.';
            header('Location: unauthorize');
            exit();
        }
        else{
            $_SESSION['hata'] = '';
        }
    }



    //Kullnıcının aktif aboneliği yoksa hata mesajı gösterir
    public static function hasActiveSubscription()
    {
        $KulllaniciAbonelik = new KullaniciAbonelikModel();
       
        if (!$KulllaniciAbonelik->hasActiveSubscription($_SESSION['kullanici_id'])) {

            $_SESSION['hata'] = 'Lütfen aboneliğinizi aktif ediniz.';
            header('Location: abonelik-paketleri');
            exit();
        }
    }





    public static function generatePassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function passwordControl($password, $hash)
    {
        return password_verify($password, $hash);
    }


public static function encrypt($data)
{
    if (empty($data)) {
        return '';
    }

    $method = "AES-256-GCM";
    $key = hash('sha256', 'mysecretkey', true);
    $iv = openssl_random_pseudo_bytes(12); // 12 byte IV
    $tag = null;

    $encrypted_data = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);

    // IV + TAG + ENCRYPTED sıralaması önemli
    $combined = $iv . $tag . $encrypted_data;

    // base64 encode + URL encode
    $encoded = base64_encode($combined);
    $urlSafe = strtr($encoded, ['+' => '-', '/' => '_', '=' => '*']);

    return rawurlencode($urlSafe);
}


public static function decrypt($data)
{
    if (empty($data) || $data === '0') return 0;

    $method = "AES-256-GCM";
    $key = hash('sha256', 'mysecretkey', true);

    // URL decode + tersine base64 düzeltmeleri
    $decoded = rawurldecode($data);
    $base64 = strtr($decoded, ['-' => '+', '_' => '/', '*' => '=']);

    $combined = base64_decode($base64);

    if (strlen($combined) < 28) {
        return null; // veri bozuk
    }

    $iv = substr($combined, 0, 12);
    $tag = substr($combined, 12, 16);
    $encrypted_data = substr($combined, 28);

    return openssl_decrypt($encrypted_data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
}
}