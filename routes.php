<?php

/*
 * Route definitions — maps URL patterns to controller functions
 *
 * Format:  'METHOD /path' => 'function_name'
 *
 * The router matches the request method + URL against these entries
 * and calls the corresponding function. The function must be defined
 * in one of the controller files loaded by controllers/index.php.
 *
 * For pages that handle both GET (show form) and POST (process form),
 * define two routes pointing to two functions, or one function that
 * checks $_SERVER['REQUEST_METHOD'] internally.
 *
 * Examples:
 *   'GET /'                    => 'landing_home',
 *   'GET /about'               => 'landing_about',
 *   'GET /login'               => 'landing_login',
 *   'POST /login'              => 'landing_login_submit',
 *   'GET /user/dashboard'      => 'user_dashboard',
 *   'GET /user/settings'       => 'user_settings',
 *   'POST /user/settings'      => 'user_settings_update',
 *   'GET /admin/users'         => 'admin_users',
 *   'POST /admin/users/block'  => 'admin_block_user',
 */

return [

    // ─── Landing ─────────────────────────────────────
    'GET /'              => 'landing_home',
    'GET /login'         => 'landing_login',
    'POST /login'        => 'landing_login_submit',
    'GET /register'      => 'landing_register',
    'POST /register'     => 'landing_register_submit',
    'GET /about'         => 'landing_about',
    'GET /contact'       => 'landing_contact',
    'POST /contact'      => 'landing_contact_submit',

    // ─── User ────────────────────────────────────────
    'GET /user/dashboard'   => 'user_dashboard',
    'GET /user/settings'    => 'user_settings',
    'POST /user/settings'   => 'user_settings_update',
    'GET /user/logout'      => 'user_logout',

    // ─── Admin ───────────────────────────────────────
    'GET /admin/dashboard'     => 'admin_dashboard',
    'GET /admin/users'         => 'admin_users',

];
