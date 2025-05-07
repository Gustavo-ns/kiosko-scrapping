<?php
// scrape.php

// 1) Silenciar warnings deprecados de PHP 8+
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// 2) Ignorar mensajes específicos de deprecado (strtolower y getIterator)
set_error_handler(function($severity, $message) {
    if ($severity === E_DEPRECATED
        && (strpos($message, 'strtolower(): Passing null') !== false
            || strpos($message, 'Return type of Symfony\\Component\\DomCrawler\\Crawler::getIterator') !== false)) {
        return true; // ignorar estos mensajes
    }
    return false; // procesar el resto
}, E_DEPRECATED);

// 3) Cargar autoloader sin mostrar advertencias
@require 'vendor/autoload.php';
$config = require 'config.php';

use Goutte\Client;
use PDO;
use GuzzleHttp\Psr7\Uri;

// Conexión a la base de datos
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Cliente Goutte para scraping
$client = new Client();

foreach ($config['sites'] as $country => $configs) {
    foreach ($configs as $conf) {
        try {
            // Petición inicial
            $crawler = $client->request('GET', $conf['url']);

            if (!empty($conf['multiple'])) {
                $crawler->filter($conf['selector'])->each(function($node) use ($conf, $country, $pdo, $client) {
                    if (!empty($conf['followLinks'])) {
                        $link = $node->filter($conf['followLinks']['linkSelector'])->attr('href') ?? '';
                        $baseUri     = new Uri($conf['url']);
                        $relativeUri = new Uri($link);
                        $fullUri     = Uri::resolve($baseUri, $relativeUri);

                        $detail = $client->request('GET', (string) $fullUri);
                        $img    = $detail->filter($conf['followLinks']['imageSelector'])->attr('src');
                        $alt    = $node->filter('img')->attr('alt') ?: 'Unknown';
                        $urlImg = strpos($img, '//') === 0 ? 'https:'.$img : $img;
                    } else {
                        $img    = $node->filter('img')->attr('src');
                        $alt    = $node->filter('img')->attr('alt') ?: 'Unknown';
                        $urlImg = strpos($img, '//') === 0 ? 'https:'.$img : $img;
                        $fullUri = null;
                    }

                    $stmt = $pdo->prepare(
                        "INSERT INTO covers (country, title, image_url, source, original_link) VALUES (:country, :title, :image, :source, :link)"
                    );
                    $stmt->execute([
                        ':country' => $country,
                        ':title'   => $alt,
                        ':image'   => $urlImg,
                        ':source'  => parse_url($conf['url'], PHP_URL_HOST),
                        ':link'    => $fullUri ? (string) $fullUri : null,
                    ]);
                });
            } else {
                $img    = $crawler->filter($conf['selector'])->attr('src');
                $alt    = $crawler->filter($conf['selector'])->attr('alt') ?: 'Unknown';
                $urlImg = strpos($img, '//') === 0 ? 'https:'.$img : $img;

                $stmt = $pdo->prepare(
                    "INSERT INTO covers (country, title, image_url, source) VALUES (:country, :title, :image, :source)"
                );
                $stmt->execute([
                    ':country' => $country,
                    ':title'   => $alt,
                    ':image'   => $urlImg,
                    ':source'  => parse_url($conf['url'], PHP_URL_HOST),
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error scraping {$conf['url']}: " . $e->getMessage());
            continue;
        }
    }
}

echo "Scraping finalizado.\n";
