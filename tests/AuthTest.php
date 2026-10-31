<?php

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        if (!self::$migrated) {
            $con = get_connection();
            $con->exec("CREATE TABLE IF NOT EXISTS users (
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
                UNIQUE KEY (email)
            )");
            self::$migrated = true;
        }

        $con = get_connection();
        $con->exec("DELETE FROM users");
    }

    private function createTestUser(string $email = 'test@example.com', string $password = 'secret123'): int
    {
        $id = query_return_id(
            "INSERT INTO users (fullname, username, email, password, timezone)
             VALUES (:fullname, :username, :email, :password, :timezone)",
            [
                'fullname' => 'Test User',
                'username' => 'testuser',
                'email'    => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'timezone' => 'Africa/Lagos',
            ]
        );
        return (int)$id;
    }

    public function test_authenticate_user_with_correct_password(): void
    {
        $this->createTestUser('auth@test.com', 'mypassword');

        $user = authenticate_user('auth@test.com', 'mypassword');

        $this->assertIsArray($user);
        $this->assertSame('auth@test.com', $user['email']);
    }

    public function test_authenticate_user_with_wrong_password(): void
    {
        $this->createTestUser('auth@test.com', 'mypassword');

        $user = authenticate_user('auth@test.com', 'wrongpassword');

        $this->assertFalse($user);
    }

    public function test_authenticate_user_with_nonexistent_email(): void
    {
        $user = authenticate_user('nobody@test.com', 'anything');

        $this->assertFalse($user);
    }

    public function test_email_exists(): void
    {
        $this->createTestUser('exists@test.com');

        $this->assertTrue(email_exists('exists@test.com'));
        $this->assertFalse(email_exists('nope@test.com'));
    }

    public function test_username_exists(): void
    {
        $this->createTestUser();

        $this->assertTrue(username_exists('testuser'));
        $this->assertFalse(username_exists('nobody'));
    }

    public static function tearDownAfterClass(): void
    {
        $con = get_connection();
        $con->exec("DROP TABLE IF EXISTS users");
    }
}
