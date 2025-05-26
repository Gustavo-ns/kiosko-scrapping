<?php
class DatabaseConnection {
    private static $instance = null;
    private $connection = null;
    private $config;

    private function __construct() {
        $this->config = require __DIR__ . '/config.php';
        $this->connect();
    }

    private function connect() {
        // Si ya hay una conexión, intentar cerrarla primero
        if ($this->connection !== null) {
            $this->connection = null;
        }

        try {
            $this->connection = new PDO(
                "mysql:host={$this->config['db']['host']};dbname={$this->config['db']['name']};charset={$this->config['db']['charset']}",
                $this->config['db']['user'],
                $this->config['db']['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Establecer un tiempo de espera más corto
                    PDO::ATTR_TIMEOUT => 5,
                    // Cerrar la conexión cuando se complete la transacción
                    PDO::ATTR_PERSISTENT => false
                ]
            );

            // Establecer un tiempo máximo de espera para consultas
            $this->connection->setAttribute(PDO::ATTR_TIMEOUT, 5);
            
            // Configurar el modo de transacción
            $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        } catch (PDOException $e) {
            // Si falla la conexión, esperar un momento y reintentar una vez
            sleep(1);
            $this->connection = new PDO(
                "mysql:host={$this->config['db']['host']};dbname={$this->config['db']['name']};charset={$this->config['db']['charset']}",
                $this->config['db']['user'],
                $this->config['db']['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if (!$this->connection || !$this->isConnected()) {
            $this->connect();
        }
        return $this->connection;
    }

    private function isConnected() {
        try {
            return $this->connection && $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            return false;
        }
    }

    public function closeConnection() {
        if ($this->connection) {
            $this->connection = null;
        }
    }

    public function __destruct() {
        $this->closeConnection();
    }

    // Prevenir la clonación del objeto
    private function __clone() {}

    // Prevenir la deserialización
    private function __wakeup() {}
} 