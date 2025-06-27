<?php
// Script para actualizar la estructura de la tabla covers
require_once 'config.php';

try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Conectado a la base de datos.\n";
    
    // Verificar si existe la columna created_at
    $result = $pdo->query("SHOW COLUMNS FROM covers LIKE 'created_at'");
    if ($result->rowCount() == 0) {
        echo "Agregando columna created_at...\n";
        $pdo->exec("ALTER TABLE covers ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Columna created_at agregada.\n";
    } else {
        echo "Columna created_at ya existe.\n";
    }
    
    // Verificar si existe la columna updated_at
    $result = $pdo->query("SHOW COLUMNS FROM covers LIKE 'updated_at'");
    if ($result->rowCount() == 0) {
        echo "Agregando columna updated_at...\n";
        $pdo->exec("ALTER TABLE covers ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "Columna updated_at agregada.\n";
    } else {
        echo "Columna updated_at ya existe.\n";
    }
    
    // Agregar índice único para evitar duplicados
    try {
        $pdo->exec("ALTER TABLE covers ADD UNIQUE INDEX unique_cover (country, source, title)");
        echo "Índice único agregado para (country, source, title).\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Índice único ya existe.\n";
        } else {
            echo "Error creando índice único: " . $e->getMessage() . "\n";
        }
    }
    
    // Verificar estructura actual
    echo "\nEstructura actual de la tabla covers:\n";
    $result = $pdo->query("DESCRIBE covers");
    while ($row = $result->fetch()) {
        echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
    }
    
    // Mostrar índices
    echo "\nÍndices de la tabla covers:\n";
    $result = $pdo->query("SHOW INDEX FROM covers");
    while ($row = $result->fetch()) {
        echo "- {$row['Key_name']}: {$row['Column_name']} ({$row['Index_type']})\n";
    }
    
    echo "\n✅ Estructura de tabla actualizada correctamente.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
