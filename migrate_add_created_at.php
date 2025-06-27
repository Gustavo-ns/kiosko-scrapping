<?php
// migrate_add_created_at.php - Añadir campo created_at a la tabla covers

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
        $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Verificando estructura de la tabla covers...\n";
    
    // Verificar si ya existe la columna created_at
    $stmt = $pdo->query("SHOW COLUMNS FROM covers LIKE 'created_at'");
    if ($stmt->rowCount() > 0) {
        echo "La columna 'created_at' ya existe.\n";
    } else {
        echo "Añadiendo columna 'created_at'...\n";
        $pdo->exec("ALTER TABLE covers ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Columna 'created_at' añadida exitosamente.\n";
    }
    
    // Verificar si ya existe la columna updated_at
    $stmt = $pdo->query("SHOW COLUMNS FROM covers LIKE 'updated_at'");
    if ($stmt->rowCount() > 0) {
        echo "La columna 'updated_at' ya existe.\n";
    } else {
        echo "Añadiendo columna 'updated_at'...\n";
        $pdo->exec("ALTER TABLE covers ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "Columna 'updated_at' añadida exitosamente.\n";
    }
    
    // Crear índice único para evitar duplicados por país y fuente
    echo "Creando índice único para evitar duplicados...\n";
    try {
        $pdo->exec("ALTER TABLE covers ADD UNIQUE KEY unique_country_source (country, source)");
        echo "Índice único 'unique_country_source' creado exitosamente.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "El índice único 'unique_country_source' ya existe.\n";
        } else {
            echo "Error creando índice único: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Migración completada exitosamente.\n";
    
} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage() . "\n";
}
