<?php
// scrape.php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/scrape_errors.log'); // Log a archivo
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Configurar zona horaria
date_default_timezone_set('America/Montevideo');

set_error_handler(function ($severity, $message) {
    if (
        $severity === E_DEPRECATED
        && (strpos($message, 'strtolower(): Passing null') !== false
            || strpos($message, 'Return type of Symfony\\Component\\DomCrawler\\Crawler::getIterator') !== false)
    ) {
        return true;
    }
    return false;
}, E_DEPRECATED);

ob_start();

$start = microtime(true);
echo "\nScraping iniciado a las " . date('H:i:s') . ".\n";

// Validar autoload de Composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Falta vendor/autoload.php. Ejecuta composer install.\n");
}
require 'vendor/autoload.php';

// Validar y cargar configuración
$config_file = __DIR__ . '/config.php';
error_log("Intentando cargar archivo de configuración: " . $config_file);

if (!file_exists($config_file)) {
    error_log("Error: No se encuentra el archivo de configuración en: " . $config_file);
    die("Error: No se encuentra el archivo config.php\n");
}

if (!is_readable($config_file)) {
    error_log("Error: El archivo de configuración no es legible: " . $config_file);
    die("Error: El archivo config.php no es legible\n");
}

$config = @include $config_file;
//error_log("Resultado de carga de configuración: " . var_export($config, true));

if ($config === false) {
    error_log("Error: Falló la carga del archivo de configuración");
    die("Error: Falló la carga del archivo config.php\n");
}

if ($config === 1) {
    error_log("Error: El archivo de configuración no devuelve un array");
    die("Error: El archivo config.php debe devolver un array de configuración\n");
}

if (!is_array($config)) {
    error_log("Error: La configuración no es un array. Tipo recibido: " . gettype($config));
    die("Error: Formato inválido en config.php. Debe devolver un array.\n");
}

if (!isset($config['db']) || !isset($config['sites'])) {
    error_log("Error: Formato inválido en config.php. Contenido: " . var_export($config, true));
    die("Error: Formato inválido en config.php. Debe contener las secciones 'db' y 'sites'.\n");
}

// Validar configuración de base de datos
$required_db_fields = ['host', 'name', 'user', 'pass'];
foreach ($required_db_fields as $field) {
    if (!isset($config['db'][$field])) {
        error_log("Error: Falta el campo '$field' en la configuración de la base de datos");
        die("Error: Configuración de base de datos incompleta. Falta el campo: $field\n");
    }
}

// Validar extensiones de procesamiento de imágenes
if (!extension_loaded('imagick') && !extension_loaded('gd')) {
    die("Se requiere Imagick o GD para el procesamiento de imágenes.\n");
}

// Incluir procesador optimizado de imágenes
require_once 'optimized-image-processor.php';
require_once 'download_image.php';

use Goutte\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client as GuzzleClient;

function getPDO() {
    global $config;
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}

$pdo = getPDO();

// Obtener la última fecha de scraping
$stmt = $pdo->prepare("SELECT value FROM configs WHERE name = 'last_scrape_date'");
$stmt->execute();
$lastScrapeDate = $stmt->fetchColumn() ?: '2000-01-01';

$hoy = date('Y-m-d');
if ($lastScrapeDate !== $hoy) {
    echo "Iniciando scraping (última fecha: $lastScrapeDate)...\n";
    $update = $pdo->prepare("REPLACE INTO configs (name, value) VALUES ('last_scrape_date', :fecha)");
    $update->execute([':fecha' => $hoy]);
}

$client = new Client();
$guzzle = new GuzzleClient([
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3'
    ],
    'timeout' => 30,
    'connect_timeout' => 10,
    'allow_redirects' => [
        'max' => 10,
        'strict' => false,
        'referer' => true,
        'protocols' => ['http', 'https'],
        'track_redirects' => true
    ],
    'verify' => false, // Desactivar verificación SSL para desarrollo
    'http_errors' => false // No lanzar excepciones en errores HTTP
]);

function checkImageMagickSupport() {
    if (!extension_loaded('imagick')) {
        error_log("La extensión Imagick no está instalada");
        return false;
    }

    $formats = Imagick::queryFormats();
    error_log("Formatos soportados por ImageMagick: " . implode(", ", $formats));
    
    if (!in_array('WEBP', $formats)) {
        error_log("ADVERTENCIA: El formato WebP no está soportado por ImageMagick");
    }
    
    return true;
}

