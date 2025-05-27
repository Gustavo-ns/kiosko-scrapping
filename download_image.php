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
        $mime = getimagesize($source_image)['mime'];
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
    foreach ([$upload_dir, $thumb_dir] as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Definir rutas de archivos
    $original_filename = $external_id . '_original.webp';
    $thumb_filename = $external_id . '_thumb.webp';
    $original_filepath = $upload_dir . '/' . $original_filename;
    $thumb_filepath = $thumb_dir . '/' . $thumb_filename;

    // Si ambos archivos existen, retornar las rutas
    if (file_exists($original_filepath) && file_exists($thumb_filepath)) {
        return [
            'thumbnail' => $thumb_filepath,
            'original' => $original_filepath
        ];
    }

    // Crear un archivo temporal para la descarga inicial
    $temp_file = tempnam(sys_get_temp_dir(), 'img_');

    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $image_data) {
            // Guardar imagen temporal
            if (file_put_contents($temp_file, $image_data)) {
                // Convertir a WebP y crear versión original
                if (convertToWebP($temp_file, $original_filepath, 90)) {
                    // Crear miniatura
                    if (convertToWebP($temp_file, $thumb_filepath, 80, 325, 500)) {
                        return [
                            'thumbnail' => $thumb_filepath,
                            'original' => $original_filepath
                        ];
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error procesando imagen: " . $e->getMessage());
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