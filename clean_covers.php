<?php
// Configurar el manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/clean_covers.log');

// Configurar el timezone
date_default_timezone_set('America/Montevideo');

// Cargar configuración de la base de datos
$cfg = require 'config.php';

try {
    // Inicializar conexión PDO
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Obtener el número de registros antes de limpiar
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM covers");
    $count = $stmt->fetch()['count'];
    echo "Registros en la tabla covers antes de limpiar: $count\n";

    // Limpiar la tabla covers
    $pdo->exec("TRUNCATE TABLE covers");
    
    // Verificar que la tabla esté vacía
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM covers");
    $count = $stmt->fetch()['count'];
    echo "Registros en la tabla covers después de limpiar: $count\n";

    // Eliminar archivos de imágenes antiguas
    $upload_dir = __DIR__ . '/images/covers';
    if (is_dir($upload_dir)) {
        $files = glob($upload_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        echo "Archivos de imágenes eliminados del directorio: $upload_dir\n";
    }

    echo "Limpieza completada exitosamente\n";
    exit(0);

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 