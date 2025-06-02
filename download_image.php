<?php
function resizeImage($source_image, $target_width, $target_height) {
    list($width, $height) = getimagesize($source_image);
    
    // Calcular proporciones
    $ratio = min($target_width / $width, $target_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Crear nueva imagen
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Mantener transparencia si existe
    imagealphablending($new_image, false);
    imagesavealpha($new_image, true);
    
    // Obtener imagen original
    $mime = getimagesize($source_image)['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($source_image);
            break;
        case 'image/png':
            $source = imagecreatefrompng($source_image);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($source_image);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($source_image);
            break;
        default:
            return false;
    }
    
    // Redimensionar
    imagecopyresampled(
        $new_image, $source,
        0, 0, 0, 0,
        $new_width, $new_height,
        $width, $height
    );
    
    return $new_image;
}

function convertToWebP($source_image, $output_file, $quality = 80, $width = null, $height = null) {
    if ($width && $height) {
        $image = resizeImage($source_image, $width, $height);
    } else {
        $image_info = getimagesize($source_image);
        if ($image_info === false) {
            error_log("Failed to get image information for: " . $source_image);
            return false;
        }
        $mime = $image_info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source_image);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source_image);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source_image);
                break;
            case 'image/webp':
                if ($source_image === $output_file) {
                    return true;
                }
                $image = imagecreatefromwebp($source_image);
                break;
            default:
                return false;
        }
    }
    
    if (!$image) {
        return false;
    }

    // Convertir a WebP
    $success = imagewebp($image, $output_file, $quality);
    imagedestroy($image);
    return $success;
}

function downloadImage($url, $external_id) {
    // Crear directorios si no existen
    $upload_dir = 'images/melwater';
    $thumb_dir = $upload_dir . '/thumbnails';
    $preview_dir = $upload_dir . '/previews';
    foreach ([$upload_dir, $thumb_dir, $preview_dir] as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
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
        return [
            'preview' => $preview_filepath,
            'thumbnail' => $thumb_filepath,
            'original' => $original_filepath
        ];
    }

    // Crear un archivo temporal para la descarga inicial
    $temp_file = tempnam(sys_get_temp_dir(), 'img_');
    error_log("Created temporary file: " . $temp_file);

    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code !== 200) {
            error_log("HTTP request failed with status code: " . $http_code . " for URL: " . $url);
            return false;
        }

        if (!$image_data) {
            error_log("No image data received. cURL error: " . $curl_error);
            return false;
        }

        $bytes_written = file_put_contents($temp_file, $image_data);
        if ($bytes_written === false) {
            error_log("Failed to write image data to temporary file: " . $temp_file);
            return false;
        }
        error_log("Successfully wrote " . $bytes_written . " bytes to temporary file");

        // Verify the temporary file exists and is readable
        if (!file_exists($temp_file) || !is_readable($temp_file)) {
            error_log("Temporary file does not exist or is not readable: " . $temp_file);
            return false;
        }

        // Verify the file contains image data
        $image_info = getimagesize($temp_file);
        if ($image_info === false) {
            error_log("File is not a valid image: " . $temp_file . " (Size: " . filesize($temp_file) . " bytes)");
            return false;
        }

        // Convertir a WebP y crear versión original
        if (!convertToWebP($temp_file, $original_filepath, 90)) {
            error_log("Failed to convert original image to WebP");
            return false;
        }

        // Crear miniatura
        if (!convertToWebP($temp_file, $thumb_filepath, 80, 600, 900)) {
            error_log("Failed to create thumbnail");
            return false;
        }

        // Crear preview de muy baja calidad
        if (!convertToWebP($temp_file, $preview_filepath, 40, 320, 480)) {
            error_log("Failed to create preview");
            return false;
        }

        return [
            'preview' => $preview_filepath,
            'thumbnail' => $thumb_filepath,
            'original' => $original_filepath
        ];

    } catch (Exception $e) {
        error_log("Error procesando imagen: " . $e->getMessage());
        return false;
    } finally {
        // Limpiar archivo temporal
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
    }

    return false;
}

// Verificar si el servidor soporta WebP
if (!function_exists('imagewebp')) {
    error_log("Este servidor no soporta la conversión a WebP. Se requiere PHP GD con soporte WebP.");
} 