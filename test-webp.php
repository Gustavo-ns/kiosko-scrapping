<?php
// test-webp.php - Script para probar conversión a WebP

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba de Conversión a WebP</h1>";

// 1. Verificar si Imagick está instalado
echo "<h2>1. Verificación de Imagick</h2>";
if (!extension_loaded('imagick')) {
    echo "<p style='color: red;'>❌ La extensión Imagick NO está instalada.</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ La extensión Imagick está instalada.</p>";
}

// 2. Verificar versión de Imagick
$imagick = new Imagick();
$version = $imagick->getVersion();
echo "<p><strong>Versión:</strong> " . $version['versionString'] . "</p>";

// 3. Verificar formatos soportados
echo "<h2>2. Formatos Soportados</h2>";
$formats = Imagick::queryFormats();
$webpSupported = in_array('WEBP', $formats);
$jpegSupported = in_array('JPEG', $formats);
$pngSupported = in_array('PNG', $formats);

echo "<p>JPEG: " . ($jpegSupported ? "✅" : "❌") . "</p>";
echo "<p>PNG: " . ($pngSupported ? "✅" : "❌") . "</p>";
echo "<p>WebP: " . ($webpSupported ? "✅" : "❌") . "</p>";

if (!$webpSupported) {
    echo "<p style='color: orange;'>⚠️ WebP no está soportado. Usaremos JPEG optimizado.</p>";
}

// 4. Verificar GD también
echo "<h2>3. Verificación de GD (alternativo)</h2>";
if (extension_loaded('gd')) {
    echo "<p style='color: green;'>✅ La extensión GD está instalada.</p>";
    $gdInfo = gd_info();
    echo "<p>WebP Support en GD: " . ($gdInfo['WebP Support'] ? "✅" : "❌") . "</p>";
} else {
    echo "<p style='color: red;'>❌ La extensión GD NO está instalada.</p>";
}

// 5. Prueba práctica con una imagen existente
echo "<h2>4. Prueba Práctica de Conversión</h2>";

// Buscar una imagen en el directorio images
$imagesDir = __DIR__ . '/images/';
$testImages = glob($imagesDir . '*.jpg');

if (empty($testImages)) {
    echo "<p style='color: orange;'>⚠️ No hay imágenes .jpg en el directorio images para probar.</p>";
    echo "<p>Creando una imagen de prueba...</p>";
    
    // Crear una imagen de prueba
    $testImg = new Imagick();
    $testImg->newImage(300, 200, '#4CAF50');
    $testImg->setImageFormat('jpeg');
    $testImg->annotateImage(new ImagickDraw(), 50, 100, 0, 'Imagen de Prueba');
    $testPath = $imagesDir . 'test_image.jpg';
    $testImg->writeImage($testPath);
    $testImg->clear();
    
    $testImages = [$testPath];
    echo "<p>✅ Imagen de prueba creada: " . basename($testPath) . "</p>";
}

$testImage = $testImages[0];
echo "<p><strong>Imagen de prueba:</strong> " . basename($testImage) . "</p>";

// Mostrar información de la imagen original
$originalSize = filesize($testImage);
echo "<p><strong>Tamaño original:</strong> " . number_format($originalSize / 1024, 2) . " KB</p>";

