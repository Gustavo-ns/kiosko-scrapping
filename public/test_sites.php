<?php
// Configuración de errores
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// Intentar diferentes rutas para encontrar el archivo de configuración
$possiblePaths = [
    dirname(__DIR__) . '/config.php',                    // Ruta relativa estándar
    dirname(__DIR__) . '/scrapers/config.php',          // En directorio scrapers
    dirname(__DIR__) . '/app/config.php',               // En directorio app
    dirname(__DIR__) . '/config/config.php',            // En directorio config
    __DIR__ . '/../config.php',                         // Ruta relativa alternativa
    __DIR__ . '/config.php',                            // En directorio public
    realpath(dirname(__DIR__)) . '/config.php',         // Ruta real absoluta
    '/home2/eyewatch/public_html/website_6c3be7ed/config.php'  // Ruta absoluta específica
];

$configFile = null;
foreach ($possiblePaths as $path) {
    echo "Buscando configuración en: {$path}\n";
    if (file_exists($path)) {
        $configFile = $path;
        echo "¡Archivo de configuración encontrado en: {$path}!\n";
        break;
    }
}

if (!$configFile) {
    echo "Error: No se pudo encontrar el archivo de configuración.\n";
    echo "Rutas probadas:\n";
    foreach ($possiblePaths as $path) {
        echo "- {$path}\n";
    }
    
    // Si no existe el archivo, vamos a crearlo en la ubicación principal
    $defaultConfig = <<<'CONFIG'
<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'eyewatch_newsroom',
        'user' => 'eyewatch_newsroom',
        'pass' => '',  // Deberás configurar la contraseña correcta
        'charset' => 'utf8mb4'
    ],
    'sites' => [
        'argentina' => [
            [
                'url' => 'https://es.kiosko.net/ar/',
                'selector' => '.thcover',
                'multiple' => true,
                'followLinks' => [
                    'linkSelector' => 'a',
                    'linkImgSelector' => '.frontPageImage a',
                    'imageSelector' => '#portada',
                ],
            ]
        ]
    ]
];
CONFIG;

    $defaultConfigPath = dirname(__DIR__) . '/config.php';
    echo "\nIntentando crear archivo de configuración en: {$defaultConfigPath}\n";
    
    if (@file_put_contents($defaultConfigPath, $defaultConfig)) {
        echo "¡Archivo de configuración creado exitosamente!\n";
        $configFile = $defaultConfigPath;
        
        // Verificar que el archivo se creó correctamente
        if (!file_exists($configFile)) {
            die("Error: El archivo se creó pero no se puede acceder a él. Verifica los permisos.\n");
        }
    } else {
        echo "Error al crear el archivo de configuración. Verifica los permisos.\n";
        echo "\nInformación del sistema:\n";
        echo "- __DIR__: " . __DIR__ . "\n";
        echo "- dirname(__DIR__): " . dirname(__DIR__) . "\n";
        echo "- realpath(__DIR__): " . realpath(__DIR__) . "\n";
        echo "- realpath(dirname(__DIR__)): " . realpath(dirname(__DIR__)) . "\n";
        echo "- getcwd(): " . getcwd() . "\n";
        
        // Verificar permisos
        echo "\nPermisos de directorios:\n";
        echo "- " . dirname(__DIR__) . ": " . substr(sprintf('%o', fileperms(dirname(__DIR__))), -4) . "\n";
        echo "- " . __DIR__ . ": " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
        
        // Listar archivos en el directorio actual y el padre
        echo "\nArchivos en el directorio actual (" . __DIR__ . "):\n";
        foreach (glob(__DIR__ . "/*") as $file) {
            echo "- " . basename($file) . " (" . substr(sprintf('%o', fileperms($file)), -4) . ")\n";
        }
        
        echo "\nArchivos en el directorio padre (" . dirname(__DIR__) . "):\n";
        foreach (glob(dirname(__DIR__) . "/*") as $file) {
            echo "- " . basename($file) . " (" . substr(sprintf('%o', fileperms($file)), -4) . ")\n";
        }
        
        die("Por favor, crea el archivo config.php manualmente en la ubicación correcta.\n");
    }
}

