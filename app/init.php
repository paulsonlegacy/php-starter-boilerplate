<?php

/*
 * Bootstrap — load Composer autoloader and config
 * Every entry point (index.php, cron jobs, CLI scripts) includes this file
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
// connect.php and functions.php are loaded by Composer autoload (see composer.json "files")
