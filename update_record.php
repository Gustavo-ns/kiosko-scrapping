<?php
// update_record.php

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

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            throw new Exception('ID inválido');
        }

        $updateFields = [];
        $params = ['id' => $id];

        // Campos que se pueden actualizar
        $allowedFields = ['grupo', 'pais', 'titulo', 'source', 'twitter_id', 'dereach'];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $updateFields[] = "$field = :$field";
                $params[$field] = $_POST[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception('No hay campos para actualizar');
        }

        $sql = "UPDATE pk_meltwater_resumen SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Registro actualizado correctamente'
        ]);

    } catch (Exception $e) {
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