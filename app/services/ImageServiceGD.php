<?php
class ImageServiceGD {
    private $imageDir;
    private $thumbnailDir;
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15'
    ];

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

    private function getRandomUserAgent() {
        return $this->userAgents[array_rand($this->userAgents)];
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
            // Extraer el dominio de la URL para el header Referer
            $parsedUrl = parse_url($url);
            $referer = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/';

            // Configurar cURL con headers más realistas
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => $this->getRandomUserAgent(),
                CURLOPT_HTTPHEADER => [
                    'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'Sec-Fetch-Dest: image',
                    'Sec-Fetch-Mode: no-cors',
                    'Sec-Fetch-Site: cross-site'
                ],
                CURLOPT_REFERER => $referer,
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_COOKIEJAR => __DIR__ . '/../../storage/cookies.txt',
                CURLOPT_COOKIEFILE => __DIR__ . '/../../storage/cookies.txt'
            ]);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("Error descargando imagen: HTTP $httpCode - URL: $url");
            }

            if (!$imageData || strlen($imageData) < 100) {
                throw new Exception("Datos de imagen inválidos o vacíos");
            }

            // Verificar que el contenido es una imagen
            if (!preg_match('/^image\//i', $contentType)) {
                throw new Exception("El contenido descargado no es una imagen ($contentType)");
            }

            // Guardar imagen temporal
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            if (!file_put_contents($tempFile, $imageData)) {
                throw new Exception("No se pudo guardar la imagen temporal");
            }

            // Verificar que el archivo es una imagen válida
            if (!getimagesize($tempFile)) {
                throw new Exception("El archivo descargado no es una imagen válida");
            }

            // Crear imagen original
            list($width, $height, $type) = getimagesize($tempFile);
            $source = $this->createImageFromFile($tempFile, $type);
            
            if (!$source) {
                throw new Exception("Formato de imagen no soportado");
            }

            // Guardar original
            if (!imagejpeg($source, $originalPath, 90)) {
                throw new Exception("Error guardando imagen original");
            }

            // Crear y guardar thumbnail
            $maxWidth = 325;
            $maxHeight = 500;
            
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);

            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Mantener transparencia si es PNG
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }

            imagecopyresampled(
                $thumbnail, $source,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );

            if (!imagejpeg($thumbnail, $thumbnailPath, 80)) {
                throw new Exception("Error guardando thumbnail");
            }

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
            error_log("Error procesando imagen: " . $e->getMessage() . " - URL: $url");
            
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