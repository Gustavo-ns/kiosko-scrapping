<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Goutte\Client;

$client = new Client();

$url = 'https://larazon.bo/';
//$url = 'https://es.kiosko.net/mx/';
$crawler = $client->request('GET', $url);
echo "✅LOL html<br>";
echo $crawler->filter('body')->html(); // Solo imprime el contenido dentro de <body>
echo "✅LOL html1<br>";
//var_dump($crawler);
// Seleccionar directamente el <img>
//$node = $crawler->filter('#bloque5 .vc_single_image-img')->eq(0);
//$node = $crawler->filter('#bloque5 .vc_single_image-img');

//$node = $crawler->filter('.thcover img');
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
echo "✅<br>";
//var_dump($node);
$nodes = $crawler->filter('.thcover img');
echo 'Cantidad de nodos encontrados: ' . $nodes->count();
/*
$nodes->each(function ($node, $i) {
    $html = $node->getNode(0) ? $node->getNode(0)->ownerDocument->saveHTML($node->getNode(0)) : '';
    echo "[$i] HTML:\n" . $html . "\n\n";
});

echo 'Cantidad de nodos encontrados: ' . $nodes->count();
*/
// Selecciona el primer nodo para procesar
if ($nodes->count() === 0) {
    die('❌ No se encontró la imagen');
}

$node = $nodes->eq(0);

$imgSrc = $node->attr('data-lazy-src');
//$imgSrc = preg_replace('/-\d+x\d+(?=\.\w+$)/', '', $imgSrc);

$alt = $node->attr('alt') ?: 'Portada La Razón';
$linkHref = $url;

echo "✅ Imagen encontrada:<br>";
echo "<strong>Link:</strong> <a href=\"$linkHref\" target=\"_blank\">$linkHref</a><br>";
echo "<strong>Imagen:</strong> <a href=\"$imgSrc\" target=\"_blank\">$imgSrc</a><br><br>";
echo "<img src=\"$imgSrc\" alt=\"$alt\" style=\"max-width:100%; height:auto;\">";
