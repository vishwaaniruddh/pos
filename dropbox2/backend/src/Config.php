<?php
declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;
use RuntimeException;

/**
 * Config — loads .env and exposes typed getters.
 * Call Config::load() once in the front controller / worker entry point.
 */
class Config
{
    private static bool $loaded = false;

    // ── Bootstrap ────────────────────────────────────────────────────────────

    public static function load(string $envDir): void
    {
        if (self::$loaded) {
            return;
        }

        $dotenv = Dotenv::createImmutable($envDir);
        $dotenv->load();

        // Validate required keys
        $dotenv->required([
            'APP_ENCRYPTION_KEY',
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
            'DROPBOX_APP_KEY',
            'DROPBOX_APP_SECRET',
            'DROPBOX_REDIRECT_URI',
            'INTERNAL_API_KEY',
        ])->notEmpty();

        $key = $_ENV['APP_ENCRYPTION_KEY'];
        if (strlen($key) !== 32) {
            throw new RuntimeException('APP_ENCRYPTION_KEY must be exactly 32 characters.');
        }

        self::$loaded = true;
    }

    // ── Getters ──────────────────────────────────────────────────────────────

    /**
     * Get an environment variable or default.
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return isset($_ENV[$key]) ? (int) $_ENV[$key] : $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        if (!isset($_ENV[$key])) {
            return $default;
        }
        return in_array(strtolower((string) $_ENV[$key]), ['true', '1', 'yes'], true);
    }

    // ── Convenience shortcuts ────────────────────────────────────────────────

    public static function isProductionEnvironment(): bool
    {
        // Local XAMPP/Windows check
        if (str_contains(__DIR__, 'xampp') || PHP_OS_FAMILY === 'Windows') {
            return false;
        }

        // CLI on production server
        if (php_sapi_name() === 'cli') {
            // Assume production if not on Windows/XAMPP
            return true;
        }

        // Web requests
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (in_array($host, ['localhost', '127.0.0.1', '::1']) || str_starts_with($host, 'localhost:')) {
            return false;
        }

        return true;
    }

    public static function dbHost(): string { 
        return self::isProductionEnvironment() ? 'localhost' : self::get('DB_HOST', '127.0.0.1'); 
    }
    public static function dbPort(): int    { 
        return self::getInt('DB_PORT', 3306); 
    }
    public static function dbName(): string { 
        return self::isProductionEnvironment() ? 'u464193275_dropbox_copier' : self::get('DB_NAME'); 
    }
    public static function dbUser(): string { 
        return self::isProductionEnvironment() ? 'u464193275_dropbox_copier' : self::get('DB_USER'); 
    }
    public static function dbPass(): string { 
        return self::isProductionEnvironment() ? 'AVav@@2026' : self::get('DB_PASS', ''); 
    }

    public static function encryptionKey(): string { return self::get('APP_ENCRYPTION_KEY'); }
    public static function internalApiKey(): string { return self::get('INTERNAL_API_KEY'); }

    public static function dropboxAppKey(): string    { return self::get('DROPBOX_APP_KEY'); }
    public static function dropboxAppSecret(): string { return self::get('DROPBOX_APP_SECRET'); }
    public static function dropboxRedirectUri(): string { 
        if (self::isProductionEnvironment()) {
            return 'https://srishringarr.com/pos/dropbox/api/auth/dropbox/callback';
        }
        return self::get('DROPBOX_REDIRECT_URI'); 
    }

    public static function appUrl(): string { 
        if (self::isProductionEnvironment()) {
            return 'https://srishringarr.com/pos/dropbox';
        }
        return rtrim(self::get('APP_URL', 'http://localhost'), '/'); 
    }
    public static function appEnv(): string { return self::get('APP_ENV', 'production'); }
    public static function isDev(): bool    { return self::appEnv() === 'development'; }
}
