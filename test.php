<?php

require 'vendor/autoload.php';

use Goutte\Client;

$client = new Client();

$url = 'https://www.abc.com.py/edicion-impresa/';
$crawler = $client->request('GET', $url);

// Buscar el script que contiene Fusion.globalContent
$scriptNode = $crawler->filter('script')->reduce(function ($node) {
    return strpos($node->text(), 'Fusion.globalContent') !== false;
});

if ($scriptNode->count() === 0) {
    die('No se encontró el script con Fusion.globalContent.');
}

// Obtener el contenido del script
$scriptContent = $scriptNode->text();

// Extraer el JSON desde Fusion.globalContent
if (preg_match('/Fusion\.globalContent\s*=\s*(\{.*?\});/s', $scriptContent, $matches)) {
    $jsonText = $matches[1];

    $data = json_decode($jsonText, true);

    if (isset($data['data']['hires'])) {
        $hiresUrl = $data['data']['hires'];
        echo "✅ URL HIRES: <a href=\"$hiresUrl\" target=\"_blank\">$hiresUrl</a><br>";
        echo "<img src=\"$hiresUrl\" alt=\"Tapa ABC HIRES\" style=\"max-width:100%; height:auto;\">";
    } else {
        echo "No se encontró el campo 'hires' en el JSON.";
    }
} else {
    echo "No se pudo extraer el JSON correctamente.";
}

?>
