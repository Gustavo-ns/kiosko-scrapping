-- Tabla para registro de errores
CREATE TABLE IF NOT EXISTS error_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL COMMENT 'Nivel del error: ERROR, WARNING, INFO, DEBUG',
    message TEXT NOT NULL COMMENT 'Mensaje del error',
    context TEXT COMMENT 'Contexto adicional en formato JSON',
    file VARCHAR(255) COMMENT 'Archivo donde ocurrió el error',
    line INT COMMENT 'Línea donde ocurrió el error',
    trace TEXT COMMENT 'Stack trace del error',
    url VARCHAR(255) COMMENT 'URL donde ocurrió el error (para errores de scraping)',
    country VARCHAR(50) COMMENT 'País relacionado con el error (para errores de scraping)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at),
    INDEX idx_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 