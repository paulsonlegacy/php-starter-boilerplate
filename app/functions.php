<?php

/*
 * Shared functions — auth, views, helpers, email
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


//////////////////////// SECURITY FUNCTIONS ///////////////////////////////

// Generate a CSRF token and store it in the session
function generate_csrf_token(): string {
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
    return $csrf_token;
}

// Verify CSRF token from a form submission
function verify_csrf_token(): bool {
    return isset($_POST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// Escape output for safe HTML rendering (use this in views, not sanitize_input)
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}



//////////////////////// AUTH FUNCTIONS ///////////////////////////////

// Check if user is logged in — returns user row or empty array
function is_user_authenticated(): array {
    if (isset($_SESSION['user'])) {
        $user_id = (int)$_SESSION['user']['id'];
        $users = query_db(
            "SELECT * FROM users WHERE id = :id LIMIT 1",
            ['id' => $user_id]
        );
        if (!empty($users)) {
            return $users[0];
        }
    }
    return [];
}

// Require login — redirects to login page if not authenticated
function logged_in(): array {
    $user = is_user_authenticated();
    if (empty($user) || $user['is_blocked'] == 1) {
        redirect(ROOT . "/login", "Please sign in", "danger");
    }
    return $user;
}

// Require admin — redirects to login if not admin
function admin_logged_in(): array {
    $user = is_user_authenticated();
    if (empty($user) || $user['is_admin'] == 0) {
        redirect(ROOT . "/admin/login");
    }
    return $user;
}

// Authenticate with email and password — returns user row or false
function authenticate_user(string $email, string $password): array|false {
    $users = query_db(
        "SELECT * FROM users WHERE email = :email LIMIT 1",
        ['email' => $email]
    );
    if (!empty($users) && password_verify($password, $users[0]['password'])) {
        return $users[0];
    }
    return false;
}

// Check if email exists in users table
function email_exists(string $email): bool {
    $users = query_db(
        "SELECT id FROM users WHERE email = :email LIMIT 1",
        ['email' => $email]
    );
    return !empty($users);
}

// Check if username exists in users table
function username_exists(string $username): bool {
    $users = query_db(
        "SELECT id FROM users WHERE username = :username LIMIT 1",
        ['username' => $username]
    );
    return !empty($users);
}



//////////////////////// VIEW FUNCTIONS ///////////////////////////////

// Render a landing page view
function landing_view(string $name, array $context = []): void {
    require(APP_PATH . "views/landing/layout.view.php");
    unset_message();
}

// Render an account page view
function account_view(string $name, array $context = []): void {
    require(APP_PATH . "views/account/layout.view.php");
    unset_message();
}

// Render an admin page view
function admin_view(string $name, array $context = []): void {
    require(APP_PATH . "views/admin/layout.view.php");
    unset_message();
}

// Clear flash messages after rendering
function unset_message(): void {
    unset($_SESSION['message'], $_SESSION['message_tag']);
}



//////////////////////// HELPER FUNCTIONS ///////////////////////////////

// Redirect with an optional flash message
function redirect(string $url, string $message = '', string $tag = 'info'): never {
    if ($message !== '') {
        $_SESSION['message'] = $message;
        $_SESSION['message_tag'] = $tag;
    }
    header("Location: $url");
    exit;
}

// Return a JSON response
function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Format date for display
function format_date(string $date): string {
    return date("d M, Y", strtotime($date));
}

// Format datetime for display
function format_datetime(string $date): string {
    return date("d M, Y  H:i A", strtotime($date));
}

// Format datetime in a specific timezone
function format_datetime_timezone(string $datetime, string $timezone): string {
    $dt = new DateTime($datetime);
    $dt->setTimezone(new DateTimeZone($timezone));
    return $dt->format('d M, Y  H:i A');
}

// Generate a unique alphanumeric ID
function generate_unique_id(int $length = 10): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}

// Validate and upload a single image
function upload_image(array $file, string $folder): array {
    if ($file['error'] !== 0) {
        return ['status' => 'failed', 'message' => 'Upload error'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['status' => 'failed', 'message' => 'File size exceeds 5MB limit'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpeg', 'jpg', 'png'])) {
        return ['status' => 'failed', 'message' => 'Invalid file type'];
    }

    $new_name = uniqid("IMG-", true) . '.' . $ext;
    $upload_path = MEDIA_PATH . $folder . '/' . $new_name;
    move_uploaded_file($file['tmp_name'], $upload_path);

    return ['status' => 'success', 'message' => 'Upload successful', 'file_name' => $new_name];
}

// Paginate query results
function paginate(string $query, int $per_page): array {
    $all_items = query_fetch($query);
    $total = count($all_items);
    $total_pages = (int)ceil($total / $per_page);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $page = min($page, max(1, $total_pages));
    $offset = ($page - 1) * $per_page;

    $items_query = $query . " LIMIT $offset, $per_page";
    $items = query_fetch($items_query);

    return [
        'items'        => $items,
        'page'         => $page,
        'total_pages'  => $total_pages,
        'total'        => $total,
        'has_previous' => $page > 1,
        'has_next'     => $page < $total_pages,
        'previous'     => $page - 1,
        'next'         => $page + 1,
    ];
}



//////////////////////// EMAIL FUNCTIONS ///////////////////////////////

// Send an email using PHPMailer
function send_mail(string $to, string $subject, string $body): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = EMAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_USERNAME;
        $mail->Password   = EMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = EMAIL_PORT;

        $mail->setFrom(EMAIL_USERNAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception) {
        if (APP_ENV === 'development') {
            error_log("Mail error: " . $mail->ErrorInfo);
        }
        return false;
    }
}