function saveImageLocally($imageUrl, $country, $alt) {
    $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $alt) . '_' . uniqid();
    error_log("Iniciando guardado de imagen - URL: $imageUrl, País: $country, Alt: $alt");
    
    // Crear estructura de directorios como Meltwater
    $upload_dir = __DIR__ . '/images/covers';
    $thumb_dir = $upload_dir . '/thumbnails';
    $preview_dir = $upload_dir . '/previews';
    foreach ([$upload_dir, $thumb_dir, $preview_dir] as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("Error al crear directorio: $dir");
                return false;
            }
        }
    }

    // Usar Guzzle para la descarga con timeout y manejo de errores
    global $guzzle;
    try {
        error_log("Descargando imagen de: $imageUrl");
        $response = $guzzle->get($imageUrl);
        $imageData = $response->getBody()->getContents();
        error_log("Imagen descargada exitosamente - Tamaño: " . strlen($imageData) . " bytes");
    } catch (Exception $e) {
        error_log("Error al descargar la imagen: $imageUrl - " . $e->getMessage());
        return false;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'img_');
    if (!file_put_contents($tempFile, $imageData)) {
        error_log("Error al guardar archivo temporal: $tempFile");
        return false;
    }

    try {
        // Definir rutas de archivos
        $original_filename = $filename . '_original.webp';
        $thumb_filename = $filename . '_thumb.webp';
        $preview_filename = $filename . '_preview.webp';
        $original_filepath = $upload_dir . '/' . $original_filename;
        $thumb_filepath = $thumb_dir . '/' . $thumb_filename;
        $preview_filepath = $preview_dir . '/' . $preview_filename;

        error_log("Rutas de archivos definidas:");
        error_log("- Original: $original_filepath");
        error_log("- Thumbnail: $thumb_filepath");
        error_log("- Preview: $preview_filepath");

        // Si ya existen todos los archivos, devolverlos
        if (file_exists($original_filepath) && file_exists($thumb_filepath) && file_exists($preview_filepath)) {
            error_log("Archivos ya existen, retornando rutas existentes");
            unlink($tempFile);
            return [
                'preview' => 'images/covers/previews/' . $preview_filename,
                'thumbnail' => 'images/covers/thumbnails/' . $thumb_filename,
                'original' => 'images/covers/' . $original_filename
            ];
        }

        // Convertir a WebP y crear versión original
        error_log("Convirtiendo a WebP - Original");
        if (!convertToWebP($tempFile, $original_filepath, 90)) {
            error_log("Error al convertir imagen original a WebP");
            unlink($tempFile);
            return false;
        }

        // Crear miniatura
        error_log("Convirtiendo a WebP - Thumbnail");
        if (!convertToWebP($tempFile, $thumb_filepath, 80, 400, 600)) {
            error_log("Error al convertir thumbnail a WebP");
            unlink($tempFile);
            return false;
        }

        // Crear preview
        error_log("Convirtiendo a WebP - Preview");
        if (!convertToWebP($tempFile, $preview_filepath, 40, 320, 480)) {
            error_log("Error al convertir preview a WebP");
            unlink($tempFile);
            return false;
        }

        unlink($tempFile);
        error_log("Imagen procesada exitosamente");
        
        return [
            'preview' => 'images/covers/previews/' . $preview_filename,
            'thumbnail' => 'images/covers/thumbnails/' . $thumb_filename,
            'original' => 'images/covers/' . $original_filename
        ];
    } catch (Exception $e) {
        error_log("Error procesando la imagen: " . $e->getMessage());
        if (file_exists($tempFile)) unlink($tempFile);
        return false;
    }
}

function cleanImageUrl($url) {
    // Eliminar sufijos de dimensiones como -754x1024
    return preg_replace('/-\d+x\d+(\.[^.]+)$/', '$1', $url);
}

