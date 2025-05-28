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

// Crear directorio de backup si no existe
$backupDir = __DIR__ . '/../database/backups';
if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0777, true)) {
        die("Error: No se pudo crear el directorio de backup\n");
    }
}

// Generar nombre del archivo
$date = date('Y-m-d_H-i-s');
$filename = "{$backupDir}/backup_{$date}.sql";

// Construir comando mysqldump
$command = sprintf(
    'mysqldump --host=%s --user=%s --password=%s %s > %s',
    escapeshellarg($config['database']['host']),
    escapeshellarg($config['database']['user']),
    escapeshellarg($config['database']['pass']),
    escapeshellarg($config['database']['name']),
    escapeshellarg($filename)
);

// Ejecutar backup
system($command, $returnVar);

if ($returnVar !== 0) {
    die("Error: No se pudo crear el backup\n");
}

// Comprimir el archivo
$zip = new ZipArchive();
$zipname = "{$filename}.zip";

if ($zip->open($zipname, ZipArchive::CREATE) !== true) {
    unlink($filename);
    die("Error: No se pudo crear el archivo ZIP\n");
}

$zip->addFile($filename, basename($filename));
$zip->close();

// Eliminar archivo SQL sin comprimir
unlink($filename);

// Limpiar backups antiguos (mantener últimos 7)
$backups = glob("{$backupDir}/backup_*.zip");
usort($backups, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

if (count($backups) > 7) {
    for ($i = 7; $i < count($backups); $i++) {
        unlink($backups[$i]);
    }
}

// Conectar a la base de datos para registrar la actividad
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
    
    // Registrar actividad
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (action, description) 
        VALUES ('backup', :description)
    ");
    
    $description = "Backup creado: " . basename($zipname);
    $stmt->execute([':description' => $description]);
    
} catch (PDOException $e) {
    echo "Advertencia: No se pudo registrar la actividad en la base de datos\n";
}

echo "Backup completado: " . basename($zipname) . "\n";
exit(0); 