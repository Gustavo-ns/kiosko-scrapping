<?php
// scrape.php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/scrape_errors.log'); // Log a archivo
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

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

// Validar extensiones de procesamiento de imágenes
if (!extension_loaded('imagick') && !extension_loaded('gd')) {
    die("Se requiere Imagick o GD para el procesamiento de imágenes.\n");
}

// Incluir procesador optimizado de imágenes
require_once 'optimized-image-processor.php';

$config = require 'config.php';

use Goutte\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client as GuzzleClient;

$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Obtener la última fecha de scraping
$stmt = $pdo->prepare("SELECT value FROM configs WHERE name = 'last_scrape_date'");
$stmt->execute();
$lastScrapeDate = $stmt->fetchColumn() ?: '2000-01-01';

$hoy = date('Y-m-d');
if ($lastScrapeDate !== $hoy) {
    echo "Limpiando imágenes y reiniciando base de datos (última fecha: $lastScrapeDate)...\n";

    $imagesDir = __DIR__ . '/images/';
    if (is_dir($imagesDir)) {
        $files = glob($imagesDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    } else {
        mkdir($imagesDir, 0777, true);
    }

    $pdo->exec('TRUNCATE TABLE covers');

    $update = $pdo->prepare("REPLACE INTO configs (name, value) VALUES ('last_scrape_date', :fecha)");
    $update->execute([':fecha' => $hoy]);
}

$client = new Client();
$guzzle = new GuzzleClient([
    'headers' => ['User-Agent' => 'Mozilla/5.0'],
    'timeout' => 10,
    'connect_timeout' => 5
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
    $savePath = __DIR__ . "/images/";

    // Usar Guzzle para la descarga con timeout y manejo de errores
    global $guzzle;
    try {
        $response = $guzzle->get($imageUrl);
        $imageData = $response->getBody()->getContents();
    } catch (Exception $e) {
        error_log("No se pudo descargar la imagen: $imageUrl - " . $e->getMessage());
        return false;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'img_');
    file_put_contents($tempFile, $imageData);

    try {
        // Usar el procesador optimizado que aprovecha las capacidades del servidor
        $result = processOptimizedImage($tempFile, $savePath, [
            'max_width' => 325,
            'max_height' => 500,
            'quality' => 85,
            'prefer_webp' => true, // Intentar WebP si está disponible
            'strip_metadata' => true
        ]);
        
        // Limpiar archivo temporal
        unlink($tempFile);
        
        if ($result['success']) {
            error_log("Imagen procesada exitosamente: " . basename($result['output_path']) . 
                     " (" . $result['format_used'] . ", " . $result['savings_percent'] . "% ahorro)");
            return "images/" . basename($result['output_path']);
        } else {
            error_log("Error procesando imagen: $imageUrl - " . $result['error']);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error procesando la imagen: $imageUrl - " . $e->getMessage());
        if (file_exists($tempFile)) unlink($tempFile);
        return false;
    }
}

function storeCover($country, $alt, $urlImg, $sourceLink) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT 1 FROM covers WHERE country=:c AND original_link=:u');
    $stmt->execute([':c' => $country, ':u' => $urlImg]);
    if (!$stmt->fetchColumn()) {
        $local = saveImageLocally($urlImg, $country, $alt);
        if ($local) {
            $ins = $pdo->prepare("INSERT INTO covers(country,title,image_url,source,original_link) VALUES(:c,:t,:i,:s,:l)");
            $ins->execute([
                ':c' => $country, 
                ':t' => $alt, 
                ':i' => $local,
                ':s' => $sourceLink, 
                ':l' => $urlImg
            ]);
        }
    }
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
            $crawler = $client->request('GET', $conf['url']);
            $urlImg = '';
            $alt = 'Portada';
            $fullUri = $conf['url'];

            if (!empty($conf['custom_extractor'])) {                switch ($conf['custom_extractor']) {
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
                $node = $crawler->filter($conf['selector']);
                if ($node->count()) {
                    $urlImg = makeAbsoluteUrl($conf['url'], $node->attr($conf['attribute']));
                } else {
                    continue;
                }
            } elseif (!empty($conf['selector']) && empty($conf['multiple'])) {
                $node = $crawler->filter($conf['selector']);
                if ($node->count()) {
                    $img = $node->attr('src') ?: '';
                    $alt = $node->attr('alt') ?: $alt;
                    $urlImg = makeAbsoluteUrl($conf['url'], $img);
                } else {
                    continue;
                }
            } elseif (!empty($conf['multiple'])) {
                $crawler->filter($conf['selector'])->each(function ($node) use ($conf, $country, $pdo, $client) {
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
