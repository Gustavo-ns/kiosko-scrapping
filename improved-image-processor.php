<?php
// improved-image-processor.php - Función mejorada para procesamiento de imágenes

/**
 * Procesa y optimiza imágenes con soporte automático para WebP
 * 
 * @param string $imageUrl URL de la imagen a descargar
 * @param string $country País de origen
 * @param string $alt Texto alternativo
 * @param array $options Opciones de configuración
 * @return string|false Ruta local del archivo guardado o false en caso de error
 */
function saveImageLocallyOptimized($imageUrl, $country, $alt, $options = []) {    // Configuración por defecto
    $config = array_merge([
        'max_width' => 600,
        'max_height' => 900,
        'quality' => 85,
        'webp_quality' => 80,
        'force_format' => null, // 'webp', 'jpeg', o null para auto-detectar
        'timeout' => 10,
        'max_file_size' => 5 * 1024 * 1024, // 5MB máximo
    ], $options);
    
    static $webpSupported = null;
    static $gdWebpSupported = null;
    
    // Verificar soporte WebP una sola vez
    if ($webpSupported === null) {
        $webpSupported = extension_loaded('imagick') && in_array('WEBP', Imagick::queryFormats());
        $gdWebpSupported = extension_loaded('gd') && function_exists('imagewebp');
        
        error_log("WebP support - Imagick: " . ($webpSupported ? 'YES' : 'NO') . 
                 ", GD: " . ($gdWebpSupported ? 'YES' : 'NO'));
    }
    
    // Generar nombre de archivo único con número aleatorio
    $random_number = mt_rand(1000, 9999);
    $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $alt) . '_' . $random_number;
    $saveDir = __DIR__ . "/images/";
    
    // Asegurar que el directorio existe
    if (!is_dir($saveDir)) {
        mkdir($saveDir, 0777, true);
    }
    
    // Descargar imagen
    global $guzzle;
    try {
        if (!isset($guzzle)) {
            $guzzle = new \GuzzleHttp\Client([
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
                'timeout' => $config['timeout'],
                'connect_timeout' => 5
            ]);
        }
        
        $response = $guzzle->get($imageUrl);
        $imageData = $response->getBody()->getContents();
        
        // Verificar tamaño
        if (strlen($imageData) > $config['max_file_size']) {
            throw new Exception("Imagen demasiado grande: " . number_format(strlen($imageData) / 1024, 2) . "KB");
        }
        
    } catch (Exception $e) {
        error_log("Error descargando imagen: $imageUrl - " . $e->getMessage());
        return false;
    }
    
    // Crear archivo temporal
    $tempFile = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tempFile, $imageData);
    
    try {
        // Verificar que es una imagen válida
        $info = @getimagesize($tempFile);
        if ($info === false) {
            throw new Exception("Archivo no es una imagen válida");
        }
        
        $originalMime = $info['mime'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($originalMime, $allowedMimes)) {
            throw new Exception("Formato de imagen no soportado: $originalMime");
        }
        
        // Determinar formato de salida
        $outputFormat = $config['force_format'];
        if (!$outputFormat) {
            if ($webpSupported || $gdWebpSupported) {
                $outputFormat = 'webp';
            } else {
                $outputFormat = 'jpeg';
            }
        }
        
        // Procesar con Imagick si está disponible
        if (extension_loaded('imagick')) {
            return processWithImagick($tempFile, $saveDir, $filename, $outputFormat, $config);
        }
        // Fallback a GD
        elseif (extension_loaded('gd')) {
            return processWithGD($tempFile, $saveDir, $filename, $outputFormat, $config);
        }
        else {
            throw new Exception("Ni Imagick ni GD están disponibles");
        }
        
    } catch (Exception $e) {
        error_log("Error procesando imagen: $imageUrl - " . $e->getMessage());
        if (file_exists($tempFile)) unlink($tempFile);
        return false;
    }
}

/**
 * Procesa imagen usando Imagick
 */
