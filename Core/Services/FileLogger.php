<?php

namespace Core\Services;

use Core\Contracts\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private $logDir;
    private $channel;

    public function __construct(string $logDir, string $channel = 'app')
    {
        $this->logDir = $logDir;
        $this->channel = $channel;

        $channelDir = $this->logDir . '/' . $this->channel;
        if (!is_dir($channelDir)) {
            mkdir($channelDir, 0755, true);
        }
    }

    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    public function success($message, $context = [])
    {
        $this->log('SUCCESS', $message, $context);
    }



    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    public function log($level, $message, $context = [])
    {
        $channelDir = $this->logDir . '/' . $this->channel;
        $logFile = $channelDir . '/' . date('Y-m-d') . '.log';
        // UTF-8 encoding'i zorla
        $message = mb_convert_encoding($message, 'UTF-8', 'auto');

        $timestamp = date('Y-m-d H:i:s');

        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
