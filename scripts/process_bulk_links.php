<?php
// process_bulk_links.php

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

        $links = isset($_POST['links']) ? explode("\n", trim($_POST['links'])) : [];
        $grupo = isset($_POST['grupo']) ? trim($_POST['grupo']) : '';
        $pais = isset($_POST['pais']) ? trim($_POST['pais']) : '';
        
        $processed = 0;
        $errors = [];

        $stmt = $pdo->prepare("
            INSERT INTO pk_meltwater_resumen 
            (grupo, pais, titulo, source, published_date, visualizar) 
            VALUES 
            (:grupo, :pais, :titulo, :source, NOW(), 0)
        ");

        foreach ($links as $link) {
            $link = trim($link);
            if (empty($link)) continue;

            try {
                $stmt->execute([
                    'grupo' => $grupo,
                    'pais' => $pais,
                    'titulo' => $link, // Temporalmente usamos el link como título
                    'source' => $link
                ]);
                $processed++;
            } catch (\PDOException $e) {
                $errors[] = "Error al procesar '$link': " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
            'message' => "Se procesaron $processed enlaces" . 
                        (count($errors) > 0 ? " con " . count($errors) . " errores" : " exitosamente")
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