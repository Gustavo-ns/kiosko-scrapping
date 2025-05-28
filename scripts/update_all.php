<?php
// Configuración inicial
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/cron_errors.log');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Zona horaria
date_default_timezone_set('America/Lima');

// Variables para el seguimiento del tiempo
$start_time = microtime(true);
$last_step_time = $start_time;

// Archivo de registro de actualizaciones
$updates_log = __DIR__ . '/updates_history.log';

// Función para formatear duración
function formatDuration($seconds) {
    if ($seconds < 60) {
        return number_format($seconds, 2) . " segundos";
    }
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return "{$minutes}m " . number_format($seconds, 2) . "s";
}

// Función para registrar eventos con tiempo
function logEvent($message, $level = 'INFO') {
    global $start_time, $last_step_time;
    $now = microtime(true);
    
    $total_duration = formatDuration($now - $start_time);
    $step_duration = formatDuration($now - $last_step_time);
    
    $date = date('Y-m-d H:i:s');
    $logMessage = sprintf(
        "[%s] [%s] %s (Paso: %s | Total: %s)\n",
        $date,
        $level,
        $message,
        $step_duration,
        $total_duration
    );
    
    file_put_contents(__DIR__ . '/cron_update.log', $logMessage, FILE_APPEND);
    echo $logMessage;
    
    $last_step_time = $now;
}

// Función para registrar la actualización en el historial
function logUpdate($status) {
    global $updates_log, $start_time;
    $date = date('Y-m-d H:i:s');
    $duration = formatDuration(microtime(true) - $start_time);
    
    $log_entry = sprintf(
        "%s | Estado: %s | Duración: %s | Servidor: %s\n",
        $date,
        $status,
        $duration,
        gethostname()
    );
    
    // Mantener solo las últimas 1000 líneas
    $existing_logs = file_exists($updates_log) ? file($updates_log) : [];
    $existing_logs = array_slice($existing_logs, -999); // Mantener 999 líneas anteriores
    $existing_logs[] = $log_entry; // Agregar la nueva línea
    
    file_put_contents($updates_log, implode('', $existing_logs));
}

// Función para ejecutar un script y capturar su salida
function executeScript($scriptPath) {
    logEvent("Iniciando ejecución de $scriptPath");
    
    ob_start();
    $result = include $scriptPath;
    $output = ob_get_clean();
    
    $status = $result !== false ? "OK" : "ERROR";
    logEvent("Finalizado $scriptPath con estado: $status", $status);
    
    if (trim($output)) {
        logEvent("Salida de $scriptPath: " . trim($output), 'DEBUG');
    }
    
    return $result !== false;
}

try {
    logEvent("=== INICIO DE ACTUALIZACIÓN AUTOMÁTICA ===", 'START');
    logEvent("Versión de PHP: " . PHP_VERSION);
    logEvent("Memoria límite: " . ini_get('memory_limit'));
    logEvent("Tiempo máximo de ejecución: " . ini_get('max_execution_time') . "s");
    
    // 1. Actualizar Melwater
    if (file_exists(__DIR__ . '/update_melwater.php')) {
        if (!executeScript(__DIR__ . '/update_melwater.php')) {
            throw new Exception("Error actualizando Melwater");
        }
    } else {
        logEvent("update_melwater.php no encontrado", 'WARNING');
    }
    
    // 2. Ejecutar scraping
    if (file_exists(__DIR__ . '/scrape.php')) {
        if (!executeScript(__DIR__ . '/scrape.php')) {
            throw new Exception("Error ejecutando scraping");
        }
    } else {
        logEvent("scrape.php no encontrado", 'WARNING');
    }
    
    // 3. Limpiar imágenes antiguas (más de 7 días)
    $imageDir = __DIR__ . '/images';
    if (is_dir($imageDir)) {
        logEvent("Iniciando limpieza de imágenes antiguas");
        
        $files = glob($imageDir . '/*');
        $now = time();
        $deleted = 0;
        $total_files = count($files);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 7 * 24 * 3600) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        logEvent(sprintf(
            "Limpieza completada: %d/%d archivos eliminados",
            $deleted,
            $total_files
        ));
    }
    
    $memory_usage = memory_get_peak_usage(true) / 1024 / 1024;
    logEvent(sprintf(
        "Uso máximo de memoria: %.2f MB",
        $memory_usage
    ));
    
    logEvent("=== ACTUALIZACIÓN COMPLETADA CON ÉXITO ===", 'SUCCESS');
    logUpdate('ÉXITO');
    
} catch (Exception $e) {
    logEvent("ERROR FATAL: " . $e->getMessage(), 'ERROR');
    logEvent("=== ACTUALIZACIÓN FALLIDA ===", 'ERROR');
    logUpdate('ERROR: ' . $e->getMessage());
    exit(1);
} 