function processWithImagick($tempFile, $saveDir, $filename, $outputFormat, $config) {
    $imagick = new Imagick();
    
    try {
        // Configurar límites de memoria
        $imagick->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 256 * 1024 * 1024);
        $imagick->setResourceLimit(Imagick::RESOURCETYPE_MAP, 256 * 1024 * 1024);
        
        // Cargar imagen
        $imagick->readImage($tempFile);
        
        if (!$imagick->valid()) {
            throw new Exception("Imagen no válida después de cargar");
        }
        
        // Convertir espacio de color si es necesario
        if ($imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
            $imagick->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }
        
        // Remover metadatos para reducir tamaño
        $imagick->stripImage();
        
        // Redimensionar si es necesario
        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();
        
        if ($width > $config['max_width'] || $height > $config['max_height']) {
            $imagick->resizeImage(
                $config['max_width'], 
                $config['max_height'], 
                Imagick::FILTER_LANCZOS, 
                1, 
                true
            );
        }
        
        // Configurar formato y calidad
        $extension = $outputFormat;
        $quality = ($outputFormat === 'webp') ? $config['webp_quality'] : $config['quality'];
        
        $imagick->setImageFormat($outputFormat);
        $imagick->setImageCompressionQuality($quality);
        
        // Optimizaciones adicionales para WebP
        if ($outputFormat === 'webp') {
            $imagick->setOption('webp:lossless', 'false');
            $imagick->setOption('webp:alpha-quality', '95');
            $imagick->setOption('webp:method', '6'); // Máxima compresión
        }
        
        // Optimizar para JPEG
        if ($outputFormat === 'jpeg') {
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setInterlaceScheme(Imagick::INTERLACE_JPEG); // Progressive JPEG
        }
        
        $finalPath = $saveDir . $filename . '.' . $extension;
        
        // Guardar imagen
        if (!$imagick->writeImage($finalPath)) {
            throw new Exception("No se pudo guardar la imagen");
        }
        
        // Verificar archivo guardado
        if (!file_exists($finalPath) || filesize($finalPath) < 1024) {
            throw new Exception("Archivo guardado no es válido");
        }
        
        // Limpiar memoria
        $imagick->clear();
        $imagick->destroy();
        unlink($tempFile);
        
        $savedSize = filesize($finalPath);
        error_log("Imagen guardada: " . basename($finalPath) . " (" . 
                 number_format($savedSize / 1024, 2) . "KB, formato: $outputFormat)");
        
        return "images/" . basename($finalPath);
        
    } catch (Exception $e) {
        if (isset($imagick)) {
            $imagick->clear();
            $imagick->destroy();
        }
        if (file_exists($tempFile)) unlink($tempFile);
        throw $e;
    }
}

/**
 * Procesa imagen usando GD (fallback)
 */
function processWithGD($tempFile, $saveDir, $filename, $outputFormat, $config) {
    $info = getimagesize($tempFile);
    $mime = $info['mime'];
    
    // Crear imagen desde archivo
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($tempFile);
            break;
        case 'image/png':
            $image = imagecreatefrompng($tempFile);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($tempFile);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($tempFile);
            } else {
                throw new Exception("WebP no soportado en GD");
            }
            break;
        default:
            throw new Exception("Formato no soportado por GD: $mime");
    }
    
    if (!$image) {
        throw new Exception("No se pudo crear imagen con GD");
    }
    
    try {
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Calcular nuevas dimensiones
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
        
        if ($originalWidth > $config['max_width'] || $originalHeight > $config['max_height']) {
            $ratio = min($config['max_width'] / $originalWidth, $config['max_height'] / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
        }
        
        // Crear nueva imagen redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia para PNG y WebP
        if ($outputFormat === 'png' || $outputFormat === 'webp') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefill($resizedImage, 0, 0, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Guardar en formato apropiado
        $extension = $outputFormat;
        $finalPath = $saveDir . $filename . '.' . $extension;
        
        $success = false;
        switch ($outputFormat) {
            case 'webp':
                if (function_exists('imagewebp')) {
                    $success = imagewebp($resizedImage, $finalPath, $config['webp_quality']);
                } else {
                    // Fallback a JPEG
                    $extension = 'jpeg';
                    $finalPath = $saveDir . $filename . '.jpg';
                    $success = imagejpeg($resizedImage, $finalPath, $config['quality']);
                }
                break;
            case 'png':
                $success = imagepng($resizedImage, $finalPath, 9);
                break;
            case 'jpeg':
            default:
                $success = imagejpeg($resizedImage, $finalPath, $config['quality']);
                break;
        }
        
        // Limpiar memoria
        imagedestroy($image);
        imagedestroy($resizedImage);
        unlink($tempFile);
        
        if (!$success || !file_exists($finalPath)) {
            throw new Exception("Error guardando imagen con GD");
        }
        
        return "images/" . basename($finalPath);
        
    } catch (Exception $e) {
        imagedestroy($image);
        if (isset($resizedImage)) imagedestroy($resizedImage);
        if (file_exists($tempFile)) unlink($tempFile);
        throw $e;
    }
}

// Función para limpiar imágenes antiguas
function cleanupOldImages($maxAge = 86400) { // 24 horas por defecto
    $imagesDir = __DIR__ . "/images/";
    $files = glob($imagesDir . "*");
    $cleaned = 0;
    
    foreach ($files as $file) {
        if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
            unlink($file);
            $cleaned++;
        }
    }
    
    if ($cleaned > 0) {
        error_log("Limpiadas $cleaned imágenes antiguas");
    }
}

// Ejemplo de uso:
/*
// Configuración personalizada
$options = [
    'max_width' => 400,
    'max_height' => 600,
    'quality' => 90,
    'webp_quality' => 85,
    'force_format' => 'webp', // Forzar WebP si está disponible
];

$localPath = saveImageLocallyOptimized($imageUrl, $country, $alt, $options);
if ($localPath) {
    echo "Imagen guardada en: $localPath";
} else {
    echo "Error guardando imagen";
}
*/
?>
