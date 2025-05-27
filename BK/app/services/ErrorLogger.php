<?php

class ErrorLogger {
    private $pdo;
    private static $LEVELS = ['ERROR', 'WARNING', 'INFO', 'DEBUG'];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Registra un error en la base de datos
     * 
     * @param string $level Nivel del error (ERROR, WARNING, INFO, DEBUG)
     * @param string $message Mensaje del error
     * @param array $context Contexto adicional
     * @param string $file Archivo donde ocurrió el error
     * @param int $line Línea donde ocurrió el error
     * @param string $trace Stack trace del error
     * @param string $url URL relacionada con el error
     * @param string $country País relacionado con el error
     * @return bool
     */
    public function log($level, $message, $context = [], $file = null, $line = null, $trace = null, $url = null, $country = null) {
        $level = strtoupper($level);
        if (!in_array($level, self::$LEVELS)) {
            $level = 'ERROR';
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO error_log (
                    level, message, context, file, line, trace, url, country
                ) VALUES (
                    :level, :message, :context, :file, :line, :trace, :url, :country
                )
            ");

            return $stmt->execute([
                ':level' => $level,
                ':message' => $message,
                ':context' => $context ? json_encode($context) : null,
                ':file' => $file,
                ':line' => $line,
                ':trace' => $trace,
                ':url' => $url,
                ':country' => $country
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando en error_log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un error
     */
    public function error($message, $context = [], $file = null, $line = null, $trace = null, $url = null, $country = null) {
        return $this->log('ERROR', $message, $context, $file, $line, $trace, $url, $country);
    }

    /**
     * Registra una advertencia
     */
    public function warning($message, $context = [], $file = null, $line = null, $trace = null, $url = null, $country = null) {
        return $this->log('WARNING', $message, $context, $file, $line, $trace, $url, $country);
    }

    /**
     * Registra información
     */
    public function info($message, $context = [], $file = null, $line = null, $trace = null, $url = null, $country = null) {
        return $this->log('INFO', $message, $context, $file, $line, $trace, $url, $country);
    }

    /**
     * Registra un mensaje de depuración
     */
    public function debug($message, $context = [], $file = null, $line = null, $trace = null, $url = null, $country = null) {
        return $this->log('DEBUG', $message, $context, $file, $line, $trace, $url, $country);
    }

    /**
     * Obtiene los últimos errores registrados
     * 
     * @param int $limit Número máximo de errores a retornar
     * @param string $level Filtrar por nivel de error
     * @param string $country Filtrar por país
     * @return array
     */
    public function getLatest($limit = 10, $level = null, $country = null) {
        try {
            $sql = "SELECT * FROM error_log WHERE 1=1";
            $params = [];

            if ($level) {
                $sql .= " AND level = :level";
                $params[':level'] = strtoupper($level);
            }

            if ($country) {
                $sql .= " AND country = :country";
                $params[':country'] = $country;
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit";
            $params[':limit'] = (int)$limit;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo errores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpia errores antiguos
     * 
     * @param int $days Número de días de antigüedad para mantener
     * @return bool
     */
    public function cleanup($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM error_log 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            return $stmt->execute([':days' => $days]);
        } catch (PDOException $e) {
            error_log("Error limpiando error_log: " . $e->getMessage());
            return false;
        }
    }
} 