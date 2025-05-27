<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Diagnóstico del Sistema ===\n\n";

// 1. Verificar archivo de configuración
echo "1. Archivo de Configuración\n";
echo "-------------------------\n";
$configFile = __DIR__ . '/app/config/config.php';
if (file_exists($configFile)) {
    echo "[✓] Archivo config.php encontrado\n";
    $config = require $configFile;
    if (isset($config['db'])) {
        echo "[✓] Configuración de base de datos presente\n";
        echo "Host: " . $config['db']['host'] . "\n";
        echo "Base de datos: " . $config['db']['name'] . "\n";
        echo "Usuario: " . $config['db']['user'] . "\n";
        echo "Charset: " . $config['db']['charset'] . "\n";
    } else {
        echo "[✗] Error: Configuración de base de datos no encontrada en config.php\n";
    }
} else {
    echo "[✗] Error: No se encuentra el archivo config.php\n";
}

echo "\n";

// 2. Verificar extensiones PHP
echo "2. Extensiones PHP\n";
echo "-------------------------\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'imagick'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "[✓] {$ext} está instalada\n";
    } else {
        echo "[✗] {$ext} NO está instalada\n";
    }
}

echo "\n";

// 3. Verificar conexión a la base de datos
echo "3. Conexión a la Base de Datos\n";
echo "-------------------------\n";
try {
    require_once __DIR__ . '/app/config/DatabaseConnection.php';
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
    echo "[✓] Conexión exitosa a la base de datos\n";

    // Verificar tabla covers
    $stmt = $pdo->query("SHOW TABLES LIKE 'covers'");
    if ($stmt->rowCount() > 0) {
        echo "[✓] Tabla 'covers' existe\n";
        
        // Verificar estructura
        $stmt = $pdo->query("DESCRIBE covers");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Columnas encontradas: " . implode(", ", $columns) . "\n";
        
        // Verificar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM covers");
        $count = $stmt->fetchColumn();
        echo "Total de registros: " . $count . "\n";
        
        if ($count > 0) {
            // Mostrar último registro
            $stmt = $pdo->query("SELECT * FROM covers ORDER BY id DESC LIMIT 1");
            $lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\nÚltimo registro:\n";
            echo "ID: " . $lastRecord['id'] . "\n";
            echo "País: " . $lastRecord['country'] . "\n";
            echo "Título: " . $lastRecord['title'] . "\n";
            echo "URL Imagen: " . $lastRecord['image_url'] . "\n";
        }
    } else {
        echo "[✗] La tabla 'covers' no existe\n";
    }
} catch (PDOException $e) {
    echo "[✗] Error de conexión: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Verificar directorios
echo "4. Directorios\n";
echo "-------------------------\n";
$directories = [
    'storage/images' => __DIR__ . '/storage/images',
    'logs' => __DIR__ . '/logs'
];

foreach ($directories as $name => $path) {
    if (is_dir($path)) {
        echo "[✓] Directorio '{$name}' existe\n";
        if (is_writable($path)) {
            echo "[✓] Directorio '{$name}' tiene permisos de escritura\n";
        } else {
            echo "[✗] Directorio '{$name}' NO tiene permisos de escritura\n";
        }
    } else {
        echo "[✗] Directorio '{$name}' NO existe\n";
    }
}

echo "\n";

// 5. Verificar logs
echo "5. Archivos de Log\n";
echo "-------------------------\n";
$logFiles = [
    'scrape_errors.log' => __DIR__ . '/logs/scrape_errors.log',
    'php_errors.log' => __DIR__ . '/logs/php_errors.log',
    'cron.log' => __DIR__ . '/logs/cron_' . date('Y-m-d') . '.log'
];

foreach ($logFiles as $name => $path) {
    if (file_exists($path)) {
        echo "[✓] {$name} existe\n";
        $size = filesize($path);
        echo "Tamaño: " . round($size / 1024, 2) . " KB\n";
        if ($size > 0) {
            echo "Últimas líneas:\n";
            $lines = array_slice(file($path), -5);
            echo implode("", $lines) . "\n";
        }
    } else {
        echo "[i] {$name} no existe (podría crearse cuando sea necesario)\n";
    }
}

// 6. Verificar configuración de scraping
echo "\n6. Configuración de Scraping\n";
echo "-------------------------\n";
$configFile = __DIR__ . '/app/config/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    if (isset($config['sites'])) {
        echo "[✓] Configuración de sitios presente\n";
        echo "Total de países configurados: " . count($config['sites']) . "\n";
        foreach ($config['sites'] as $country => $sites) {
            echo "- {$country}: " . count($sites) . " sitios\n";
        }
    } else {
        echo "[✗] No se encontró la configuración de sitios\n";
    }
} else {
    echo "[✗] No se puede acceder al archivo de configuración\n";
} 