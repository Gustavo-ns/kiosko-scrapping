<?php
// Configuración de zona horaria para Uruguay
date_default_timezone_set('America/Montevideo');

// Configuración de logs
$logFile = __DIR__ . '/logs/cron_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);

// Crear directorio de logs si no existe
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Función para escribir en el log
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    writeLog("Iniciando actualización...");

    // Definir constantes
    define('ROOT_PATH', __DIR__);
    define('APP_PATH', __DIR__ . '/app');
    define('CONFIG_PATH', APP_PATH . '/config');
    define('ASSETS_VERSION', '1.0.0');

    // Cargar dependencias
    require_once APP_PATH . '/config/DatabaseConnection.php';
    require_once APP_PATH . '/models/MeltwaterModel.php';
    require_once APP_PATH . '/services/ImageService.php';
    require_once APP_PATH . '/controllers/MeltwaterController.php';

    // Crear instancia del controlador y ejecutar actualización
    $controller = new MeltwaterController();
    $result = $controller->update();

    writeLog("Actualización completada exitosamente.");
    writeLog(json_encode($result));

} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    writeLog("Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
    writeLog("Stack trace: " . $e->getTraceAsString());
} 