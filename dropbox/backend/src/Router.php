<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

/**
 * Router — lightweight HTTP router.
 *
 * Usage:
 *   $router->get('/auth/status', [AuthController::class, 'status']);
 *   $router->post('/jobs', [JobController::class, 'create']);
 *   $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
 */
class Router
{
    /** @var array<string, array<string, array{0: string, 1: string}>> */
    private array $routes = [];

    // ── Registration ────────────────────────────────────────────────────────

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        // Convert :param placeholders → named capture groups
        $pattern = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[$method][$pattern] = $handler;
    }

    // ── Dispatch ─────────────────────────────────────────────────────────────

    public function dispatch(string $method, string $path): void
    {
        // Strip query string
        $path = strtok($path, '?');

        // Handle CORS preflight
        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $pattern => $handler) {
            if (preg_match($pattern, $path, $matches)) {
                // Extract named captures as params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->$action($params);
                return;
            }
        }

        // 404
        http_response_code(404);
        echo json_encode([
            'error'   => true,
            'code'    => 'NOT_FOUND',
            'message' => "Route {$method} {$path} not found.",
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Parse the current request body as JSON.
     */
    public static function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON in request body: ' . json_last_error_msg());
        }
        return $decoded ?? [];
    }

    /**
     * Send a JSON response and exit.
     * @param mixed $data
     */
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send a JSON error and exit.
     */
    public static function error(string $code, string $message, int $status = 400): void
    {
        self::json(['error' => true, 'code' => $code, 'message' => $message], $status);
    }

    /**
     * Get query string parameter.
     * @param mixed $default
     * @return mixed
     */
    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}
