<?php
function resizeImage($source_image, $target_width, $target_height) {
    error_log("Intentando redimensionar imagen: " . $source_image);
    
    if (!file_exists($source_image)) {
        error_log("El archivo de origen no existe: " . $source_image);
        return false;
    }

    if (!is_readable($source_image)) {
        error_log("El archivo de origen no es legible: " . $source_image);
        return false;
    }

    $image_info = @getimagesize($source_image);
    if ($image_info === false) {
        error_log("No se pudo obtener información de la imagen. Tamaño del archivo: " . filesize($source_image) . " bytes");
        // Intentar detectar el tipo de archivo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $source_image);
        finfo_close($finfo);
        error_log("Tipo MIME detectado: " . $mime_type);
        return false;
    }

    list($width, $height) = $image_info;
    $mime = $image_info['mime'];
    error_log("Información de imagen - Ancho: $width, Alto: $height, MIME: $mime");
    
    // Calcular proporciones
    $ratio = min($target_width / $width, $target_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Crear nueva imagen
    $new_image = imagecreatetruecolor($new_width, $new_height);
    if (!$new_image) {
        error_log("No se pudo crear nueva imagen con dimensiones: {$new_width}x{$new_height}");
        return false;
    }
    
    // Mantener transparencia si existe
    imagealphablending($new_image, false);
    imagesavealpha($new_image, true);
    
    // Obtener imagen original
    try {
        switch ($mime) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($source_image);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($source_image);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($source_image);
                break;
            case 'image/webp':
                $source = @imagecreatefromwebp($source_image);
                break;
            default:
                error_log("Tipo de imagen no soportado: " . $mime);
                return false;
        }
    } catch (Exception $e) {
        error_log("Error al crear imagen desde archivo: " . $e->getMessage());
        return false;
    }
    
    if (!$source) {
        error_log("No se pudo crear imagen desde archivo fuente. Tipo MIME: " . $mime);
        return false;
    }
    
    // Redimensionar
    if (!imagecopyresampled(
        $new_image, $source,
        0, 0, 0, 0,
        $new_width, $new_height,
        $width, $height
    )) {
        error_log("Fallo al redimensionar la imagen");
        imagedestroy($source);
        imagedestroy($new_image);
        return false;
    }
    
    imagedestroy($source);
    return $new_image;
}

function convertToWebP($source_image, $output_file, $quality = 80, $width = null, $height = null) {
    error_log("Iniciando conversión a WebP - Origen: $source_image, Destino: $output_file");
    
    if (!file_exists($source_image)) {
        error_log("Archivo de origen no existe: " . $source_image);
        return false;
    }

    if (!is_readable($source_image)) {
        error_log("Archivo de origen no es legible: " . $source_image);
        return false;
    }

    if ($width && $height) {
        $image = resizeImage($source_image, $width, $height);
        if (!$image) {
            error_log("Fallo al redimensionar imagen para WebP");
            return false;
        }
    } else {
        $image_info = @getimagesize($source_image);
        if ($image_info === false) {
            error_log("No se pudo obtener información de la imagen para WebP. Tamaño del archivo: " . filesize($source_image) . " bytes");
            return false;
        }
        
        $mime = $image_info['mime'];
        error_log("Procesando imagen de tipo: " . $mime);
        
        try {
            switch ($mime) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($source_image);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($source_image);
                    if ($image) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($source_image);
                    break;
                case 'image/webp':
                    if ($source_image === $output_file) {
                        return true;
                    }
                    $image = @imagecreatefromwebp($source_image);
                    break;
                default:
                    error_log("Tipo de imagen no soportado para WebP: " . $mime);
                    return false;
            }
        } catch (Exception $e) {
            error_log("Error al crear imagen para WebP: " . $e->getMessage());
            return false;
        }
    }
    
    if (!$image) {
        error_log("No se pudo crear recurso de imagen para WebP");
        return false;
    }

    // Asegurar que el directorio de destino existe
    $output_dir = dirname($output_file);
    if (!file_exists($output_dir)) {
        if (!mkdir($output_dir, 0755, true)) {
            error_log("No se pudo crear el directorio de destino: " . $output_dir);
            imagedestroy($image);
            return false;
        }
    }

    // Convertir a WebP
    $success = imagewebp($image, $output_file, $quality);
    $result = $success ? "éxito" : "fallo";
    error_log("Conversión a WebP completada con " . $result);
    
    imagedestroy($image);
    
    if ($success && file_exists($output_file)) {
        error_log("Archivo WebP creado correctamente: " . $output_file . " (" . filesize($output_file) . " bytes)");
        return true;
    } else {
        error_log("Error al crear archivo WebP: " . $output_file);
        return false;
    }
}

