<?php

/*
 * PHPUnit bootstrap — loads the app so tests can call your functions
 *
 * Uses .env.testing if it exists, otherwise .env
 * This lets you point tests at a separate test database
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$envFile = file_exists(dirname(__DIR__) . '/.env.testing') ? '.env.testing' : '.env';
$dotenv = Dotenv::createImmutable(dirname(__DIR__), $envFile);
$dotenv->load();

// Define constants the app needs
define('APP_PATH', dirname(__DIR__) . '/');
define('MEDIA_PATH', APP_PATH . 'media/');
define('STATIC_PATH', APP_PATH . 'assets/');
define('VIEW_PATH', APP_PATH . 'views/');
define('DBHOST', $_ENV['DB_HOST']);
define('DBNAME', $_ENV['DB_NAME']);
define('DBUSER', $_ENV['DB_USER']);
define('DBPASS', $_ENV['DB_PASS'] ?? '');
define('ROOT', $_ENV['APP_URL'] ?? 'http://localhost');
define('MEDIA_ROOT', ROOT . '/media');
define('STATIC_ROOT', ROOT . '/assets');
define('DOMAIN', $_ENV['APP_DOMAIN'] ?? 'localhost');
define('APP_ENV', 'testing');
define('EMAIL_HOST', '');
define('EMAIL_USERNAME', '');
define('EMAIL_PASSWORD', '');
define('EMAIL_PORT', 465);
