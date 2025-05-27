<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// Definir constantes
define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', APP_PATH . '/config');
define('ASSETS_VERSION', '1.0.0');

try {
    // Cargar el autoloader si existe
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    // Cargar la configuración de la base de datos
    require_once APP_PATH . '/config/DatabaseConnection.php';

    // Cargar los modelos necesarios
    require_once APP_PATH . '/models/MeltwaterModel.php';
    require_once APP_PATH . '/services/ImageService.php';

    // Cargar el controlador
    require_once APP_PATH . '/controllers/MeltwaterController.php';

    // Crear una instancia del controlador
    $controller = new MeltwaterController();

    // Ejecutar la actualización
    $controller->update();

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Registrar el error en el log
    error_log("Error en update.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
} 