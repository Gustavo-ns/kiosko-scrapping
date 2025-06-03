<?php
// Cargar la configuración
$cfg = require_once 'config.php';

// Configurar logging
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/update_portadas.log');
error_reporting(E_ALL);

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

    // 1. Limpiar registros antiguos
    $pdo->exec("DELETE FROM portadas WHERE published_date < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    
    // 2. Actualizar la tabla portadas
    require_once 'process_portadas.php';
    
    // 3. Optimizar la tabla
    $pdo->exec("OPTIMIZE TABLE portadas");
    
    echo json_encode([
        'success' => true,
        'message' => 'Tabla portadas actualizada exitosamente'
    ]);

} catch (Exception $e) {
    error_log("Error actualizando portadas: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 