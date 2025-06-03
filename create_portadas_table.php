<?php
// Cargar la configuración
$cfg = require_once 'config.php';

try {
    // Primero intentar conectar sin charset
    try {
        $pdo = new PDO(
            "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']}",
            $cfg['db']['user'],
            $cfg['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown character set') !== false) {
            // Si falla, intentar con utf8mb4
            $pdo = new PDO(
                "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset=utf8mb4",
                $cfg['db']['user'],
                $cfg['db']['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } else {
            throw $e;
        }
    }

    // Establecer charset después de la conexión
    $pdo->exec("SET NAMES utf8mb4");

    // Crear la tabla portadas
    $sql = "CREATE TABLE IF NOT EXISTS portadas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_type ENUM('meltwater', 'cover', 'resumen') NOT NULL,
        original_id INT,
        title VARCHAR(255),
        grupo VARCHAR(100),
        pais VARCHAR(100),
        url_destino TEXT,
        content_image TEXT,
        preview_url TEXT,
        thumbnail_url TEXT,
        original_url TEXT,
        published_date DATETIME,
        dereach DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (source_type),
        INDEX (grupo),
        INDEX (pais),
        INDEX (published_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "Tabla 'portadas' creada exitosamente\n";

} catch (PDOException $e) {
    die("Error creando la tabla: " . $e->getMessage() . "\n");
} 