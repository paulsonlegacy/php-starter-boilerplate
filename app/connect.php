<?php

/*
 * Database layer — query functions using PDO
 *
 * This file provides all database interaction through plain functions.
 * Every function uses PDO with prepared statements to prevent SQL injection.
 *
 * Functions in this file:
 *   get_connection()      — returns a shared PDO instance (one per request)
 *   query_db()            — parameterized queries (SELECT, INSERT, UPDATE, DELETE)
 *   query_fetch()         — read-only SELECT for queries with no user input
 *   query_return_id()     — insert a row and get back its auto-increment ID
 *   query_transaction()   — wrap multiple writes in an atomic transaction
 */


/**
 * Get a shared PDO connection (one connection per request).
 *
 * Uses a static variable so the connection is created once and reused
 * across all query calls in the same request. This is more efficient
 * than creating a new connection for every query.
 *
 * @return PDO The shared database connection
 *
 * @example
 *   $con = get_connection();
 *   // Rarely needed directly — the query functions call this internally
 */
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


/**
 * Execute a parameterized query — use for all queries that include user input.
 *
 * This is the main query function. It uses prepared statements with named
 * parameters (:name) to safely bind user-provided values. Works for
 * SELECT, INSERT, UPDATE, and DELETE.
 *
 * @param string   $query  SQL query with named parameters (e.g., :email, :id)
 * @param array    $data   Associative array of parameter values to bind
 * @param PDO|null $con    Optional PDO connection (pass one inside a transaction)
 * @return array   Array of rows (associative arrays), or empty array if no results
 *
 * @example Selecting with a parameter:
 *   $users = query_db("SELECT * FROM users WHERE email = :email", ['email' => $email]);
 *
 * @example Inserting a row:
 *   query_db(
 *       "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)",
 *       ['username' => 'john', 'email' => 'john@test.com', 'password' => $hashed]
 *   );
 *
 * @example Updating a row:
 *   query_db(
 *       "UPDATE users SET email = :email WHERE id = :id LIMIT 1",
 *       ['email' => 'new@test.com', 'id' => 5]
 *   );
 *
 * @example Deleting a row:
 *   query_db("DELETE FROM notifications WHERE id = :id", ['id' => $notification_id]);
 */
function query_db(string $query, array $data = [], ?PDO $con = null): array {
    if ($con === null) {
        $con = get_connection();
    }

    $statement = $con->prepare($query);
    $statement->execute($data);

    $result = $statement->fetchAll();
    return is_array($result) && !empty($result) ? $result : [];
}


/**
 * Execute a read-only SELECT query with no user input.
 *
 * Use this ONLY for queries where the entire SQL string is hardcoded —
 * no variables, no user input, no dynamic values. This is a convenience
 * shortcut for simple fetches like loading settings or counting rows.
 *
 * WARNING: Never concatenate user input into the query string.
 *          Use query_db() with parameters instead.
 *
 * @param string   $query  A complete SQL SELECT query with no parameters
 * @param PDO|null $con    Optional PDO connection (for use inside a transaction)
 * @return array   Array of rows (associative arrays), or empty array if no results
 *
 * @example Fetching app settings:
 *   $settings = query_fetch("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
 *   $setting = $settings[0] ?? null;
 *
 * @example Counting rows:
 *   $counts = query_fetch("SELECT COUNT(*) AS total FROM users");
 *   $total = $counts[0]['total'];
 *
 * @example WRONG — never do this:
 *   query_fetch("SELECT * FROM users WHERE id = $user_id");  // SQL injection risk!
 *   // Use query_db() with parameters instead:
 *   query_db("SELECT * FROM users WHERE id = :id", ['id' => $user_id]);
 */
function query_fetch(string $query, ?PDO $con = null): array {
    if ($con === null) {
        $con = get_connection();
    }

    $statement = $con->prepare($query);
    $statement->execute();

    $result = $statement->fetchAll();
    return is_array($result) && !empty($result) ? $result : [];
}


/**
 * Insert a row and return its auto-increment ID.
 *
 * Works the same as query_db() but returns the ID of the newly inserted
 * row instead of the result set. Useful when you need to reference
 * the new row immediately (e.g., creating a user then creating related records).
 *
 * @param string   $query  SQL INSERT query with named parameters
 * @param array    $data   Associative array of parameter values to bind
 * @param PDO|null $con    Optional PDO connection (for use inside a transaction)
 * @return string|null     The new row's ID as a string, or null on failure
 *
 * @example Creating a user and getting the ID:
 *   $user_id = query_return_id(
 *       "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)",
 *       ['username' => 'jane', 'email' => 'jane@test.com', 'password' => $hashed]
 *   );
 *   // $user_id is now "42" (string) or null if the insert failed
 *
 * @example Using the ID for a related insert:
 *   $user_id = query_return_id($insert_query, $user_data);
 *   if ($user_id) {
 *       query_db("INSERT INTO profiles (user_id, bio) VALUES (:uid, :bio)",
 *           ['uid' => $user_id, 'bio' => 'Hello!']);
 *   }
 */
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


/**
 * Run multiple database writes inside an atomic transaction.
 *
 * If any query inside the callback throws an exception, ALL queries
 * are rolled back — nothing is saved. This prevents partial writes
 * that would leave your data in an inconsistent state.
 *
 * The callback receives the PDO connection as its argument. Pass this
 * connection to query_db() or query_return_id() so all writes share
 * the same transaction.
 *
 * @param callable $callback  Function that receives a PDO connection and performs writes
 * @return void
 * @throws Exception  Re-throws any exception from the callback after rolling back
 *
 * @example Transferring money between two users (both writes succeed or neither does):
 *   query_transaction(function (PDO $con) use ($from_id, $to_id, $amount) {
 *       query_db(
 *           "UPDATE users SET balance = balance - :amount WHERE id = :id",
 *           ['amount' => $amount, 'id' => $from_id],
 *           $con
 *       );
 *       query_db(
 *           "UPDATE users SET balance = balance + :amount WHERE id = :id",
 *           ['amount' => $amount, 'id' => $to_id],
 *           $con
 *       );
 *   });
 *
 * @example If the second write fails, the first is rolled back:
 *   query_transaction(function (PDO $con) {
 *       query_db("INSERT INTO orders ...", [...], $con);  // This gets rolled back
 *       query_db("INSERT INTO bad_table ...", [...], $con);  // This throws
 *   });
 *   // Neither row is inserted
 */
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
