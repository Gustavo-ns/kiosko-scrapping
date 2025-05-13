<?php

require 'vendor/autoload.php';

use Goutte\Client;

$client = new Client();

$url = 'https://www.ultimahora.com/';
$crawler = $client->request('GET', $url);

// Buscar el botón que contiene el atributo data-src
$buttonNode = $crawler->filter('bsp-page-promo-modal button[data-fancybox="fancybox-tapa"]');

if ($buttonNode->count() === 0) {
    die('No se encontró el botón con la tapa.');
}

$hiresUrl = $buttonNode->attr('data-src');

if (!$hiresUrl) {
    die('No se encontró el atributo data-src.');
}

echo "✅ URL HIRES: <a href=\"$hiresUrl\" target=\"_blank\">$hiresUrl</a><br>";
echo "<img src=\"$hiresUrl\" alt=\"Portada Última Hora\" style=\"max-width:100%; height:auto;\">";

?>