function storeCover($country, $alt, $urlImg, $sourceLink) {
    try {
        $pdo = getPDO();
        if (!$pdo instanceof PDO) {
            error_log("Error: Failed to get valid PDO instance in storeCover function");
            return false;
        }
        
        // Limpiar la URL de la imagen antes de procesarla
        $urlImg = cleanImageUrl($urlImg);
        error_log("Procesando cover - País: $country, URL: $urlImg, Fuente: $sourceLink");
        
        // Verificar si ya existe la imagen
        $stmt = $pdo->prepare('SELECT 1 FROM covers WHERE country=:c AND original_link=:u');
        $stmt->execute([':c' => $country, ':u' => $urlImg]);
        if ($stmt->fetchColumn()) {
            error_log("Cover ya existe para $country y $urlImg");
            return true;
        }

        $imageResult = saveImageLocally($urlImg, $country, $alt);
        if (!$imageResult) {
            error_log("Error al guardar imagen localmente para $urlImg");
            return false;
        }

        error_log("Imagen guardada exitosamente: " . print_r($imageResult, true));

        try {
            $ins = $pdo->prepare("INSERT INTO covers(
                country, title, image_url, source, original_link, 
                preview_url, thumbnail_url, original_url, 
                created_at, updated_at, scraped_at
            ) VALUES(
                :c, :t, :i, :s, :l, 
                :pr, :th, :or,
                NOW(), NOW(), NOW()
            )");
            
            $params = [
                ':c' => $country, 
                ':t' => $alt, 
                ':i' => $imageResult['thumbnail'],
                ':s' => $sourceLink, 
                ':l' => $urlImg,
                ':pr' => $imageResult['preview'],
                ':th' => $imageResult['thumbnail'],
                ':or' => $imageResult['original']
            ];
            
            error_log("Intentando insertar cover con parámetros: " . print_r($params, true));
            
            $ins->execute($params);
            error_log("Cover insertado exitosamente");
            return true;
            
        } catch (PDOException $e) {
            // Si la tabla no tiene las columnas de timestamp, usar estructura antigua
            if (strpos($e->getMessage(), 'created_at') !== false) {
                error_log("Fallback: usando estructura antigua sin timestamps para $sourceLink");
                $ins_fallback = $pdo->prepare("INSERT INTO covers(
                    country, title, image_url, source, original_link, 
                    thumbnail_url, original_url, scraped_at
                ) VALUES(
                    :c, :t, :i, :s, :l, 
                    :th, :or, NOW()
                )");
                
                $ins_fallback->execute([
                    ':c' => $country, 
                    ':t' => $alt, 
                    ':i' => $imageResult['thumbnail'],
                    ':s' => $sourceLink, 
                    ':l' => $urlImg,
                    ':th' => $imageResult['thumbnail'],
                    ':or' => $imageResult['original']
                ]);
                error_log("Cover insertado exitosamente con estructura antigua");
                return true;
            } else {
                error_log("Error al insertar cover: " . $e->getMessage());
                throw $e;
            }
        }
    } catch (Exception $e) {
        error_log("Error in storeCover: " . $e->getMessage());
        return false;
    }
    return true;
}

function extractHiresFromFusionScript($crawler) {
    $script = $crawler->filter('script')->reduce(function ($node) {
        return strpos($node->text(), 'Fusion.globalContent') !== false;
    });

    if ($script->count() === 0) return null;

    $content = $script->text();
    if (preg_match('/Fusion\\.globalContent\\s*=\\s*(\{.*?\});/s', $content, $m)) {
        $json = json_decode($m[1], true);
        return isset($json['data']['hires']) ? $json['data']['hires'] : null;
    }

    return null;
}

function extractWithXPath($url, $xpath, $attribute = 'href', $timeout = 5) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout + 2,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; scraper-bot)',
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_RANGE => '0-65535',
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html || strlen($html) < 100) return null;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!$dom->loadHTML($html)) return null;

    $xpathObj = new DOMXPath($dom);
    $nodes = $xpathObj->query($xpath);
    if ($nodes && $nodes->length > 0) {
        return $nodes->item(0)->getAttribute($attribute);
    }

    return null;
}

function makeAbsoluteUrl($baseUrl, $relativeUrl) {
    if (strpos($relativeUrl, '//') === 0) return 'https:' . $relativeUrl;
    if (strpos($relativeUrl, 'http') === 0) return $relativeUrl;
    return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
}

