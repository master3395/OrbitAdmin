<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Minimal regex router with parameter capture and middleware-style guards.
 * Routes register a callable (file include or closure).
 */
final class Router
{
    /** @var array<int,array{method:string,regex:string,handler:mixed,vars:array<int,string>,name:?string}> */
    private array $routes = [];

    /** @var array<string,callable> */
    private array $named = [];

    private string $basePrefix = '';

    public function setBasePrefix(string $prefix): void
    {
        $this->basePrefix = rtrim($prefix, '/');
    }

    public function get(string $path, $handler, ?string $name = null): void    { $this->add('GET', $path, $handler, $name); }
    public function post(string $path, $handler, ?string $name = null): void   { $this->add('POST', $path, $handler, $name); }
    public function any(string $path, $handler, ?string $name = null): void    { $this->add('ANY', $path, $handler, $name); }

    private function add(string $method, string $path, $handler, ?string $name = null): void
    {
        $path = '/' . trim($path, '/');
        $vars = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', static function ($m) use (&$vars) {
            $vars[] = $m[1];
            return '([^/]+)';
        }, $path);
        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
            'vars'    => $vars,
            'name'    => $name,
        ];
    }

    /** @return array{path:string,method:string} */
    public function currentRequest(): array
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($this->basePrefix !== '' && strpos($uri, $this->basePrefix) === 0) {
            $uri = substr($uri, strlen($this->basePrefix));
        }
        $uri = '/' . trim($uri, '/');
        return [
            'path'   => $uri === '' ? '/' : $uri,
            'method' => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
        ];
    }

    public function dispatch(): void
    {
        $req = $this->currentRequest();
        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $req['method']) {
                continue;
            }
            if (preg_match($route['regex'], $req['path'], $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['vars'] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }
                $this->invoke($route['handler'], $params);
                return;
            }
        }
        $this->notFound();
    }

    /** @param array<string,?string> $params */
    private function invoke($handler, array $params): void
    {
        $GLOBALS['orbit_route_params'] = $params;
        if (is_callable($handler)) {
            $handler($params);
            return;
        }
        if (is_string($handler) && is_file($handler)) {
            $params; // available in scope as $params
            require $handler;
            return;
        }
        $this->serverError('Invalid route handler.');
    }

    public function notFound(): void
    {
        http_response_code(404);
        $view = Config::get('BASE_PATH') . '/views/pages/errors/404.php';
        if (is_file($view)) {
            require $view;
        } else {
            echo '404 Not Found';
        }
    }

    public function serverError(string $msg = 'Internal Server Error'): void
    {
        http_response_code(500);
        $view = Config::get('BASE_PATH') . '/views/pages/errors/500.php';
        if (is_file($view)) {
            $errorMessage = $msg;
            require $view;
        } else {
            echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        }
    }
}
