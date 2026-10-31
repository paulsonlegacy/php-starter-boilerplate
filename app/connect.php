<?php

/*
 * Database layer — query functions using PDO
 *
 * query_db()          — parameterized queries (INSERT, UPDATE, DELETE, SELECT)
 * query_fetch()       — read-only SELECT with no user input in the query
 * query_return_id()   — insert and return the new row's ID
 * query_transaction() — wrap multiple writes in an atomic transaction
 */


// Get a shared PDO connection (created once per request)
function get_connection(): PDO {
    static $con = null;
    if ($con === null) {
        $dsn = "mysql:host=" . DBHOST . ";dbname=" . DBNAME . ";charset=utf8mb4";
        $con = new PDO($dsn, DBUSER, DBPASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $con;
}


// Parameterized query — use for all queries that include user input
// Works for SELECT, INSERT, UPDATE, DELETE
function query_db(string $query, array $data = [], ?PDO $con = null): array {
    if ($con === null) {
        $con = get_connection();
    }

    $statement = $con->prepare($query);
    $statement->execute($data);

    $result = $statement->fetchAll();
    return is_array($result) && !empty($result) ? $result : [];
}


// Read-only fetch — use ONLY for queries with NO user input
// Example: query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1")
// WARNING: Never put user input into the query string, use query_db() instead
function query_fetch(string $query, ?PDO $con = null): array {
    if ($con === null) {
        $con = get_connection();
    }

    $statement = $con->prepare($query);
    $statement->execute();

    $result = $statement->fetchAll();
    return is_array($result) && !empty($result) ? $result : [];
}


// Insert a row and return its auto-increment ID
function query_return_id(string $query, array $data = [], ?PDO $con = null): string|null {
    if ($con === null) {
        $con = get_connection();
    }

    try {
        $statement = $con->prepare($query);
        $statement->execute($data);
        return $con->lastInsertId();
    } catch (Exception) {
        return null;
    }
}


// Run multiple writes atomically — if anything fails, everything rolls back
function query_transaction(callable $callback): void {
    $con = get_connection();

    $con->beginTransaction();
    try {
        $callback($con);
        $con->commit();
    } catch (Exception $e) {
        $con->rollBack();
        throw $e;
    }
}