try {
    $imagick = new Imagick($testImage);
    $originalWidth = $imagick->getImageWidth();
    $originalHeight = $imagick->getImageHeight();
    echo "<p><strong>Dimensiones originales:</strong> {$originalWidth}x{$originalHeight}</p>";
    
    // Prueba 1: Conversión a WebP (si está soportado)
    if ($webpSupported) {
        echo "<h3>Prueba 1: Conversión a WebP</h3>";
        $webpPath = $imagesDir . 'test_converted.webp';
        
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality(85);
        $imagick->writeImage($webpPath);
        
        if (file_exists($webpPath)) {
            $webpSize = filesize($webpPath);
            $savings = round((($originalSize - $webpSize) / $originalSize) * 100, 1);
            echo "<p style='color: green;'>✅ Conversión a WebP exitosa!</p>";
            echo "<p><strong>Tamaño WebP:</strong> " . number_format($webpSize / 1024, 2) . " KB</p>";
            echo "<p><strong>Ahorro:</strong> {$savings}%</p>";
            echo "<p><a href='images/" . basename($webpPath) . "' target='_blank'>Ver imagen WebP</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Error al crear archivo WebP</p>";
        }
    }
      // Prueba 2: Optimización JPEG con Imagick
    echo "<h3>Prueba 2: Optimización JPEG con Imagick</h3>";
    $optimizedPath = $imagesDir . 'test_optimized.jpg';
    
    // Crear nueva instancia para evitar problemas con rewindIterator
    $imagickOptimized = new Imagick($testImage);
    $imagickOptimized->setImageFormat('jpeg');
    $imagickOptimized->setImageCompressionQuality(85);
    $imagickOptimized->stripImage(); // Remover metadatos
    $imagickOptimized->writeImage($optimizedPath);
    $imagickOptimized->clear();
    $imagickOptimized->destroy();
    
    if (file_exists($optimizedPath)) {
        $optimizedSize = filesize($optimizedPath);
        $savings = round((($originalSize - $optimizedSize) / $originalSize) * 100, 1);
        echo "<p style='color: green;'>✅ Optimización JPEG exitosa!</p>";
        echo "<p><strong>Tamaño optimizado:</strong> " . number_format($optimizedSize / 1024, 2) . " KB</p>";
        echo "<p><strong>Ahorro:</strong> {$savings}%</p>";
        echo "<p><a href='images/" . basename($optimizedPath) . "' target='_blank'>Ver imagen optimizada</a></p>";
    }
      // Prueba 3: Redimensionamiento con Imagick
    echo "<h3>Prueba 3: Redimensionamiento + Optimización con Imagick</h3>";
    $resizedPath = $imagesDir . 'test_resized.jpg';
    
    // Crear nueva instancia para redimensionamiento
    $imagickResized = new Imagick($testImage);    $newWidth = $imagickResized->getImageWidth();
    $newHeight = $imagickResized->getImageHeight();
    
    if ($originalWidth > 600 || $originalHeight > 900) {
        $imagickResized->resizeImage(600, 900, Imagick::FILTER_LANCZOS, 1, true);
        $newWidth = $imagickResized->getImageWidth();
        $newHeight = $imagickResized->getImageHeight();
    }
    $imagickResized->setImageFormat('jpeg');
    $imagickResized->setImageCompressionQuality(85);
    $imagickResized->stripImage();
    $imagickResized->writeImage($resizedPath);
      if (file_exists($resizedPath)) {
        $resizedSize = filesize($resizedPath);
        $savings = round((($originalSize - $resizedSize) / $originalSize) * 100, 1);
        echo "<p style='color: green;'>✅ Redimensionamiento exitoso!</p>";
        echo "<p><strong>Nuevas dimensiones:</strong> {$newWidth}x{$newHeight}</p>";
        echo "<p><strong>Tamaño final:</strong> " . number_format($resizedSize / 1024, 2) . " KB</p>";
        echo "<p><strong>Ahorro total:</strong> {$savings}%</p>";
        echo "<p><a href='images/" . basename($resizedPath) . "' target='_blank'>Ver imagen redimensionada</a></p>";
    }
    
    $imagickResized->clear();
    $imagickResized->destroy();
    
    // Prueba 4: Conversión WebP con GD (ya que GD soporta WebP)
    echo "<h3>Prueba 4: Conversión WebP con GD</h3>";
    if (function_exists('imagewebp')) {
        $webpPathGD = $imagesDir . 'test_converted_gd.webp';
        
        // Crear imagen desde JPEG con GD
        $gdImage = imagecreatefromjpeg($testImage);
        
        if ($gdImage !== false) {
            // Convertir a WebP con calidad 85
            $webpResult = imagewebp($gdImage, $webpPathGD, 85);
            imagedestroy($gdImage);
            
            if ($webpResult && file_exists($webpPathGD)) {
                $webpSizeGD = filesize($webpPathGD);
                $savingsGD = round((($originalSize - $webpSizeGD) / $originalSize) * 100, 1);
                echo "<p style='color: green;'>✅ Conversión WebP con GD exitosa!</p>";
                echo "<p><strong>Tamaño WebP (GD):</strong> " . number_format($webpSizeGD / 1024, 2) . " KB</p>";
                echo "<p><strong>Ahorro:</strong> {$savingsGD}%</p>";
                echo "<p><a href='images/" . basename($webpPathGD) . "' target='_blank'>Ver imagen WebP (GD)</a></p>";
            } else {
                echo "<p style='color: red;'>❌ Error al crear archivo WebP con GD</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Error al cargar imagen con GD</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Función imagewebp() no disponible en GD</p>";
    }
    
    $imagick->clear();
    $imagick->destroy();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error durante las pruebas: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Recomendaciones</h2>";
if ($webpSupported || (extension_loaded('gd') && function_exists('imagewebp'))) {
    if ($webpSupported) {
        echo "<p style='color: green;'>✅ Tu servidor soporta WebP via Imagick. Puedes implementar conversión automática a WebP.</p>";
    } else {
        echo "<p style='color: green;'>✅ Tu servidor soporta WebP via GD. Puedes implementar conversión automática a WebP.</p>";
    }
    echo "<p><strong>Beneficios esperados:</strong> 25-35% de reducción en tamaño de archivos.</p>";
    echo "<p><strong>Recomendación:</strong> Usar GD para WebP ya que está disponible y es más confiable en tu servidor.</p>";
} else {
    echo "<p style='color: orange;'>⚠️ WebP no está disponible. Usa optimización JPEG agresiva como alternativa.</p>";
    echo "<p><strong>Beneficios esperados:</strong> 15-25% de reducción en tamaño con JPEG optimizado.</p>";
}

echo "<hr>";
echo "<p><strong>Siguiente paso:</strong> Si las pruebas fueron exitosas, podemos modificar scrape.php para usar el mejor formato disponible.</p>";
?>
