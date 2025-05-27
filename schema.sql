-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS eyewatch_newsroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eyewatch_newsroom;

-- Tabla de configuraciones
CREATE TABLE IF NOT EXISTS configs (
    name VARCHAR(50) PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de portadas
CREATE TABLE IF NOT EXISTS covers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    source VARCHAR(255) NOT NULL,
    original_link TEXT NOT NULL,
    local_path VARCHAR(255),
    status ENUM('pending', 'downloaded', 'error') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_country (country),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear directorios necesarios
-- Nota: Esto debe ejecutarse manualmente en el sistema:
-- mkdir -p storage/images
-- mkdir -p storage/logs
-- mkdir -p logs
-- chmod -R 755 storage
-- chmod -R 755 logs 