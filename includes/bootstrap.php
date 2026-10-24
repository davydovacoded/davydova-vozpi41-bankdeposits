<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/config/config.php';

if (!is_file($configPath)) {
    throw new RuntimeException('Рабочий файл конфигурации не найден.');
}

$config = require $configPath;

if (!is_array($config) || !isset($config['app'], $config['db'])) {
    throw new RuntimeException('Рабочий файл конфигурации имеет неверную структуру.');
}

$debug = (bool) ($config['app']['debug'] ?? false);

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

$timezone = (string) ($config['app']['timezone'] ?? 'Europe/Moscow');
date_default_timezone_set($timezone);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('bank_deposits_session');
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

require_once $projectRoot . '/includes/helpers.php';
require_once $projectRoot . '/includes/database.php';

return $config;
