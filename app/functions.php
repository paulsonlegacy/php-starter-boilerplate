<?php

/*
 * Shared functions — security, auth, views, helpers, email
 *
 * This file contains all reusable functions organized into sections.
 * Every function has a PHPDoc block with parameters, return types,
 * and usage examples — follow this pattern when adding new functions.
 *
 * Sections:
 *   SECURITY    — CSRF tokens, output escaping
 *   AUTH        — authentication, authorization, user checks
 *   VIEWS       — template rendering
 *   HELPERS     — redirect, JSON response, date formatting, file uploads, pagination
 *   EMAIL       — sending emails via PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



//////////////////////// SECURITY FUNCTIONS ///////////////////////////////


/**
 * Generate a CSRF token and store it in the session.
 *
 * Call this in your controller before rendering a form. Pass the returned
 * token to the view and include it as a hidden input field.
 *
 * @return string The generated CSRF token (64 hex characters)
 *
 * @example In a controller:
 *   $csrf_token = generate_csrf_token();
 *   $context = ['csrf_token' => $csrf_token];
 *   landing_view('login', $context);
 *
 * @example In a view (the hidden input):
 *   <input type="hidden" name="csrf_token" value="<?= e($context['csrf_token']) ?>">
 */
function generate_csrf_token(): string {
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
    return $csrf_token;
}


/**
 * Verify that the submitted CSRF token matches the session token.
 *
 * Call this at the top of any POST handler before processing form data.
 * Uses hash_equals() for timing-safe comparison to prevent timing attacks.
 *
 * @return bool True if the token is valid, false otherwise
 *
 * @example In a controller POST handler:
 *   if (!verify_csrf_token()) {
 *       redirect(ROOT . '/login', "Invalid request", 'danger');
 *   }
 */
function verify_csrf_token(): bool {
    return isset($_POST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}


/**
 * Escape a value for safe HTML output (prevents XSS attacks).
 *
 * Use this in views whenever you display dynamic data. It converts
 * characters like <, >, ", ' into their HTML entity equivalents so
 * they render as text instead of being interpreted as HTML/JavaScript.
 *
 * @param mixed $value The value to escape (will be cast to string)
 * @return string The HTML-safe string
 *
 * @example In a view:
 *   <h1>Welcome, <?= e($user['fullname']) ?></h1>
 *   <p>Email: <?= e($user['email']) ?></p>
 *
 * @example What it prevents:
 *   e('<script>alert("xss")</script>')
 *   // Returns: &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;
 *   // Renders as harmless text in the browser
 *
 * @example Names with apostrophes work correctly:
 *   e("O'Brien")  // Returns: O&#039;Brien
 */
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}



//////////////////////// AUTH FUNCTIONS ///////////////////////////////


/**
 * Check if the current user is authenticated (logged in).
 *
 * Reads the user ID from the session and fetches the full user row
 * from the database. Does NOT redirect — just returns data or empty.
 *
 * @return array The user's database row as an associative array, or empty array if not logged in
 *
 * @example
 *   $user = is_user_authenticated();
 *   if (!empty($user)) {
 *       echo "Logged in as " . $user['fullname'];
 *   }
 */
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


/**
 * Require the user to be logged in — redirect to login page if not.
 *
 * Call this at the top of any controller function that requires authentication.
 * Also blocks users who have been marked as blocked (is_blocked = 1).
 *
 * @return array The authenticated user's database row
 *
 * @example In a controller:
 *   function account_dashboard(): void {
 *       $user = logged_in();  // Redirects away if not authenticated
 *       // ... rest of the handler, $user is guaranteed to exist
 *   }
 */
function logged_in(): array {
    $user = is_user_authenticated();
    if (empty($user) || $user['is_blocked'] == 1) {
        redirect(ROOT . "/login", "Please sign in", "danger");
    }
    return $user;
}


