<?php
require_once __DIR__ . '/../models/MeltwaterModel.php';
require_once __DIR__ . '/../services/ImageService.php';

class MeltwaterController {
    private $model;
    private $imageService;

    public function __construct() {
        try {
            $this->model = new MeltwaterModel();
            $this->imageService = new ImageService();
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        } catch (Exception $e) {
            $this->handleGenericError($e);
        }
    }

    public function index() {
        try {
            // Generar ETag basado en la última actualización
            $etag = '"' . $this->model->getContentHash() . '"';
            
            // Configurar headers de caché y tipo de contenido
            header('Content-Type: text/html; charset=UTF-8');
            header('ETag: ' . $etag);

            // Verificar si el contenido ha cambiado
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
                http_response_code(304); // Not Modified
                exit;
            }

            // Obtener los datos
            $meltwater_docs = $this->model->getMeltwaterDocs();
            $covers = $this->model->getCovers();
            $documents = array_merge($meltwater_docs, $covers);

            // Ordenar por fecha
            usort($documents, function($a, $b) {
                $date_a = isset($a['published_date']) ? $a['published_date'] : $a['scraped_at'];
                $date_b = isset($b['published_date']) ? $b['published_date'] : $b['scraped_at'];
                return strtotime($date_b) - strtotime($date_a);
            });

            // Obtener grupos y países
            $grupos = $this->model->getGrupos();
            $paises = $this->model->getPaises();

            // Pasar el ImageService a la vista
            $imageService = $this->imageService;

            // Renderizar vista
            require_once __DIR__ . '/../views/templates/melwater.php';
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        } catch (Exception $e) {
            $this->handleGenericError($e);
        }
    }

    public function update() {
        header('Content-Type: application/json');
        
        try {
            // URL de la API y API key
            $apiUrl = "https://api.meltwater.com/v3/exports/recurring";
            $apiKey = "8PMcUPYZ1M954yDpIh6mI8CE61fqwG2LFulSbPGo";

            // Obtener datos de la API
            $documents = $this->fetchMeltwaterData($apiUrl, $apiKey);
            
            // Actualizar registros
            $updatedCount = $this->model->updateMeltwaterDocuments($documents);

            $this->sendJsonResponse([
                'success' => true,
                'message' => "Se actualizaron {$updatedCount} registros correctamente."
            ]);

        } catch (PDOException $e) {
            $this->handleDatabaseError($e, true);
        } catch (Exception $e) {
            $this->handleGenericError($e, true);
        }
    }

    private function fetchMeltwaterData($apiUrl, $apiKey) {
        $ch = curl_init($apiUrl);
        if ($ch === false) {
            throw new Exception("No se pudo inicializar cURL");
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["apikey: $apiKey"],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception("Error de cURL: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception("La API respondió con código HTTP: " . $httpCode);
        }

        curl_close($ch);

        // Decodificar JSON inicial
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
        }

        if (!isset($data['recurring_exports'][0]['data_url'])) {
            throw new Exception("No se encontró 'data_url' en la respuesta de la API.");
        }

        // Obtener los datos del JSON
        $dataUrl = $data['recurring_exports'][0]['data_url'];
        $response = file_get_contents($dataUrl);
        if ($response === FALSE) {
            throw new Exception("Error al obtener los datos del archivo JSON remoto.");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error al decodificar JSON de datos: " . json_last_error_msg());
        }

        if (!isset($data['documents']) || !is_array($data['documents'])) {
            throw new Exception("No se encontraron documentos válidos en la respuesta.");
        }

        return $data['documents'];
    }

    private function handleDatabaseError($e, $isJson = false) {
        $errorCode = $e->getCode();
        $errorMessage = '';

        switch ($errorCode) {
            case 1203:
                $errorMessage = 'El servidor está experimentando mucha carga. Por favor, intenta de nuevo en unos momentos.';
                break;
            case 2002:
                $errorMessage = 'No se pudo conectar a la base de datos. Por favor, verifica la conexión.';
                break;
            case 2006:
                $errorMessage = 'Se perdió la conexión con la base de datos. Por favor, intenta de nuevo.';
                break;
            default:
                $errorMessage = 'Error de base de datos. Por favor, intenta de nuevo más tarde.';
        }

        if ($isJson) {
            $this->sendJsonResponse(['success' => false, 'message' => $errorMessage, 'code' => $errorCode]);
        } else {
            header('HTTP/1.1 503 Service Unavailable');
            echo "<h1>Error del Servidor</h1>";
            echo "<p>{$errorMessage}</p>";
        }
        exit;
    }

    private function handleGenericError($e, $isJson = false) {
        $errorMessage = 'Ha ocurrido un error inesperado. Por favor, intenta de nuevo más tarde.';
        
        if ($isJson) {
            $this->sendJsonResponse(['success' => false, 'message' => $errorMessage]);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo "<h1>Error del Servidor</h1>";
            echo "<p>{$errorMessage}</p>";
        }
        exit;
    }

    private function sendJsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
} 