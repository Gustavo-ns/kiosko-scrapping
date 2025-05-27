<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/config/DatabaseConnection.php';

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
    $stmt = $pdo->query("SELECT id, country, title, scraped_at FROM covers ORDER BY scraped_at DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | ";
        echo "País: " . $row['country'] . " | ";
        echo "Título: " . $row['title'] . " | ";
        echo "Fecha: " . $row['scraped_at'] . "\n";
    }

    // 4. Verificar registros sin imágenes o con problemas
    echo "\nRegistros potencialmente problemáticos:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT id, country, title, image_url FROM covers WHERE image_url = '' OR image_url IS NULL");
    $problemCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | ";
        echo "País: " . $row['country'] . " | ";
        echo "Título: " . $row['title'] . " | ";
        echo "URL Imagen: " . ($row['image_url'] ?: 'VACÍO') . "\n";
        $problemCount++;
    }
    if ($problemCount === 0) {
        echo "No se encontraron registros problemáticos.\n";
    }

    // 5. Verificar directorio de imágenes
    echo "\nVerificando directorio de imágenes:\n";
    echo "----------------------------------------\n";
    $imageDir = __DIR__ . '/storage/images';
    if (is_dir($imageDir)) {
        $images = glob($imageDir . '/*');
        echo "Total de archivos de imagen: " . count($images) . "\n";
        if (count($images) > 0) {
            echo "Últimas 5 imágenes:\n";
            $recentImages = array_slice($images, -5);
            foreach ($recentImages as $image) {
                echo basename($image) . "\n";
            }
        }
    } else {
        echo "El directorio de imágenes no existe.\n";
    }

    // 6. Verificar logs de errores
    $logFiles = [
        'logs/scrape_errors.log',
        'logs/cron_' . date('Y-m-d') . '.log',
        'storage/logs/error.log'
    ];

    echo "\nÚltimos errores en logs:\n";
    echo "----------------------------------------\n";
    $foundLogs = false;
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile)) {
            echo "\n>> $logFile:\n";
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
    }

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
} 