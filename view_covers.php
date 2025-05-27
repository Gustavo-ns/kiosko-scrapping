<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/config/DatabaseConnection.php';

try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=eyewatch_newsroom;charset=utf8mb4",
        "eyewatch_newsroom",
        "w1F#riF>Tjw1F#riF>Tj",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== Registros en la tabla covers ===\n\n";

    // 1. Total de registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM covers");
    $total = $stmt->fetchColumn();
    echo "Total de registros: " . $total . "\n\n";

    // 2. Registros por país
    echo "Registros por país:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT country, COUNT(*) as total FROM covers GROUP BY country ORDER BY total DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['country'], 20) . ": " . $row['total'] . " portadas\n";
    }

    // 3. Últimos 5 registros
    echo "\nÚltimos 5 registros agregados:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT country, title, DATE_FORMAT(scraped_at, '%Y-%m-%d %H:%i') as fecha FROM covers ORDER BY scraped_at DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['fecha'] . " | " . str_pad($row['country'], 10) . " | " . $row['title'] . "\n";
    }

    // 4. Verificar registros sin imágenes
    echo "\nRegistros sin imágenes:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT id, country, title FROM covers WHERE image_url = '' OR image_url IS NULL");
    $problemCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . " | ";
        echo "País: " . $row['country'] . " | ";
        echo "Título: " . $row['title'] . "\n";
        $problemCount++;
    }
    if ($problemCount === 0) {
        echo "No se encontraron registros sin imágenes.\n";
    }

    // 5. Verificar última actualización
    echo "\nÚltima actualización:\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT value FROM configs WHERE name = 'last_scrape_date'");
    $lastUpdate = $stmt->fetchColumn();
    echo "Fecha del último scraping: " . ($lastUpdate ?: 'Nunca') . "\n";

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
} 