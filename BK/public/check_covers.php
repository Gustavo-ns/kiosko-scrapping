<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir la ruta base del proyecto
define('BASE_PATH', dirname(__DIR__));

// Verificar que el archivo existe antes de incluirlo
$dbConnectionFile = BASE_PATH . '/app/config/DatabaseConnection.php';
if (!file_exists($dbConnectionFile)) {
    die("Error: No se encuentra el archivo DatabaseConnection.php en: {$dbConnectionFile}\n");
}

require_once $dbConnectionFile;

try {
    // Conectar a la base de datos
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();

    // 1. Mostrar estructura de la tabla
    echo "Estructura de la tabla covers:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("DESCRIBE covers");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 15) . " | " . 
             str_pad($row['Type'], 20) . " | " . 
             ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " | " . 
             $row['Default'] . "\n";
    }

    // 2. Verificar total de registros por país
    echo "\nRegistros por país:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT country, COUNT(*) as total FROM covers GROUP BY country ORDER BY total DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['country'], 20) . ": " . $row['total'] . " portadas\n";
    }

    // 3. Verificar registros más recientes
    echo "\nÚltimas 5 portadas agregadas:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT id, country, title, created_at as scraped_at FROM covers ORDER BY created_at DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . str_pad($row['id'], 5) . " | ";
        echo "País: " . str_pad($row['country'], 10) . " | ";
        echo "Título: " . str_pad($row['title'], 30) . " | ";
        echo "Fecha: " . $row['scraped_at'] . "\n";
    }

    // 4. Verificar registros sin imágenes o con problemas
    echo "\nRegistros potencialmente problemáticos:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT id, country, title, image_url FROM covers WHERE image_url = '' OR image_url IS NULL");
    $problemCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . str_pad($row['id'], 5) . " | ";
        echo "País: " . str_pad($row['country'], 10) . " | ";
        echo "Título: " . str_pad($row['title'], 30) . " | ";
        echo "URL Imagen: " . ($row['image_url'] ?: 'VACÍO') . "\n";
        $problemCount++;
    }
    if ($problemCount === 0) {
        echo "No se encontraron registros problemáticos.\n";
    }

    // 5. Verificar directorio de imágenes
    echo "\nVerificando directorio de imágenes:\n";
    echo "----------------------------------------\n";
    $imageDir = BASE_PATH . '/storage/images';
    if (is_dir($imageDir)) {
        $images = glob($imageDir . '/*');
        echo "Total de archivos de imagen: " . count($images) . "\n";
        if (count($images) > 0) {
            echo "Últimas 5 imágenes:\n";
            $recentImages = array_slice($images, -5);
            foreach ($recentImages as $image) {
                echo basename($image) . " (" . date("Y-m-d H:i:s", filemtime($image)) . ")\n";
            }
        }
    } else {
        echo "El directorio de imágenes no existe: {$imageDir}\n";
        echo "Intentando crear el directorio...\n";
        if (@mkdir($imageDir, 0755, true)) {
            echo "Directorio creado exitosamente.\n";
        } else {
            echo "No se pudo crear el directorio.\n";
        }
    }

    // 6. Verificar logs de errores
    $logFiles = [
        BASE_PATH . '/logs/scrape_errors.log',
        BASE_PATH . '/logs/cron_' . date('Y-m-d') . '.log',
        BASE_PATH . '/storage/logs/error.log'
    ];

    echo "\nÚltimos errores en logs:\n";
    echo "----------------------------------------\n";
    $foundLogs = false;
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile)) {
            echo "\n>> " . basename($logFile) . ":\n";
            $lines = file($logFile);
            if (!empty($lines)) {
                $lastLines = array_slice($lines, -5);
                echo implode("", $lastLines);
                $foundLogs = true;
            }
        }
    }
    if (!$foundLogs) {
        echo "No se encontraron archivos de log.\n";
        echo "Rutas buscadas:\n";
        foreach ($logFiles as $logFile) {
            echo "- {$logFile}\n";
        }
    }

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
} 