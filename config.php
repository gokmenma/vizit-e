<?php
require_once __DIR__ . '/vendor/autoload.php';

// .env dosyasını yükle
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    "host" => $_ENV['DB_HOST'] ,
    "dbname" => $_ENV['DB_NAME'] ,
    "username" => $_ENV['DB_USER'],
    "password" => $_ENV['DB_PASS'] ,
    "charset" => "utf8mb4",
    'app_url' => $_ENV['APP_URL'] ?? 'http://localhost/sgk-vizite',
    'base_path' => $_ENV['BASE_PATH'] ?? '/sgk-vizite',
];