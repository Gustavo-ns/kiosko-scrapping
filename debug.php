<?php
require_once 'cache_config.php';

// Cargar configuración de la base de datos
$cfg = require 'config.php';

echo "<!-- Debug: Iniciando aplicación -->\n";

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
    
    echo "<!-- Debug: Conexión DB exitosa -->\n";

    // Obtener los datos de Meltwater
    $stmt = $pdo->query("
        SELECT 
            med.*,
            pk.*,
            'meltwater' as source_type
        FROM pk_melwater pk
        LEFT JOIN medios med ON pk.external_id = med.twitter_id
        WHERE med.visualizar = 1
        ORDER BY med.grupo, med.pais, med.dereach DESC
        LIMIT 10
    ");
    $meltwater_docs = $stmt->fetchAll();
    
    echo "<!-- Debug: Documentos Meltwater: " . count($meltwater_docs) . " -->\n";

    // Obtener los datos de covers
    $stmt = $pdo->query("
        SELECT 
            c.*,
            med.*,
            'cover' as source_type
        FROM covers c
        LEFT JOIN medios med ON c.source = med.source
        WHERE med.visualizar = 1
        ORDER BY c.scraped_at DESC
        LIMIT 10
    ");
    $covers = $stmt->fetchAll();
    
    echo "<!-- Debug: Documentos Covers: " . count($covers) . " -->\n";

    // Obtener los datos de pk_meltwater_resumen
    $stmt = $pdo->query("
        SELECT *, 'resumen' as source_type FROM `pk_meltwater_resumen`
        WHERE visualizar = 1 
        LIMIT 10
    ");
    $pk_meltwater_resumen = $stmt->fetchAll();
    
    echo "<!-- Debug: Documentos Resumen: " . count($pk_meltwater_resumen) . " -->\n";

    // Combinar todos los documentos
    $documents = array_merge($meltwater_docs, $covers, $pk_meltwater_resumen);
    
    echo "<!-- Debug: Total documentos combinados: " . count($documents) . " -->\n";

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Kiosko Scrapping</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .card h3 { margin: 0 0 10px 0; color: #333; }
        .card p { margin: 5px 0; color: #666; }
    </style>
</head>
<body>
    <h1>Debug - Datos de la Base de Datos</h1>
    
    <div class="debug">
        <strong>Resumen de datos:</strong><br>
        Documentos Meltwater: <?= count($meltwater_docs) ?><br>
        Documentos Covers: <?= count($covers) ?><br>
        Documentos Resumen: <?= count($pk_meltwater_resumen) ?><br>
        <strong>Total: <?= count($documents) ?></strong>
    </div>

    <?php if (empty($documents)): ?>
        <div class="debug" style="background: #ffdddd;">
            <strong>⚠️ No se encontraron documentos en la base de datos</strong><br>
            Esto puede indicar que:
            <ul>
                <li>Las tablas están vacías</li>
                <li>Todos los registros tienen visualizar = 0</li>
                <li>Hay un problema con las consultas SQL</li>
            </ul>
        </div>
    <?php else: ?>
        <h2>Primeros 5 documentos encontrados:</h2>
        <?php foreach (array_slice($documents, 0, 5) as $index => $doc): ?>
            <div class="card">
                <h3>Documento #<?= $index + 1 ?> (<?= htmlspecialchars($doc['source_type']) ?>)</h3>
                <p><strong>Título:</strong> <?= htmlspecialchars($doc['title'] ?? $doc['titulo'] ?? 'Sin título') ?></p>
                <p><strong>Grupo:</strong> <?= htmlspecialchars($doc['grupo'] ?? 'No definido') ?></p>
                <p><strong>País:</strong> <?= htmlspecialchars($doc['pais'] ?? $doc['country'] ?? 'No definido') ?></p>
                <p><strong>Imagen:</strong> <?= htmlspecialchars($doc['content_image'] ?? $doc['image_url'] ?? $doc['source'] ?? 'Sin imagen') ?></p>
                <p><strong>Fecha:</strong> <?= htmlspecialchars($doc['published_date'] ?? $doc['scraped_at'] ?? $doc['created_at'] ?? 'Sin fecha') ?></p>
                <p><strong>Visualizar:</strong> <?= $doc['visualizar'] ?? 'No definido' ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Estructura de las tablas:</h2>
    <div class="debug">
        <?php
        // Verificar estructura de tablas
        try {
            $tables = ['pk_melwater', 'covers', 'pk_meltwater_resumen', 'medios'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->fetch();
                if ($exists) {
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
                    $count = $stmt->fetch();
                    echo "<strong>$table:</strong> {$count['total']} registros<br>";
                } else {
                    echo "<strong>$table:</strong> ❌ No existe<br>";
                }
            }
        } catch (Exception $e) {
            echo "Error verificando tablas: " . $e->getMessage();
        }
        ?>
    </div>
</body>
</html>