function isValidImage($path) {
    $info = @getimagesize($path);
    return $info !== false && in_array($info['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
}

function verifyHttpResponse($url) {
    global $guzzle;
    error_log("Verificando URL: " . $url);
    
    try {
        $response = $guzzle->head($url, [
            'allow_redirects' => true,
            'timeout' => 10
        ]);
        
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaderLine('content-type');
        
        error_log("Respuesta de $url - Status: $statusCode, Content-Type: $contentType");
        
        if ($statusCode !== 200) {
            error_log("Error: Status code $statusCode para $url");
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error verificando respuesta HTTP para $url: " . $e->getMessage());
        return false;
    }
}

$total = 0;
foreach ($config['sites'] as $country => $confs) {
    $total += count($confs);
}
echo "Total de sitios a scrapear: $total\n<br>";

$counter = 0;
foreach ($config['sites'] as $country => $configs) {
    foreach ($configs as $conf) {
        $counter++;
        $pct = round($counter / $total * 100);
        echo "Procesando [$counter/$total: $pct%] {$conf['url']}\n<br>";

        try {
            // Verificar que la URL es accesible
            if (!verifyHttpResponse($conf['url'])) {
                error_log("Saltando {$conf['url']} debido a respuesta HTTP inválida");
                continue;
            }

            error_log("Iniciando scraping de: " . $conf['url']);
            $crawler = $client->request('GET', $conf['url']);
            $urlImg = '';
            $alt = 'Portada';
            $fullUri = $conf['url'];

            if (!empty($conf['custom_extractor'])) {
                error_log("Usando extractor personalizado: " . $conf['custom_extractor']);
                switch ($conf['custom_extractor']) {
                    case 'extractWithXPath':
                        $xpath = isset($conf['xpath']) ? $conf['xpath'] : '//img';
                        $attr = isset($conf['attribute']) ? $conf['attribute'] : 'src';
                        $urlImg = extractWithXPath($conf['url'], $xpath, $attr);
                        if ($urlImg) $urlImg = makeAbsoluteUrl($conf['url'], $urlImg);
                        break;
                    case 'extractHiresFromFusionScript':
                        $hiresUrl = extractHiresFromFusionScript($crawler);
                        if ($hiresUrl) $urlImg = makeAbsoluteUrl($conf['url'], $hiresUrl);
                        break;
                }
                if ($urlImg) storeCover($country, $alt, $urlImg, $fullUri);
                continue;
            }

            if (!empty($conf['attribute']) && !empty($conf['selector'])) {
                error_log("Intentando extraer imagen con selector: {$conf['selector']} y atributo: {$conf['attribute']}");
                $node = $crawler->filter($conf['selector']);
                if ($node->count()) {
                    $urlImg = makeAbsoluteUrl($conf['url'], $node->attr($conf['attribute']));
                    // Aplicar transformación de URL si está definida
                    if (isset($conf['transformImageUrl']) && is_callable($conf['transformImageUrl'])) {
                        $urlImg = $conf['transformImageUrl']($urlImg);
                        error_log("URL transformada: " . $urlImg);
                    }
                    error_log("URL de imagen encontrada: " . $urlImg);
                } else {
                    error_log("No se encontró nodo con el selector: {$conf['selector']}");
                    continue;
                }
            } elseif (!empty($conf['selector']) && empty($conf['multiple'])) {
                error_log("Intentando extraer imagen con selector simple: {$conf['selector']}");
                $node = $crawler->filter($conf['selector']);
                if ($node->count()) {
                    $img = $node->attr('src') ?: '';
                    $alt = $node->attr('alt') ?: $alt;
                    $urlImg = makeAbsoluteUrl($conf['url'], $img);
                    error_log("URL de imagen encontrada: " . $urlImg . ", Alt: " . $alt);
                } else {
                    error_log("No se encontró nodo con el selector: {$conf['selector']}");
                    continue;
                }
            } elseif (!empty($conf['multiple'])) {
                error_log("Procesando múltiples imágenes con selector: {$conf['selector']}");
                $crawler->filter($conf['selector'])->each(function ($node) use ($conf, $country, $client) {
                    $base = new Uri($conf['url']);
                    $urlImg = '';
                    $linkPage = $conf['url'];
                    $alt = 'Portada';

                    if (!empty($conf['followLinks'])) {
                        $linkNode = $node->filter($conf['followLinks']['linkSelector']);
                        if ($linkNode->count()) {
                            $link = $linkNode->attr('href') ?: '';
                            $full = Uri::resolve($base, new Uri($link));
                            $detail = $client->request('GET', (string)$full);
                            $imgNode = $detail->filter($conf['followLinks']['imageSelector']);
                            if ($imgNode->count()) {
                                $img = $imgNode->attr('src') ?: '';
                                $alt = $node->filter('img')->attr('alt') ?: 'Portada';
                                $urlImg = makeAbsoluteUrl((string)$full, $img);
                                $linkPage = $full;
                            }
                        }
                    } else {
                        $imgNode = $node->filter('img');
                        if ($imgNode->count()) {
                            $img = $imgNode->attr('src') ?: '';
                            $alt = $imgNode->attr('alt') ?: 'Portada';
                            $urlImg = makeAbsoluteUrl($conf['url'], $img);
                        }
                    }

                    if ($urlImg) {
                        storeCover($country, $alt, $urlImg, $linkPage);
                    }
                });
                continue;
            }

            if ($urlImg) {
                storeCover($country, $alt, $urlImg, $fullUri);
            }
        } catch (Exception $e) {
            error_log("Error en {$conf['url']}: " . $e->getMessage());
            continue;
        }
    }
}

$end = microtime(true);
echo "\nScraping finalizado a las " . date('H:i:s') . ". Duración: " . round($end - $start, 2) . "s\n";

// Procesar portadas después del scraping
if (file_exists(__DIR__ . '/process_portadas.php')) {
    echo "\nIniciando procesamiento de portadas...\n";
    $process_start = microtime(true);
    
    ob_start();
    $result = include __DIR__ . '/process_portadas.php';
    $output = ob_get_clean();
    
    if ($result === false) {
        echo "Error procesando portadas\n";
        error_log("Error procesando portadas después del scraping");
    } else {
        $process_end = microtime(true);
        echo "Procesamiento de portadas completado en " . round($process_end - $process_start, 2) . "s\n";
    }
    
    if (trim($output)) {
        echo "Salida del procesamiento de portadas:\n" . trim($output) . "\n";
    }
} else {
    echo "\nprocess_portadas.php no encontrado\n";
    error_log("process_portadas.php no encontrado después del scraping");
}
