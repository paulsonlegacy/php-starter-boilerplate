<?php

/*
 * Route definitions — maps URL patterns to controller functions
 *
 * Format:  'METHOD /path' => 'function_name'
 *
 * The router tries an exact match first, then falls back to
 * parameterized pattern matching. The matched function must be defined
 * in one of the controller files loaded by controllers/index.php.
 *
 * ── Static routes ────────────────────────────────────────────────────
 * Plain paths — exact match only.
 *   'GET /'                    => 'landing_home',
 *   'GET /login'               => 'landing_login',
 *   'POST /login'              => 'landing_login_submit',
 *   'GET /admin/users'         => 'admin_users',
 *
 * ── Parameterized routes ─────────────────────────────────────────────
 * Use {name} placeholders to capture URL segments. Each placeholder
 * matches one segment (no slashes). Read the value in your controller
 * with route_param('name') — always returns a string; cast to int when
 * the segment is a numeric ID.
 *
 *   'GET /posts/{slug}'        => 'landing_post_detail',
 *   // In controller: $slug = route_param('slug');
 *
 *   'GET /users/{id}'          => 'admin_user_detail',
 *   // In controller: $id = (int) route_param('id');
 *
 *   'GET /orders/{id}/items'   => 'user_order_items',
 *   // In controller: $id = route_param('id'); // e.g. "ORD-00001"
 *
 * Note: exact routes always win over parameterized ones, so
 * 'GET /posts/new' (exact) will never be caught by 'GET /posts/{slug}'.
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
