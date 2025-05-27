<?php

class ScraperController {
    private $pdo;
    private $config;

    public function __construct() {
        $this->config = require CONFIG_PATH . '/config.php';
        $this->initDatabase();
    }

    private function initDatabase() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->config['db']['host']};dbname={$this->config['db']['name']};charset={$this->config['db']['charset']}",
                $this->config['db']['user'],
                $this->config['db']['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public function scrape() {
        try {
            // Configurar manejo de errores
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
            ini_set('error_log', ROOT_PATH . '/logs/scrape_errors.log');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

            // Validar Imagick
            if (!extension_loaded('imagick')) {
                throw new Exception("La extensión Imagick no está habilitada.");
            }

            // Iniciar el proceso de scraping
            $start = microtime(true);
            $result = $this->executeScraping();
            $time = round(microtime(true) - $start, 2);

            // Devolver resultado como JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Scraping completado en {$time} segundos",
                'details' => $result
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function executeScraping() {
        require_once APP_PATH . '/scrapers/ScraperService.php';
        $scraper = new ScraperService($this->pdo, $this->config);
        return $scraper->execute();
    }
} 