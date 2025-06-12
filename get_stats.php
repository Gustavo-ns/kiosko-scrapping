<?php
header('Content-Type: application/json');

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

    // Obtener conteo de registros Meltwater
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pk_melwater");
    $meltwater = $stmt->fetch()['count'];

    // Obtener conteo de registros Covers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM covers");
    $covers = $stmt->fetch()['count'];

    // Obtener total de registros en portadas
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM portadas");
    $total = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'meltwater' => $meltwater,
        'covers' => $covers,
        'total' => $total
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 