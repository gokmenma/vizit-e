<?php
// App/Helper/UserAgent.php
namespace App\Helper;

class UserAgent 
{
    public static function parseOS($userAgent) {
        if (empty($userAgent)) {
            return 'Unknown';
        }

    // Windows NT 10.0 için özel kontrol
    if (preg_match('/Windows NT 10\.0/', $userAgent)) {
          // Farklı build numarası formatları
          $patterns = [
            '/Windows NT 10\.0.*?build (\d+)/i',
            '/Windows NT 10\.0.*?(\d{5,})/',
            '/Windows NT 10\.0.*?Win64.*?(\d{5,})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                $buildNumber = (int)$matches[1];
                
                // Windows 11 build numaraları
                if ($buildNumber >= 22000) {
                    return 'Windows 11';
                }
                // Windows 10 build numaraları
                elseif ($buildNumber >= 10240) {
                    return 'Windows 10';
                }
                break;
            }
        }
        
        return 'Windows 10'; // Varsayılan
    }
        if (preg_match('/Windows NT 6.3/', $userAgent)) return 'Windows 8.1';
        if (preg_match('/Windows NT 6.2/', $userAgent)) return 'Windows 8';
        if (preg_match('/Windows NT 6.1/', $userAgent)) return 'Windows 7';
        if (preg_match('/Windows NT/', $userAgent)) return 'Windows';

        // macOS
        if (preg_match('/Mac OS X ([\d_]+)/', $userAgent, $matches)) {
            $version = str_replace('_', '.', $matches[1]);
            return "macOS $version";
        }
        if (preg_match('/Macintosh/', $userAgent)) return 'macOS';

        // Linux dağıtımları
        if (preg_match('/Ubuntu/', $userAgent)) return 'Ubuntu Linux';
        if (preg_match('/Linux/', $userAgent)) return 'Linux';

        // Mobil işletim sistemleri
        if (preg_match('/iPhone OS ([\d_]+)/', $userAgent, $matches)) {
            $version = str_replace('_', '.', $matches[1]);
            return "iOS $version";
        }
        if (preg_match('/Android ([\d.]+)/', $userAgent, $matches)) {
            return "Android {$matches[1]}";
        }

        return 'Unknown';
    }

    public static function parseBrowser($userAgent) {
        if (empty($userAgent)) {
            return 'Unknown';
        }

        // Edge
        if (preg_match('/Edg\/([\d.]+)/', $userAgent, $matches)) {
            return "Edge {$matches[1]}";
        }
        
        // Chrome
        if (preg_match('/Chrome\/([\d.]+)/', $userAgent, $matches)) {
            return "Chrome {$matches[1]}";
        }
        
        // Firefox
        if (preg_match('/Firefox\/([\d.]+)/', $userAgent, $matches)) {
            return "Firefox {$matches[1]}";
        }
        
        // Safari
        if (preg_match('/Safari\/([\d.]+)/', $userAgent, $matches)) {
            if (!preg_match('/Chrome/', $userAgent)) { // Chrome'u dışla
                return "Safari {$matches[1]}";
            }
        }
        
        // Opera
        if (preg_match('/Opera\/([\d.]+)/', $userAgent, $matches)) {
            return "Opera {$matches[1]}";
        }
        
        // Internet Explorer
        if (preg_match('/MSIE ([\d.]+)/', $userAgent, $matches)) {
            return "Internet Explorer {$matches[1]}";
        }

        return 'Unknown';
    }
}