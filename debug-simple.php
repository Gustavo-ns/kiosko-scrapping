<?php
require_once 'cache_config.php';

// Cargar configuración de la base de datos
$cfg = require 'config.php';

echo "<!DOCTYPE html><html><head><title>Debug Simple</title></head><body>";
echo "<h1>Debug - Diagnóstico Básico</h1>";

try {
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<p>✅ Conexión a base de datos exitosa</p>";

    // Verificar tablas
    $tables = ['pk_melwater', 'covers', 'pk_meltwater_resumen', 'medios'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
            $count = $stmt->fetch();
            echo "<p><strong>$table:</strong> {$count['total']} registros</p>";
        } catch (Exception $e) {
            echo "<p><strong>$table:</strong> ❌ Error: " . $e->getMessage() . "</p>";
        }
    }

    // Obtener documentos con visualizar = 1
    $stmt = $pdo->query("
        SELECT 
            med.*,
            pk.*,
            'meltwater' as source_type
        FROM pk_melwater pk
        LEFT JOIN medios med ON pk.external_id = med.twitter_id
        WHERE med.visualizar = 1
        LIMIT 5
    ");
    $meltwater_docs = $stmt->fetchAll();
    
    echo "<h2>Documentos Meltwater encontrados: " . count($meltwater_docs) . "</h2>";
    
    if (count($meltwater_docs) > 0) {
        foreach ($meltwater_docs as $index => $doc) {
            echo "<div style='border:1px solid #ddd; padding:10px; margin:5px;'>";
            echo "<h4>Documento " . ($index + 1) . "</h4>";
            echo "<p>Título: " . htmlspecialchars($doc['title'] ?? 'Sin título') . "</p>";
            echo "<p>Grupo: " . htmlspecialchars($doc['grupo'] ?? 'No definido') . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ No se encontraron documentos con visualizar = 1</p>";
    }

} catch (PDOException $e) {
    echo "<p>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
