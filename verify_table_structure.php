<?php
require_once 'config.php';

$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
    $config['db']['user'], $config['db']['pass']);

echo "Estructura de la tabla covers:\n";
echo "================================\n";
$stmt = $pdo->query('DESCRIBE covers');
while ($row = $stmt->fetch()) {
    echo sprintf("%-20s %-30s %-15s %-10s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Default']
    );
}

echo "\nÍndices de la tabla covers:\n";
echo "============================\n";
$stmt = $pdo->query('SHOW INDEX FROM covers');
while ($row = $stmt->fetch()) {
    echo sprintf("%-20s %-20s %-10s\n", 
        $row['Key_name'], 
        $row['Column_name'], 
        $row['Non_unique'] ? 'No' : 'Sí'
    );
}

echo "\nRegistros actuales:\n";
echo "===================\n";
$stmt = $pdo->query('SELECT COUNT(*) as total FROM covers');
$count = $stmt->fetch();
echo "Total de covers: " . $count['total'] . "\n";
?>
