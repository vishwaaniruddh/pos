<?php
declare(strict_types=1);

/**
 * Front Controller — /backend/public/index.php
 *
 * All requests to /api/v1/* are routed here.
 * XAMPP .htaccess or Nginx config should rewrite to this file.
 *
 * Example Apache .htaccess in backend/public/:
 *   RewriteEngine On
 *   RewriteCond %{REQUEST_FILENAME} !-f
 *   RewriteRule ^ index.php [QSA,L]
 */

// ── Debugging (Temporary) ──────────────────────────────────────────────────────
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// ── Bootstrap ──────────────────────────────────────────────────────────────────

$root = dirname(__DIR__);  // default: backend/

// If we are packed in 'api/index.php', the backend root is actually at $root/backend
if (basename(__DIR__) === 'api') {
    $root = $root . '/backend';
}

// Composer autoloader
require $root . '/vendor/autoload.php';

use App\Auth\OAuthController;
use App\Config;
use App\Database;
use App\Dropbox\FolderController;
use App\Jobs\JobController;
use App\Router;

// Load environment
Config::load($root);

// Connect to DB
Database::connect();

// ── CORS Headers ───────────────────────────────────────────────────────────────

$allowedOrigin = Config::appUrl();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key, Authorization');
header('Access-Control-Max-Age: 3600');

// HSTS (enable in production)
if (!Config::isDev()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Handle preflight immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Extract Path ───────────────────────────────────────────────────────────────

// Strip /api/v1 prefix (adjust if your server path differs)
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$scriptDir   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path        = '/' . ltrim(substr($requestUri, strlen($scriptDir)), '/');

// Remove query string
$path = strtok($path, '?') ?: '/';

// Strip /index.php if present in the URL
if (str_starts_with($path, '/index.php')) {
    $path = substr($path, 10) ?: '/';
}

// Strip /api/v1 prefix if present
if (str_starts_with($path, '/api/v1')) {
    $path = substr($path, 7) ?: '/';
}

// Strip /v1 prefix if present (since /api is stripped by $scriptDir in subdirectories)
if (str_starts_with($path, '/v1')) {
    $path = substr($path, 3) ?: '/';
}

$method = strtoupper($_SERVER['REQUEST_METHOD']);

// ── Router ─────────────────────────────────────────────────────────────────────

$router = new Router();

// Auth
$router->get('/auth/dropbox',              [OAuthController::class, 'initiate']);
$router->get('/auth/dropbox/callback',     [OAuthController::class, 'callback']);
$router->get('/auth/status',               [OAuthController::class, 'status']);
$router->post('/auth/disconnect',          [OAuthController::class, 'disconnect']);

// Jobs
$router->post('/jobs',                     [JobController::class, 'create']);
$router->get('/jobs',                      [JobController::class, 'index']);
$router->get('/jobs/:id',                  [JobController::class, 'show']);
$router->get('/jobs/:id/progress',         [JobController::class, 'progress']);
$router->get('/jobs/:id/files',            [JobController::class, 'files']);
$router->get('/jobs/:id/logs',             [JobController::class, 'logs']);
$router->post('/jobs/:id/cancel',          [JobController::class, 'cancel']);
$router->post('/jobs/:id/start',           [JobController::class, 'start']);
$router->post('/jobs/:id/restart',         [JobController::class, 'restart']);
$router->get('/jobs/:id/report',           [JobController::class, 'report']);
$router->get('/jobs/:id/clean-scan',       [JobController::class, 'cleanScan']);
$router->post('/jobs/:id/clean-delete',    [JobController::class, 'cleanDelete']);
$router->post('/jobs/start-worker',        [JobController::class, 'startWorker']);

// Dropbox utilities
$router->get('/dropbox/folders',           [FolderController::class, 'browse']);

// ── Dispatch ───────────────────────────────────────────────────────────────────

try {
    $router->dispatch($method, $path);
} catch (InvalidArgumentException $e) {
    Router::error('INVALID_JSON', $e->getMessage(), 400);
} catch (RuntimeException $e) {
    Router::error('SERVER_ERROR', $e->getMessage(), 500);
} catch (Throwable $e) {
    // Log and hide internal errors in production
    error_log('[SKU Copier] Unhandled exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Router::error(
        'INTERNAL_ERROR',
        Config::isDev() ? $e->getMessage() : 'An internal server error occurred.',
        500
    );
}
