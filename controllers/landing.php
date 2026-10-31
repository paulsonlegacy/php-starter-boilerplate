<?php

/*
 * Landing controller — handles all public-facing pages
 *
 * Function naming: landing_{page} for GET, landing_{page}_submit for POST
 */


function landing_home(): void {
    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting' => $setting,
        'title'   => ucwords($setting['name']) . " | Home",
    ];

    landing_view('home', $context);
}


function landing_login(): void {
    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting'    => $setting,
        'title'      => ucwords($setting['name']) . " | Login",
        'csrf_token' => generate_csrf_token(),
    ];

    landing_view('login', $context);
}


function landing_login_submit(): void {
    if (!verify_csrf_token()) {
        redirect(ROOT . '/login', "Invalid request", 'danger');
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        redirect(ROOT . '/login', "Email or password cannot be empty", 'danger');
    }

    $user = authenticate_user($email, $password);

    if (!$user || $user['is_blocked'] == 1) {
        redirect(ROOT . '/login', "Invalid credentials", 'danger');
    }

    unset($_SESSION['user']);
    query_db("UPDATE users SET last_login = NOW() WHERE id = :id LIMIT 1", ['id' => $user['id']]);
    $_SESSION['user'] = $user;

    redirect(ROOT . "/account/dashboard");
}


function landing_register(): void {
    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting'    => $setting,
        'title'      => ucwords($setting['name']) . " | Register",
        'csrf_token' => generate_csrf_token(),
    ];

    landing_view('register', $context);
}


function landing_register_submit(): void {
    // TODO: implement registration logic
    redirect(ROOT . '/register', "Registration coming soon", 'info');
}


function landing_about(): void {
    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting' => $setting,
        'title'   => ucwords($setting['name']) . " | About",
    ];

    landing_view('about', $context);
}


function landing_contact(): void {
    $setting = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = !empty($setting) ? $setting[0] : ['name' => 'My App'];

    $context = [
        'setting'    => $setting,
        'title'      => ucwords($setting['name']) . " | Contact",
        'csrf_token' => generate_csrf_token(),
    ];

    landing_view('contact', $context);
}


function landing_contact_submit(): void {
    // TODO: implement contact form logic
    redirect(ROOT . '/contact', "Message sent", 'success');
}