/**
 * Require the user to be an admin — redirect to login if not.
 *
 * Call this at the top of any admin controller function.
 * Checks both that the user is logged in AND has is_admin = 1.
 *
 * @return array The authenticated admin user's database row
 *
 * @example In an admin controller:
 *   function admin_users(): void {
 *       $user = admin_logged_in();  // Redirects away if not admin
 *       // ... admin-only logic
 *   }
 */
function admin_logged_in(): array {
    $user = is_user_authenticated();
    if (empty($user) || $user['is_admin'] == 0) {
        redirect(ROOT . "/admin/login");
    }
    return $user;
}


/**
 * Authenticate a user by email and password.
 *
 * Looks up the user by email, then verifies the password against
 * the stored hash using password_verify().
 *
 * @param string $email    The user's email address
 * @param string $password The plaintext password to verify
 * @return array|false     The user's database row if valid, false if not
 *
 * @example
 *   $user = authenticate_user('john@test.com', 'mypassword');
 *   if ($user) {
 *       $_SESSION['user'] = $user;
 *       redirect(ROOT . '/account/dashboard');
 *   } else {
 *       redirect(ROOT . '/login', 'Invalid credentials', 'danger');
 *   }
 */
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


/**
 * Check if an email address already exists in the users table.
 *
 * @param string $email The email to check
 * @return bool True if the email is already registered
 *
 * @example During registration:
 *   if (email_exists($email)) {
 *       redirect(ROOT . '/register', 'Email already taken', 'danger');
 *   }
 */
function email_exists(string $email): bool {
    $users = query_db(
        "SELECT id FROM users WHERE email = :email LIMIT 1",
        ['email' => $email]
    );
    return !empty($users);
}


/**
 * Check if a username already exists in the users table.
 *
 * @param string $username The username to check
 * @return bool True if the username is already taken
 *
 * @example During registration:
 *   if (username_exists($username)) {
 *       redirect(ROOT . '/register', 'Username already taken', 'danger');
 *   }
 */
function username_exists(string $username): bool {
    $users = query_db(
        "SELECT id FROM users WHERE username = :username LIMIT 1",
        ['username' => $username]
    );
    return !empty($users);
}



//////////////////////// VIEW FUNCTIONS ///////////////////////////////


/**
 * Render a landing (public) page view inside its layout.
 *
 * Loads the landing layout template, which includes the specific page
 * template ($name) inside it. The $context array makes variables
 * available to both the layout and the page template.
 *
 * @param string $name    The view name (matches views/landing/{name}.view.php)
 * @param array  $context Variables to pass to the template
 * @return void
 *
 * @example
 *   landing_view('home', ['title' => 'Welcome', 'setting' => $setting]);
 *   // Loads: views/landing/layout.view.php, which includes views/landing/home.view.php
 */
function landing_view(string $name, array $context = []): void {
    require(APP_PATH . "views/landing/layout.view.php");
    unset_message();
}


/**
 * Render an account (logged-in user) page view inside its layout.
 *
 * @param string $name    The view name (matches views/account/{name}.view.php)
 * @param array  $context Variables to pass to the template
 * @return void
 *
 * @example
 *   account_view('dashboard', ['title' => 'Dashboard', 'user' => $user]);
 */
function account_view(string $name, array $context = []): void {
    require(APP_PATH . "views/account/layout.view.php");
    unset_message();
}


/**
 * Render an admin page view inside its layout.
 *
 * @param string $name    The view name (matches views/admin/{name}.view.php)
 * @param array  $context Variables to pass to the template
 * @return void
 *
 * @example
 *   admin_view('users', ['title' => 'Users', 'users' => $users]);
 */
function admin_view(string $name, array $context = []): void {
    require(APP_PATH . "views/admin/layout.view.php");
    unset_message();
}


/**
 * Clear flash messages from the session after a view has been rendered.
 *
 * Called automatically by the view functions — you shouldn't need
 * to call this directly.
 *
 * @return void
 */
