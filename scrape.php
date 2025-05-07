<?php
// scrape.php
require 'vendor/autoload.php';
$config = require 'config.php';

use Goutte\Client;
use PDO;

$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$client = new Client();

foreach ($config['sites'] as $country => $configs) {
    foreach ($configs as $conf) {
        try {
            $crawler = $client->request('GET', $conf['url']);
            if (!empty($conf['multiple'])) {
                $crawler->filter($conf['selector'])->each(function($node) use ($conf, $country, $pdo, $client) {
                    // Si hay que seguir link
                    if (!empty($conf['followLinks'])) {
                        $link = $node->filter($conf['followLinks']['linkSelector'])->attr('href');
                        $full = (new \GuzzleHttp\Psr7\Uri($conf['url']))->resolve(new \GuzzleHttp\Psr7\Uri($link));
                        $detail = $client->request('GET', (string)$full);
                        $img = $detail->filter($conf['followLinks']['imageSelector'])->attr('src');
                        $alt = @$node->filter('img')->attr('alt') ?: 'Unknown';
                        $urlImg = strpos($img, '//') === 0 ? 'https:'.$img : $img;
                    } else {
                        $img = $node->filter('img')->attr('src');
                        $alt = $node->filter('img')->attr('alt') ?: 'Unknown';
                        $urlImg = strpos($img, '//') === 0 ? 'https:'.$img : $img;
                        $full = null;
                    }
                    // Inserta en BD
                    $stmt = $pdo->prepare("
                        INSERT INTO covers (country, title, image_url, source, original_link)
                        VALUES (:country, :title, :image, :source, :link)
                    ");
                    $stmt->execute([
                        ':country' => $country,
                        ':title'   => $alt,
                        ':image'   => $urlImg,
                        ':source'  => parse_url($conf['url'], PHP_URL_HOST),
                        ':link'    => $full ? (string)$full : null,
                    ]);
                });
            } else {
                $img = $crawler->filter($conf['selector'])->attr('src');
                $alt = $crawler->filter($conf['selector'])->attr('alt') ?: 'Unknown';
                $urlImg = strpos($img, '//') === 0 ? 'https:'.$img : $img;
                $stmt = $pdo->prepare("
                    INSERT INTO covers (country, title, image_url, source)
                    VALUES (:country, :title, :image, :source)
                ");
                $stmt->execute([
                    ':country' => $country,
                    ':title'   => $alt,
                    ':image'   => $urlImg,
                    ':source'  => parse_url($conf['url'], PHP_URL_HOST),
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error scraping {$conf['url']}: ".$e->getMessage());
            continue;
        }
    }
}
echo "Scraping finalizado.\n";
