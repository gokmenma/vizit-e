<?php

namespace Core\Services;

use Models\Model;
use App\Helper\UserAgent;
use Core\Contracts\LoggerInterface;
use PDO; // Veritabanı bağlantısı için PDO kullanıyoruz

class DatabaseLogger extends Model implements LoggerInterface

{
    private $channel;

    public function __construct(string $channel = 'app')
    {
        // ÖNCE! Üst sınıfın (Model) constructor'ını çağırarak veritabanı bağlantısını kur.
        parent::__construct(); 
        $this->channel = $channel;
    }

    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    public function success($message, $context = [])
    {
        $this->log('SUCCESS', $message, $context);
    }

    

    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    public function log($level, $message, $context = [])
    {
        $userId = $context['user_id'] ?? ($_SESSION['kullanici_id'] ?? 0);

        $sql = "INSERT INTO logs (
                    ip_address,user_id,
                    browser,
                    level, message, 
                    context, channel) 
                VALUES (:ip_address,:user_id,:browser,:level, :message, :context, :channel)";



        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 0,
            ':user_id' => $userId,
            ':level'   => $level,
            ':message' => $message,
            ':context' => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            ':channel' => $this->channel,
           ':browser' => UserAgent::parseBrowser($_SERVER['HTTP_USER_AGENT']) ?? 0


        ]);
    }
}
