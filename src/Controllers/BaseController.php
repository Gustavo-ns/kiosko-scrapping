<?php

namespace App\Controllers;

class BaseController
{
    protected $config;
    protected $pdo;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        
        // Inicializar conexión PDO
        try {
            $this->pdo = new \PDO(
                "mysql:host={$this->config['database']['host']};dbname={$this->config['database']['name']};charset={$this->config['database']['charset']}",
                $this->config['database']['user'],
                $this->config['database']['pass'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
        } catch (\PDOException $e) {
            die('Error de conexión: ' . $e->getMessage());
        }
    }

    protected function view($name, $data = [])
    {
        extract($data);
        $viewPath = $this->config['paths']['views'] . '/' . $name . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("Vista no encontrada: {$name}");
        }
        
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    protected function redirect($route)
    {
        header("Location: ?route={$route}");
        exit;
    }

    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 