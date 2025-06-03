<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Configurar archivo de log específico
ini_set('error_log', __DIR__ . '/process_portadas_error.log');

// Función para logging
function logMessage($message, $type = 'INFO') {
    $date = date('Y-m-d H:i:s');
    error_log("[$date][$type] $message");
}

// Función para procesar cada documento
function processDocument($doc, $source_type, $medio_info = null) {
    $processed = [
        'source_type' => $source_type,
        'original_id' => null,
        'title' => null,
        'grupo' => null,
        'pais' => null,
        'url_destino' => null,
        'content_image' => null,
        'preview_url' => null,
        'thumbnail_url' => null,
        'original_url' => null,
        'published_date' => null,
        'dereach' => null
    ];

    // Usar información del medio si está disponible
    if ($medio_info) {
        $processed['grupo'] = $medio_info['grupo'];
        $processed['pais'] = $medio_info['pais'];
        $processed['dereach'] = $medio_info['dereach'];
    }

    switch ($source_type) {
        case 'meltwater':
            $processed['original_id'] = isset($doc['id']) ? $doc['id'] : null;
            $processed['title'] = isset($doc['content_text']) ? substr($doc['content_text'], 0, 255) : null;
            if (!$medio_info) {
                $processed['pais'] = isset($doc['country_name']) ? $doc['country_name'] : null;
            }
            $processed['url_destino'] = isset($doc['url_destino']) ? $doc['url_destino'] : null;
            $processed['content_image'] = isset($doc['content_image']) ? $doc['content_image'] : null;
            $processed['published_date'] = isset($doc['published_date']) ? $doc['published_date'] : null;
            break;

        case 'cover':
            $processed['original_id'] = isset($doc['id']) ? $doc['id'] : null;
            $processed['title'] = isset($doc['title']) ? $doc['title'] : null;
            if (!$medio_info) {
                $processed['pais'] = isset($doc['country']) ? $doc['country'] : null;
            }
            $processed['url_destino'] = isset($doc['original_link']) ? $doc['original_link'] : null;
            $processed['preview_url'] = isset($doc['preview_url']) ? $doc['preview_url'] : null;
            $processed['thumbnail_url'] = isset($doc['thumbnail_url']) ? $doc['thumbnail_url'] : null;
            $processed['original_url'] = isset($doc['original_url']) ? $doc['original_url'] : null;
            $processed['content_image'] = isset($doc['image_url']) ? $doc['image_url'] : null;
            $processed['published_date'] = isset($doc['scraped_at']) ? $doc['scraped_at'] : null;
            break;

        case 'resumen':
            $processed['original_id'] = isset($doc['id']) ? $doc['id'] : null;
            $processed['title'] = isset($doc['titulo']) ? $doc['titulo'] : null;
            if (!$medio_info) {
                $processed['grupo'] = isset($doc['grupo']) ? $doc['grupo'] : null;
                $processed['pais'] = isset($doc['pais']) ? $doc['pais'] : null;
            }
            $processed['url_destino'] = isset($doc['twitter_id']) ? 'https://twitter.com/i/status/' . $doc['twitter_id'] : null;
            $processed['content_image'] = isset($doc['source']) ? $doc['source'] : null;
            $processed['published_date'] = isset($doc['published_date']) ? $doc['published_date'] : null;
            break;
    }

    return $processed;
}

try {
    // Verificar la ruta del archivo de configuración
    $config_file = __DIR__ . '/config.php';
    logMessage("Intentando cargar configuración desde: $config_file");
    
    if (!file_exists($config_file)) {
        throw new Exception("No se encuentra el archivo de configuración en: " . $config_file);
    }

    // Cargar la configuración
    $cfg = require $config_file;
    logMessage("Configuración cargada");

    // Verificar la configuración
    if (!isset($cfg['db']) || !isset($cfg['db']['host']) || !isset($cfg['db']['name']) || 
        !isset($cfg['db']['user']) || !isset($cfg['db']['pass'])) {
        throw new Exception("Configuración de base de datos incompleta");
    }

    logMessage("Intentando conexión a la base de datos");
    // Primero intentar conectar sin charset
    try {
        $dsn = "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset=utf8mb4";
        logMessage("DSN de conexión: $dsn");
        
        $pdo = new PDO(
            $dsn,
            $cfg['db']['user'],
            $cfg['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        logMessage("Conexión inicial exitosa");
    } catch (PDOException $e) {
        logMessage("Error en primer intento de conexión: " . $e->getMessage(), 'ERROR');
        if (strpos($e->getMessage(), 'Unknown character set') !== false) {
            logMessage("Intentando conexión con charset utf8mb4");
            $pdo = new PDO(
                "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset=utf8mb4",
                $cfg['db']['user'],
                $cfg['db']['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            logMessage("Conexión con charset utf8mb4 exitosa");
        } else {
            throw $e;
        }
    }

    // Establecer charset después de la conexión
    $pdo->exec("SET NAMES utf8mb4");
    logMessage("Charset establecido a utf8mb4");

    // 1. Borrar todos los registros de portadas
    $pdo->exec("TRUNCATE portadas");
    logMessage("Tabla portadas vaciada");

    $total_inserted = 0;
    $melwater_titles_dereach = [];
    $inserted_keys = [];

    // 2. Insertar desde pk_melwater (prioridad más alta)
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
    foreach ($melwater_rows as $row) {
        if (empty($row['medio_title'])) continue;
        $key = mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach'] . '|meltwater';
        if (isset($inserted_keys[$key])) continue;
        $original_url = '/images/melwater/' . $row['external_id'] . '_original.webp';
        $thumbnail_url = '/images/melwater/previews/' . $row['external_id'] . 'previews.webp';
        $insert = $pdo->prepare("INSERT INTO portadas (title, grupo, pais, published_date, dereach, source_type, external_id, visualizar, original_url, thumbnail_url, indexed_date) VALUES (:title, :grupo, :pais, :published_date, :dereach, 'meltwater', :external_id, :visualizar, :original_url, :thumbnail_url, NOW())");
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

    // 3. Insertar desde covers (solo si no existe ya title+dereach en portadas de Meltwater y tipo)
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
        $insert = $pdo->prepare("INSERT INTO portadas (title, grupo, pais, published_date, dereach, source_type, external_id, visualizar, original_url, thumbnail_url, indexed_date) VALUES (:title, :grupo, :pais, :published_date, :dereach, 'cover', :external_id, :visualizar, :original_url, :thumbnail_url, NOW())");
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

    // 4. Insertar desde resumen (solo si no existe ya title+dereach en portadas de Meltwater y tipo)
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
        $insert = $pdo->prepare("INSERT INTO portadas (title, grupo, pais, published_date, dereach, source_type, external_id, visualizar, original_url, thumbnail_url, indexed_date) VALUES (:title, :grupo, :pais, :published_date, :dereach, 'resumen', :external_id, :visualizar, :original_url, NULL, NOW())");
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

    logMessage("Total insertados en portadas: $total_inserted");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "Procesamiento completado. Total insertados: $total_inserted"
    ]);
    exit;

} catch (PDOException $e) {
    logMessage("Error de base de datos: " . $e->getMessage(), 'ERROR');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    logMessage("Error general: " . $e->getMessage(), 'ERROR');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
} 