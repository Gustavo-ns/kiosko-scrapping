<?php
// get_grupos.php

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

    // Obtener grupos únicos
    $stmt = $pdo->query("
        SELECT DISTINCT grupo 
        FROM pk_meltwater_resumen 
        WHERE grupo IS NOT NULL AND grupo != '' 
        ORDER BY grupo
    ");
    
    $grupos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'grupos' => $grupos
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 