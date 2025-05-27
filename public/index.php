<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// Crear directorio de logs si no existe
if (!is_dir(dirname(__DIR__) . '/logs')) {
    mkdir(dirname(__DIR__) . '/logs', 0755, true);
}

// Manejador de errores personalizado
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('[Y-m-d H:i:s] ') . "Error [$errno] $errstr\n";
    $error_message .= "Archivo: $errfile : $errline\n";
    $error_message .= "URI: " . $_SERVER['REQUEST_URI'] . "\n";
    $error_message .= "Referer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'N/A') . "\n";
    error_log($error_message);
    
    if (ini_get('display_errors')) {
        echo "<h1>Error Detectado</h1>";
        echo "<pre>$error_message</pre>";
    }
    return true;
}
set_error_handler('customErrorHandler');

// Manejador de excepciones no capturadas
function uncaughtExceptionHandler($e) {
    $error_message = date('[Y-m-d H:i:s] ') . "Excepción no capturada: " . $e->getMessage() . "\n";
    $error_message .= "Archivo: " . $e->getFile() . " : " . $e->getLine() . "\n";
    $error_message .= "Traza:\n" . $e->getTraceAsString() . "\n";
    error_log($error_message);
    
    if (ini_get('display_errors')) {
        echo "<h1>Error del Sistema</h1>";
        echo "<pre>$error_message</pre>";
    }
}
set_exception_handler('uncaughtExceptionHandler');

// Cargar el bootstrap
require_once __DIR__ . '/../app/config/bootstrap.php';

// Crear una instancia del router
$router = new Router();

// Cargar las rutas
require_once APP_PATH . '/routes.php';

// Procesar la solicitud
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 