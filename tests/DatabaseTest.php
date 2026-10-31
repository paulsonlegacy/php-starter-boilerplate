<?php

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        if (!self::$migrated) {
            $con = get_connection();
            $con->exec("CREATE TABLE IF NOT EXISTS test_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0
            )");
            self::$migrated = true;
        }

        // Clean up before each test
        $con = get_connection();
        $con->exec("DELETE FROM test_items");
    }

    public function test_query_db_insert_and_select(): void
    {
        query_db(
            "INSERT INTO test_items (name, amount) VALUES (:name, :amount)",
            ['name' => 'Widget', 'amount' => 99.99]
        );

        $items = query_db(
            "SELECT * FROM test_items WHERE name = :name",
            ['name' => 'Widget']
        );

        $this->assertCount(1, $items);
        $this->assertSame('Widget', $items[0]['name']);
        $this->assertSame('99.99', $items[0]['amount']);
    }

    public function test_query_return_id(): void
    {
        $id = query_return_id(
            "INSERT INTO test_items (name, amount) VALUES (:name, :amount)",
            ['name' => 'Gadget', 'amount' => 50.00]
        );

        $this->assertNotNull($id);
        $this->assertGreaterThan(0, (int)$id);
    }

    public function test_query_fetch_returns_empty_for_no_results(): void
    {
        $result = query_fetch("SELECT * FROM test_items WHERE id = 999999");
        $this->assertSame([], $result);
    }

    public function test_query_transaction_commits(): void
    {
        query_transaction(function (PDO $con) {
            query_db(
                "INSERT INTO test_items (name, amount) VALUES (:name, :amount)",
                ['name' => 'Item A', 'amount' => 10.00],
                $con
            );
            query_db(
                "INSERT INTO test_items (name, amount) VALUES (:name, :amount)",
                ['name' => 'Item B', 'amount' => 20.00],
                $con
            );
        });

        $items = query_fetch("SELECT * FROM test_items ORDER BY name");
        $this->assertCount(2, $items);
    }

    public function test_query_transaction_rolls_back_on_error(): void
    {
        try {
            query_transaction(function (PDO $con) {
                query_db(
                    "INSERT INTO test_items (name, amount) VALUES (:name, :amount)",
                    ['name' => 'Should Not Exist', 'amount' => 10.00],
                    $con
                );
                throw new Exception("Simulated failure");
            });
        } catch (Exception) {
            // Expected
        }

        $items = query_fetch("SELECT * FROM test_items WHERE name = 'Should Not Exist'");
        $this->assertSame([], $items);
    }

    public static function tearDownAfterClass(): void
    {
        $con = get_connection();
        $con->exec("DROP TABLE IF EXISTS test_items");
    }
}
