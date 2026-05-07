<?php

namespace Core\Contracts;

interface LoggerInterface
{
    public function info($message, $context = []);
    public function success($message, $context = []);

    public function warning($message, $context = []);
    public function error($message, $context = []);
    public function log($level, $message, $context = []);
}