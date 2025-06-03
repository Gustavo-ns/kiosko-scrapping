<?php
require 'vendor/autoload.php';
require_once 'download_image.php';

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;

// Configurar zona horaria
date_default_timezone_set('America/Montevideo');

// Configurar logging
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/test_popular.log');
error_reporting(E_ALL);

// Función helper para imprimir con saltos de línea HTML
function echoBr($text) {
    echo $text . "<br>\n";
}

echoBr("Iniciando prueba de scraping de Popular...");

$guzzle = new GuzzleClient([
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3'
    ],
    'timeout' => 30,
    'connect_timeout' => 10,
    'allow_redirects' => true,
    'verify' => false,
    'http_errors' => false
]);

$client = new Client();
$client->setClient($guzzle);

$url = 'https://www.popular.com.py/';
echoBr("Accediendo a: $url");

try {
    // Obtener la página principal
    $crawler = $client->request('GET', $url);
    echoBr("Página principal cargada.");
    
    // Buscar el enlace de la portada
    echoBr("Buscando selector '.portada a'...");
    $nodes = $crawler->filter('.portada a');
    echoBr("Encontrados " . $nodes->count() . " nodos.");
    
    if ($nodes->count() > 0) {
        // Obtener y mostrar todos los atributos del nodo
        $node = $nodes->first();
        echoBr("\nAtributos del nodo encontrado:");
        
        // Extraer atributos individualmente para evitar el error de conversión de array
        echoBr("href: " . $node->attr('href'));
        echoBr("class: " . $node->attr('class'));
        echoBr("data-src: " . $node->attr('data-src'));
        
        // Obtener el href y limpiar dimensiones
        $href = $node->attr('href');
        $href = preg_replace('/-\d+x\d+(\.[^.]+)$/', '$1', $href);
        echoBr("\nEnlace encontrado (sin dimensiones): " . $href);
        
        if ($href) {
            // Hacer el enlace absoluto si es necesario
            if (strpos($href, 'http') !== 0) {
                $href = rtrim($url, '/') . '/' . ltrim($href, '/');
            }
            echoBr("URL absoluta: " . $href);
            
            // Verificar si es una URL de imagen
            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $href)) {
                echoBr("\nURL es un enlace directo a imagen. Verificando accesibilidad...");
                
                // Verificar que la imagen es accesible
                $response = $guzzle->head($href);
                $statusCode = $response->getStatusCode();
                $contentType = $response->getHeaderLine('content-type');
                
                echoBr("Status Code: " . $statusCode);
                echoBr("Content-Type: " . $contentType);
                
                if ($statusCode === 200 && strpos($contentType, 'image/') === 0) {
                    echoBr("\nImagen válida encontrada!");
                    
                    // Probar descarga de imagen
                    echoBr("\nProbando descarga de imagen...");
                    $tempFile = tempnam(sys_get_temp_dir(), 'img_test_');
                    
                    try {
                        $imageResponse = $guzzle->get($href);
                        $imageContent = $imageResponse->getBody()->getContents();
                        file_put_contents($tempFile, $imageContent);
                        
                        if (file_exists($tempFile)) {
                            $imageInfo = getimagesize($tempFile);
                            if ($imageInfo) {
                                echoBr("Imagen descargada exitosamente:");
                                echoBr("Dimensiones: " . $imageInfo[0] . "x" . $imageInfo[1]);
                                echoBr("Tipo MIME: " . $imageInfo['mime']);
                                echoBr("Tamaño: " . filesize($tempFile) . " bytes");
                                
                                // Mostrar la imagen
                                echo "<img src='$href' style='max-width: 800px; height: auto;'><br>\n";
                            } else {
                                echoBr("Error: El archivo descargado no es una imagen válida");
                            }
                        }
                    } catch (Exception $e) {
                        echoBr("Error descargando imagen: " . $e->getMessage());
                    } finally {
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                } else {
                    echoBr("Error: La URL no devolvió una imagen válida");
                }
            } else {
                echoBr("La URL no parece ser un enlace directo a imagen");
            }
        }
    } else {
        echoBr("No se encontró el selector '.portada a'");
        
        // Mostrar la estructura del HTML para diagnóstico
        echoBr("\nEstructura del HTML:");
        echoBr(substr($crawler->html(), 0, 1000) . "...");
    }
    
} catch (Exception $e) {
    echoBr("Error: " . $e->getMessage());
    echoBr("Trace:\n" . $e->getTraceAsString());
} 