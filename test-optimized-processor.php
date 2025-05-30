<?php
// test-optimized-processor.php - Prueba del procesador optimizado

require_once 'optimized-image-processor.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba del Procesador Optimizado de Imágenes</h1>";

// 1. Mostrar capacidades del servidor
echo "<h2>1. Capacidades del Servidor</h2>";
$capabilities = getImageProcessingCapabilities();

echo "<p><strong>GD disponible:</strong> " . ($capabilities['gd_available'] ? "✅" : "❌") . "</p>";
echo "<p><strong>Imagick disponible:</strong> " . ($capabilities['imagick_available'] ? "✅" : "❌") . "</p>";
echo "<p><strong>Soporte WebP:</strong> " . ($capabilities['webp_support'] ? "✅" : "❌") . "</p>";
echo "<p><strong>Procesador recomendado:</strong> " . $capabilities['recommended_processor'] . "</p>";

// 2. Buscar una imagen de prueba
echo "<h2>2. Procesamiento de Imagen de Prueba</h2>";
$imagesDir = __DIR__ . '/images/';
$testImages = glob($imagesDir . '*.jpg');

if (empty($testImages)) {
    echo "<p style='color: red;'>❌ No hay imágenes .jpg disponibles para probar.</p>";
    exit;
}

$testImage = $testImages[0];
$originalSize = filesize($testImage);

echo "<p><strong>Imagen de prueba:</strong> " . basename($testImage) . "</p>";
echo "<p><strong>Tamaño original:</strong> " . number_format($originalSize / 1024, 2) . " KB</p>";

// 3. Procesar imagen con configuración automática
echo "<h3>Procesamiento Automático</h3>";
$result = autoProcessImage($testImage, $imagesDir);

if ($result['success']) {
    echo "<p style='color: green;'>✅ Procesamiento exitoso!</p>";
    echo "<p><strong>Formato usado:</strong> " . $result['format_used'] . "</p>";
    echo "<p><strong>Tamaño final:</strong> " . number_format($result['final_size'] / 1024, 2) . " KB</p>";
    echo "<p><strong>Ahorro:</strong> " . $result['savings_percent'] . "%</p>";
    echo "<p><a href='images/" . basename($result['output_path']) . "' target='_blank'>Ver imagen procesada</a></p>";
} else {
    echo "<p style='color: red;'>❌ Error en el procesamiento: " . $result['error'] . "</p>";
}

// 4. Prueba manual con diferentes opciones
echo "<h3>Procesamiento Manual (JPEG forzado)</h3>";
$manualOptions = [
    'max_width' => 600,
    'max_height' => 900,
    'quality' => 80,
    'prefer_webp' => false, // Forzar JPEG
    'strip_metadata' => true
];

$manualResult = processOptimizedImage($testImage, $imagesDir, $manualOptions);

if ($manualResult['success']) {
    echo "<p style='color: green;'>✅ Procesamiento manual exitoso!</p>";
    echo "<p><strong>Formato usado:</strong> " . $manualResult['format_used'] . "</p>";
    echo "<p><strong>Tamaño final:</strong> " . number_format($manualResult['final_size'] / 1024, 2) . " KB</p>";
    echo "<p><strong>Ahorro:</strong> " . $manualResult['savings_percent'] . "%</p>";
    echo "<p><a href='images/" . basename($manualResult['output_path']) . "' target='_blank'>Ver imagen procesada (manual)</a></p>";
} else {
    echo "<p style='color: red;'>❌ Error en el procesamiento manual: " . $manualResult['error'] . "</p>";
}

echo "<hr>";
echo "<h2>3. Conclusiones</h2>";

if ($capabilities['webp_support']) {
    echo "<p style='color: green;'>🎉 <strong>¡Excelente!</strong> Tu servidor puede procesar imágenes WebP usando GD.</p>";
    echo "<p><strong>Recomendación:</strong> Integrar el procesador automático en scrape.php para obtener máximo ahorro de espacio.</p>";
} else {
    echo "<p style='color: orange;'>⚠️ WebP no disponible, pero puedes usar optimización JPEG avanzada.</p>";
    echo "<p><strong>Recomendación:</strong> Usar el procesador con JPEG optimizado para reducir el tamaño de archivos.</p>";
}

echo "<p><strong>Siguiente paso:</strong> Si estás satisfecho con los resultados, podemos modificar scrape.php para usar este procesador.</p>";
?>
