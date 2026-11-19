<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Database — PDO singleton with helper methods.
 */
class Database
{
    private static ?PDO $pdo = null;

    // ── Connection ───────────────────────────────────────────────────────────

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::dbHost(),
            Config::dbPort(),
            Config::dbName()
        );

        try {
            self::$pdo = new PDO($dsn, Config::dbUser(), Config::dbPass(), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                1002                         => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci", // 1002 is PDO::MYSQL_ATTR_INIT_COMMAND (deprecated in PHP 8.5)
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        return self::connect();
    }

    // ── Query Helpers ────────────────────────────────────────────────────────

    /**
     * Execute a query and return all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::prepare($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return a single row.
     */
    public static function fetchOne(string $sql, array $params = []): array|false
    {
        $stmt = self::prepare($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Execute an INSERT and return the last insert ID.
     */
    public static function insert(string $sql, array $params = []): string|false
    {
        self::prepare($sql, $params);
        return self::pdo()->lastInsertId();
    }

    /**
     * Execute an UPDATE/DELETE and return affected rows.
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::prepare($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Prepare and execute a statement.
     */
    private static function prepare(string $sql, array $params): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get the last inserted ID.
     */
    public static function lastInsertId(): string
    {
        return self::pdo()->lastInsertId();
    }

    /**
     * Begin a transaction.
     */
    public static function beginTransaction(): void
    {
        self::pdo()->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    public static function commit(): void
    {
        self::pdo()->commit();
    }

    /**
     * Rollback a transaction.
     */
    public static function rollback(): void
    {
        self::pdo()->rollBack();
    }
}
