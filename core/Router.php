<?php
class Router
{
    private $routes  = [];
    private $version;
    private $basePath;

    public function __construct($version = 'v1', $basePath = '')
    {
        $this->version  = $version;
        // Normalize: no trailing slash, always starts with / (or empty)
        $this->basePath = rtrim($basePath, '/');
    }

    public function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => "/api/{$this->version}" . $path,
            'handler' => $handler,
        ];
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Decode percent-encoded characters and strip query string
        $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        // Strip basePath prefix (only when it is non-empty and actually present)
        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Guarantee a leading slash
        $uri = '/' . ltrim($uri, '/');

        // Remove trailing slash (except for root "/")
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            $pattern = preg_replace(
                '/\{[a-zA-Z0-9_]+\}/',
                '([a-zA-Z0-9_-]+)',
                $route['path']
            );
            $pattern = '#^' . $pattern . '$#';

            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return call_user_func_array($route['handler'], $matches);
            }
        }

        http_response_code(404);
        echo json_encode([
            'message'      => 'Ruta no encontrada',
            'uri_received' => $uri,
            'method'       => $method,
            'base_path'    => $this->basePath,
            'raw_uri'      => $_SERVER['REQUEST_URI'],
            'routes'       => array_map(fn($r) => $r['method'] . ' ' . $r['path'], $this->routes),
        ]);
    }
}
?>