function downloadImage($url, $external_id) {
    error_log("Iniciando descarga de imagen desde: " . $url);
    
    // Crear directorios si no existen
    $upload_dir = 'images/melwater';
    $thumb_dir = $upload_dir . '/thumbnails';
    $preview_dir = $upload_dir . '/previews';
    foreach ([$upload_dir, $thumb_dir, $preview_dir] as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("No se pudo crear el directorio: " . $dir);
                return false;
            }
        }
    }

    // Definir rutas de archivos
    $original_filename = $external_id . '_original.webp';
    $thumb_filename = $external_id . '_thumb.webp';
    $preview_filename = $external_id . '_preview.webp';
    $original_filepath = $upload_dir . '/' . $original_filename;
    $thumb_filepath = $thumb_dir . '/' . $thumb_filename;
    $preview_filepath = $preview_dir . '/' . $preview_filename;

    // Si todos los archivos existen, retornar las rutas
    if (file_exists($original_filepath) && file_exists($thumb_filepath) && file_exists($preview_filepath)) {
        error_log("Archivos existentes encontrados, retornando rutas");
        return [
            'preview' => $preview_filepath,
            'thumbnail' => $thumb_filepath,
            'original' => $original_filepath
        ];
    }

    // Crear un archivo temporal para la descarga inicial
    $temp_file = tempnam(sys_get_temp_dir(), 'img_');
    error_log("Archivo temporal creado: " . $temp_file);

    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false
        ]);

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        error_log("Respuesta HTTP: " . $http_code . ", Tipo de contenido: " . $content_type);

        if ($http_code !== 200) {
            error_log("Error HTTP " . $http_code . " al descargar la imagen");
            error_log("Headers recibidos: " . $headers);
            return false;
        }

        if (!preg_match('/^image\//i', $content_type)) {
            error_log("El contenido descargado no es una imagen. Tipo de contenido: " . $content_type);
            error_log("Primeros 1000 bytes del contenido: " . substr($body, 0, 1000));
            return false;
        }

        if (empty($body)) {
            error_log("No se recibió contenido de imagen");
            return false;
        }

        // Guardar imagen temporal
        if (file_put_contents($temp_file, $body) === false) {
            error_log("Error al escribir el archivo temporal");
            return false;
        }

        // Verificar que el archivo temporal es una imagen válida
        $image_info = @getimagesize($temp_file);
        if ($image_info === false) {
            error_log("El archivo descargado no es una imagen válida");
            error_log("Tamaño del archivo: " . filesize($temp_file) . " bytes");
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_type = finfo_file($finfo, $temp_file);
            finfo_close($finfo);
            error_log("Tipo MIME detectado: " . $detected_type);
            return false;
        }

        error_log("Imagen válida detectada - Dimensiones: {$image_info[0]}x{$image_info[1]}, Tipo: {$image_info['mime']}");

        // Convertir a WebP y crear versión original
        if (!convertToWebP($temp_file, $original_filepath, 90)) {
            error_log("Error al convertir imagen original a WebP");
            return false;
        }

        // Crear miniatura
        if (!convertToWebP($temp_file, $thumb_filepath, 80, 600, 900)) {
            error_log("Error al crear miniatura WebP");
            return false;
        }

        // Crear preview
        if (!convertToWebP($temp_file, $preview_filepath, 40, 320, 480)) {
            error_log("Error al crear preview WebP");
            return false;
        }

        error_log("Procesamiento de imagen completado exitosamente");
        return [
            'preview' => $preview_filepath,
            'thumbnail' => $thumb_filepath,
            'original' => $original_filepath
        ];

    } catch (Exception $e) {
        error_log("Error procesando imagen: " . $url . " - " . $e->getMessage());
        return false;
    } finally {
        // Limpiar archivo temporal
        if (file_exists($temp_file)) {
            unlink($temp_file);
            error_log("Archivo temporal eliminado: " . $temp_file);
        }
    }

    return false;
}

// Verificar si el servidor soporta WebP
if (!function_exists('imagewebp')) {
    error_log("Este servidor no soporta la conversión a WebP. Se requiere PHP GD con soporte WebP.");
} 