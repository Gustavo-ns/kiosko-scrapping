<?php
// clear_records.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        // Eliminar registros más antiguos que 24 horas
        $stmt = $pdo->prepare("
            DELETE FROM pk_meltwater_resumen 
            WHERE published_date < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND visualizar = 0
        ");
        
        $stmt->execute();
        $rowsAffected = $stmt->rowCount();

        echo json_encode([
            'success' => true, 
            'message' => "Se eliminaron $rowsAffected registros antiguos"
        ]);
    } catch (\PDOException $e) {
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Método no permitido'
    ]);
} 