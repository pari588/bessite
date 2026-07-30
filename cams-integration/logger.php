<?php
// logger.php
require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Dotenv\Dotenv;

// 1. Load .env if available
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// 2. Load cams.conf if available
$config = [];
if (file_exists(__DIR__ . '/cams.conf')) {
    $config = parse_ini_file(__DIR__ . '/cams.conf', true);
}

// 3. Determine log level (env > cams.conf > default: info)
$logLevelName = $_ENV['LOG_LEVEL'] ??
                ($config['log']['level'] ?? 'info');

$logLevelName = strtoupper($logLevelName);

// Validate and fallback if needed
if (!defined('Monolog\\Logger::' . $logLevelName)) {
    $logLevelName = 'INFO';
}

// Convert to Monolog constant
$logLevel = constant('Monolog\\Logger::' . $logLevelName);

// 4. Create logger instance
$logger = new Logger('cams_php');

// 5. Add console/file output
$logger->pushHandler(new StreamHandler(__DIR__ . '/app.log', $logLevel));

// Optional: also log to PHP output (console in CLI mode)
if (php_sapi_name() === 'cli') {
    $logger->pushHandler(new StreamHandler('php://stdout', $logLevel));
}

// 6. Return logger instance
return $logger;
