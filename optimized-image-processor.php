<?php
// optimized-image-processor.php - Procesador de imágenes optimizado basado en las capacidades del servidor

/**
 * Procesa y optimiza una imagen usando el mejor método disponible
 * 
 * @param string $imagePath Ruta de la imagen original
 * @param string $outputDir Directorio de salida
 * @param array $options Opciones de procesamiento
 * @return array Resultado del procesamiento
 */
function processOptimizedImage($imagePath, $outputDir, $options = []) {    // Configuración por defecto
    $defaults = [
        'max_width' => 600,
        'max_height' => 900,
        'quality' => 85,
        'prefer_webp' => true,
        'strip_metadata' => true
    ];
    
    $options = array_merge($defaults, $options);
    $result = [
        'success' => false,
        'original_size' => 0,
        'final_size' => 0,
        'savings_percent' => 0,
        'format_used' => '',
        'output_path' => '',
        'error' => ''
    ];
    
    try {
        if (!file_exists($imagePath)) {
            $result['error'] = 'Archivo de imagen no existe';
            return $result;
        }
        
        $result['original_size'] = filesize($imagePath);
        $pathInfo = pathinfo($imagePath);
        $baseName = $pathInfo['filename'];
        
        // Verificar capacidades del servidor
        $hasGD = extension_loaded('gd');
        $hasImagick = extension_loaded('imagick');
        $gdWebPSupport = $hasGD && function_exists('imagewebp');
        
        // Determinar el mejor formato y método
        $useWebP = $options['prefer_webp'] && $gdWebPSupport;
        $targetFormat = $useWebP ? 'webp' : 'jpg';
        $outputPath = $outputDir . $baseName . '.' . $targetFormat;
        
        if ($useWebP && $gdWebPSupport) {
            // Usar GD para WebP
            $success = processWithGD($imagePath, $outputPath, $options);
            $result['format_used'] = 'WebP (GD)';
        } elseif ($hasImagick) {
            // Usar Imagick para JPEG optimizado
            $success = processWithImagick($imagePath, $outputPath, $options);
            $result['format_used'] = 'JPEG (Imagick)';
        } elseif ($hasGD) {
            // Usar GD para JPEG optimizado
            $success = processWithGD($imagePath, $outputPath, $options);
            $result['format_used'] = 'JPEG (GD)';
        } else {
            $result['error'] = 'No hay extensiones de procesamiento de imágenes disponibles';
            return $result;
        }
        
        if ($success && file_exists($outputPath)) {
            $result['success'] = true;
            $result['output_path'] = $outputPath;
            $result['final_size'] = filesize($outputPath);
            $result['savings_percent'] = round((($result['original_size'] - $result['final_size']) / $result['original_size']) * 100, 1);
        } else {
            $result['error'] = 'Error durante el procesamiento de la imagen';
        }
        
    } catch (Exception $e) {
        $result['error'] = 'Excepción: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Procesa imagen con GD
 */
function processWithGD($inputPath, $outputPath, $options) {
    try {
        // Detectar tipo de imagen
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo) return false;
        
        $imageType = $imageInfo[2];
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        
        // Crear imagen desde archivo
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($inputPath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($inputPath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($inputPath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) return false;
        
        // Calcular nuevas dimensiones si es necesario
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
        
        if ($originalWidth > $options['max_width'] || $originalHeight > $options['max_height']) {
            $ratio = min($options['max_width'] / $originalWidth, $options['max_height'] / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
        }
        
        // Crear imagen redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia para PNG
        if ($imageType == IMAGETYPE_PNG) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefill($resizedImage, 0, 0, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Guardar en el formato apropiado
        $success = false;
        $pathInfo = pathinfo($outputPath);
        $extension = strtolower($pathInfo['extension']);
        
        switch ($extension) {
            case 'webp':
                if (function_exists('imagewebp')) {
                    $success = imagewebp($resizedImage, $outputPath, $options['quality']);
                }
                break;
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($resizedImage, $outputPath, $options['quality']);
                break;
            case 'png':
                // PNG quality is 0-9, convert from 0-100
                $pngQuality = 9 - round(($options['quality'] / 100) * 9);
                $success = imagepng($resizedImage, $outputPath, $pngQuality);
                break;
        }
        
        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        return $success;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Procesa imagen con Imagick
 */
function processWithImagick($inputPath, $outputPath, $options) {
    try {
        $imagick = new Imagick($inputPath);
        
        // Obtener dimensiones originales
        $originalWidth = $imagick->getImageWidth();
        $originalHeight = $imagick->getImageHeight();
        
        // Redimensionar si es necesario
        if ($originalWidth > $options['max_width'] || $originalHeight > $options['max_height']) {
            $imagick->resizeImage($options['max_width'], $options['max_height'], Imagick::FILTER_LANCZOS, 1, true);
        }
        
        // Configurar formato y calidad
        $pathInfo = pathinfo($outputPath);
        $extension = strtolower($pathInfo['extension']);
        
        switch ($extension) {
            case 'webp':
                $imagick->setImageFormat('webp');
                break;
            case 'jpg':
            case 'jpeg':
                $imagick->setImageFormat('jpeg');
                break;
            case 'png':
                $imagick->setImageFormat('png');
                break;
        }
        
        $imagick->setImageCompressionQuality($options['quality']);
        
        // Remover metadatos si se solicita
        if ($options['strip_metadata']) {
            $imagick->stripImage();
        }
        
        // Guardar imagen
        $success = $imagick->writeImage($outputPath);
        
        // Limpiar memoria
        $imagick->clear();
        $imagick->destroy();
        
        return $success;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Función de utilidad para obtener información de capacidades del servidor
 */
function getImageProcessingCapabilities() {
    $capabilities = [
        'gd_available' => extension_loaded('gd'),
        'imagick_available' => extension_loaded('imagick'),
        'webp_support' => false,
        'recommended_processor' => 'none'
    ];
    
    if ($capabilities['gd_available']) {
        $gdInfo = gd_info();
        $capabilities['webp_support'] = isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'];
    }
    
    // Determinar procesador recomendado
    if ($capabilities['webp_support']) {
        $capabilities['recommended_processor'] = 'gd_webp';
    } elseif ($capabilities['imagick_available']) {
        $capabilities['recommended_processor'] = 'imagick_jpeg';
    } elseif ($capabilities['gd_available']) {
        $capabilities['recommended_processor'] = 'gd_jpeg';
    }
    
    return $capabilities;
}

/**
 * Función helper para procesar imagen con configuración automática
 */
function autoProcessImage($inputPath, $outputDir) {
    $capabilities = getImageProcessingCapabilities();
      $options = [
        'max_width' => 600,
        'max_height' => 900,
        'quality' => 85,
        'prefer_webp' => $capabilities['webp_support'],
        'strip_metadata' => true
    ];
    
    return processOptimizedImage($inputPath, $outputDir, $options);
}
?>
