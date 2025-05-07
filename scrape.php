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

@require 'vendor/autoload.php';
$config = require 'config.php';

use Goutte\Client;
use PDO;
use GuzzleHttp\Psr7\Uri;

$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$client = new Client();

function saveImageLocally($url, $country, $title) {
    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
    $filename = $country . '_' . $slug . '_' . uniqid() . '.' . $ext;
    $directory = __DIR__ . '/images/';
    $path = $directory . $filename;

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    try {
        $imageData = file_get_contents($url);
        if ($imageData === false) return false;

        // Crear imagen desde el flujo de datos
        $imagick = new Imagick();
        $imagick->readImageBlob($imageData);

        // Redimensionar la imagen (ancho 300px, manteniendo la relación de aspecto)
        $imagick->resizeImage(300, 0, Imagick::FILTER_LANCZOS, 1);

        // Guardar la imagen redimensionada
        $imagick->writeImage($path);

        return 'images/' . $filename;
    } catch (Exception $e) {
        error_log("No se pudo guardar la imagen redimensionada: $url");
        return false;
    }
}

foreach ($config['sites'] as $country => $configs) {
    foreach ($configs as $conf) {
        try {
            $crawler = $client->request('GET', $conf['url']);

            if (!empty($conf['multiple'])) {
                $crawler->filter($conf['selector'])->each(function($node) use ($conf, $country, $pdo, $client) {
                    if (!empty($conf['followLinks'])) {
                        $link = $node->filter($conf['followLinks']['linkSelector'])->attr('href') ?? '';
                        $baseUri     = new Uri($conf['url']);
                        $relativeUri = new Uri($link);
                        $fullUri     = Uri::resolve($baseUri, $relativeUri);

                        $detail = $client->request('GET', (string) $fullUri);
                        $img     = $detail->filter($conf['followLinks']['imageSelector'])->attr('src');
                        $alt     = $node->filter('img')->attr('alt') ?: 'Unknown';
                        $urlImg  = strpos($img, '//') === 0 ? 'https:'.$img : $img;
                    } else {
                        $img     = $node->filter('img')->attr('src');
                        $alt     = $node->filter('img')->attr('alt') ?: 'Unknown';
                        $urlImg  = strpos($img, '//') === 0 ? 'https:'.$img : $img;
                        $fullUri = null;
                    }

                    // Evitar duplicados por URL original
                    $checkStmt = $pdo->prepare('SELECT 1 FROM covers WHERE country = :country AND original_link = :url LIMIT 1');
                    $checkStmt->execute([':country' => $country, ':url' => $urlImg]);
                    $exists = $checkStmt->fetchColumn();

                    if (!$exists) {
                        $localImg = saveImageLocally($urlImg, $country, $alt);
                        if (!$localImg) return;

                        $insertStmt = $pdo->prepare(
                            "INSERT INTO covers (country, title, image_url, source, original_link)
                             VALUES (:country, :title, :image, :source, :link)"
                        );
                        $insertStmt->execute([
                            ':country' => $country,
                            ':title'   => $alt,
                            ':image'   => $localImg,
                            ':source' => (string) $fullUri,
                            ':link'    => $urlImg,
                        ]);
                    }
                });
            } else {
                $img    = $crawler->filter($conf['selector'])->attr('src');
                $alt    = $crawler->filter($conf['selector'])->attr('alt') ?: 'Unknown';
                $urlImg = strpos($img, '//') === 0 ? 'https:'.$img : $img;

                $checkStmt = $pdo->prepare('SELECT 1 FROM covers WHERE country = :country AND original_link = :url LIMIT 1');
                $checkStmt->execute([':country' => $country, ':url' => $urlImg]);
                $exists = $checkStmt->fetchColumn();

                if (!$exists) {
                    $localImg = saveImageLocally($urlImg, $country, $alt);
                    if (!$localImg) continue;

                    $insertStmt = $pdo->prepare(
                        "INSERT INTO covers (country, title, image_url, source, original_link)
                         VALUES (:country, :title, :image, :source, :link)"
                    );
                    $insertStmt->execute([
                        ':country' => $country,
                        ':title'   => $alt,
                        ':image'   => $localImg,
                        ':source' => (string) $fullUri,
                        ':link'    => $urlImg,
                    ]);
                }
            }
        } catch (\Exception $e) {
            error_log("Error scraping {$conf['url']}: " . $e->getMessage());
            continue;
        }
    }
}

echo "\nScraping finalizado a las " . date('H:i:s') . ".\n";
