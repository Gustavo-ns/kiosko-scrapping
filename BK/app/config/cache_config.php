<?php
// Configuración de caché y versión de assets
if (!defined('ASSETS_VERSION')) {
    define('ASSETS_VERSION', '1.0.0');
}

// Tiempos de caché en segundos
define('CACHE_TIME_IMAGES', 604800);    // 1 semana
define('CACHE_TIME_STATIC', 86400);     // 1 día
define('CACHE_TIME_DATA', 1800);        // 30 minutos

/**
 * Establece los headers de caché según el tipo de contenido
 * @param string $type Tipo de contenido ('image', 'static', 'data')
 * @param bool $isPublic Si el contenido es público o privado
 */
function setHeadersForContentType($type, $public = true) {
    $cache_time = 0;
    
    switch ($type) {
        case 'css':
        case 'js':
            $cache_time = 2592000; // 30 días
            break;
        case 'image':
            $cache_time = 604800; // 7 días
            break;
        case 'data':
            $cache_time = 300; // 5 minutos
            break;
        default:
            $cache_time = 0; // No cachear
    }
    
    if ($cache_time > 0) {
        $privacy = $public ? 'public' : 'private';
        header("Cache-Control: $privacy, max-age=$cache_time");
        header('Pragma: cache');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
    } else {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
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