# PHP Starter Boilerplate

A lightweight, framework-free PHP starter project using a functional (non-OOP) approach with proper tooling: Composer for dependencies, `.env` for secrets, PHPUnit for tests, and a route-based controller system.

No classes. No OOP. Just plain functions, clean structure, and the tooling that matters. Built for developers who want structure without the overhead of a full framework.

---

## Project Structure

```
project/
├── .env                  # Your secrets (DB password, SMTP, etc.) — never committed
├── .env.example          # Template showing what keys are needed — committed
├── .env.testing          # Test environment config (separate test database)
├── .gitignore            # Ignores .env, vendor/, uploads
├── .htaccess             # Routes all requests + blocks access to sensitive files
├── composer.json         # Dependencies and autoloading config
├── index.php             # Front controller — the single entry point
├── routes.php            # Route definitions — maps URLs to functions
├── phpunit.xml           # PHPUnit configuration
│
├── app/                  # Core application files
│   ├── config.php        # Loads .env, defines constants, starts sessions
│   ├── connect.php       # Database query functions (PDO)
│   ├── functions.php     # Auth, views, helpers, email
│   ├── init.php          # Bootstrap — loads Composer + config
│   └── migrate.php       # Database migration script (run once)
│
├── controllers/          # Grouped controller files
│   ├── index.php         # Loads all controller files
│   ├── landing.php       # Public pages: home, login, register, etc.
│   ├── account.php       # Logged-in user pages: dashboard, settings, etc.
│   └── admin.php         # Admin pages: dashboard, users, etc.
│
├── views/                # Templates (HTML with embedded PHP)
│   ├── 404.php
│   ├── landing/          # Public page templates
│   │   ├── layout.view.php
│   │   ├── home.view.php
│   │   └── login.view.php
│   ├── account/          # User dashboard templates
│   │   ├── layout.view.php
│   │   └── dashboard.view.php
│   └── admin/            # Admin panel templates
│       ├── layout.view.php
│       └── dashboard.view.php
│
├── tests/                # PHPUnit test files
│   ├── bootstrap.php     # Test environment setup
│   ├── HelpersTest.php
│   ├── DatabaseTest.php
│   └── AuthTest.php
│
└── assets/               # CSS, JS, images (publicly accessible)
```

