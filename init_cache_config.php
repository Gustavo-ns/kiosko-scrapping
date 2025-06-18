<?php
// Script para inicializar la configuración de caché en la base de datos

// Cargar configuración de la base de datos
$cfg = require 'config.php';

try {
    // Conexión a la base de datos
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Configuración inicial
    $initial_config = [
        'assets_version' => '1.0.13',
        'cache_time_images' => '86400',    // 1 día
        'cache_time_static' => '86400',     // 1 día
        'cache_time_data' => '0'            // Sin caché para datos dinámicos
    ];

    // Insertar o actualizar la configuración
    $stmt = $pdo->prepare("REPLACE INTO configs (name, value) VALUES (:name, :value)");
    
    foreach ($initial_config as $name => $value) {
        $stmt->execute([
            ':name' => $name,
            ':value' => $value
        ]);
        echo "Configuración {$name} establecida a {$value}\n";
    }

    echo "\n✅ Configuración de caché inicializada exitosamente\n";

} catch (PDOException $e) {
    die("Error al inicializar la configuración de caché: " . $e->getMessage() . "\n");
} 