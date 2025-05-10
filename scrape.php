<?php
// scrape.php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

set_error_handler(function($severity, $message) {
    if ($severity === E_DEPRECATED
        && (strpos($message, 'strtolower(): Passing null') !== false
            || strpos($message, 'Return type of Symfony\\Component\\DomCrawler\\Crawler::getIterator') !== false)) {
        return true;
    }
    return false;
}, E_DEPRECATED);

echo "\nScraping iniciado a las " . date('H:i:s') . ".\n";

@require 'vendor/autoload.php';
$config = require 'config.php';

use Goutte\Client;
use PDO;
use GuzzleHttp\Psr7\Uri;

///////////////////////////
// Cambia a false si no quieres limpiar la carpeta de imágenes al iniciar
$limpiarInicial = false; 
if ($limpiarInicial) {
    // Limpiar carpeta de imágenes al iniciar
    $imagesDir = __DIR__ . '/images/';
    if (is_dir($imagesDir)) {
        $files = glob($imagesDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    } else {
        mkdir($imagesDir, 0777, true);
    }
    
    // Conexión a la base de datos
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Limpiar tabla covers antes de insertar
    $pdo->exec('TRUNCATE TABLE covers');
}
///////////////////////////

$client = new Client();

function saveImageLocally($url, $country, $title) {
    if (empty($url)) {
        error_log("URL vacía en saveImageLocally para $title ($country)");
        return false;
    }

    // Preparar nombres
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
    $filename = "{$country}_{$slug}_" . uniqid() . ".webp";
    $path = __DIR__ . '/images/' . $filename;

    try {
        $imageData = file_get_contents($url);
        if ($imageData === false) return false;

        $imagick = new Imagick();
        $imagick->readImageBlob($imageData);

        // Convertir a RGB si es necesario
        if ($imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK) {
            $imagick->transformImageColorspace(Imagick::COLORSPACE_RGB);
        }

        // Redimensionar
        $imagick->resizeImage(300, 0, Imagick::FILTER_LANCZOS, 1);

        // Establecer formato WebP
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality(80); // puedes ajustar la calidad

        // Guardar imagen
        $imagick->writeImage($path);
        $imagick->clear();
        $imagick->destroy();

        return 'images/' . $filename;
    } catch (Exception $e) {
        error_log("No se pudo guardar la imagen: $url - " . $e->getMessage());
        return false;
    }
}


function extractHiresFromFusionScript($crawler) {
    $script = $crawler->filter('script')->reduce(function($node) {
        return strpos($node->text(), 'Fusion.globalContent') !== false;
    });
    if ($script->count() === 0) return null;
    $content = $script->text();
    if (preg_match('/Fusion\.globalContent\s*=\s*(\{.*?\});/s', $content, $m)) {
        $json = json_decode($m[1], true);
        return $json['data']['hires'] ?? null;
    }
    return null;
}
$total = 0;
foreach ($config['sites'] as $country => $confs) {
    $total += count($confs);
}
echo "Total de sitios a scrapear: $total\n";

$counter = 0;

foreach ($config['sites'] as $country => $configs) {
    foreach ($configs as $conf) {
        $counter++;
        $percent = round($counter / $total * 100);
        echo "\rProcesando sitio $counter de $total ($percent%) — {$conf['url']}   ";

        try {
           
foreach ($config['sites'] as $country => $configs) {
    foreach ($configs as $conf) {
        try {
            $crawler = $client->request('GET', $conf['url']);
            $urlImg = '';
            $alt = 'Portada';
            $fullUri = $conf['url'];
            // ABC Color custom extractor
            if (!empty($conf['custom_extractor']) && $conf['custom_extractor'] === 'extractHiresFromFusionScript') {
                $urlImg = extractHiresFromFusionScript($crawler);
                if (!$urlImg) continue;
            }
            // Popular: href is the image directly
            elseif (!empty($conf['followLinks']) && $conf['followLinks']['linkSelector'] === null && $conf['multiple'] === false) {
                $link = $crawler->filter($conf['selector'])->attr('href') ?: '';
                if (!$link) continue;
                $urlImg = strpos($link, '//') === 0 ? 'https:' . $link : $link;
            }
            // Standard non-multiple
            elseif (empty($conf['multiple'])) {
                $img = $crawler->filter($conf['selector'])->attr('src') ?: '';
                if (!$img) continue;
                $urlImg = strpos($img, '//') === 0 ? 'https:' . $img : $img;
                $alt = $crawler->filter($conf['selector'])->attr('alt') ?: $alt;
            }
            // Multiple case
            else {
                $crawler->filter($conf['selector'])->each(function($node) use($conf, $country, $pdo, $client) {
                    $base = new Uri($conf['url']);
                    if (!empty($conf['followLinks'])) {
                        $link = $node->filter($conf['followLinks']['linkSelector'])->attr('href') ?: '';
                        $full = Uri::resolve($base, new Uri($link));
                        $detail = $client->request('GET', (string)$full);
                        $img = $detail->filter($conf['followLinks']['imageSelector'])->attr('src') ?: '';
                        $alt = $node->filter('img')->attr('alt') ?: 'Portada';
                        $urlImg = strpos($img, '//')===0?'https:'.$img:$img;
                        $linkPage = (string)$full;
                    } else {
                        $img = $node->filter('img')->attr('src') ?: '';
                        $alt = $node->filter('img')->attr('alt') ?: 'Portada';
                        $urlImg = strpos($img,'//')===0?'https:'.$img:$img;
                        $linkPage = $conf['url'];
                    }
                    $stmt = $pdo->prepare('SELECT 1 FROM covers WHERE country=:c AND original_link=:u');
                    $stmt->execute([':c'=>$country,':u'=>$urlImg]);
                    if (!$stmt->fetchColumn()) {
                        $local = saveImageLocally($urlImg,$country,$alt);
                        if ($local) {
                            $ins = $pdo->prepare("INSERT INTO covers(country,title,image_url,source,original_link)VALUES(:c,:t,:i,:s,:l)");
                            $ins->execute([':c'=>$country,':t'=>$alt,':i'=>$local,':s'=>$linkPage,':l'=>$urlImg]);
                        }
                    }
                });
                continue;
            }
            // Insert single
            $stmt = $pdo->prepare('SELECT 1 FROM covers WHERE country=:c AND original_link=:u');
            $stmt->execute([':c'=>$country,':u'=>$urlImg]);
            if (!$stmt->fetchColumn()) {
                $local = saveImageLocally($urlImg,$country,$alt);
                if ($local) {
                    $ins = $pdo->prepare("INSERT INTO covers(country,title,image_url,source,original_link)VALUES(:c,:t,:i,:s,:l)");
                    $ins->execute([':c'=>$country,':t'=>$alt,':i'=>$local,':s'=>$fullUri,':l'=>$urlImg]);
                }
            }
        } catch (Exception $e) {
            error_log("Error en {$conf['url']}: " . $e->getMessage());
            continue;
        }
    }
}

        } catch (\Exception $e) {
            error_log("Error en {$conf['url']}: " . $e->getMessage());
            continue;
        }
    }
}

echo "\nScraping finalizado a las " . date('H:i:s') . ".\n";
