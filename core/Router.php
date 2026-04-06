<?php
/**
 * Clase Router - Manejo de rutas
 */
class Router {
    private $routes = [];
    
    public function add($method, $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }
    
    public function get($path, $callback) {
        $this->add('GET', $path, $callback);
    }
    
    public function post($path, $callback) {
        $this->add('POST', $path, $callback);
    }
    
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remover base path
        $basePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', BASE_PATH);
        $requestUri = str_replace($basePath, '', $requestUri);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestUri)) {
                return call_user_func($route['callback']);
            }
        }
        
        // 404
        http_response_code(404);
        echo "404 - Página no encontrada";
    }
    
    private function matchPath($routePath, $requestUri) {
        $pattern = '#^' . preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath) . '$#';
        return preg_match($pattern, $requestUri);
    }
}