function unset_message(): void {
    unset($_SESSION['message'], $_SESSION['message_tag']);
}



//////////////////////// HELPER FUNCTIONS ///////////////////////////////


/**
 * Redirect the user to another URL with an optional flash message.
 *
 * Flash messages are stored in the session and displayed once on the
 * next page load (the view layout checks for them).
 *
 * @param string $url     The URL to redirect to (use ROOT . '/path' for internal links)
 * @param string $message Optional flash message to display after redirect
 * @param string $tag     Message type: 'info', 'success', 'warning', or 'danger'
 * @return never          This function stops execution (calls exit)
 *
 * @example Simple redirect:
 *   redirect(ROOT . '/account/dashboard');
 *
 * @example Redirect with a success message:
 *   redirect(ROOT . '/login', 'Account created! Please sign in.', 'success');
 *
 * @example Redirect with an error:
 *   redirect(ROOT . '/register', 'Email already taken', 'danger');
 */
function redirect(string $url, string $message = '', string $tag = 'info'): never {
    if ($message !== '') {
        $_SESSION['message'] = $message;
        $_SESSION['message_tag'] = $tag;
    }
    header("Location: $url");
    exit;
}


/**
 * Send a JSON response and stop execution.
 *
 * Sets the Content-Type header to application/json and the HTTP status code.
 * Use this for API endpoints or AJAX handlers.
 *
 * @param mixed $data   The data to encode as JSON (array, object, string, etc.)
 * @param int   $status HTTP status code (200 = OK, 400 = Bad Request, 404 = Not Found, etc.)
 * @return never        This function stops execution (calls exit)
 *
 * @example Success response:
 *   json_response(['status' => 'success', 'user_id' => 42]);
 *
 * @example Error response with 400 status:
 *   json_response(['status' => 'error', 'message' => 'Invalid email'], 400);
 */
function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}


/**
 * Format a date string for display (e.g., "15 Jan, 2025").
 *
 * @param string $date A date string parsable by strtotime() (e.g., "2025-01-15")
 * @return string The formatted date
 *
 * @example
 *   format_date('2025-01-15');          // "15 Jan, 2025"
 *   format_date('2025-12-31 23:59:59'); // "31 Dec, 2025"
 */
function format_date(string $date): string {
    return date("d M, Y", strtotime($date));
}


/**
 * Format a datetime string for display (e.g., "15 Jan, 2025  02:30 PM").
 *
 * @param string $date A datetime string parsable by strtotime()
 * @return string The formatted datetime
 *
 * @example
 *   format_datetime('2025-01-15 14:30:00'); // "15 Jan, 2025  02:30 PM"
 */
function format_datetime(string $date): string {
    return date("d M, Y  H:i A", strtotime($date));
}


/**
 * Format a datetime string in a specific timezone.
 *
 * Converts the datetime from the server's timezone to the specified
 * timezone before formatting. Useful for showing times in the user's
 * local timezone.
 *
 * @param string $datetime A datetime string (e.g., "2025-01-15 14:30:00")
 * @param string $timezone A valid PHP timezone (e.g., "Africa/Lagos", "America/New_York")
 * @return string The formatted datetime in the specified timezone
 *
 * @example
 *   format_datetime_timezone('2025-01-15 14:30:00', 'Africa/Lagos');
 *   // "15 Jan, 2025  03:30 PM" (WAT is UTC+1)
 */
function format_datetime_timezone(string $datetime, string $timezone): string {
    $dt = new DateTime($datetime);
    $dt->setTimezone(new DateTimeZone($timezone));
    return $dt->format('d M, Y  H:i A');
}


