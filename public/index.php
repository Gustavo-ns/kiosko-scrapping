<?php
// Configuración de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir constantes
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Autoloader
spl_autoload_register(function ($class) {
    // Convertir el nombre de la clase en una ruta de archivo
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    
    // Buscar el archivo en diferentes directorios
    $paths = [
        BASE_PATH . '/app/core/',
        BASE_PATH . '/app/controllers/',
        BASE_PATH . '/app/models/',
        BASE_PATH . '/app/config/'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path . $file)) {
            require_once $path . $file;
            return;
        }
    }
});

// Cargar el Router
require_once APP_PATH . '/core/Router.php';

// Crear instancia del router
$router = new Router();

// Definir rutas
$router->get('/', function() {
    include BASE_PATH . '/public/views/mealwater.php';
});

$router->get('/covers', function() {
    include BASE_PATH . '/public/views/covers.php';
});

$router->get('/mealwater', function() {
    include BASE_PATH . '/public/views/mealwater.php';
});

// Manejar 404
$router->notFound(function() {
    http_response_code(404);
    include BASE_PATH . '/public/views/404.php';
});

// Despachar la ruta
$router->dispatch(); 