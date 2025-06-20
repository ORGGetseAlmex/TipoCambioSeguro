<?php
namespace App;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, callable $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $action = $this->routes[$method][$path] ?? null;
        if ($action) {
            call_user_func($action);
        } else {
            http_response_code(404);
            echo 'Not Found';
        }
    }
}
