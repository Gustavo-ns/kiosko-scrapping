<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir la ruta base del proyecto
define('BASE_PATH', dirname(dirname(__FILE__)));
require_once BASE_PATH . '/app/config/DatabaseConnection.php';

try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=eyewatch_newsroom;charset=utf8mb4",
        "eyewatch_newsroom",
        "w1F#riF>Tjw1F#riF>Tj",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== Diagnóstico de la Base de Datos ===\n\n";

    // 1. Verificar conexión
    echo "1. Conexión a la base de datos:\n";
    echo "----------------------------------------\n";
    echo "Host: 127.0.0.1\n";
    echo "Base de datos: eyewatch_newsroom\n";
    echo "Charset: utf8mb4\n";
    echo "Ruta base: " . BASE_PATH . "\n\n";

    // 2. Verificar tabla covers
    echo "2. Estructura de la tabla covers:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SHOW CREATE TABLE covers");
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $tableInfo['Create Table'] . "\n\n";

    // 3. Total de registros
    echo "3. Estadísticas de registros:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM covers");
    $total = $stmt->fetchColumn();
    echo "Total de registros: " . $total . "\n\n";

    // 4. Registros por país
    if ($total > 0) {
        echo "4. Registros por país:\n";
        echo "----------------------------------------\n";
        $stmt = $pdo->query("SELECT country, COUNT(*) as total FROM covers GROUP BY country ORDER BY total DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo str_pad($row['country'], 20) . ": " . $row['total'] . " portadas\n";
        }
        echo "\n";

        // 5. Últimos 5 registros
        echo "5. Últimos 5 registros agregados:\n";
        echo "----------------------------------------\n";
        $stmt = $pdo->query("SELECT id, country, title, image_url, source, scraped_at FROM covers ORDER BY scraped_at DESC LIMIT 5");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: " . $row['id'] . "\n";
            echo "País: " . $row['country'] . "\n";
            echo "Título: " . $row['title'] . "\n";
            echo "URL Imagen: " . $row['image_url'] . "\n";
            echo "Fuente: " . $row['source'] . "\n";
            echo "Fecha: " . $row['scraped_at'] . "\n";
            echo "----------------------------------------\n";
        }
    }

    // 6. Verificar última actualización
    echo "6. Información de actualización:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT * FROM configs WHERE name IN ('last_scrape_date', 'last_scrape_status')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['name'] . ": " . $row['value'] . "\n";
    }

    // 7. Verificar logs recientes
    echo "\n7. Últimos errores registrados:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT * FROM error_log ORDER BY created_at DESC LIMIT 5");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Fecha: " . $row['created_at'] . "\n";
            echo "Error: " . $row['message'] . "\n";
            echo "----------------------------------------\n";
        }
    } else {
        echo "No se encontró tabla de logs\n";
    }

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
} 