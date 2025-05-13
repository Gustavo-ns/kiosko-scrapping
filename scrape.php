<?php
// scrape.php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/scrape_errors.log'); // Log a archivo
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

set_error_handler(function($severity, $message) {
    if ($severity === E_DEPRECATED
        && (strpos($message, 'strtolower(): Passing null') !== false
            || strpos($message, 'Return type of Symfony\\Component\\DomCrawler\\Crawler::getIterator') !== false)) {
        return true;
    }
    return false;
}, E_DEPRECATED);

$start = microtime(true);
echo "\nScraping iniciado a las " . date('H:i:s') . ".\n";

// Validar autoload de Composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Falta vendor/autoload.php. Ejecuta composer install.\n");
}
require 'vendor/autoload.php';

// Validar Imagick
if (!extension_loaded('imagick')) {
    die("La extensión Imagick no está habilitada.\n");
}

$config = require 'config.php';

use Goutte\Client;
use PDO;
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
$guzzle = new GuzzleClient(['headers' => ['User-Agent' => 'Mozilla/5.0']]);

function makeAbsoluteUrl($baseUrl, $relativeUrl) {
    if (strpos($relativeUrl, '//') === 0) return 'https:' . $relativeUrl;
    if (strpos($relativeUrl, 'http') === 0) return $relativeUrl;
    return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
}

function saveImageLocally($url, $country, $title) {
    global $guzzle;
    if (empty($url)) {
        error_log("URL vacía en saveImageLocally para $title ($country)");
        return false;
    }

    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
    $filename = "{$country}_{$slug}_" . uniqid() . ".webp";
    $path = __DIR__ . '/images/' . $filename;

    try {
        $response = $guzzle->get($url);
        $imageData = $response->getBody()->getContents();

        $imagick = new Imagick();
        $imagick->readImageBlob($imageData);

        if ($imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
            $imagick->transformImageColorspace(Imagick::COLORSPACE_RGB);
        }

        $imagick->resizeImage(300, 0, Imagick::FILTER_LANCZOS, 1);
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality(80);

        try {
            $imagick->writeImage($path);
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }

        return 'images/' . $filename;
    } catch (Exception $e) {
        error_log("No se pudo guardar la imagen: $url - " . $e->getMessage());
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
            $ins->execute([':c' => $country, ':t' => $alt, ':i' => $local, ':s' => $sourceLink, ':l' => $urlImg]);
        }
    }
}

function extractHiresFromFusionScript($crawler) {
    $script = $crawler->filter('script')->reduce(function($node) {
        return strpos($node->text(), 'Fusion.globalContent') !== false;
    });

    if ($script->count() === 0) return null;

    $content = $script->text();
    if (preg_match('/Fusion\\.globalContent\\s*=\\s*(\{.*?\});/s', $content, $m)) {
        $json = json_decode($m[1], true);
        return $json['data']['hires'] ?? null;
    }

    return null;
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

            if (!empty($conf['custom_extractor']) && $conf['custom_extractor'] === 'extractHiresFromFusionScript') {
                $urlImg = extractHiresFromFusionScript($crawler);
                if (!$urlImg) continue;
            } elseif (!empty($conf['followLinks']) && $conf['followLinks']['linkSelector'] === null && $conf['multiple'] === false) {
               $node = $crawler->filter($conf['selector']);
                if ($node->count()) {
                    $urlImg = $node->attr($conf['followLinks']['attribute']);
                    $urlImg = makeAbsoluteUrl($conf['url'], $urlImg);
                } else {
                    continue;
                }
            } elseif (empty($conf['multiple'])) {
                $node = $crawler->filter($conf['selector']);
                if ($node->count()) {
                    $img = $node->attr('src') ?: '';
                    $alt = $node->attr('alt') ?: $alt;
                    $urlImg = makeAbsoluteUrl($conf['url'], $img);
                } else {
                    continue;
                }
            } else {
                $crawler->filter($conf['selector'])->each(function($node) use($conf, $country, $pdo, $client) {
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
                            $linkNodeImg = $detail->filter($conf['followLinks']['linkImgSelector']);
                            $imgNode = $detail->filter($conf['followLinks']['imageSelector']);
                            if ($imgNode->count()) {
                                $img = $imgNode->attr('src') ?: '';
                                $alt = $node->filter('img')->attr('alt') ?: 'Portada';
                                $urlImg = makeAbsoluteUrl((string)$full, $img);
                                $linkNodeImgHref = $linkNodeImg->attr('href') ?: '';
                                $linkPage = $linkNodeImgHref ?: (string)$full;
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
