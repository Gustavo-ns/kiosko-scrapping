<?php
require_once __DIR__ . '/../models/CoversModel.php';

class ApiController {
    private $model;

    public function __construct() {
        try {
            $this->model = new CoversModel();
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        } catch (Exception $e) {
            $this->handleGenericError($e);
        }
    }

    public function getCovers() {
        header('Content-Type: application/json');
        try {
            $country = isset($_GET['country']) ? $_GET['country'] : null;
            $covers = $this->model->getCovers($country);
            echo json_encode($covers);
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, true);
        } catch (Exception $e) {
            $this->handleGenericError($e, true);
        }
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
            echo json_encode(['error' => $errorMessage, 'code' => $errorCode]);
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
            echo json_encode(['error' => $errorMessage]);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo "<h1>Error del Servidor</h1>";
            echo "<p>{$errorMessage}</p>";
        }
        exit;
    }
} 