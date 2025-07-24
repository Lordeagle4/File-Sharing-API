<?php

namespace App\Core;

class Router
{
    private static array $routes = [];

    public static function get(string $uri, array $action): void
    {
        self::$routes['GET'][$uri] = $action;
    }

    public static function post(string $uri, array $action): void
    {
        self::$routes['POST'][$uri] = $action;
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if (!empty($basePath) && str_starts_with($requestUri, $basePath)) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route => $action) {
            $pattern = preg_replace_callback(
                '#\{(\w+)(\?)?\}#',
                fn($matches) => isset($matches[2]) && $matches[2] === '?' ?
                    '(?P<' . $matches[1] . '>[\w\-]*)?' :
                    '(?P<' . $matches[1] . '>[\w\-]+)',
                $route
            );

            $pattern = "#^" . rtrim($pattern, '/') . "/?$#";

            if (preg_match($pattern, $requestUri, $matches)) {
                $params = array_filter(
                    $matches,
                    fn($key) => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );

                [$class, $method] = $action;
                (new $class)->$method(...array_values($params));
                return;
            }
        }

        Response::json(['error' => 'Route not found'], 404);
    }
}
