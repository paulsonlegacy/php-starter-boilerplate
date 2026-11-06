<?php

/*
 * Database migration — run once to create tables
 * Usage: php app/migrate.php           (create tables if they don't exist)
 *        php app/migrate.php --fresh   (drop all tables and recreate — dev only!)
 * Or:    composer migrate
 *
 * Uses CREATE TABLE IF NOT EXISTS so it's safe to run multiple times.
 *
 * SCHEMA CHANGES DURING DEVELOPMENT:
 *   This script only creates tables — it cannot add, remove, or rename columns.
 *   If you change a column here, the database won't know about it.
 *
 *   During development (no real data): run  php app/migrate.php --fresh
 *   This drops every table and rebuilds from scratch. All data is lost.
 *
 *   In production (real data you must keep): write ALTER TABLE statements.
 *   Example: ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER email;
 *   Run these manually or add them to a separate migration script.
 */

require_once __DIR__ . '/init.php';

$con = get_connection();
$is_fresh = in_array('--fresh', $argv ?? []);

$table_names = ['settings', 'users', 'notifications', 'messages'];

if ($is_fresh) {
    if (APP_ENV === 'production') {
        echo "ERROR: --fresh is disabled in production. Use ALTER TABLE for schema changes.\n";
        exit(1);
    }

    echo "Dropping all tables...\n";
    $con->exec("SET FOREIGN_KEY_CHECKS = 0");
    foreach (array_reverse($table_names) as $name) {
        $con->exec("DROP TABLE IF EXISTS $name");
        echo "  Dropped $name\n";
    }
    $con->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n";
}

echo "Running migrations...\n";

$tables = [

    "CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(20) NOT NULL,
        domain VARCHAR(30) NOT NULL,
        email VARCHAR(60) NULL,
        address VARCHAR(60) NULL
    )",

    "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        fullname VARCHAR(100) NOT NULL,
        username VARCHAR(30) NOT NULL,
        email VARCHAR(60) NOT NULL,
        phone VARCHAR(30) NULL,
        password VARCHAR(255) NOT NULL,
        balance DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
        language VARCHAR(10) NOT NULL DEFAULT 'en',
        timezone VARCHAR(60) NOT NULL DEFAULT 'UTC',
        theme VARCHAR(10) NOT NULL DEFAULT 'light',
        is_admin TINYINT NOT NULL DEFAULT 0,
        is_blocked TINYINT NOT NULL DEFAULT 0,
        date_joined DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME NULL,
        token_hash VARCHAR(255) NULL,
        token_expires DATETIME NULL,

        UNIQUE KEY (username),
        UNIQUE KEY (email),
        UNIQUE KEY (token_hash)
    )",

    "CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,

        KEY (user_id)
    )",

    "CREATE TABLE IF NOT EXISTS messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(60) NOT NULL,
        email VARCHAR(60) NOT NULL,
        subject VARCHAR(60) NULL,
        message TEXT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP
    )",

];

foreach ($tables as $i => $sql) {
    $con->exec($sql);
    echo "  Table " . ($i + 1) . " OK\n";
}

echo "Done. " . count($tables) . " tables checked/created.\n";
