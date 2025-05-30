<?php
// test-scrape-webp.php - Prueba rápida de scraping con WebP

require_once 'optimized-image-processor.php';

echo "<h1>Prueba de Scraping con WebP</h1>";

// Simular una imagen de prueba
$testImageUrl = 'https://via.placeholder.com/400x600/4CAF50/FFFFFF?text=Prueba+WebP';
$country = 'test';
$alt = 'Imagen de prueba';

echo "<h2>1. Verificar capacidades del servidor</h2>";
$capabilities = getImageProcessingCapabilities();
echo "<p><strong>Procesador recomendado:</strong> " . $capabilities['recommended_processor'] . "</p>";
echo "<p><strong>WebP soportado:</strong> " . ($capabilities['webp_support'] ? "✅ Sí" : "❌ No") . "</p>";

echo "<h2>2. Simular función saveImageLocally con WebP</h2>";

function testSaveImageLocally($imageUrl, $country, $alt) {
    $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $alt) . '_' . uniqid();
    $savePath = __DIR__ . "/images/";

    // Simular descarga con cURL simple
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$imageData) {
        echo "<p style='color: red;'>❌ Error descargando imagen: HTTP $httpCode</p>";
        return false;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tempFile, $imageData);    try {
        // Usar el procesador optimizado
        $result = processOptimizedImage($tempFile, $savePath, [
            'max_width' => 600,
            'max_height' => 900,
            'quality' => 85,
            'prefer_webp' => true,
            'strip_metadata' => true
        ]);
        
        // Limpiar archivo temporal
        unlink($tempFile);
        
        if ($result['success']) {
            echo "<p style='color: green;'>✅ Imagen procesada exitosamente!</p>";
            echo "<p><strong>Formato usado:</strong> " . $result['format_used'] . "</p>";
            echo "<p><strong>Tamaño original:</strong> " . number_format($result['original_size'] / 1024, 2) . " KB</p>";
            echo "<p><strong>Tamaño final:</strong> " . number_format($result['final_size'] / 1024, 2) . " KB</p>";
            echo "<p><strong>Ahorro:</strong> " . $result['savings_percent'] . "%</p>";
            echo "<p><a href='images/" . basename($result['output_path']) . "' target='_blank'>Ver imagen procesada</a></p>";
            return "images/" . basename($result['output_path']);
        } else {
            echo "<p style='color: red;'>❌ Error procesando imagen: " . $result['error'] . "</p>";
            return false;
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Excepción: " . $e->getMessage() . "</p>";
        if (file_exists($tempFile)) unlink($tempFile);
        return false;
    }
}

// Ejecutar prueba
$result = testSaveImageLocally($testImageUrl, $country, $alt);

echo "<hr>";
echo "<h2>3. Conclusión</h2>";

if ($result) {
    echo "<p style='color: green;'>🎉 <strong>¡Éxito!</strong> La integración de WebP en el scraping está funcionando correctamente.</p>";
    if ($capabilities['webp_support']) {
        echo "<p><strong>Tu servidor aprovechará WebP para reducir significativamente el tamaño de las imágenes.</strong></p>";
    } else {
        echo "<p><strong>Se usará optimización JPEG avanzada como alternativa a WebP.</strong></p>";
    }
    echo "<p><strong>Siguiente paso:</strong> El scraping automático ahora utilizará el procesador optimizado.</p>";
} else {
    echo "<p style='color: red;'>⚠️ Hay problemas con la integración. Revisa los errores arriba.</p>";
}
?>
