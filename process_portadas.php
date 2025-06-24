<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Configurar archivo de log específico
ini_set('error_log', __DIR__ . '/process_portadas_error.log');

// Incluir procesador de imágenes
require_once 'optimized-image-processor.php';
require_once 'download_image.php';

// Función para logging
function logMessage($message, $type = 'INFO') {
    $date = date('Y-m-d H:i:s');
    error_log("[$date][$type] $message");
}

// Función para verificar y regenerar imágenes faltantes
function verifyAndRegenerateImage($external_id, $source_type, $original_url = null, $thumbnail_url = null) {
    global $pdo;
    
    logMessage("Verificando imágenes para external_id: {$external_id}, source_type: {$source_type}");
    
    // Verificar si los archivos existen
    $original_exists = $original_url && file_exists(__DIR__ . '/' . $original_url);
    $thumbnail_exists = $thumbnail_url && file_exists(__DIR__ . '/' . $thumbnail_url);
    
    if ($original_exists && $thumbnail_exists) {
        logMessage("Imágenes existentes para {$external_id}");
        return ['original_url' => $original_url, 'thumbnail_url' => $thumbnail_url];
    }
    
    logMessage("Imágenes faltantes para {$external_id}, intentando regenerar...", 'WARNING');
    
    // Intentar regenerar según el tipo de fuente
    switch ($source_type) {
        case 'meltwater':
            return regenerateMeltwaterImage($external_id);
        case 'cover':
            return regenerateCoverImage($external_id);
        case 'resumen':
            return regenerateResumenImage($external_id);
        default:
            logMessage("Tipo de fuente no soportado para regeneración: {$source_type}", 'ERROR');
            return false;
    }
}

