<?php
// Configuración de base de datos
$config = [
    'host'    => 'localhost',
    'name'    => 'eyewatch_newsroom',
    'user'    => 'eyewatch_newsroom',
    'pass'    => 'w1F#riF>Tjw1F#riF>Tj',
    'charset' => 'utf8mb4',
];

try {
    // Crear conexión PDO
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<h2 style='color:green;'>✅ Conexión exitosa a la base de datos '{$config['name']}'</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>❌ Error de conexión:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
