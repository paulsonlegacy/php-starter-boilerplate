<?php

/*
 * Database migration — run once to create tables
 * Usage: php app/migrate.php
 * Or:    composer migrate
 *
 * This replaces the create_tables() call that used to run on every request.
 * Run this once after deployment, or after changing the schema.
 */

require_once __DIR__ . '/init.php';

echo "Running migrations...\n";

$con = get_connection();

// Create tables — add your CREATE TABLE statements here
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