---

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- MySQL / MariaDB
- Composer ([getcomposer.org](https://getcomposer.org))
- Apache with `mod_rewrite` enabled (XAMPP/LAMPP includes this)

### Setup

```bash
# 1. Clone or copy this boilerplate
cp -r boilerplate/ my-new-project/
cd my-new-project/

# 2. Create your .env file
cp .env.example .env
# Edit .env with your actual database credentials and app URL

# 3. Install PHP dependencies
composer install

# 4. Create the database and tables
php app/migrate.php

# 5. Run tests (optional, to verify everything works)
composer test
```

### Local Development (XAMPP/LAMPP)

If using XAMPP, place the project in `htdocs/`:

```
C:\xampp\htdocs\my-project\    (Windows)
/opt/lampp/htdocs/my-project/  (Linux)
```

Your `.env` for local dev:
```
APP_ENV=development
APP_URL=http://localhost/my-project
APP_DOMAIN=localhost
DB_HOST=localhost
DB_NAME=my_project_db
DB_USER=root
DB_PASS=
SESSION_SECURE=false
```

Visit: `http://localhost/my-project`

---

## How the Router Works

This is the core of the framework. Here's the full request lifecycle:

### Step 1: .htaccess catches every URL

When someone visits `yoursite.com/login`, Apache sees that `/login` is not a real file or folder, so `.htaccess` rewrites it to:

```
index.php?url=login
```

### Step 2: index.php loads everything

```php
require("./app/init.php");          // Loads Composer, .env, starts session
require("./controllers/index.php"); // Loads all controller functions
$routes = require("./routes.php");  // Loads the route map
```

### Step 3: The URL is matched against routes.php

The router builds a key like `"GET /login"` from the request, and looks it up in the routes array:

```php
// routes.php
return [
    'GET /login'  => 'landing_login',         // Show the login form
    'POST /login' => 'landing_login_submit',   // Process the login form
];
```

### Step 4: The matched function is called

The function `landing_login()` is defined in `controllers/landing.php`. It runs, builds a context array, and calls `landing_view('login', $context)` to render the template.

### Adding a new page

1. **Write the function** in the appropriate controller file:

```php
// controllers/landing.php
function landing_pricing(): void {
    $context = ['title' => 'Pricing'];
    landing_view('pricing', $context);
}
```

2. **Add the route** in `routes.php`:

```php
'GET /pricing' => 'landing_pricing',
```

3. **Create the view** at `views/landing/pricing.view.php`

That's it. Three steps.

### Adding a new controller

If your app grows and you need a separate controller (e.g., for an API or a special section):

1. Create `controllers/api.php` with your functions
2. Add `require_once __DIR__ . '/api.php';` to `controllers/index.php`
3. Add routes in `routes.php`

---

## How .env Works

### The problem it solves

Without `.env`, your database passwords and API keys live directly in your PHP code, which means they end up in Git. Anyone who sees your repo sees your passwords.

### How it works

1. You create a `.env` file with your secrets:
   ```
   DB_PASS=my_secret_password
   EMAIL_PASSWORD=smtp_password_here
   ```

2. The `vlucas/phpdotenv` library reads this file and puts the values into `$_ENV`

3. Your `config.php` reads from `$_ENV` instead of hardcoding:
   ```php
   define('DBPASS', $_ENV['DB_PASS']);
   ```

4. `.gitignore` excludes `.env` from Git — your secrets never leave your machine

### Where does .env live?

The config checks **two locations** in order:

1. **One level above the project** — e.g., `/home/user/.env` when your project is at `/home/user/public_html/`. This is the safest option on shared hosting because the file is completely outside the web-accessible directory.

2. **In the project root** — e.g., `/home/user/public_html/.env`. This works on VPS or local dev. The `.htaccess` file blocks any browser request to `.env`.

### Deploying to shared hosting

```
/home/youruser/
├── .env                    ← Put it here (option 1, safest)
└── public_html/
    ├── .env                ← Or here (option 2, protected by .htaccess)
    ├── index.php
    ├── .htaccess
    ├── app/
    ├── controllers/
    ├── views/
    └── vendor/
```

You create the `.env` file **once** on the server (via cPanel File Manager, SSH, or FTP). It stays there permanently. When you deploy new code, the `.env` is already in place.

### Deploying to a VPS

Same thing, but you can also set environment variables at the OS level or use a deployment tool to create the `.env` during CI/CD.

---

## How Migrations Work

### The confusion

You're used to creating tables in phpMyAdmin by clicking through a GUI. That works, but:
- You can't reproduce it on another machine
- You can't track what changed
- You can't automate it during deployment

### What migration actually means here

`migrate.php` is just a PHP script that runs `CREATE TABLE IF NOT EXISTS` SQL statements — the **exact same SQL** you would type into phpMyAdmin's SQL tab. There's nothing magical about it.

### How it connects to your database

Migration does **not** connect to phpMyAdmin. phpMyAdmin is just a web UI that talks to MySQL. The migration script talks to MySQL **directly** using PDO, the same way your app does:

```
Your App (PHP) ──PDO──> MySQL Server <──Web UI── phpMyAdmin
```

Both your app and phpMyAdmin connect to the same MySQL server. The migration script uses the exact same `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` from your `.env` file.

### Running migrations

```bash
# From the command line
php app/migrate.php

# Or via Composer
composer migrate
```

Output:
```
Running migrations...
  Table 1 OK
  Table 2 OK
  Table 3 OK
  Table 4 OK
Done. 4 tables checked/created.
```

### What it does

It runs `CREATE TABLE IF NOT EXISTS` for each table. The `IF NOT EXISTS` means:
- First run: creates the tables
- Second run: does nothing (tables already exist)
- Safe to run multiple times

### Adding a new table

Edit `app/migrate.php` and add your `CREATE TABLE` statement to the `$tables` array:

```php
$tables = [
    // ... existing tables ...

    "CREATE TABLE IF NOT EXISTS payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY (user_id)
    )",
];
```

Then run `php app/migrate.php` again.

### Can I still use phpMyAdmin?

Yes. phpMyAdmin and migrations are not mutually exclusive. You can:
- Use `migrate.php` for creating tables (reproducible, trackable)
- Use phpMyAdmin for browsing data, running ad-hoc queries, debugging

---

## How Testing Works

### What PHPUnit does

PHPUnit is a tool that runs your functions with known inputs and checks that the outputs are correct. It doesn't need a browser — it runs from the command line.

### Running tests

```bash
# Run all tests
composer test

# Run a specific test file
./vendor/bin/phpunit tests/HelpersTest.php

# Run a specific test
./vendor/bin/phpunit --filter test_e_escapes_html
```

### Test database

Tests use `.env.testing` which should point to a **separate test database**. This way tests can create, insert, and delete data without touching your development data.

```
DB_NAME=myproject_test_db    ← in .env.testing
DB_NAME=myproject_db         ← in .env (development)
```

Create the test database in phpMyAdmin (just the empty database), then run tests — they create their own tables.

### Writing a test

```php
// tests/MyFeatureTest.php
use PHPUnit\Framework\TestCase;

class MyFeatureTest extends TestCase
{
    public function test_something_works(): void
    {
        $result = my_function('input');
        $this->assertSame('expected output', $result);
    }
}
```

### What to test

Focus tests on **business logic** — the functions that calculate money, validate data, or make decisions. Don't test that a view renders HTML. Test that your split algorithm divides correctly, that your balance calculations don't lose cents, that your auth rejects bad passwords.

---

## How Composer Works

### What it does

1. **Dependency management** — `composer install` downloads libraries (PHPMailer, phpdotenv, PHPUnit) into the `vendor/` folder
2. **Autoloading** — generates `vendor/autoload.php` which automatically loads your files and library classes. No more `require` chains everywhere.

### Key commands

```bash
composer install              # Install all dependencies from composer.lock
composer require some/package # Add a new dependency
composer update               # Update dependencies to latest versions
composer test                 # Run PHPUnit (shortcut defined in composer.json)
composer migrate              # Run migrations (shortcut defined in composer.json)
```

### The vendor/ folder

`vendor/` contains all downloaded libraries. It's in `.gitignore` because:
- It's large (can be hundreds of MB)
- It's reproducible — `composer install` recreates it from `composer.lock`
- Every environment (dev, staging, production) runs `composer install` to get its own copy

On shared hosting without SSH access, you run `composer install` locally and upload the `vendor/` folder along with your code.

---

## Deployment Checklist

### Shared hosting (cPanel/DirectAdmin)

1. Run `composer install` locally
2. Upload all files to `public_html/` (including `vendor/`)
3. Create `.env` on the server (ideally one level above `public_html/`)
4. Run migration: access `yoursite.com` once (or SSH in and run `php app/migrate.php`)
5. Verify `.env` is blocked: visit `yoursite.com/.env` — should get 403 Forbidden

### VPS with CI/CD

1. Push code to GitHub
2. CI runs `composer install` and `composer test`
3. CD deploys via `rsync` over SSH
4. Post-deploy: `composer install --no-dev` and `php app/migrate.php` on the server
5. `.env` is already on the server from initial setup

---

## License

MIT — use this boilerplate however you like.
