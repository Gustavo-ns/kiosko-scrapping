<?php
// Cargar configuración de la base de datos
$cfg = require 'config.php';

try {
    // Conexión a la base de datos
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Obtener configuración de caché desde la base de datos
    $stmt = $pdo->query("SELECT name, value FROM configs WHERE name IN ('assets_version', 'cache_time_images', 'cache_time_static', 'cache_time_data')");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Definir constantes con valores por defecto si no existen en la base de datos
    define('ASSETS_VERSION', isset($configs['assets_version']) ? $configs['assets_version'] : '1.0.13');
    define('CACHE_TIME_IMAGES', (int)(isset($configs['cache_time_images']) ? $configs['cache_time_images'] : 86400));    // 1 día
    define('CACHE_TIME_STATIC', (int)(isset($configs['cache_time_static']) ? $configs['cache_time_static'] : 86400));     // 1 día
    define('CACHE_TIME_DATA', (int)(isset($configs['cache_time_data']) ? $configs['cache_time_data'] : 0));            // Sin caché para datos dinámicos

} catch (PDOException $e) {
    // En caso de error, usar valores por defecto
    define('ASSETS_VERSION', '1.0.13');
    define('CACHE_TIME_IMAGES', 86400);    // 1 día
    define('CACHE_TIME_STATIC', 86400);     // 1 día
    define('CACHE_TIME_DATA', 0);            // Sin caché para datos dinámicos
    error_log("Error al cargar configuración de caché desde la base de datos: " . $e->getMessage());
}

/**
 * Establece los headers de caché según el tipo de contenido
 * @param string $type Tipo de contenido ('image', 'static', 'data', 'html')
 * @param bool $isPublic Si el contenido es público o privado
 */
function setHeadersForContentType($type, $isPublic = true) {
    $cacheControl = $isPublic ? 'public' : 'private';
    
    switch ($type) {
        case 'image':
            $maxAge = CACHE_TIME_IMAGES;
            break;
        case 'static':
            $maxAge = CACHE_TIME_STATIC;
            break;
        case 'data':
        case 'html':
            // Para contenido dinámico que se actualiza cada hora
            $maxAge = 0;
            $cacheControl = 'no-cache, no-store, must-revalidate';
            header('Cache-Control: ' . $cacheControl);
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            return;
        default:
            $maxAge = 0;
            $cacheControl = 'no-store';
    }

    // Establecer headers de caché
    header('Cache-Control: ' . $cacheControl . ', max-age=' . $maxAge);
    header('Pragma: ' . ($isPublic ? 'cache' : 'no-cache'));
    
    // Establecer fecha de expiración
    if ($maxAge > 0) {
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }
}

// Función para generar ETag basado en el contenido
function generateETag($content) {
    return '"' . md5($content . ASSETS_VERSION) . '"';
}

// Función para verificar si el contenido ha cambiado
function hasContentChanged($etag) {
    $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : null;
    return $if_none_match !== $etag;
}

// Función para servir contenido con caché
function serveContentWithCache($content, $content_type = 'data') {
    $etag = generateETag($content);
    header('ETag: ' . $etag);
    
    // Verificar si el contenido ha cambiado
    if (!hasContentChanged($etag)) {
        http_response_code(304); // Not Modified
        exit;
    }
    
    setHeadersForContentType($content_type);
    return $content;
}

/**
 * Actualiza la configuración de caché en la base de datos
 * @param array $config Array con la configuración a actualizar
 * @return bool True si la actualización fue exitosa
 */
function updateCacheConfig($config) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($config as $name => $value) {
            $stmt = $pdo->prepare("REPLACE INTO configs (name, value) VALUES (:name, :value)");
            $stmt->execute([
                ':name' => $name,
                ':value' => (string)$value
            ]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al actualizar configuración de caché: " . $e->getMessage());
        return false;
    }
} 