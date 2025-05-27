<?php
class ImageServiceGD {
    private $imageDir;
    private $thumbnailDir;

    public function __construct() {
        $this->imageDir = __DIR__ . '/../../storage/images/original/';
        $this->thumbnailDir = __DIR__ . '/../../storage/images/thumbnails/';
        
        // Asegurar que los directorios existan
        if (!file_exists($this->imageDir)) {
            mkdir($this->imageDir, 0777, true);
        }
        if (!file_exists($this->thumbnailDir)) {
            mkdir($this->thumbnailDir, 0777, true);
        }
    }

    public function downloadImage($url, $id) {
        // Definir rutas de archivos
        $originalPath = $this->imageDir . $id . '.jpg';
        $thumbnailPath = $this->thumbnailDir . $id . '.jpg';
        
        // Si ya existe la imagen, retornar las rutas
        if (file_exists($originalPath) && file_exists($thumbnailPath)) {
            return [
                'original' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $originalPath),
                'thumbnail' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $thumbnailPath)
            ];
        }

        try {
            // Descargar imagen
            $ch = curl_init($url);
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

            // Crear imagen original
            list($width, $height, $type) = getimagesize($tempFile);
            $source = $this->createImageFromFile($tempFile, $type);
            
            if (!$source) {
                throw new Exception("Formato de imagen no soportado");
            }

            // Guardar original
            imagejpeg($source, $originalPath, 90);

            // Crear y guardar thumbnail
            $maxWidth = 325;
            $maxHeight = 500;
            
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);

            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled(
                $thumbnail, $source,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );

            imagejpeg($thumbnail, $thumbnailPath, 80);

            // Liberar memoria
            imagedestroy($source);
            imagedestroy($thumbnail);

            // Limpiar archivo temporal
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            return [
                'original' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $originalPath),
                'thumbnail' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $thumbnailPath)
            ];

        } catch (Exception $e) {
            error_log("Error procesando imagen: " . $e->getMessage());
            
            // Limpiar archivos en caso de error
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            if (file_exists($originalPath)) {
                unlink($originalPath);
            }
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
            
            return false;
        }
    }

    private function createImageFromFile($file, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($file);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($file);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($file);
            default:
                return false;
        }
    }
} 