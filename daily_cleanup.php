<?php
/**
 * Script automático para limpiar registros antiguos
 * Se ejecuta diariamente para mantener la base de datos limpia
 */

// Configurar para ejecución desde línea de comandos
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log de actividad
function logActivity($message) {
    $logFile = __DIR__ . '/logs/daily_cleanup.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Crear directorio de logs si no existe
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // También mostrar en consola si se ejecuta desde línea de comandos
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

try {
    logActivity("Iniciando limpieza diaria de registros...");
    
    // Cargar configuración de la base de datos
    $cfg = require __DIR__ . '/config.php';

    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // 1. Limpiar registros de pk_meltwater_resumen antiguos (más de 24 horas) que no se visualizan
    $stmt1 = $pdo->prepare("
        DELETE FROM pk_meltwater_resumen 
        WHERE indexed_date < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND visualizar = 0
    ");
    $stmt1->execute();
    $deletedResumen = $stmt1->rowCount();
    
    // 2. Limpiar registros de pk_melwater muy antiguos (más de 7 días) para no sobrecargar la BD
    $stmt2 = $pdo->prepare("
        DELETE FROM pk_melwater 
        WHERE indexed_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt2->execute();
    $deletedMeltwater = $stmt2->rowCount();
    
    // 3. Limpiar registros de covers muy antiguos (más de 7 días)
    $stmt3 = $pdo->prepare("
        DELETE FROM covers 
        WHERE indexed_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt3->execute();
    $deletedCovers = $stmt3->rowCount();
    
    // 4. Limpiar imágenes huérfanas (opcional)
    $imageDir = __DIR__ . '/images';
    $deletedImages = 0;
    
    if (is_dir($imageDir)) {
        $files = glob($imageDir . '/*');
        $cutoffTime = time() - (7 * 24 * 60 * 60); // 7 días
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                // Verificar que la imagen no esté siendo utilizada
                $filename = basename($file);
                $stmt4 = $pdo->prepare("
                    SELECT COUNT(*) as count FROM (
                        SELECT content_image FROM pk_melwater WHERE content_image LIKE ?
                        UNION ALL
                        SELECT image_url FROM covers WHERE image_url LIKE ?
                        UNION ALL
                        SELECT source FROM pk_meltwater_resumen WHERE source LIKE ?
                    ) as combined
                ");
                $stmt4->execute(["%$filename%", "%$filename%", "%$filename%"]);
                $result = $stmt4->fetch();
                
                if ($result['count'] == 0) {
                    unlink($file);
                    $deletedImages++;
                }
            }
        }
    }
    
    // 5. Optimizar tablas después de la limpieza
    $pdo->exec("OPTIMIZE TABLE pk_meltwater_resumen, pk_melwater, covers");
    
    $summary = "Limpieza completada: $deletedResumen registros de resumen, $deletedMeltwater de meltwater, $deletedCovers de covers, $deletedImages imágenes";
    logActivity($summary);
    
    // Si se ejecuta desde web, devolver JSON
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $summary,
            'deleted' => [
                'resumen' => $deletedResumen,
                'meltwater' => $deletedMeltwater,
                'covers' => $deletedCovers,
                'images' => $deletedImages
            ]
        ]);
    }
    
} catch (Exception $e) {
    $errorMsg = "Error durante la limpieza: " . $e->getMessage();
    logActivity($errorMsg);
    
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $errorMsg
        ]);
    } else {
        exit(1);
    }
}
?>
