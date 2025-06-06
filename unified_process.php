<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/unified_process.log');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Configurar zona horaria
date_default_timezone_set('America/Montevideo');

// Función para logging
function logMessage($message, $type = 'INFO') {
    $date = date('Y-m-d H:i:s');
    error_log("[$date][$type] $message");
}

// Validar autoload de Composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Falta vendor/autoload.php. Ejecuta composer install.\n");
}
require 'vendor/autoload.php';
require 'config.php';
require 'optimized-image-processor.php';
require 'download_image.php';

use Goutte\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client as GuzzleClient;

function getPDO() {
    global $config;
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            logMessage("Error de conexión a la base de datos: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    return $pdo;
}

// Cargar configuración
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    die("Error: No se encuentra el archivo config.php\n");
}
$config = require $config_file;

// Conexión a la base de datos
try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
        $config['db']['user'],
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage() . "\n");
}

// 1. PROCESO DE MELTWATER
logMessage("Iniciando proceso de Meltwater");
try {
    // URLs específicas para cada horario
    $scheduledUrls = [
        '6:30' => 'https://downloads.exports.meltwater.com/v3/recurring/13742063?data_key=3f4aed98-80fe-3fb1-9cf0-ce4681a26d7c',
        '7:35' => 'https://downloads.exports.meltwater.com/v3/recurring/13820798?data_key=99180c35-19a8-3b35-8ae7-368a020d3f60',
        '8:30' => 'https://downloads.exports.meltwater.com/v3/recurring/13841083?data_key=1590e1c0-0060-3ce6-af3b-431a90b5e3e4',
        '9:35' => 'https://downloads.exports.meltwater.com/v3/recurring/13869250?data_key=262188f9-da14-3148-b734-e071ee3d9f7e',
        '10:35' => 'https://downloads.exports.meltwater.com/v3/recurring/13869241?data_key=7481954f-4c7c-34c4-89e8-59b79b063ae7'
    ];

    // Obtener la hora actual y determinar qué URL usar
    $currentHour = date('H:i');
    $dataUrl = null;
    foreach ($scheduledUrls as $time => $url) {
        if ($currentHour >= $time) {
            $dataUrl = $url;
        }
    }
    if (!$dataUrl) {
        $dataUrl = end($scheduledUrls);
    }

    // Obtener y procesar datos de Meltwater
    $response = file_get_contents($dataUrl);
    if ($response === FALSE) {
        throw new Exception("Error al obtener los datos del archivo JSON.");
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        throw new Exception("No se encontraron documentos en la respuesta.");
    }

    // Preparar la consulta de inserción
    $stmt = $pdo->prepare("INSERT INTO pk_melwater (
        external_id, published_date, source_id, social_network, 
        country_code, country_name, author_name, content_image, 
        content_text, url_destino, input_names
    ) VALUES (
        :external_id, :published_date, :source_id, :social_network,
        :country_code, :country_name, :author_name, :content_image,
        :content_text, :url_destino, :input_names
    ) ON DUPLICATE KEY UPDATE
        published_date = VALUES(published_date),
        source_id = VALUES(source_id),
        social_network = VALUES(social_network),
        country_code = VALUES(country_code),
        country_name = VALUES(country_name),
        author_name = VALUES(author_name),
        content_image = VALUES(content_image),
        content_text = VALUES(content_text),
        url_destino = VALUES(url_destino),
        input_names = VALUES(input_names)");

    $updatedCount = 0;
    foreach ($data['documents'] as $doc) {
        $author_name = isset($doc['author']['name']) ? $doc['author']['name'] : 'N/A';
        $content_image = isset($doc['content']['image']) ? $doc['content']['image'] : null;
        $content_text = isset($doc['content']['opening_text']) ? $doc['content']['opening_text'] : '';
        $country_code = strtolower(isset($doc['location']['country_code']) ? $doc['location']['country_code'] : 'zz');
        $country_name = isset($country_names[$country_code]) ? $country_names[$country_code] : ucfirst($country_code);
        $url_destino = isset($doc['url']) ? $doc['url'] : '#';
        $external_id = isset($doc['author']['external_id']) ? $doc['author']['external_id'] : '';
        $published_date = isset($doc['published_date']) ? $doc['published_date'] : '';
        $source_id = isset($doc['source']['id']) ? $doc['source']['id'] : '';
        
        // Procesar la imagen si existe
        if ($content_image && $external_id) {
            try {
                // Crear directorios si no existen
                $melwater_dir = __DIR__ . '/images/melwater';
                $previews_dir = $melwater_dir . '/previews';
                foreach ([$melwater_dir, $previews_dir] as $dir) {
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                }

                // Descargar y procesar la imagen
                $image_data = file_get_contents($content_image);
                if ($image_data !== false) {
                    $temp_file = tempnam(sys_get_temp_dir(), 'melwater_');
                    file_put_contents($temp_file, $image_data);

                    // Procesar imagen original
                    $original_filename = $external_id . '_original.webp';
                    $original_path = $melwater_dir . '/' . $original_filename;
                    
                    if (convertToWebP($temp_file, $original_path, 90)) {
                        // Procesar preview
                        $preview_filename = $external_id . '_preview.webp';
                        $preview_path = $previews_dir . '/' . $preview_filename;
                        
                        if (convertToWebP($temp_file, $preview_path, 40, 320, 480)) {
                            $content_image = 'images/melwater/' . $original_filename;
                        }
                    }
                    unlink($temp_file);
                }
            } catch (Exception $e) {
                logMessage("Error procesando imagen para {$external_id}: " . $e->getMessage(), 'ERROR');
            }
        }
        
        // Extraer red social del source_id
        $social_network = '';
        if (!empty($source_id) && strpos($source_id, 'social:') === 0) {
            $parts = explode(':', $source_id);
            $social_network = isset($parts[1]) ? ucfirst($parts[1]) : '';
        }

        // Obtener los inputs names
        $input_names = [];
        if (isset($doc['matched']['inputs']) && is_array($doc['matched']['inputs'])) {
            foreach ($doc['matched']['inputs'] as $input) {
                if (isset($input['name'])) {
                    $input_names[] = $input['name'];
                }
            }
        }
        $input_names_str = implode(', ', $input_names);

        try {
            $stmt->execute([
                ':external_id' => $external_id,
                ':published_date' => $published_date,
                ':source_id' => $source_id,
                ':social_network' => $social_network,
                ':country_code' => $country_code,
                ':country_name' => $country_name,
                ':author_name' => $author_name,
                ':content_image' => $content_image,
                ':content_text' => $content_text,
                ':url_destino' => $url_destino,
                ':input_names' => $input_names_str
            ]);
            $updatedCount++;
        } catch (PDOException $e) {
            logMessage("Error al actualizar registro {$external_id}: " . $e->getMessage(), 'ERROR');
            continue;
        }
    }
    logMessage("Meltwater actualizado: $updatedCount registros");
} catch (Exception $e) {
    logMessage("Error en proceso Meltwater: " . $e->getMessage(), 'ERROR');
}

