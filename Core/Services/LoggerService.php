<?php
// App/Helper/Logger.php
namespace Core\Services;

class LoggerService
{
    private static $logDir = null;
    
    public static function init($logDir = null) {
        self::$logDir = $logDir ?: __DIR__ . '/../../logs';
        
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    private static function log($level, $message, $context = []) {
        if (self::$logDir === null) {
            self::init();
        }
        
        $logFile = self::$logDir . '/cron_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage; // Konsola da yazdır
    }
}

