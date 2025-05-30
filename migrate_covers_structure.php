<?php
// migrate_covers_structure.php - Migración para actualizar estructura de la tabla covers

require_once 'config.php';

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Iniciando migración de la tabla covers...\n";
    
    // Verificar si las columnas ya existen
    $stmt = $pdo->query("SHOW COLUMNS FROM covers LIKE 'thumbnail_url'");
    $thumbnailExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM covers LIKE 'original_url'");
    $originalExists = $stmt->fetch();
    
    if (!$thumbnailExists) {
        echo "Agregando columna thumbnail_url...\n";
        $pdo->exec("ALTER TABLE covers ADD COLUMN thumbnail_url VARCHAR(255) DEFAULT NULL AFTER image_url");
    } else {
        echo "La columna thumbnail_url ya existe.\n";
    }
    
    if (!$originalExists) {
        echo "Agregando columna original_url...\n";
        $pdo->exec("ALTER TABLE covers ADD COLUMN original_url VARCHAR(255) DEFAULT NULL AFTER thumbnail_url");
    } else {
        echo "La columna original_url ya existe.\n";
    }
    
    // Migrar datos existentes para compatibilidad
    echo "Migrando datos existentes...\n";
    $pdo->exec("UPDATE covers SET thumbnail_url = image_url, original_url = image_url WHERE thumbnail_url IS NULL OR original_url IS NULL");
    
    echo "✅ Migración completada exitosamente!\n";
    echo "La tabla covers ahora incluye:\n";
    echo "- image_url: Imagen principal mostrada (thumbnail)\n";
    echo "- thumbnail_url: Ruta específica del thumbnail\n";
    echo "- original_url: Ruta específica de la imagen original\n";
    
} catch (PDOException $e) {
    echo "❌ Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
?>
