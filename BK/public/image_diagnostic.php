<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/image_diagnostic.log');

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

echo "=== Diagnóstico de Procesamiento de Imágenes ===\n\n";

// 1. Verificar extensiones requeridas
echo "1. Verificando extensiones PHP:\n";
$required_extensions = ['imagick', 'curl', 'gd'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "[✓] {$ext} está instalada\n";
        if ($ext === 'imagick') {
            $version = Imagick::getVersion();
            echo "   Versión de ImageMagick: " . $version['versionString'] . "\n";
        }
    } else {
        echo "[✗] {$ext} NO está instalada\n";
    }
}

// 2. Verificar directorios
echo "\n2. Verificando directorios:\n";
$directories = [
    'storage/images' => __DIR__ . '/storage/images',
    'storage/images/original' => __DIR__ . '/storage/images/original',
    'storage/images/thumbnails' => __DIR__ . '/storage/images/thumbnails',
    'logs' => __DIR__ . '/logs'
];

foreach ($directories as $name => $path) {
    echo "\nVerificando $name ($path):\n";
    if (!is_dir($path)) {
        echo "- Creando directorio...\n";
        mkdir($path, 0777, true);
    }
    
    if (is_dir($path)) {
        echo "- Directorio existe: [✓]\n";
        if (is_writable($path)) {
            echo "- Permisos de escritura: [✓]\n";
        } else {
            echo "- Permisos de escritura: [✗]\n";
            echo "  Intentando establecer permisos...\n";
            chmod($path, 0777);
        }
    }
}

// 3. Probar procesamiento de imagen
echo "\n3. Probando procesamiento de imagen:\n";
$test_url = "https://raw.githubusercontent.com/github/explore/80688e429a7d4ef2fca1e82350fe8e3517d3494d/topics/php/php.png";
echo "- Descargando imagen de prueba...\n";

try {
    // Descargar imagen
    $ch = curl_init($test_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$imageData) {
        throw new Exception("Error descargando imagen: HTTP $httpCode");
    }

    // Guardar imagen temporal
    $tempFile = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tempFile, $imageData);

    echo "- Imagen descargada correctamente\n";

    // Procesar con Imagick
    try {
        $imagick = new Imagick();
        $imagick->readImage($tempFile);
        
        if ($imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
            $imagick->transformImageColorspace(Imagick::COLORSPACE_RGB);
        }

        $imagick->resizeImage(300, 0, Imagick::FILTER_LANCZOS, 1);
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality(80);

        $testOutput = __DIR__ . '/storage/images/test.webp';
        $imagick->writeImage($testOutput);
        $imagick->clear();
        $imagick->destroy();

        echo "- Imagen procesada y guardada correctamente\n";
        echo "- Archivo de prueba creado en: $testOutput\n";
    } catch (ImagickException $e) {
        echo "- Error procesando imagen con Imagick: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "- Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
}

// 4. Verificar permisos del usuario web
echo "\n4. Verificando permisos del usuario web:\n";
echo "- Usuario actual: " . get_current_user() . "\n";
echo "- ID del proceso: " . getmypid() . "\n";

if (function_exists('posix_getuid')) {
    echo "- UID del proceso: " . posix_getuid() . "\n";
    echo "- GID del proceso: " . posix_getgid() . "\n";
} 