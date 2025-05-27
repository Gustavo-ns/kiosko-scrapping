<?php
// Configuración de errores en desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir constantes
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', APP_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ASSETS_VERSION', '1.0.0');

// Crear directorios necesarios si no existen
$directories = [
    STORAGE_PATH . '/images',
    ROOT_PATH . '/logs',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Verificar extensiones requeridas
$required_extensions = ['pdo', 'curl', 'imagick', 'json'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("La extensión $ext es requerida pero no está instalada.");
    }
}

// Cargar el autoloader de Composer
require_once ROOT_PATH . '/vendor/autoload.php';

// Cargar configuraciones
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/cache_config.php';
require_once CONFIG_PATH . '/DatabaseConnection.php';

// Cargar modelos
require_once APP_PATH . '/models/MeltwaterModel.php';
require_once APP_PATH . '/models/CoversModel.php';

// Cargar servicios
require_once APP_PATH . '/services/ImageService.php';

// Cargar controladores
require_once APP_PATH . '/controllers/MeltwaterController.php';
require_once APP_PATH . '/controllers/ApiController.php';

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Configurar codificación
mb_internal_encoding('UTF-8');

// Función de autoload personalizada (por si acaso)
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/services/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
}); 