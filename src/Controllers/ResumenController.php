<?php

namespace App\Controllers;

class ResumenController extends BaseController
{
    public function index()
    {
        $stmt = $this->pdo->query("SELECT * FROM pk_meltwater_resumen ORDER BY published_date DESC");
        $registros = $stmt->fetchAll();
        
        echo $this->view('resumen/index', [
            'registros' => $registros,
            'config' => $this->config
        ]);
    }

    public function updateRecord()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            
            if ($id <= 0) {
                throw new \Exception('ID inválido');
            }

            $updateFields = [];
            $params = ['id' => $id];

            $allowedFields = ['grupo', 'pais', 'titulo', 'source', 'twitter_id', 'dereach'];

            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = $_POST[$field];
                }
            }

            if (empty($updateFields)) {
                throw new \Exception('No hay campos para actualizar');
            }

            $sql = "UPDATE pk_meltwater_resumen SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $this->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateVisibility()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $visualizar = isset($_POST['visualizar']) ? (int)$_POST['visualizar'] : 0;

            if ($id <= 0) {
                throw new \Exception('ID inválido');
            }

            $stmt = $this->pdo->prepare("UPDATE pk_meltwater_resumen SET visualizar = :visualizar WHERE id = :id");
            $stmt->execute(['visualizar' => $visualizar, 'id' => $id]);

            return $this->json([
                'success' => true,
                'message' => 'Visibilidad actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getGrupos()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT DISTINCT grupo 
                FROM pk_meltwater_resumen 
                WHERE grupo IS NOT NULL AND grupo != '' 
                ORDER BY grupo
            ");
            
            $grupos = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            return $this->json([
                'success' => true,
                'grupos' => $grupos
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
} 