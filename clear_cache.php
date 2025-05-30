<?php
/**
 * Script para limpiar caché del servidor
 * Puede ser llamado manualmente o desde un cron job
 */

// Establecer headers anti-caché
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cleared' => []];

try {
    // 1. Limpiar OPcache si está habilitado
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $response['cleared'][] = 'OPcache';
    }
    
    // 2. Limpiar caché de archivos si existe
    if (function_exists('apc_clear_cache')) {
        apc_clear_cache();
        $response['cleared'][] = 'APC Cache';
    }
    
    // 3. Limpiar archivos de caché temporales si existen
    $cache_dirs = ['cache/', 'tmp/', 'temp/'];
    foreach ($cache_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 3600) { // Archivos más antiguos de 1 hora
                    unlink($file);
                }
            }
            $response['cleared'][] = $dir;
        }
    }
    
    // 4. Forzar limpieza de headers de caché para próximas peticiones
    $cache_buster = time();
    file_put_contents('.cache_version', $cache_buster);
    $response['cleared'][] = 'Cache version: ' . $cache_buster;
    
    $response['success'] = true;
    $response['message'] = 'Caché limpiado exitosamente';
    $response['timestamp'] = date('Y-m-d H:i:s');
    
} catch (Exception $e) {
    $response['message'] = 'Error al limpiar caché: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
