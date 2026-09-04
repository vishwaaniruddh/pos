<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Config;
use App\Router;

/**
 * ApiKeyMiddleware — validates the X-Api-Key header.
 *
 * Used to protect internal endpoints called by the CLI worker.
 * Call ApiKeyMiddleware::require() at the top of any protected controller method.
 */
class ApiKeyMiddleware
{
    /**
     * Abort with 401 if X-Api-Key header is missing or does not match INTERNAL_API_KEY.
     */
    public static function require(): void
    {
        $header = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if (empty($header)) {
            Router::error('MISSING_API_KEY', 'X-Api-Key header is required.', 401);
        }

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals(Config::internalApiKey(), $header)) {
            Router::error('INVALID_API_KEY', 'Invalid X-Api-Key.', 401);
        }
    }
}