// Verificar que tenemos una ruta válida
if (empty($configFile) || !file_exists($configFile)) {
    die("Error: No se pudo encontrar o crear el archivo de configuración.\n");
}

// Definir la ruta base del proyecto
define('BASE_PATH', dirname($configFile));

// Verificar que el archivo de autoload existe
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Error: No se encuentra el archivo autoload.php. Ejecuta 'composer install' primero.\n");
}

require_once $autoloadPath;

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\RequestOptions;

class NewsScraper {
    private $client;
    private $config;
    
    public function __construct() {
        global $configFile;
        
        $this->client = new Client();
        $guzzleClient = new GuzzleClient([
            'verify' => false,
            'timeout' => 300,
            'connect_timeout' => 30,
            'read_timeout' => 300,
            'http_errors' => false,
            RequestOptions::ALLOW_REDIRECTS => [
                'max'             => 10,
                'strict'          => false,
                'referer'         => true,
                'protocols'       => ['http', 'https'],
                'track_redirects' => true
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            ],
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_ENCODING => '',
                CURLOPT_COOKIESESSION => true,
                CURLOPT_RETURNTRANSFER => true
            ]
        ]);
        
        $this->client->setClient($guzzleClient);
        
        try {
            if (!is_readable($configFile)) {
                throw new \Exception("El archivo de configuración no es legible: {$configFile}");
            }
            
            $this->config = require $configFile;
            if (!is_array($this->config)) {
                throw new \Exception("El archivo de configuración no devuelve un array");
            }
            if (!isset($this->config['sites'])) {
                throw new \Exception("La configuración no contiene la sección 'sites'");
            }
        } catch (\Exception $e) {
            die("Error cargando configuración: " . $e->getMessage() . "\n");
        }
    }
    
    private function checkResponse($crawler, $url) {
        if (!$crawler) {
            throw new \Exception("No se pudo obtener respuesta de {$url}");
        }
        
        // Verificar si hay contenido
        $content = $crawler->html();
        if (empty($content)) {
            throw new \Exception("No se recibió contenido de {$url}");
        }
        
        return true;
    }
    
    public function scrape($country = 'argentina') {
        if (!isset($this->config['sites'][$country])) {
            echo "País no encontrado en la configuración: {$country}\n";
            return;
        }
        $sites = $this->config['sites'][$country];
        
        foreach ($sites as $site) {
            try {
                echo "\nProcesando {$site['url']}\n";
                
                // Verificar URL antes de procesar
                if (!filter_var($site['url'], FILTER_VALIDATE_URL)) {
                    throw new \Exception("URL inválida: {$site['url']}");
                }
                
                $crawler = $this->client->request('GET', $site['url']);
                
                // Verificar respuesta
                $this->checkResponse($crawler, $site['url']);
                
                echo "Buscando portadas con selector: {$site['selector']}\n";
                
                $covers = $crawler->filter($site['selector']);
                if ($covers->count() === 0) {
                    echo "No se encontraron portadas en {$site['url']}\n";
                    continue;
                }
                
                echo "Encontradas {$covers->count()} portadas\n";
                
                $covers->each(function (Crawler $node) use ($site) {
                    try {
                        if (isset($site['followLinks'])) {
                            // Intentar obtener el enlace
                            try {
                                $link = $node->filter($site['followLinks']['linkSelector'])->link();
                            } catch (\Exception $e) {
                                throw new \Exception("No se pudo obtener el enlace: " . $e->getMessage());
                            }
                            
                            $detailUrl = $link->getUri();
                            echo "\nSiguiendo enlace: {$detailUrl}\n";
                            
                            // Visitar la página de detalle con reintento
                            $maxRetries = 3;
                            $retry = 0;
                            $detailCrawler = null;
                            
                            while ($retry < $maxRetries) {
                                try {
                                    $detailCrawler = $this->client->click($link);
                                    if ($this->checkResponse($detailCrawler, $detailUrl)) {
                                        break;
                                    }
                                } catch (\Exception $e) {
                                    $retry++;
                                    if ($retry >= $maxRetries) {
                                        throw $e;
                                    }
                                    echo "Reintento {$retry} de {$maxRetries}...\n";
                                    sleep(2);
                                }
                            }
                            
                            if (!$detailCrawler) {
                                throw new \Exception("No se pudo acceder a la página de detalle después de {$maxRetries} intentos");
                            }
                            
                            // En la página de detalle, buscar el enlace a la imagen completa
                            if (isset($site['followLinks']['linkImgSelector'])) {
                                try {
                                    $fullImageLink = $detailCrawler->filter($site['followLinks']['linkImgSelector'])->link();
                                    $fullImagePage = $this->client->click($fullImageLink);
                                    
                                    // Verificar respuesta
                                    $this->checkResponse($fullImagePage, $fullImageLink->getUri());
                                    
                                    // Obtener la imagen final
                                    $imageUrl = $fullImagePage->filter($site['followLinks']['imageSelector'])->attr('src');
                                    
                                    if (empty($imageUrl)) {
                                        throw new \Exception("URL de imagen vacía");
                                    }
                                    
                                    // Asegurarse de que la URL de la imagen es absoluta
                                    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                        $imageUrl = $this->makeAbsoluteUrl($imageUrl, $fullImageLink->getUri());
                                    }
                                    
                                    // Extraer el título/nombre del periódico
                                    $title = $node->attr('title');
                                    if (empty($title)) {
                                        $title = $node->attr('alt');
                                    }
                                    if (empty($title)) {
                                        $title = basename($imageUrl);
                                    }
                                    
                                    echo "Imagen encontrada: {$imageUrl}\n";
                                    echo "Título: {$title}\n";
                                    echo "------------------------\n";
                                    
                                    $this->saveImage($imageUrl, $title, $country);
                                    
                                } catch (\Exception $e) {
                                    throw new \Exception("Error procesando imagen: " . $e->getMessage());
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        echo "Error procesando portada: " . $e->getMessage() . "\n";
                    }
                });
            } catch (\Exception $e) {
                echo "Error procesando sitio {$site['url']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function makeAbsoluteUrl($relativeUrl, $baseUrl) {
        if (parse_url($relativeUrl, PHP_URL_SCHEME) !== null) {
            return $relativeUrl;
        }
        
        $baseParts = parse_url($baseUrl);
        if ($relativeUrl[0] === '/') {
            return $baseParts['scheme'] . '://' . $baseParts['host'] . $relativeUrl;
        }
        
        $path = isset($baseParts['path']) ? dirname($baseParts['path']) : '/';
        return $baseParts['scheme'] . '://' . $baseParts['host'] . $path . '/' . $relativeUrl;
    }
    
    private function saveImage($url, $title, $country) {
        try {
            // Configurar contexto para la descarga
            $context = stream_context_create([
                'http' => [
                    'timeout' => 300,
                    'header' => [
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                        'Accept: image/webp,image/*,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.5',
                    ]
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            
            $imageData = file_get_contents($url, false, $context);
            if ($imageData === false) {
                throw new \Exception("No se pudo descargar la imagen");
            }
            
            // Crear directorio si no existe
            $directory = dirname(__DIR__) . "/images/{$country}/" . date('Y-m-d');
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            
            // Generar nombre de archivo seguro
            $filename = $directory . '/' . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '.jpg';
            
            // Guardar imagen
            if (file_put_contents($filename, $imageData)) {
                echo "Imagen guardada: {$filename}\n";
            } else {
                echo "Error al guardar la imagen: {$filename}\n";
            }
        } catch (\Exception $e) {
            echo "Error guardando imagen: " . $e->getMessage() . "\n";
        }
    }
}

// Configurar límites de memoria y tiempo
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Ejecutar el scraper
try {
    $scraper = new NewsScraper();
    $scraper->scrape('argentina');
} catch (\Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
