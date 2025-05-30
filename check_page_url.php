<?php
// Verificar si existe la columna page_url
header('Content-Type: text/plain');

$cfg = require 'config.php';

try {
    echo "Intentando conectar a la base de datos...\n";
    echo "Host: {$cfg['db']['host']}\n";
    echo "Database: {$cfg['db']['name']}\n";
    echo "User: {$cfg['db']['user']}\n\n";
    
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Conexión exitosa!\n\n";

    echo "=== Verificando columna page_url ===\n\n";

    // Verificar pk_melwater
    echo "Tabla pk_melwater:\n";
    $stmt = $pdo->query("DESCRIBE pk_melwater");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasPageUrl = false;
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'page_url') {
            $hasPageUrl = true;
        }
    }
    echo $hasPageUrl ? "✅ page_url EXISTE en pk_melwater\n\n" : "❌ page_url NO EXISTE en pk_melwater\n\n";

    // Verificar covers
    echo "Tabla covers:\n";
    $stmt = $pdo->query("DESCRIBE covers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasPageUrl = false;
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'page_url') {
            $hasPageUrl = true;
        }
    }
    echo $hasPageUrl ? "✅ page_url EXISTE en covers\n\n" : "❌ page_url NO EXISTE en covers\n\n";

    // Verificar pk_meltwater_resumen
    echo "Tabla pk_meltwater_resumen:\n";
    $stmt = $pdo->query("DESCRIBE pk_meltwater_resumen");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasPageUrl = false;
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'page_url') {
            $hasPageUrl = true;
        }
    }
    echo $hasPageUrl ? "✅ page_url EXISTE en pk_meltwater_resumen\n\n" : "❌ page_url NO EXISTE en pk_meltwater_resumen\n\n";

    // Verificar medios
    echo "Tabla medios:\n";
    $stmt = $pdo->query("DESCRIBE medios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasPageUrl = false;
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'page_url') {
            $hasPageUrl = true;
        }
    }
    echo $hasPageUrl ? "✅ page_url EXISTE en medios\n\n" : "❌ page_url NO EXISTE en medios\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
