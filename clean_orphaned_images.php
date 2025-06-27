<?php
// clean_orphaned_images.php - Limpiar imágenes que ya no están en uso

require_once 'config.php';

function cleanOrphanedImages() {
    global $config;
    
    try {
        $pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
            $config['db']['user'], $config['db']['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "Iniciando limpieza de imágenes huérfanas...\n";
        
        // Obtener todas las rutas de imágenes actualmente en la base de datos
        $stmt = $pdo->query("SELECT thumbnail_url, original_url FROM covers");
        $usedImages = [];
        
        while ($row = $stmt->fetch()) {
            if (!empty($row['thumbnail_url'])) {
                $usedImages[] = basename($row['thumbnail_url']);
            }
            if (!empty($row['original_url'])) {
                $usedImages[] = basename($row['original_url']);
            }
        }
        
        echo "Imágenes en uso en la BD: " . count($usedImages) . "\n";
        
        // Directorios a limpiar
        $directories = [
            __DIR__ . '/images/covers',
            __DIR__ . '/images/covers/thumbnails'
        ];
        
        $deletedFiles = 0;
        $totalFiles = 0;
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                echo "Directorio no existe: $dir\n";
                continue;
            }
            
            $files = glob($dir . '/*.webp');
            $totalFiles += count($files);
            
            foreach ($files as $file) {
                $filename = basename($file);
                
                // Si el archivo no está en la lista de imágenes usadas, eliminarlo
                if (!in_array($filename, $usedImages)) {
                    if (unlink($file)) {
                        $deletedFiles++;
                        echo "Eliminado: $filename\n";
                    } else {
                        echo "Error eliminando: $filename\n";
                    }
                }
            }
        }
        
        echo "\nLimpieza completada:\n";
        echo "- Total de archivos: $totalFiles\n";
        echo "- Archivos eliminados: $deletedFiles\n";
        echo "- Archivos mantenidos: " . ($totalFiles - $deletedFiles) . "\n";
        
    } catch (PDOException $e) {
        echo "Error en la limpieza: " . $e->getMessage() . "\n";
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    cleanOrphanedImages();
}
?>
