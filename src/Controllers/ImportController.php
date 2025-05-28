<?php

namespace App\Controllers;

class ImportController extends BaseController
{
    public function index()
    {
        echo $this->view('import/index', [
            'config' => $this->config
        ]);
    }

    public function processBulkLinks()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'error' => 'Método no permitido']);
        }

        $grupo = $_POST['grupo'] ?? '';
        $pais = $_POST['pais'] ?? '';
        $links = $_POST['links'] ?? '';

        if (empty($grupo) || empty($pais) || empty($links)) {
            return $this->json([
                'success' => false,
                'error' => 'Todos los campos son requeridos'
            ]);
        }

        $links = array_filter(array_map('trim', explode("\n", $links)));
        if (empty($links)) {
            return $this->json([
                'success' => false,
                'error' => 'No se encontraron enlaces válidos'
            ]);
        }

        $errors = [];
        $processed = 0;

        foreach ($links as $link) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO pk_meltwater_resumen (grupo, pais, source, original_link, published_date)
                    VALUES (:grupo, :pais, :source, :link, NOW())
                ");

                $stmt->execute([
                    ':grupo' => $grupo,
                    ':pais' => $pais,
                    ':source' => $link,
                    ':link' => $link
                ]);

                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Error al procesar {$link}: " . $e->getMessage();
            }
        }

        return $this->json([
            'success' => true,
            'message' => "Se procesaron {$processed} enlaces correctamente",
            'errors' => $errors
        ]);
    }
} 