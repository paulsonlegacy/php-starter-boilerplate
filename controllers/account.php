<?php

/*
 * Account controller — handles logged-in user pages
 *
 * Every function here starts with logged_in() to enforce authentication.
 */


function account_dashboard(): void {
    $user = logged_in();
    $user_id = (int)$user['id'];

    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting' => $setting,
        'title'   => ucwords($setting['name']) . " | Dashboard",
        'user'    => $user,
    ];

    account_view('dashboard', $context);
}


function account_settings(): void {
    $user = logged_in();

    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting'    => $setting,
        'title'      => ucwords($setting['name']) . " | Settings",
        'user'       => $user,
        'csrf_token' => generate_csrf_token(),
    ];

    account_view('settings', $context);
}


function account_settings_update(): void {
    $user = logged_in();

    if (!verify_csrf_token()) {
        redirect(ROOT . '/account/settings', "Invalid request", 'danger');
    }

    // TODO: implement settings update logic
    redirect(ROOT . '/account/settings', "Settings updated", 'success');
}


function account_logout(): void {
    session_destroy();
    redirect(ROOT . '/login');
}
