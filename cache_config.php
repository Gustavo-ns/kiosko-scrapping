<?php
// Versión de los assets para control de caché
define('ASSETS_VERSION', '1.0.13');

// Tiempos de caché en segundos
define('CACHE_TIME_IMAGES', 86400);    // 1 día
define('CACHE_TIME_STATIC', 86400);     // 1 día
define('CACHE_TIME_DATA', 0);            // Sin caché para datos dinámicos

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