// Función para regenerar imagen de Meltwater
function regenerateMeltwaterImage($external_id) {
    global $pdo;
    
    logMessage("Regenerando imagen Meltwater para {$external_id}");
    
    // Obtener la URL original de la imagen desde pk_melwater
    $stmt = $pdo->prepare("SELECT content_image FROM pk_melwater WHERE external_id = :external_id");
    $stmt->execute([':external_id' => $external_id]);
    $row = $stmt->fetch();
    
    if (!$row || !$row['content_image']) {
        logMessage("No se encontró URL de imagen para Meltwater {$external_id}", 'ERROR');
        return false;
    }
    
    // Si content_image es una URL externa, descargarla
    if (filter_var($row['content_image'], FILTER_VALIDATE_URL)) {
        $imageUrl = $row['content_image'];
        
        // Crear directorios si no existen
        $melwater_dir = __DIR__ . '/images/melwater';
        $previews_dir = $melwater_dir . '/previews';
        
        foreach ([$melwater_dir, $previews_dir] as $dir) {
            if (!file_exists($dir)) {
                if (!@mkdir($dir, 0755, true)) {
                    logMessage("Error al crear directorio: {$dir}", 'ERROR');
                    return false;
                }
            }
        }
        
        // Generar número aleatorio para el nombre del archivo
        $random_number = mt_rand(1000, 9999);
        $original_filename = $external_id . '_' . $random_number . '_original.webp';
        $preview_filename = $external_id . '_' . $random_number . '_preview.webp';
        $original_path = $melwater_dir . '/' . $original_filename;
        $preview_path = $previews_dir . '/' . $preview_filename;
        
        try {
            // Descargar imagen
            $image_data = @file_get_contents($imageUrl);
            if ($image_data === false) {
                logMessage("Error al descargar imagen desde: {$imageUrl}", 'ERROR');
                return false;
            }
            
            // Crear archivo temporal
            $temp_file = tempnam(sys_get_temp_dir(), 'melwater_');
            if ($temp_file === false) {
                logMessage("No se pudo crear archivo temporal", 'ERROR');
                return false;
            }
            
            // Guardar imagen en archivo temporal
            if (@file_put_contents($temp_file, $image_data) === false) {
                logMessage("Error al guardar archivo temporal", 'ERROR');
                return false;
            }
            
            // Convertir a WebP
            if (!@convertToWebP($temp_file, $original_path, 90)) {
                logMessage("Error al convertir imagen original", 'ERROR');
                return false;
            }
            
            if (!@convertToWebP($temp_file, $preview_path, 25, 320, 480)) {
                logMessage("Error al convertir preview", 'ERROR');
                return false;
            }
            
            // Limpiar archivo temporal
            @unlink($temp_file);
            
            // Actualizar la base de datos con las nuevas rutas
            $new_original_url = 'images/melwater/' . $original_filename;
            $new_preview_url = 'images/melwater/previews/' . $preview_filename;
            
            $update_stmt = $pdo->prepare("UPDATE pk_melwater SET content_image = :content_image, preview_image = :preview_image WHERE external_id = :external_id");
            $update_stmt->execute([
                ':content_image' => $new_original_url,
                ':preview_image' => $new_preview_url,
                ':external_id' => $external_id
            ]);
            
            logMessage("Imagen Meltwater regenerada exitosamente para {$external_id}");
            return ['original_url' => $new_original_url, 'thumbnail_url' => $new_preview_url];
            
        } catch (Exception $e) {
            logMessage("Error regenerando imagen Meltwater: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    return false;
}

// Función para regenerar imagen de Cover
function regenerateCoverImage($external_id) {
    global $pdo;
    
    logMessage("Regenerando imagen Cover para {$external_id}");
    
    // Obtener la URL original de la imagen desde covers
    $stmt = $pdo->prepare("SELECT original_link FROM covers WHERE source = :source");
    $stmt->execute([':source' => $external_id]);
    $row = $stmt->fetch();
    
    if (!$row || !$row['original_link']) {
        logMessage("No se encontró URL de imagen para Cover {$external_id}", 'ERROR');
        return false;
    }
    
    // Aquí podrías implementar la lógica para regenerar la imagen del cover
    // Por ahora, retornamos false ya que requiere el proceso de scraping completo
    logMessage("Regeneración de Cover requiere proceso de scraping completo", 'WARNING');
    return false;
}

// Función para regenerar imagen de Resumen
function regenerateResumenImage($external_id) {
    global $pdo;
    
    logMessage("Regenerando imagen Resumen para {$external_id}");
    
    // Obtener la URL original de la imagen desde pk_meltwater_resumen
    $stmt = $pdo->prepare("SELECT source FROM pk_meltwater_resumen WHERE twitter_id = :twitter_id");
    $stmt->execute([':twitter_id' => $external_id]);
    $row = $stmt->fetch();
    
    if (!$row || !$row['source']) {
        logMessage("No se encontró URL de imagen para Resumen {$external_id}", 'ERROR');
        return false;
    }
    
    // Para resumen, la imagen suele ser una URL externa directa
    // Podríamos implementar descarga directa aquí si es necesario
    logMessage("Regeneración de Resumen requiere proceso específico", 'WARNING');
    return false;
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

    // Crear tabla temporal para nuevos datos
    $pdo->exec("CREATE TEMPORARY TABLE temp_portadas LIKE portadas");
    logMessage("Tabla temporal creada");

    // Verificar si existen las columnas de timestamp
    $stmt = $pdo->query("SHOW COLUMNS FROM portadas LIKE 'created_at'");
    $has_timestamps = $stmt->rowCount() > 0;

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
            pk.published_date,
            pk.indexed_date,
            pk.content_image,
            pk.preview_image
        FROM pk_melwater pk
        INNER JOIN medios m ON m.twitter_id = pk.external_id
        WHERE m.visualizar = 1 AND m.grupo IS NOT NULL
        AND pk.indexed_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND NOT EXISTS (
            SELECT 1 FROM temp_portadas p 
            WHERE p.external_id = pk.external_id 
            AND p.indexed_date = pk.indexed_date
            AND p.source_type = 'meltwater'
        )
    ";
    $stmt = $pdo->query($sql);
    $melwater_rows = $stmt->fetchAll();
    foreach ($melwater_rows as $row) {
        if (empty($row['medio_title'])) continue;
        $key = mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach'] . '|meltwater';
        if (isset($inserted_keys[$key])) continue;
        
        // Usar las URLs de imágenes de la base de datos
        $original_url = $row['content_image'];
        $thumbnail_url = $row['preview_image'];
        
        // Verificar y regenerar imágenes si es necesario
        $image_result = verifyAndRegenerateImage($row['external_id'], 'meltwater', $original_url, $thumbnail_url);
        if ($image_result) {
            $original_url = $image_result['original_url'];
            $thumbnail_url = $image_result['thumbnail_url'];
        }
        
        // Verificar que los archivos existan antes de insertar
        $original_path = __DIR__ . '/' . $original_url;
        $thumbnail_path = __DIR__ . '/' . $thumbnail_url;
        
        if (!file_exists($original_path) || !file_exists($thumbnail_path)) {
            logMessage("Error: Archivos de imagen no encontrados para {$row['external_id']}", 'ERROR');
            logMessage("Original path: {$original_path}", 'ERROR');
            logMessage("Thumbnail path: {$thumbnail_path}", 'ERROR');
            continue;
        }
        
        // Preparar la consulta SQL según si existen las columnas de timestamp
        if ($has_timestamps) {
            $insert = $pdo->prepare("INSERT INTO temp_portadas (
                title, grupo, pais, published_date, indexed_date, dereach, 
                source_type, external_id, visualizar, 
                original_url, thumbnail_url, 
                created_at, updated_at
            ) VALUES (
                :title, :grupo, :pais, :published_date, :indexed_date, :dereach, 
                'meltwater', :external_id, :visualizar, 
                :original_url, :thumbnail_url,
                NOW(), NOW()
            )");
        } else {
            $insert = $pdo->prepare("INSERT INTO temp_portadas (
                title, grupo, pais, published_date, indexed_date, dereach, 
                source_type, external_id, visualizar, 
                original_url, thumbnail_url
            ) VALUES (
                :title, :grupo, :pais, :published_date, :indexed_date, :dereach, 
                'meltwater', :external_id, :visualizar, 
                :original_url, :thumbnail_url
            )");
        }
        
        $insert->execute([
            'title' => mb_substr($row['medio_title'], 0, 255),
            'grupo' => $row['grupo'],
            'pais' => $row['pais'],
            'published_date' => $row['published_date'],
            'indexed_date' => $row['indexed_date'],
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

    // 3. Insertar desde covers
    $sql = "
        SELECT 
            c.id,
            c.source AS external_id,
            m.title AS medio_title,
            m.grupo,
            m.pais,
            m.dereach,
            m.visualizar,
            c.original_url AS original_url,
            c.thumbnail_url AS thumbnail_url
        FROM covers c
        INNER JOIN medios m ON m.source = c.source
        WHERE m.visualizar = 1 AND m.grupo IS NOT NULL
    ";
    $stmt = $pdo->query($sql);
    $cover_rows = $stmt->fetchAll();
    foreach ($cover_rows as $row) {
        if (empty($row['medio_title'])) continue;
        $key = mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach'] . '|cover';
        if (isset($melwater_titles_dereach[mb_substr($row['medio_title'], 0, 255) . '|' . $row['dereach']])) continue;
        if (isset($inserted_keys[$key])) continue;
        
        // Verificar y regenerar imágenes si es necesario
        $image_result = verifyAndRegenerateImage($row['external_id'], 'cover', $row['original_url'], $row['thumbnail_url']);
        if ($image_result) {
            $original_url = $image_result['original_url'];
            $thumbnail_url = $image_result['thumbnail_url'];
        } else {
            $original_url = $row['original_url'];
            $thumbnail_url = $row['thumbnail_url'];
        }
        
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
            'published_date' => date('Y-m-d H:i:s'),
            'dereach' => $row['dereach'],
            'external_id' => $row['external_id'],
            'visualizar' => $row['visualizar'],
            'original_url' => $original_url,
            'thumbnail_url' => $thumbnail_url
        ]);
        
        $inserted_keys[$key] = true;
        $total_inserted++;
    }
    logMessage("Insertados desde Covers: " . count($cover_rows));

    // 4. Insertar desde resumen
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
        
        // Verificar y regenerar imágenes si es necesario
        $image_result = verifyAndRegenerateImage($row['external_id'], 'resumen', $row['original_url'], null);
        if ($image_result) {
            $original_url = $image_result['original_url'];
            $thumbnail_url = $image_result['thumbnail_url'];
        } else {
            $original_url = $row['original_url'];
            $thumbnail_url = null;
        }
        
        $insert = $pdo->prepare("INSERT INTO temp_portadas (
            title, grupo, pais, published_date, dereach, 
            source_type, external_id, visualizar, 
            original_url, thumbnail_url,
            created_at, updated_at
        ) VALUES (
            :title, :grupo, :pais, :published_date, :dereach, 
            'resumen', :external_id, :visualizar, 
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
            'original_url' => $original_url,
            'thumbnail_url' => $thumbnail_url
        ]);
        
        $inserted_keys[$key] = true;
        $total_inserted++;
    }
    logMessage("Insertados desde Resumen: " . count($resumen_rows));

    // Actualizar la tabla portadas con los nuevos datos
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

    // Limpiar datos antiguos (opcional)
    $pdo->exec("
        DELETE FROM portadas 
        WHERE published_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");

    logMessage("Total insertados en portadas: $total_inserted");

    // Limpiar solo archivos temporales en el directorio de Meltwater
    $melwater_dir = __DIR__ . '/images/melwater';
    if (is_dir($melwater_dir)) {
        // Obtener lista de archivos que están en uso en portadas
        $stmt = $pdo->query("SELECT external_id FROM portadas WHERE source_type = 'meltwater'");
        $active_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener lista de archivos que están en uso en pk_melwater
        $stmt = $pdo->query("SELECT external_id FROM pk_melwater");
        $pk_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Combinar ambas listas
        $active_files = array_unique(array_merge($active_files, $pk_files));
        
        // Crear un array de nombres de archivos que deben mantenerse
        $keep_files = [];
        foreach ($active_files as $external_id) {
            // Mantener archivos con el formato correcto
            $keep_files[] = $external_id . '_original.webp';
            $keep_files[] = $external_id . '_preview.webp';
        }
        
        // Obtener todos los archivos en el directorio
        $files = glob($melwater_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                
                // Verificar si el archivo tiene el formato correcto
                if (preg_match('/^(\d+)_\d+_(original|preview)\.webp$/', $filename, $matches)) {
                    $external_id = $matches[1];
                    $type = $matches[2];
                    
                    // Solo eliminar si:
                    // 1. No está en la lista de archivos a mantener
                    // 2. No está en uso en ninguna tabla
                    // 3. Tiene más de 24 horas de antigüedad
                    if (!in_array($filename, $keep_files)) {
                        $file_age = time() - filemtime($file);
                        if ($file_age > 86400) { // 24 horas en segundos
                            // Verificar una última vez que no está en uso
                            $stmt = $pdo->prepare("
                                SELECT 1 FROM portadas 
                                WHERE source_type = 'meltwater' 
                                AND external_id = :external_id
                                UNION
                                SELECT 1 FROM pk_melwater 
                                WHERE external_id = :external_id
                            ");
                            $stmt->execute([':external_id' => $external_id]);
                            if ($stmt->rowCount() == 0) {
                                unlink($file);
                                logMessage("Archivo temporal eliminado (más de 24 horas y no en uso): $filename");
                            } else {
                                logMessage("Archivo encontrado en uso (no eliminado): $filename");
                            }
                        } else {
                            logMessage("Archivo temporal encontrado (menos de 24 horas): $filename");
                        }
                    } else {
                        logMessage("Archivo activo encontrado (no eliminado): $filename");
                    }
                } else {
                    logMessage("Archivo con formato incorrecto encontrado (no eliminado): $filename");
                }
            }
        }
        logMessage("Limpieza de archivos temporales completada");
    }

    // Limpiar archivos no usados en images/covers y subcarpetas
    $covers_dirs = [
        __DIR__ . '/images/covers',
        __DIR__ . '/images/covers/thumbnails',
        __DIR__ . '/images/covers/previews'
    ];
    
    // Obtener todos los nombres de archivos en uso
    $used_files = [];
    
    // Archivos en uso en la tabla covers
    $stmt = $pdo->query("SELECT original_url, thumbnail_url FROM covers");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach (['original_url', 'thumbnail_url'] as $col) {
            if (!empty($row[$col])) {
                $used_files[] = basename($row[$col]);
            }
        }
    }
    
    // Archivos en uso en la tabla portadas
    $stmt = $pdo->query("SELECT original_url, thumbnail_url FROM portadas WHERE source_type = 'cover'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach (['original_url', 'thumbnail_url'] as $col) {
            if (!empty($row[$col])) {
                $used_files[] = basename($row[$col]);
            }
        }
    }
    
    $used_files = array_unique($used_files);
    
    foreach ($covers_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    $file_age = time() - filemtime($file);
                    
                    // Solo eliminar si:
                    // 1. No está en la lista de archivos en uso
                    // 2. Tiene más de 24 horas de antigüedad
                    if (!in_array($filename, $used_files) && $file_age > 86400) {
                        unlink($file);
                        logMessage("Archivo de covers eliminado (más de 24 horas y no en uso): $filename");
                    } else {
                        logMessage("Archivo de covers encontrado (no eliminado): $filename");
                    }
                }
            }
        }
    }

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