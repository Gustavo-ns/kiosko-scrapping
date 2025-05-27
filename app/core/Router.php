<?php

class Router {
    private $routes = [];
    private $basePath;

    public function __construct() {
        // Detectar el directorio base de la aplicación
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $this->basePath = $scriptName === '/' ? '' : $scriptName;
    }

    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch($method, $uri) {
        // Remover el directorio base de la URI
        $uri = str_replace($this->basePath, '', $uri);
        
        // Asegurarse de que la URI comience con /
        $uri = '/' . ltrim($uri, '/');

        if (!isset($this->routes[$method][$uri])) {
            throw new Exception("Ruta no encontrada: $method $uri");
        }

        $handler = $this->routes[$method][$uri];
        list($controller, $action) = explode('@', $handler);

        $controllerFile = APP_PATH . "/controllers/{$controller}.php";
        if (!file_exists($controllerFile)) {
            throw new Exception("Controlador no encontrado: $controller");
        }

        require_once $controllerFile;
        $controllerInstance = new $controller();
        
        if (!method_exists($controllerInstance, $action)) {
            throw new Exception("Acción no encontrada: $action en $controller");
        }

        return $controllerInstance->$action();
    }
} 