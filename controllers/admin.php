<?php

/*
 * Admin controller — handles admin panel pages
 *
 * Every function here starts with admin_logged_in() to enforce admin access.
 */


function admin_dashboard(): void {
    $user = admin_logged_in();

    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting' => $setting,
        'title'   => ucwords($setting['name']) . " | Admin Dashboard",
        'user'    => $user,
    ];

    admin_view('dashboard', $context);
}


function admin_users(): void {
    $user = admin_logged_in();

    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $users = query_fetch("SELECT * FROM users ORDER BY id DESC");

    $context = [
        'setting' => $setting,
        'title'   => ucwords($setting['name']) . " | Users",
        'user'    => $user,
        'users'   => $users,
    ];

    admin_view('users', $context);
}
