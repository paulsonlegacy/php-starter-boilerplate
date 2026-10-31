<?php

/*
 * Front Controller — all requests route through here via .htaccess
 *
 * How it works:
 * 1. .htaccess catches every URL and sends it here as ?url=...
 * 2. This file loads the app (init.php) and all controllers (controllers/index.php)
 * 3. It reads the route definitions from routes.php
 * 4. It matches the current METHOD + URL against those routes
 * 5. It calls the matched function
 *
 * To add a new page:
 * 1. Define the function in the appropriate controller file
 * 2. Add the route in routes.php
 * That's it.
 */

require("./app/init.php");
require("./controllers/index.php");

// Load route definitions
$routes = require("./routes.php");

// Build the route key from the current request
$method = $_SERVER['REQUEST_METHOD'];
$url = '/' . trim(strtolower($_GET['url'] ?? ''), '/');
$route_key = "$method $url";

// Try exact match first
if (isset($routes[$route_key])) {
    $handler = $routes[$route_key];
    if (function_exists($handler)) {
        $handler();
        return;
    }
}

// 404 — no route matched
http_response_code(404);
require(APP_PATH . "views/404.php");
