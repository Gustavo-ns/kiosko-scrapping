<?php
class ImageService {
    private $imageDir;
    private $thumbnailDir;
    private $lastError;

    public function __construct() {
        $this->imageDir = __DIR__ . '/../../storage/images/original/';
        $this->thumbnailDir = __DIR__ . '/../../storage/images/thumbnails/';
        $this->lastError = '';
        
        // Asegurar que los directorios existan y sean escribibles
        $this->checkAndCreateDirectory($this->imageDir);
        $this->checkAndCreateDirectory($this->thumbnailDir);

        // Verificar soporte WebP
        if (!function_exists('imagewebp')) {
            $this->lastError = "Este servidor no soporta la conversión a WebP. Se requiere PHP GD con soporte WebP.";
            error_log($this->lastError);
        }
    }

    private function checkAndCreateDirectory($dir) {
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                $error = error_get_last();
                $errorMessage = isset($error['message']) ? $error['message'] : 'Error desconocido';
                $this->lastError = "No se pudo crear el directorio $dir: " . $errorMessage;
                error_log($this->lastError);
                throw new Exception($this->lastError);
            }
        }
        
        if (!is_writable($dir)) {
            $this->lastError = "El directorio $dir no tiene permisos de escritura";
            error_log($this->lastError);
            throw new Exception($this->lastError);
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function downloadImage($url, $id) {
        if (empty($url) || empty($id)) {
            $this->lastError = "URL o ID vacíos";
            error_log($this->lastError);
            return false;
        }

        // Definir rutas de archivos
        $originalPath = $this->imageDir . $id . '.webp';
        $thumbnailPath = $this->thumbnailDir . $id . '.webp';
        
        // Si ya existe la imagen, retornar las rutas
        if (file_exists($originalPath) && file_exists($thumbnailPath)) {
            return [
                'original' => '/storage/images/original/' . $id . '.webp',
                'thumbnail' => '/storage/images/thumbnails/' . $id . '.webp'
            ];
        }

        // Crear un archivo temporal para la descarga inicial
        $tempFile = tempnam(sys_get_temp_dir(), 'img_');

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200 || !$imageData) {
                throw new Exception("Error descargando imagen: HTTP $httpCode - $error");
            }

            // Guardar imagen temporal
            if (!file_put_contents($tempFile, $imageData)) {
                throw new Exception("Error guardando imagen temporal");
            }

            // Verificar que es una imagen válida
            if (!getimagesize($tempFile)) {
                throw new Exception("El archivo descargado no es una imagen válida");
            }

            // Convertir a WebP y crear versión original
            if (!$this->convertToWebP($tempFile, $originalPath, 90)) {
                throw new Exception("Error convirtiendo imagen original a WebP: " . $this->lastError);
            }

            // Crear miniatura
            if (!$this->convertToWebP($tempFile, $thumbnailPath, 80, 325, 500)) {
                throw new Exception("Error creando thumbnail WebP: " . $this->lastError);
            }

            return [
                'original' => '/storage/images/original/' . $id . '.webp',
                'thumbnail' => '/storage/images/thumbnails/' . $id . '.webp'
            ];

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Error procesando imagen {$url}: " . $e->getMessage());
            return false;
        } finally {
            // Limpiar archivo temporal
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function resizeImage($sourceImage, $targetWidth, $targetHeight) {
        list($width, $height) = getimagesize($sourceImage);
        
        // Calcular proporciones
        $ratio = min($targetWidth / $width, $targetHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Crear nueva imagen
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Mantener transparencia si existe
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        
        // Obtener imagen original
        $mime = getimagesize($sourceImage)['mime'];
        $source = $this->createImageFromFile($sourceImage, $mime);
        
        if (!$source) {
            return false;
        }
        
        // Redimensionar
        imagecopyresampled(
            $newImage, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );
        
        imagedestroy($source);
        return $newImage;
    }

    private function convertToWebP($sourceImage, $outputFile, $quality = 80, $width = null, $height = null) {
        try {
            if ($width && $height) {
                $image = $this->resizeImage($sourceImage, $width, $height);
            } else {
                $mime = getimagesize($sourceImage)['mime'];
                $image = $this->createImageFromFile($sourceImage, $mime);
                
                if ($mime === 'image/png') {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
            }
            
            if (!$image) {
                return false;
            }

            // Convertir a WebP
            $success = imagewebp($image, $outputFile, $quality);
            imagedestroy($image);
            return $success;

        } catch (Exception $e) {
            error_log("Error convirtiendo a WebP: " . $e->getMessage());
            return false;
        }
    }

    private function createImageFromFile($file, $mime) {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($file);
            case 'image/png':
                return imagecreatefrompng($file);
            case 'image/gif':
                return imagecreatefromgif($file);
            case 'image/webp':
                return imagecreatefromwebp($file);
            default:
                return false;
        }
    }
} 