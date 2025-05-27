<?php

class Router {
    private $routes = [];
    private $notFoundCallback;
    private $baseUrl;

    public function __construct() {
        // Establecer un manejador por defecto para rutas no encontradas
        $this->notFoundCallback = function() {
            http_response_code(404);
            echo "404 - Página no encontrada";
        };
        
        // Detectar el base URL
        $this->baseUrl = '/kiosko-scrapping/public';
    }

    public function add($method, $path, $callback) {
        // Normalizar el método y la ruta
        $method = strtoupper($method);
        $path = '/'. trim($path, '/');
        
        if ($path === '/') {
            $path = '/index';
        }
        
        // Convertir la ruta en una expresión regular
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^{$pattern}/?$#";
        
        $this->routes[$method][$pattern] = [
            'callback' => $callback,
            'original' => $path
        ];
        
        return $this;
    }

    public function get($path, $callback) {
        return $this->add('GET', $path, $callback);
    }

    public function post($path, $callback) {
        return $this->add('POST', $path, $callback);
    }

    public function notFound($callback) {
        $this->notFoundCallback = $callback;
        return $this;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remover el base URL del URI
        if (strpos($uri, $this->baseUrl) === 0) {
            $uri = substr($uri, strlen($this->baseUrl));
        }
        
        $uri = '/'. trim($uri, '/');
        
        if ($uri === '/') {
            $uri = '/index';
        }

        // Buscar una ruta coincidente
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $pattern => $route) {
                if (preg_match($pattern, $uri, $matches)) {
                    // Filtrar solo los parámetros nombrados
                    $params = array_filter($matches, function($key) {
                        return !is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    // Ejecutar el callback con los parámetros
                    return call_user_func_array($route['callback'], $params);
                }
            }
        }

        // Si no se encuentra la ruta, ejecutar el manejador 404
        return call_user_func($this->notFoundCallback);
    }
} 