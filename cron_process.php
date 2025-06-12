<?php
// Configurar el manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cron_process.log');

// Configurar el timezone
date_default_timezone_set('America/Montevideo');

// Configurar el límite de tiempo de ejecución
set_time_limit(900); // 15 minutos para todo el proceso

// Configurar el límite de memoria
ini_set('memory_limit', '512M');

// Cargar configuración de la base de datos
$cfg = require 'config.php';

try {
    // Inicializar conexión PDO
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Función para logging
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message\n";
    error_log($logMessage, 3, __DIR__ . '/cron_process.log');
}

// Función para limpiar imágenes antiguas
function cleanOldImages($pdo) {
    try {
        // Obtener la última fecha de scraping desde la tabla configs
        $stmt = $pdo->query("SELECT value FROM configs WHERE name = 'last_scraping_date'");
        $lastScraping = $stmt->fetch();
        
        if (!$lastScraping) {
            logMessage("No se encontró registro de última fecha de scraping", 'WARNING');
            return;
        }
        
        $lastScrapingDate = new DateTime($lastScraping['value']);
        $now = new DateTime();
        $diff = $now->diff($lastScrapingDate);
        
        // Si ha pasado más de un día desde el último scraping
        if ($diff->days >= 1) {
            logMessage("Iniciando limpieza de imágenes antiguas");
            
            // Directorios a limpiar
            $directories = [
                __DIR__ . '/images/melwater',
                __DIR__ . '/images/melwater/previews',
                __DIR__ . '/images/portadas',
                __DIR__ . '/images/portadas/previews'
            ];
            
            foreach ($directories as $dir) {
                if (file_exists($dir)) {
                    $files = glob($dir . '/*');
                    $deletedCount = 0;
                    
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                            $deletedCount++;
                        }
                    }
                    
                    logMessage("Se eliminaron {$deletedCount} archivos en {$dir}");
                }
            }
            
            // Actualizar la fecha del último scraping
            $stmt = $pdo->prepare("UPDATE configs SET value = NOW() WHERE name = 'last_scraping_date'");
            $stmt->execute();
            
            logMessage("Limpieza de imágenes completada");
        } else {
            logMessage("No es necesario limpiar imágenes aún. Última limpieza hace {$diff->days} días");
        }
    } catch (Exception $e) {
        logMessage("Error al limpiar imágenes: " . $e->getMessage(), 'ERROR');
    }
}

// Función para ejecutar un proceso y verificar su resultado
function executeProcess($processName, $scriptPath) {
    logMessage("Iniciando proceso: $processName");
    
    // Ejecutar el script y capturar la salida
    $output = [];
    $returnVar = 0;
    exec("php " . escapeshellarg($scriptPath) . " 2>&1", $output, $returnVar);
    
    // Verificar si hubo errores
    if ($returnVar !== 0) {
        logMessage("Error en el proceso $processName. Código de retorno: $returnVar", 'ERROR');
        foreach ($output as $line) {
            logMessage($line, 'ERROR');
        }
        return false;
    }
    
    // Mostrar la salida del proceso
    foreach ($output as $line) {
        logMessage($line);
    }
    
    logMessage("Proceso $processName completado exitosamente");
    return true;
}

try {
    logMessage("Iniciando proceso de actualización automática");
    
    // Limpiar imágenes antiguas si es necesario
    cleanOldImages($pdo);
    
    // Ejecutar los procesos en secuencia
    $processes = [
        ['name' => 'Actualización de Meltwater', 'script' => __DIR__ . '/update_melwater.php'],
        ['name' => 'Scraping de Portadas', 'script' => __DIR__ . '/scrape.php'],
        ['name' => 'Actualización de Portadas', 'script' => __DIR__ . '/update_portadas.php']
    ];

    $success = true;
    $totalProcesses = count($processes);

    foreach ($processes as $index => $process) {
        logMessage("Progreso: " . (($index + 1) * 100 / $totalProcesses) . "%");
        
        if (!executeProcess($process['name'], $process['script'])) {
            $success = false;
            logMessage("Error en el proceso: {$process['name']}", 'ERROR');
            break;
        }
        
        logMessage("Proceso completado: {$process['name']}");
    }

    if ($success) {
        logMessage("Todos los procesos se completaron exitosamente");
    } else {
        logMessage("El proceso se detuvo debido a errores", 'ERROR');
    }

} catch (Exception $e) {
    logMessage("Error general en el proceso: " . $e->getMessage(), 'ERROR');
}

// Limpiar logs antiguos (mantener solo los últimos 7 días)
$logFile = __DIR__ . '/cron_process.log';
if (file_exists($logFile)) {
    $maxAge = 7 * 24 * 60 * 60; // 7 días en segundos
    $fileAge = time() - filemtime($logFile);
    
    if ($fileAge > $maxAge) {
        unlink($logFile);
        logMessage("Log antiguo eliminado");
    }
}
?> 