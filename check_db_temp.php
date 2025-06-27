<?php
include 'config.php';
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    
    echo "Covers en la base de datos:\n";
    $result = $pdo->query("SELECT id, country, title, source, created_at, updated_at FROM covers ORDER BY id DESC LIMIT 10");
    while ($row = $result->fetch()) {
        echo "ID: {$row['id']}, País: {$row['country']}, Título: " . substr($row['title'], 0, 30) . "..., Creado: {$row['created_at']}, Actualizado: {$row['updated_at']}\n";
    }
    
    echo "\nConteo total por país:\n";
    $result = $pdo->query("SELECT country, COUNT(*) as total FROM covers GROUP BY country ORDER BY total DESC");
    while ($row = $result->fetch()) {
        echo "{$row['country']}: {$row['total']} covers\n";
    }
    
    echo "\nCovers creados hoy:\n";
    $result = $pdo->query("SELECT COUNT(*) as hoy FROM covers WHERE DATE(created_at) = CURDATE()");
    $hoy = $result->fetch();
    echo "Covers de hoy: {$hoy['hoy']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
