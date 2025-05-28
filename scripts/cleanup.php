<?php

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = parse_ini_file(__DIR__ . '/../.env');
    foreach ($dotenv as $key => $value) {
        putenv("$key=$value");
    }
}

// Cargar configuración
$config = require __DIR__ . '/../config/app.php';

// Procesar argumentos
$options = getopt('', ['days:']);
$days = isset($options['days']) ? (int)$options['days'] : 7;

if ($days <= 0) {
    die("Error: El número de días debe ser mayor que 0\n");
}

// Conectar a la base de datos
try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['name']};charset={$config['database']['charset']}",
        $config['database']['user'],
        $config['database']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// Obtener imágenes antiguas
$stmt = $pdo->prepare("
    SELECT image_url 
    FROM covers 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
");

$stmt->execute([':days' => $days]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($images)) {
    echo "No se encontraron imágenes para eliminar.\n";
    exit(0);
}

// Eliminar imágenes
$imagesPath = $config['images']['storage_path'];
$deleted = 0;
$errors = [];

foreach ($images as $image) {
    $imagePath = $imagesPath . '/' . basename($image);
    
    if (file_exists($imagePath)) {
        if (unlink($imagePath)) {
            $deleted++;
        } else {
            $errors[] = "No se pudo eliminar: {$imagePath}";
        }
    }
}

// Eliminar registros de la base de datos
$stmt = $pdo->prepare("
    DELETE FROM covers 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
");

$stmt->execute([':days' => $days]);
$deletedRecords = $stmt->rowCount();

// Mostrar resultados
echo "Proceso completado:\n";
echo "- Imágenes eliminadas: {$deleted}\n";
echo "- Registros eliminados: {$deletedRecords}\n";

if (!empty($errors)) {
    echo "\nErrores encontrados:\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
}

// Registrar actividad
$stmt = $pdo->prepare("
    INSERT INTO activity_log (action, description) 
    VALUES ('cleanup', :description)
");

$description = "Limpieza de imágenes: {$deleted} archivos y {$deletedRecords} registros eliminados (>= {$days} días)";
$stmt->execute([':description' => $description]);

exit(0); 