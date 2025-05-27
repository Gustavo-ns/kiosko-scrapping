<?php

class DatabaseConnection {
    private static $instance = null;
    private $connection;
    private $config;

    private function __construct() {
        // Cargar configuración
        $configFile = dirname(dirname(__DIR__)) . '/config.php';
        if (!file_exists($configFile)) {
            throw new Exception("Archivo de configuración no encontrado");
        }
        
        $this->config = require $configFile;
        
        if (!isset($this->config['db'])) {
            throw new Exception("Configuración de base de datos no encontrada");
        }

        $dbConfig = $this->config['db'];

        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $dbConfig['host'],
                $dbConfig['name'],
                $dbConfig['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $dbConfig['user'],
                $dbConfig['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevenir la clonación del objeto
    private function __clone() {}

    // Prevenir la deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
} 