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

try {
    // Cargar bootstrap que contiene las definiciones de constantes y configuración básica
    $bootstrap_file = dirname(__DIR__) . '/app/config/bootstrap.php';
    if (!file_exists($bootstrap_file)) {
        throw new Exception("El archivo bootstrap.php no existe en: $bootstrap_file");
    }
    require_once $bootstrap_file;

    // Obtener la acción de la URL
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';

    // Debug de enrutamiento
    echo "<h2>Información de Enrutamiento:</h2>";
    echo "<pre>";
    echo "Acción solicitada: " . htmlspecialchars($action) . "\n";
    echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "</pre>";

    // Instanciar el controlador apropiado basado en la ruta
    switch ($action) {
        case 'update':
            $controller = new MeltwaterController();
            $controller->update();
            break;
        case 'api':
            $controller = new ApiController();
            $controller->getCovers();
            break;
        default:
            $controller = new MeltwaterController();
            $controller->index();
            break;
    }
} catch (Exception $e) {
    // Log del error
    error_log($e->getMessage());
    
    if (ini_get('display_errors')) {
        echo "<h1>Error de Aplicación</h1>";
        echo "<pre>";
        echo "Mensaje: " . htmlspecialchars($e->getMessage()) . "\n";
        echo "Archivo: " . htmlspecialchars($e->getFile()) . "\n";
        echo "Línea: " . $e->getLine() . "\n";
        echo "Traza:\n" . htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    } else {
        // Mensaje genérico para producción
        header('HTTP/1.1 500 Internal Server Error');
        echo "<h1>Error del Sistema</h1>";
        echo "<p>Ha ocurrido un error. Por favor, contacte al administrador.</p>";
    }
} 