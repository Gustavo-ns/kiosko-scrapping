<?php

// Cargar el autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = parse_ini_file(__DIR__ . '/../.env');
    foreach ($dotenv as $key => $value) {
        putenv("$key=$value");
    }
}

// Cargar configuración
$config = require __DIR__ . '/../config/app.php';

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $config['paths']['logs'] . '/error.log');

// Establecer zona horaria
date_default_timezone_set('America/Boise');

// Iniciar la sesión
session_start();

// Enrutamiento básico
$route = isset($_GET['route']) ? $_GET['route'] : 'home';

// Mapeo de rutas a controladores
$routes = [
    'home' => 'App\Controllers\HomeController@index',
    'resumen' => 'App\Controllers\ResumenController@index',
    'importar' => 'App\Controllers\ImportController@index',
    'scrape' => 'App\Controllers\ScrapeController@index',
    'scrape/start' => 'App\Controllers\ScrapeController@start',
    'scrape/stop' => 'App\Controllers\ScrapeController@stop',
    'scrape/status' => 'App\Controllers\ScrapeController@status',
];

// Procesar la ruta
if (isset($routes[$route])) {
    $parts = explode('@', $routes[$route]);
    $controller = $parts[0];
    $method = $parts[1];
    if (class_exists($controller)) {
        $instance = new $controller();
        if (method_exists($instance, $method)) {
            $instance->$method();
            exit;
        }
    }
}

// Si no se encuentra la ruta, mostrar error 404
http_response_code(404);
echo 'Página no encontrada'; 