// 2. PROCESO DE SCRAPING
logMessage("Iniciando proceso de scraping");
try {
    $client = new Client();
    $guzzle = new GuzzleClient([
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3'
        ],
        'timeout' => 30,
        'connect_timeout' => 10,
        'verify' => false,
        'http_errors' => false
    ]);

    // Procesar cada sitio configurado
    $total = 0;
    foreach ($config['sites'] as $country => $confs) {
        $total += count($confs);
    }
    logMessage("Total de sitios a scrapear: $total");

    $counter = 0;
    foreach ($config['sites'] as $country => $configs) {
        foreach ($configs as $conf) {
            $counter++;
            $pct = round($counter / $total * 100);
            logMessage("Procesando [$counter/$total: $pct%] {$conf['url']}");

            try {
                // Verificar que la URL es accesible
                if (!verifyHttpResponse($conf['url'])) {
                    logMessage("Saltando {$conf['url']} debido a respuesta HTTP inválida", 'WARNING');
                    continue;
                }

                logMessage("Iniciando scraping de: " . $conf['url']);
                $crawler = $client->request('GET', $conf['url']);
                $urlImg = '';
                $alt = 'Portada';
                $fullUri = $conf['url'];

                if (!empty($conf['custom_extractor'])) {
                    logMessage("Usando extractor personalizado: " . $conf['custom_extractor']);
                    switch ($conf['custom_extractor']) {
                        case 'extractWithXPath':
                            $xpath = isset($conf['xpath']) ? $conf['xpath'] : '//img';
                            $attr = isset($conf['attribute']) ? $conf['attribute'] : 'src';
                            $urlImg = extractWithXPath($conf['url'], $xpath, $attr);
                            if ($urlImg) $urlImg = makeAbsoluteUrl($conf['url'], $urlImg);
                            break;
                        case 'extractHiresFromFusionScript':
                            $hiresUrl = extractHiresFromFusionScript($crawler);
                            if ($hiresUrl) $urlImg = makeAbsoluteUrl($conf['url'], $hiresUrl);
                            break;
                    }
                    if ($urlImg) {
                        if (storeCover($country, $alt, $urlImg, $fullUri)) {
                            logMessage("Cover guardado exitosamente para {$conf['url']}");
                        } else {
                            logMessage("Error al guardar cover para {$conf['url']}", 'ERROR');
                        }
                    }
                    continue;
                }

                if (!empty($conf['attribute']) && !empty($conf['selector'])) {
                    logMessage("Intentando extraer imagen con selector: {$conf['selector']} y atributo: {$conf['attribute']}");
                    $node = $crawler->filter($conf['selector']);
                    if ($node->count()) {
                        $urlImg = makeAbsoluteUrl($conf['url'], $node->attr($conf['attribute']));
                        // Aplicar transformación de URL si está definida
                        if (isset($conf['transformImageUrl']) && is_callable($conf['transformImageUrl'])) {
                            $urlImg = $conf['transformImageUrl']($urlImg);
                            logMessage("URL transformada: " . $urlImg);
                        }
                        logMessage("URL de imagen encontrada: " . $urlImg);
                    } else {
                        logMessage("No se encontró nodo con el selector: {$conf['selector']}", 'WARNING');
                        continue;
                    }
                } elseif (!empty($conf['selector']) && empty($conf['multiple'])) {
                    logMessage("Intentando extraer imagen con selector simple: {$conf['selector']}");
                    $node = $crawler->filter($conf['selector']);
                    if ($node->count()) {
                        $img = $node->attr('src') ?: '';
                        $alt = $node->attr('alt') ?: $alt;
                        $urlImg = makeAbsoluteUrl($conf['url'], $img);
                        logMessage("URL de imagen encontrada: " . $urlImg . ", Alt: " . $alt);
                    } else {
                        logMessage("No se encontró nodo con el selector: {$conf['selector']}", 'WARNING');
                        continue;
                    }
                } elseif (!empty($conf['selector']) && !empty($conf['multiple'])) {
                    logMessage("Procesando múltiples imágenes con selector: {$conf['selector']}");
                    $crawler->filter($conf['selector'])->each(function ($node) use ($conf, $country, $client) {
                        $base = new Uri($conf['url']);
                        $urlImg = '';
                        $linkPage = $conf['url'];
                        $alt = 'Portada';

                        if (!empty($conf['followLinks'])) {
                            $linkNode = $node->filter($conf['followLinks']['linkSelector']);
                            if ($linkNode->count()) {
                                $link = $linkNode->attr('href') ?: '';
                                $full = Uri::resolve($base, new Uri($link));
                                $detail = $client->request('GET', (string)$full);
                                $imgNode = $detail->filter($conf['followLinks']['imageSelector']);
                                if ($imgNode->count()) {
                                    $img = $imgNode->attr('src') ?: '';
                                    $alt = $node->filter('img')->attr('alt') ?: 'Portada';
                                    $urlImg = makeAbsoluteUrl((string)$full, $img);
                                    $linkPage = $full;
                                }
                            }
                        } else {
                            $imgNode = $node->filter('img');
                            if ($imgNode->count()) {
                                $img = $imgNode->attr('src') ?: '';
                                $alt = $imgNode->attr('alt') ?: 'Portada';
                                $urlImg = makeAbsoluteUrl($conf['url'], $img);
                            }
                        }

                        if ($urlImg) {
                            if (storeCover($country, $alt, $urlImg, $linkPage)) {
                                logMessage("Cover guardado exitosamente para $linkPage");
                            } else {
                                logMessage("Error al guardar cover para $linkPage", 'ERROR');
                            }
                        }
                    });
                    continue;
                }

                if ($urlImg) {
                    if (storeCover($country, $alt, $urlImg, $fullUri)) {
                        logMessage("Cover guardado exitosamente para {$conf['url']}");
                    } else {
                        logMessage("Error al guardar cover para {$conf['url']}", 'ERROR');
                    }
                }
            } catch (Exception $e) {
                logMessage("Error en {$conf['url']}: " . $e->getMessage(), 'ERROR');
                continue;
            }
        }
    }
    logMessage("Scraping completado");
} catch (Exception $e) {
    logMessage("Error en proceso de scraping: " . $e->getMessage(), 'ERROR');
}

