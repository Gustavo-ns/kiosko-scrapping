<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// Cargar el bootstrap
require_once __DIR__ . '/../app/config/bootstrap.php';

// Crear una instancia del controlador
require_once APP_PATH . '/controllers/MeltwaterController.php';
$controller = new MeltwaterController();

// Ejecutar la actualización
try {
    $controller->update();
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 