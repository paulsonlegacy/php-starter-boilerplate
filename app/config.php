<?php

/*
 * Configuration — reads secrets from .env via phpdotenv
 *
 * .env lookup order:
 *   1. One level above the project (ideal for shared hosting: outside public_html)
 *   2. Project root (standard for VPS or local dev)
 *
 * This means on shared hosting you can place .env at /home/user/.env
 * and your project files inside /home/user/public_html/ — the .env
 * stays completely unreachable by any HTTP request.
 */

use Dotenv\Dotenv;

$project_root = dirname(__DIR__);
$parent_dir = dirname($project_root);

if (file_exists($parent_dir . '/.env')) {
    $dotenv = Dotenv::createImmutable($parent_dir);
} else {
    $dotenv = Dotenv::createImmutable($project_root);
}

$dotenv->load();

// Crash immediately if required keys are missing
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_URL', 'APP_DOMAIN']);

// PHP BASE PATHS
define('APP_PATH', $project_root . '/');
define('MEDIA_PATH', APP_PATH . 'media/');
define('STATIC_PATH', APP_PATH . 'assets/');
define('VIEW_PATH', APP_PATH . 'views/');

// DATABASE
define('DBHOST', $_ENV['DB_HOST']);
define('DBNAME', $_ENV['DB_NAME']);
define('DBUSER', $_ENV['DB_USER']);
define('DBPASS', $_ENV['DB_PASS'] ?? '');

// HTML BASE PATHS
define('ROOT', $_ENV['APP_URL']);
define('MEDIA_ROOT', ROOT . '/media');
define('STATIC_ROOT', ROOT . '/assets');

// EMAIL
define('EMAIL_HOST', $_ENV['EMAIL_HOST'] ?? 'smtp.gmail.com');
define('EMAIL_USERNAME', $_ENV['EMAIL_USERNAME'] ?? '');
define('EMAIL_PASSWORD', $_ENV['EMAIL_PASSWORD'] ?? '');
define('EMAIL_PORT', (int)($_ENV['EMAIL_PORT'] ?? 465));

// OTHERS
define('DOMAIN', $_ENV['APP_DOMAIN']);
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// SESSION CONFIGURATION
$session_lifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 10800);
$session_secure = ($_ENV['SESSION_SECURE'] ?? 'true') === 'true';

ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'domain'   => DOMAIN,
    'path'     => '/',
    'secure'   => $session_secure,
    'httponly'  => true,
    'samesite' => 'Lax',
]);

session_start();

// SESSION ID REGENERATION
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} else {
    if (time() - $_SESSION['last_regeneration'] >= $session_lifetime) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
