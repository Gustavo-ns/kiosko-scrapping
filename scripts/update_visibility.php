<?php
// update_visibility.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $visualizar = isset($_POST['visualizar']) ? intval($_POST['visualizar']) : 0;

    if ($id > 0) {
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
            
            $stmt = $pdo->prepare("UPDATE pk_meltwater_resumen SET visualizar = :visualizar WHERE id = :id");
            $stmt->execute(['visualizar' => $visualizar, 'id' => $id]);

            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
    }
}
