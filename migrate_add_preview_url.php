<?php
require_once 'config.php';

// Cargar configuración de la base de datos
$cfg = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "Verificando estructura de la tabla covers...\n";
    
    // Verificar si la columna preview_url ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM covers LIKE 'preview_url'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Agregando columna preview_url a la tabla covers...\n";
        $pdo->exec("ALTER TABLE covers ADD COLUMN preview_url VARCHAR(255) NULL AFTER thumbnail_url");
        echo "✅ Columna preview_url agregada exitosamente\n";
    } else {
        echo "⚠️  La columna preview_url ya existe\n";
    }
    
    // Verificar la estructura final
    echo "\nEstructura actual de la tabla covers:\n";
    $stmt = $pdo->query("DESCRIBE covers");
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']}) " . 
             ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
             ($row['Default'] ? " DEFAULT {$row['Default']}" : '') . "\n";
    }
    
    echo "\n✅ Migración completada exitosamente\n";
    
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