// 3. PROCESO DE PORTADAS
logMessage("Iniciando proceso de portadas");
try {
    // Verificar si existen las columnas de timestamp
    $stmt = $pdo->query("SHOW COLUMNS FROM portadas LIKE 'created_at'");
    $has_timestamps = $stmt->rowCount() > 0;

    // Crear tabla temporal
    $pdo->exec("CREATE TEMPORARY TABLE temp_portadas LIKE portadas");
    logMessage("Tabla temporal creada");

    $total_inserted = 0;
    $melwater_titles_dereach = [];
    $inserted_keys = [];

    // Procesar datos de Meltwater
    $sql = "
        SELECT 
            pk.id,
            pk.external_id,
            m.title AS medio_title,
            m.grupo,
            m.pais,
            m.dereach,
            m.visualizar,
            pk.published_date
        FROM pk_melwater pk
        INNER JOIN medios m ON m.twitter_id = pk.external_id
        WHERE m.visualizar = 1 AND m.grupo IS NOT NULL
        AND pk.published_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    $stmt = $pdo->query($sql);
    $melwater_rows = $stmt->fetchAll();
    
    // En la sección de procesamiento de portadas, después de obtener $melwater_rows
    foreach ($melwater_rows as $row) {
        if (empty($row['medio_title'])) continue;
        $key = mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach'] . '|meltwater';
        if (isset($inserted_keys[$key])) continue;
        
        // Corregir el formato de las URLs de las imágenes
        $original_url = 'images/melwater/' . $row['external_id'] . '_original.webp';
        $thumbnail_url = 'images/melwater/previews/' . $row['external_id'] . '_preview.webp';
        
        // Verificar que los archivos existan antes de insertar
        $original_path = __DIR__ . '/' . $original_url;
        $thumbnail_path = __DIR__ . '/' . $thumbnail_url;
        
        if (!file_exists($original_path) || !file_exists($thumbnail_path)) {
            logMessage("Error: Archivos de imagen no encontrados para {$row['external_id']}", 'ERROR');
            continue;
        }
        
        // Preparar la consulta SQL según si existen las columnas de timestamp
        if ($has_timestamps) {
            $insert = $pdo->prepare("INSERT INTO temp_portadas (
                title, grupo, pais, published_date, dereach, 
                source_type, external_id, visualizar, 
                original_url, thumbnail_url, 
                created_at, updated_at
            ) VALUES (
                :title, :grupo, :pais, :published_date, :dereach, 
                'meltwater', :external_id, :visualizar, 
                :original_url, :thumbnail_url,
                NOW(), NOW()
            )");
        } else {
            $insert = $pdo->prepare("INSERT INTO temp_portadas (
                title, grupo, pais, published_date, dereach, 
                source_type, external_id, visualizar, 
                original_url, thumbnail_url
            ) VALUES (
                :title, :grupo, :pais, :published_date, :dereach, 
                'meltwater', :external_id, :visualizar, 
                :original_url, :thumbnail_url
            )");
        }
        
        $insert->execute([
            'title' => mb_substr($row['medio_title'], 0, 255),
            'grupo' => $row['grupo'],
            'pais' => $row['pais'],
            'published_date' => $row['published_date'],
            'dereach' => $row['dereach'],
            'external_id' => $row['external_id'],
            'visualizar' => $row['visualizar'],
            'original_url' => $original_url,
            'thumbnail_url' => $thumbnail_url
        ]);
        
        $melwater_titles_dereach[mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach']] = true;
        $inserted_keys[$key] = true;
        $total_inserted++;
    }
    logMessage("Insertados desde Meltwater: " . count($melwater_rows));

    // Procesar datos de Covers
    $sql = "
        SELECT 
            c.id,
            c.source AS external_id,
            m.title AS medio_title,
            m.grupo,
            m.pais,
            m.dereach,
            m.visualizar,
            c.scraped_at AS published_date,
            c.original_url AS original_url,
            c.thumbnail_url AS thumbnail_url
        FROM covers c
        INNER JOIN medios m ON m.source = c.source
        WHERE m.visualizar = 1 AND m.grupo IS NOT NULL
        AND c.scraped_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    $stmt = $pdo->query($sql);
    $cover_rows = $stmt->fetchAll();
    foreach ($cover_rows as $row) {
        if (empty($row['medio_title'])) continue;
        $key = mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach'] . '|cover';
        if (isset($melwater_titles_dereach[mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach']])) continue;
        if (isset($inserted_keys[$key])) continue;
        
        $insert = $pdo->prepare("INSERT INTO temp_portadas (
            title, grupo, pais, published_date, dereach, 
            source_type, external_id, visualizar, 
            original_url, thumbnail_url,
            created_at, updated_at
        ) VALUES (
            :title, :grupo, :pais, :published_date, :dereach, 
            'cover', :external_id, :visualizar, 
            :original_url, :thumbnail_url,
            NOW(), NOW()
        )");
        
        $insert->execute([
            'title' => mb_substr($row['medio_title'], 0, 255),
            'grupo' => $row['grupo'],
            'pais' => $row['pais'],
            'published_date' => $row['published_date'],
            'dereach' => $row['dereach'],
            'external_id' => $row['external_id'],
            'visualizar' => $row['visualizar'],
            'original_url' => $row['original_url'],
            'thumbnail_url' => $row['thumbnail_url']
        ]);
        
        $inserted_keys[$key] = true;
        $total_inserted++;
    }
    logMessage("Insertados desde Covers: " . count($cover_rows));

    // Procesar datos de Resumen
    $sql = "
        SELECT 
            r.id,
            r.twitter_id AS external_id,
            m.title AS medio_title,
            m.grupo,
            m.pais,
            m.dereach,
            m.visualizar,
            r.published_date,
            r.source AS original_url
        FROM pk_meltwater_resumen r
        INNER JOIN medios m ON m.twitter_id = r.twitter_id
        WHERE m.visualizar = 1 AND m.grupo IS NOT NULL
        AND r.published_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    $stmt = $pdo->query($sql);
    $resumen_rows = $stmt->fetchAll();
    foreach ($resumen_rows as $row) {
        if (empty($row['medio_title'])) continue;
        $key = mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach'] . '|resumen';
        if (isset($melwater_titles_dereach[mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach']])) continue;
        if (isset($inserted_keys[$key])) continue;
        
        $insert = $pdo->prepare("INSERT INTO temp_portadas (
            title, grupo, pais, published_date, dereach, 
            source_type, external_id, visualizar, 
            original_url, thumbnail_url,
            created_at, updated_at
        ) VALUES (
            :title, :grupo, :pais, :published_date, :dereach, 
            'resumen', :external_id, :visualizar, 
            :original_url, NULL,
            NOW(), NOW()
        )");
        
        $insert->execute([
            'title' => mb_substr($row['medio_title'], 0, 255),
            'grupo' => $row['grupo'],
            'pais' => $row['pais'],
            'published_date' => $row['published_date'],
            'dereach' => $row['dereach'],
            'external_id' => $row['external_id'],
            'visualizar' => $row['visualizar'],
            'original_url' => $row['original_url']
        ]);
        
        $inserted_keys[$key] = true;
        $total_inserted++;
    }
    logMessage("Insertados desde Resumen: " . count($resumen_rows));

    // Actualizar tabla portadas
    if ($has_timestamps) {
        $pdo->exec("
            INSERT INTO portadas 
            SELECT * FROM temp_portadas 
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                grupo = VALUES(grupo),
                pais = VALUES(pais),
                published_date = VALUES(published_date),
                dereach = VALUES(dereach),
                visualizar = VALUES(visualizar),
                original_url = VALUES(original_url),
                thumbnail_url = VALUES(thumbnail_url),
                updated_at = NOW()
        ");
    } else {
        $pdo->exec("
            INSERT INTO portadas 
            SELECT * FROM temp_portadas 
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                grupo = VALUES(grupo),
                pais = VALUES(pais),
                published_date = VALUES(published_date),
                dereach = VALUES(dereach),
                visualizar = VALUES(visualizar),
                original_url = VALUES(original_url),
                thumbnail_url = VALUES(thumbnail_url)
        ");
    }

    // Limpiar datos antiguos
    $pdo->exec("
        DELETE FROM portadas 
        WHERE published_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");

    logMessage("Proceso de portadas completado. Total insertados: $total_inserted");
} catch (Exception $e) {
    logMessage("Error en proceso de portadas: " . $e->getMessage(), 'ERROR');
}

// Respuesta final
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Proceso unificado completado"
]);

// Funciones auxiliares para el proceso de scraping
function verifyHttpResponse($url) {
    global $guzzle;
    logMessage("Verificando URL: " . $url);
    
    try {
        $response = $guzzle->head($url, [
            'allow_redirects' => true,
            'timeout' => 10
        ]);
        
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaderLine('content-type');
        
        logMessage("Respuesta de $url - Status: $statusCode, Content-Type: $contentType");
        
        if ($statusCode !== 200) {
            logMessage("Error: Status code $statusCode para $url", 'WARNING');
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        logMessage("Error verificando respuesta HTTP para $url: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

function extractWithXPath($url, $xpath, $attribute = 'href', $timeout = 5) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout + 2,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; scraper-bot)',
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_RANGE => '0-65535',
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html || strlen($html) < 100) return null;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!$dom->loadHTML($html)) return null;

    $xpathObj = new DOMXPath($dom);
    $nodes = $xpathObj->query($xpath);
    if ($nodes && $nodes->length > 0) {
        return $nodes->item(0)->getAttribute($attribute);
    }

    return null;
}

function extractHiresFromFusionScript($crawler) {
    $script = $crawler->filter('script')->reduce(function ($node) {
        return strpos($node->text(), 'Fusion.globalContent') !== false;
    });

    if ($script->count() === 0) return null;

    $content = $script->text();
    if (preg_match('/Fusion\\.globalContent\\s*=\\s*(\{.*?\});/s', $content, $m)) {
        $json = json_decode($m[1], true);
        return isset($json['data']['hires']) ? $json['data']['hires'] : null;
    }

    return null;
}

function makeAbsoluteUrl($baseUrl, $relativeUrl) {
    if (strpos($relativeUrl, '//') === 0) return 'https:' . $relativeUrl;
    if (strpos($relativeUrl, 'http') === 0) return $relativeUrl;
    return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
}

function cleanImageUrl($url) {
    // Eliminar sufijos de dimensiones como -754x1024
    return preg_replace('/-\d+x\d+(\.[^.]+)$/', '$1', $url);
}

function saveImageLocally($imageUrl, $country, $alt) {
    $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $alt) . '_' . uniqid();
    logMessage("Iniciando guardado de imagen - URL: $imageUrl, País: $country, Alt: $alt");
    
    // Crear estructura de directorios como Meltwater
    $upload_dir = __DIR__ . '/images/covers';
    $thumb_dir = $upload_dir . '/thumbnails';
    $preview_dir = $upload_dir . '/previews';
    foreach ([$upload_dir, $thumb_dir, $preview_dir] as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                logMessage("Error al crear directorio: $dir", 'ERROR');
                return false;
            }
        }
    }

    // Usar Guzzle para la descarga con timeout y manejo de errores
    global $guzzle;
    try {
        logMessage("Descargando imagen de: $imageUrl");
        $response = $guzzle->get($imageUrl);
        $imageData = $response->getBody()->getContents();
        logMessage("Imagen descargada exitosamente - Tamaño: " . strlen($imageData) . " bytes");
    } catch (Exception $e) {
        logMessage("Error al descargar la imagen: $imageUrl - " . $e->getMessage(), 'ERROR');
        return false;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'img_');
    if (!file_put_contents($tempFile, $imageData)) {
        logMessage("Error al guardar archivo temporal: $tempFile", 'ERROR');
        return false;
    }

    try {
        // Definir rutas de archivos
        $original_filename = $filename . '_original.webp';
        $thumb_filename = $filename . '_thumb.webp';
        $preview_filename = $filename . '_preview.webp';
        $original_filepath = $upload_dir . '/' . $original_filename;
        $thumb_filepath = $thumb_dir . '/' . $thumb_filename;
        $preview_filepath = $preview_dir . '/' . $preview_filename;

        logMessage("Rutas de archivos definidas:");
        logMessage("- Original: $original_filepath");
        logMessage("- Thumbnail: $thumb_filepath");
        logMessage("- Preview: $preview_filepath");

        // Si ya existen todos los archivos, devolverlos
        if (file_exists($original_filepath) && file_exists($thumb_filepath) && file_exists($preview_filepath)) {
            logMessage("Archivos ya existen, retornando rutas existentes");
            unlink($tempFile);
            return [
                'preview' => 'images/covers/previews/' . $preview_filename,
                'thumbnail' => 'images/covers/thumbnails/' . $thumb_filename,
                'original' => 'images/covers/' . $original_filename
            ];
        }

        // Convertir a WebP y crear versión original
        logMessage("Convirtiendo a WebP - Original");
        if (!convertToWebP($tempFile, $original_filepath, 90)) {
            logMessage("Error al convertir imagen original a WebP", 'ERROR');
            unlink($tempFile);
            return false;
        }

        // Crear miniatura
        logMessage("Convirtiendo a WebP - Thumbnail");
        if (!convertToWebP($tempFile, $thumb_filepath, 80, 400, 600)) {
            logMessage("Error al convertir thumbnail a WebP", 'ERROR');
            unlink($tempFile);
            return false;
        }

        // Crear preview
        logMessage("Convirtiendo a WebP - Preview");
        if (!convertToWebP($tempFile, $preview_filepath, 40, 320, 480)) {
            logMessage("Error al convertir preview a WebP", 'ERROR');
            unlink($tempFile);
            return false;
        }

        unlink($tempFile);
        logMessage("Imagen procesada exitosamente");
        
        return [
            'preview' => 'images/covers/previews/' . $preview_filename,
            'thumbnail' => 'images/covers/thumbnails/' . $thumb_filename,
            'original' => 'images/covers/' . $original_filename
        ];
    } catch (Exception $e) {
        logMessage("Error procesando la imagen: " . $e->getMessage(), 'ERROR');
        if (file_exists($tempFile)) unlink($tempFile);
        return false;
    }
}

function storeCover($country, $alt, $urlImg, $sourceLink) {
    try {
        $pdo = getPDO();
        if (!$pdo instanceof PDO) {
            logMessage("Error: Failed to get valid PDO instance in storeCover function", 'ERROR');
            return false;
        }
        
        // Limpiar la URL de la imagen antes de procesarla
        $urlImg = cleanImageUrl($urlImg);
        logMessage("Procesando cover - País: $country, URL: $urlImg, Fuente: $sourceLink");
        
        // Verificar si ya existe la imagen
        $stmt = $pdo->prepare('SELECT 1 FROM covers WHERE country=:c AND original_link=:u');
        $stmt->execute([':c' => $country, ':u' => $urlImg]);
        if ($stmt->fetchColumn()) {
            logMessage("Cover ya existe para $country y $urlImg");
            return true;
        }

        $imageResult = saveImageLocally($urlImg, $country, $alt);
        if (!$imageResult) {
            logMessage("Error al guardar imagen localmente para $urlImg", 'ERROR');
            return false;
        }

        logMessage("Imagen guardada exitosamente: " . print_r($imageResult, true));

        try {
            $ins = $pdo->prepare("INSERT INTO covers(
                country, title, image_url, source, original_link, 
                preview_url, thumbnail_url, original_url, 
                created_at, updated_at, scraped_at
            ) VALUES(
                :c, :t, :i, :s, :l, 
                :pr, :th, :or,
                NOW(), NOW(), NOW()
            )");
            
            $params = [
                ':c' => $country, 
                ':t' => $alt, 
                ':i' => $imageResult['thumbnail'],
                ':s' => $sourceLink, 
                ':l' => $urlImg,
                ':pr' => $imageResult['preview'],
                ':th' => $imageResult['thumbnail'],
                ':or' => $imageResult['original']
            ];
            
            logMessage("Intentando insertar cover con parámetros: " . print_r($params, true));
            
            $ins->execute($params);
            logMessage("Cover insertado exitosamente");
            return true;
            
        } catch (PDOException $e) {
            // Si la tabla no tiene las columnas de timestamp, usar estructura antigua
            if (strpos($e->getMessage(), 'created_at') !== false) {
                logMessage("Fallback: usando estructura antigua sin timestamps para $sourceLink");
                $ins_fallback = $pdo->prepare("INSERT INTO covers(
                    country, title, image_url, source, original_link, 
                    thumbnail_url, original_url, scraped_at
                ) VALUES(
                    :c, :t, :i, :s, :l, 
                    :th, :or, NOW()
                )");
                
                $ins_fallback->execute([
                    ':c' => $country, 
                    ':t' => $alt, 
                    ':i' => $imageResult['thumbnail'],
                    ':s' => $sourceLink, 
                    ':l' => $urlImg,
                    ':th' => $imageResult['thumbnail'],
                    ':or' => $imageResult['original']
                ]);
                logMessage("Cover insertado exitosamente con estructura antigua");
                return true;
            } else {
                logMessage("Error al insertar cover: " . $e->getMessage(), 'ERROR');
                throw $e;
            }
        }
    } catch (Exception $e) {
        logMessage("Error in storeCover: " . $e->getMessage(), 'ERROR');
        return false;
    }
    return true;
} 