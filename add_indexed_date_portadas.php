<?php
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

    // Verificar si la columna ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM portadas LIKE 'indexed_date'");
    if ($stmt->rowCount() == 0) {
        // Añadir columna indexed_date si no existe
        $sql = "ALTER TABLE portadas ADD COLUMN indexed_date DATETIME NULL AFTER published_date";
        $pdo->exec($sql);
        echo "Columna indexed_date añadida exitosamente a la tabla portadas\n";
    } else {
        echo "La columna indexed_date ya existe en la tabla portadas\n";
    }

} catch (PDOException $e) {
    die("Error modificando la tabla: " . $e->getMessage() . "\n");
} 