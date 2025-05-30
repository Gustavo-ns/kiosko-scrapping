<?php
// Verificar estructura de directorios para carga progresiva
echo "=== Verificación de Estructura de Directorios ===\n\n";

$directories = [
    'images/covers',
    'images/covers/thumbnails', 
    'images/covers/previews',
    'images/melwater',
    'images/melwater/thumbnails',
    'images/melwater/previews'
];

foreach ($directories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    $exists = file_exists($fullPath);
    $writable = is_writable($fullPath);
    
    echo "📁 $dir: ";
    if ($exists) {
        echo "✅ Existe";
        if ($writable) {
            echo " - ✅ Escribible";
        } else {
            echo " - ❌ No escribible";
        }
        
        // Contar archivos
        $files = glob($fullPath . '/*');
        $count = count($files);
        echo " - $count archivos";
    } else {
        echo "❌ No existe";
    }
    echo "\n";
}

echo "\n=== Verificación de Base de Datos ===\n";

require_once 'config.php';
$cfg = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Verificar columnas en la tabla covers
    $stmt = $pdo->query("DESCRIBE covers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedColumns = ['preview_url', 'thumbnail_url', 'original_url'];
    
    echo "Columnas en tabla covers:\n";
    foreach ($expectedColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ $col\n";
        } else {
            echo "❌ $col (faltante)\n";
        }
    }
    
    // Verificar datos recientes con nueva estructura
    $stmt = $pdo->query("SELECT COUNT(*) as total, 
                                COUNT(preview_url) as with_preview,
                                COUNT(thumbnail_url) as with_thumbnail,
                                COUNT(original_url) as with_original
                         FROM covers 
                         WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stats = $stmt->fetch();
    
    echo "\nEstadísticas de imágenes (últimas 24h):\n";
    echo "Total: {$stats['total']}\n";
    echo "Con preview: {$stats['with_preview']}\n";
    echo "Con thumbnail: {$stats['with_thumbnail']}\n";
    echo "Con original: {$stats['with_original']}\n";
    
} catch (Exception $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
}

echo "\n=== Prueba de Función downloadImage ===\n";

require_once 'download_image.php';

// Probar con una imagen de ejemplo
$testUrl = 'https://via.placeholder.com/800x600/0066cc/ffffff?text=Test+Image';
$testId = 'test_' . time();

echo "Probando descarga de imagen: $testUrl\n";
$result = downloadImage($testUrl, $testId);

if ($result && is_array($result)) {
    echo "✅ Función downloadImage funcionando\n";
    foreach ($result as $type => $path) {
        $exists = file_exists($path);
        $size = $exists ? filesize($path) : 0;
        echo "  $type: $path (" . ($exists ? "✅ $size bytes" : "❌ No existe") . ")\n";
    }
} else {
    echo "❌ Error en función downloadImage\n";
}

echo "\n=== Verificación Completa ===\n";
?>
