<?php

require 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;
use Goutte\Client;

$client = new Client(HttpClient::create([
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ]
]));

$url = 'https://www.eldiario.net/portal/';
$crawler = $client->request('GET', $url);

// Guarda el HTML recibido para inspección
file_put_contents('eldiario.html', $crawler->html());


// Buscar el <a> que contiene la imagen de portada
$node = $crawler->filter('.tdm-inline-image-wrap a.td-modal-image');

if ($node->count() === 0) {
    die('❌ No se encontró el enlace de la imagen');
}

$imgUrl = $node->attr('href');
$alt = $node->filter('img')->attr('title') ?? 'Portada El Diario';
$linkHref = $url;

echo "✅ Imagen encontrada:<br>";
echo "<strong>Link:</strong> <a href=\"$linkHref\" target=\"_blank\">$linkHref</a><br>";
echo "<strong>Imagen:</strong> <a href=\"$imgUrl\" target=\"_blank\">$imgUrl</a><br><br>";
echo "<img src=\"$imgUrl\" alt=\"$alt\" style=\"max-width:100%; height:auto;\">";