/**
 * Generate a unique alphanumeric ID (uppercase letters and digits).
 *
 * Uses cryptographically secure random_int() for each character.
 * Suitable for transaction IDs, reference numbers, etc.
 *
 * @param int $length The length of the ID to generate (default: 10)
 * @return string The generated ID (e.g., "A3B7X9K2M1")
 *
 * @example
 *   $deposit_id = generate_unique_id();       // "A3B7X9K2M1" (10 chars)
 *   $short_id   = generate_unique_id(6);      // "K9M2X1" (6 chars)
 *   $long_id    = generate_unique_id(12);     // "A3B7X9K2M1P4" (12 chars)
 */
function generate_unique_id(int $length = 10): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}


/**
 * Validate and upload a single image file.
 *
 * Checks for upload errors, file size (max 5MB), and allowed extensions
 * (jpeg, jpg, png). Generates a unique filename to prevent collisions.
 *
 * @param array  $file   The file array from $_FILES (e.g., $_FILES['avatar'])
 * @param string $folder The subfolder inside media/ to upload to (e.g., "users", "deposits")
 * @return array Status array with 'status' ('success' or 'failed'), 'message', and 'file_name' on success
 *
 * @example
 *   if (isset($_FILES['proof'])) {
 *       $result = upload_image($_FILES['proof'], 'deposits');
 *       if ($result['status'] === 'success') {
 *           $filename = $result['file_name'];
 *           // Save $filename to the database
 *       } else {
 *           redirect(ROOT . '/upload', $result['message'], 'danger');
 *       }
 *   }
 */
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


/**
 * Paginate query results with page navigation metadata.
 *
 * Takes a base SQL query and splits the results into pages.
 * Reads the current page from $_GET['page'] (defaults to 1).
 *
 * @param string $query    The base SQL query (without LIMIT — it's added automatically)
 * @param int    $per_page Number of items per page
 * @return array Associative array with keys:
 *               'items'        => array of rows for the current page
 *               'page'         => current page number
 *               'total_pages'  => total number of pages
 *               'total'        => total number of items across all pages
 *               'has_previous' => bool, true if there's a previous page
 *               'has_next'     => bool, true if there's a next page
 *               'previous'     => previous page number
 *               'next'         => next page number
 *
 * @example In a controller:
 *   $result = paginate("SELECT * FROM users ORDER BY id DESC", 10);
 *   $context = [
 *       'users'      => $result['items'],
 *       'pagination' => $result,
 *   ];
 *   admin_view('users', $context);
 *
 * @example In a view (pagination links):
 *   <?php if ($context['pagination']['has_previous']): ?>
 *       <a href="?page=<?= $context['pagination']['previous'] ?>">Previous</a>
 *   <?php endif; ?>
 *   <?php if ($context['pagination']['has_next']): ?>
 *       <a href="?page=<?= $context['pagination']['next'] ?>">Next</a>
 *   <?php endif; ?>
 */
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


/**
 * Send an HTML email using PHPMailer over SMTP.
 *
 * Uses the SMTP credentials defined in .env (EMAIL_HOST, EMAIL_USERNAME,
 * EMAIL_PASSWORD, EMAIL_PORT). Logs errors in development mode.
 *
 * @param string $to      Recipient email address
 * @param string $subject Email subject line
 * @param string $body    HTML email body
 * @return bool True if the email was sent, false on failure
 *
 * @example Sending a welcome email:
 *   $body = "<h1>Welcome!</h1><p>Thanks for signing up.</p>";
 *   $sent = send_mail($user['email'], 'Welcome to Our App', $body);
 *   if (!$sent) {
 *       error_log("Failed to send welcome email to " . $user['email']);
 *   }
 *
 * @example Using an HTML template:
 *   ob_start();
 *   $name = $user['fullname'];
 *   require(VIEW_PATH . 'emails/welcome.html');
 *   $body = ob_get_clean();
 *   send_mail($user['email'], 'Welcome!', $body);
 */
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
        if (APP_DEBUG) {
            error_log("Mail error: " . $mail->ErrorInfo);
        }
        return false;
    }
}
