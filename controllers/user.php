<?php

/*
 * User controller — handles logged-in user pages
 *
 * Every function here starts with logged_in() to enforce authentication.
 */


function user_dashboard(): void {
    $user = logged_in();
    $user_id = (int)$user['id'];

    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting' => $setting,
        'title'   => ucwords($setting['name']) . " | Dashboard",
        'user'    => $user,
    ];

    user_view('dashboard', $context);
}


function user_settings(): void {
    $user = logged_in();

    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting'    => $setting,
        'title'      => ucwords($setting['name']) . " | Settings",
        'user'       => $user,
        'csrf_token' => generate_csrf_token(),
    ];

    user_view('settings', $context);
}


function user_settings_update(): void {
    $user = logged_in();

    if (!verify_csrf_token()) {
        redirect(ROOT . '/user/settings', "Invalid request", 'danger');
    }

    // TODO: implement settings update logic
    redirect(ROOT . '/user/settings', "Settings updated", 'success');
}


function user_logout(): void {
    session_destroy();
    redirect(ROOT . '/login');
}
