<?php
// config.php
require_once __DIR__ . '/vendor/autoload.php';

// 0. Load .env if it exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// 1. Try to get from Environment (e.g. SetEnv in Apache or .env if loaded)
$app_url = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);

// 2. Auto-detect if HTTP request
if (!$app_url && isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']); // e.g. /care-dental/care-dental
    
    // Trim trailing slash just in case
    $app_url = rtrim("$protocol://$host$path", '/');
}

// 3. Fallback for CLI or failures
if (!$app_url) {
    // Default XAMPP path
    $app_url = 'http://localhost/care-dental/care-dental';
}

// Ensure no trailing slash
$app_url = rtrim($app_url, '/');

if (!defined('APP_URL')) {
    define('APP_URL', $app_url);
}
?>
