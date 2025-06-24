<?php
// Iniciar el buffer de salida
ob_start();

// Configurar el manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/process_all.log');

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
    logMessage("Error de conexión a la base de datos: " . $e->getMessage(), 'ERROR');
    die("Error de conexión a la base de datos");
}

// Función para obtener la fecha de inicio (ayer a las 18hs)
function getStartDate() {
    $startDate = new DateTime();
    $startDate->setTime(18, 0, 0); // Establecer a las 18:00
    $startDate->modify('-1 day'); // Retroceder un día
    return $startDate;
}

// Función para logging
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message\n";
    error_log($logMessage, 3, __DIR__ . '/process_all.log');
    
    // Enviar el log al JavaScript
    echo "<script>addLogFromPHP('" . addslashes($message) . "', '" . strtolower($type) . "');</script>";
    
    // También mostrar en el HTML
    echo "<div class='log-entry log-" . strtolower($type) . "'>";
    echo "<i class='log-icon fas " . 
        (strtolower($type) === 'info' ? 'fa-info-circle' :
         strtolower($type) === 'warning' ? 'fa-exclamation-circle' :
         'fa-times-circle') . "'></i>";
    echo "<div class='log-content'>";
    echo "<div class='log-message'>" . htmlspecialchars($message) . "</div>";
    echo "<div class='log-timestamp'>" . $timestamp . "</div>";
    echo "</div></div>";
    
    flush();
    ob_flush();
}

// Función para ejecutar un proceso y verificar su resultado
function executeProcess($processName, $scriptPath) {
    logMessage("Iniciando proceso: $processName");
    
    // Pasar la fecha de inicio como parámetro
    $startDate = getStartDate();
    $startDateStr = $startDate->format('Y-m-d H:i:s');
    
    // Ejecutar el script y capturar la salida
    $output = [];
    $returnVar = 0;
    exec("php $scriptPath --start-date=\"$startDateStr\" 2>&1", $output, $returnVar);
    
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

// Obtener los datos unificados de la tabla portadas
$stmt = $pdo->query("
    SELECT p.*, m.preview_image as melwater_preview
    FROM portadas p
    LEFT JOIN pk_melwater m ON p.external_id = m.external_id
    WHERE p.published_date >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 18:00:00')
    AND p.published_date <= NOW()
    AND p.published_date IS NOT NULL
    ORDER BY p.published_date DESC
");
$documents = $stmt->fetchAll();

// Log para verificar los datos de preview_image
foreach ($documents as $doc) {
    if (isset($doc['melwater_preview'])) {
        logMessage("Preview image para external_id {$doc['external_id']}: " . $doc['melwater_preview']);
    } else {
        logMessage("No hay preview image para external_id {$doc['external_id']}", 'WARNING');
    }
}

// Filtrar documentos por fecha
$now = new DateTime(); // ahora
$yesterday = new DateTime();
$yesterday->setTime(18, 0, 0); // Establecer a las 18:00
$yesterday->modify('-1 day'); // Retroceder un día

error_log("Total documentos obtenidos de portadas: " . count($documents));
error_log("Rango de fechas: " . $yesterday->format('Y-m-d H:i:s') . " hasta " . $now->format('Y-m-d H:i:s'));

$filtered_documents = array_filter($documents, function($doc) use ($yesterday, $now) {
    $date_str = isset($doc['published_date']) ? $doc['published_date'] : null;
    if (!$date_str) return false;
    try {
        $doc_date = new DateTime($date_str);
        // Validar que la fecha no sea futura
        if ($doc_date > $now) {
            error_log("Documento con fecha futura encontrado: " . $date_str);
            return false;
        }
        // Validar que la fecha no sea anterior a ayer a las 18hs
        if ($doc_date < $yesterday) {
            error_log("Documento con fecha anterior al rango permitido: " . $date_str);
            return false;
        }
        $is_in_range = $doc_date >= $yesterday && $doc_date <= $now;
        if (!$is_in_range) return false;
    } catch (Exception $e) {
        error_log("Error al procesar fecha: " . $date_str . " - " . $e->getMessage());
        return false;
    }
    // Solo mostrar si visualizar=1
    if (isset($doc['visualizar']) && $doc['visualizar'] != 1) return false;
    return true;
});

// Log de documentos filtrados
error_log("Documentos después de filtrar fechas: " . count($filtered_documents));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proceso Completo de Portadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --error-color: #e74c3c;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .progress-container {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .progress {
            height: 1.5rem;
            border-radius: 10px;
            background-color: #e9ecef;
        }

        .progress-bar {
            background-color: var(--secondary-color);
            transition: width 0.6s ease;
        }

        .log-container {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 600px;
            overflow-y: auto;
            position: relative;
        }

        .log-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: linear-gradient(transparent, var(--card-background));
            pointer-events: none;
        }

        .log-entry {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            border-left: 4px solid #ddd;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .log-entry:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .log-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
            width: 24px;
            text-align: center;
        }

        .log-content {
            flex-grow: 1;
            min-width: 0; /* Evita que el contenido se desborde */
        }

        .log-message {
            word-break: break-word;
            margin-bottom: 4px;
        }

        .log-timestamp {
            font-size: 0.8rem;
            color: #666;
            white-space: nowrap;
        }

        .log-info { 
            border-left-color: var(--secondary-color);
            background: rgba(52, 152, 219, 0.05);
        }
        
        .log-warning { 
            border-left-color: var(--warning-color);
            background: rgba(241, 196, 15, 0.05);
        }
        
        .log-error { 
            border-left-color: var(--error-color);
            background: rgba(231, 76, 60, 0.05);
        }

        .log-info .log-icon { color: var(--secondary-color); }
        .log-warning .log-icon { color: var(--warning-color); }
        .log-error .log-icon { color: var(--error-color); }

        .log-group {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: rgba(0,0,0,0.02);
        }

        .log-group-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .log-group-title i {
            color: var(--secondary-color);
        }

        .auto-scroll {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--card-background);
            padding: 0.5rem;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .auto-scroll:hover {
            transform: scale(1.1);
        }

        .auto-scroll.active {
            background: var(--secondary-color);
            color: white;
        }

        .process-status {
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .process-status:hover {
            transform: translateY(-2px);
        }

        .process-success { 
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
        }
        
        .process-error { 
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--error-color);
        }

        .status-icon {
            font-size: 1.2rem;
        }

        .process-success .status-icon {
            color: var(--success-color);
        }

        .process-error .status-icon {
            color: var(--error-color);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }

        .stat-card h3 {
            font-size: 1.5rem;
            margin: 0;
            color: var(--primary-color);
        }

        .stat-card p {
            color: #666;
            margin: 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        .tabs-container {
            background: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .tabs-header {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 0.5rem;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            border: none;
            background: none;
            color: var(--primary-color);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            margin-right: 0.5rem;
            border-radius: 5px;
        }

        .tab-button:hover {
            background: rgba(52, 152, 219, 0.1);
        }

        .tab-button.active {
            color: var(--secondary-color);
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--secondary-color);
            border-radius: 3px 3px 0 0;
        }

        .tab-content {
            display: none;
            padding: 1.5rem;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .filter-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            background: #f8f9fa;
            color: var(--primary-color);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-button:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .filter-button.active {
            background: var(--secondary-color);
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .action-button.primary {
            background: var(--secondary-color);
            color: white;
            box-shadow: 0 2px 4px rgba(52, 152, 219, 0.2);
        }

        .action-button.secondary {
            background: #f8f9fa;
            color: var(--primary-color);
            border: 2px solid #e9ecef;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .action-button:active {
            transform: translateY(0);
        }

        .action-button i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .filter-buttons {
                justify-content: center;
            }

            .action-buttons {
                justify-content: center;
            }

            .action-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-newspaper"></i> Proceso Completo de Portadas</h1>
            <p>Sistema automatizado de procesamiento de portadas</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-sync"></i>
                <h3 id="meltwaterCount">0</h3>
                <p>Registros Meltwater</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-image"></i>
                <h3 id="coversCount">0</h3>
                <p>Portadas Procesadas</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-newspaper"></i>
                <h3 id="totalCount">0</h3>
                <p>Total Registros</p>
            </div>
        </div>
        
        <div class="progress-container">
            <h4 class="mb-3">Progreso General</h4>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 0%" id="progressBar"></div>
            </div>
        </div>

        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-button active" data-tab="logs">
                    <i class="fas fa-list"></i> Logs
                </button>
                <button class="tab-button" data-tab="status">
                    <i class="fas fa-tasks"></i> Estado
                </button>
                <button class="tab-button" data-tab="errors">
                    <i class="fas fa-exclamation-triangle"></i> Errores
                </button>
            </div>

            <div class="tab-content active" id="logs-tab">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="logSearch" placeholder="Buscar en logs...">
                </div>
                <div class="filter-buttons">
                    <button class="filter-button active" data-filter="all">
                        <i class="fas fa-list"></i> Todos
                    </button>
                    <button class="filter-button" data-filter="info">
                        <i class="fas fa-info-circle"></i> Info
                    </button>
                    <button class="filter-button" data-filter="warning">
                        <i class="fas fa-exclamation-circle"></i> Advertencias
                    </button>
                    <button class="filter-button" data-filter="error">
                        <i class="fas fa-times-circle"></i> Errores
                    </button>
                </div>
                <div id="logsContent" class="log-container">
                    <?php
                    // Mostrar logs iniciales
                    logMessage("Iniciando proceso de actualización");
                    logMessage("Total documentos obtenidos: " . count($documents));
                    logMessage("Documentos filtrados: " . count($filtered_documents));
                    logMessage("Rango de fechas: " . $yesterday->format('Y-m-d H:i:s') . " hasta " . $now->format('Y-m-d H:i:s'));
                    ?>
                </div>
                <div class="action-buttons">
                    <button class="action-button primary" onclick="clearLogs()">
                        <i class="fas fa-trash"></i> Limpiar Logs
                    </button>
                    <button class="action-button secondary" onclick="exportLogs()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>

            <div class="tab-content" id="status-tab">
                <div id="processStatus" class="log-container"></div>
            </div>

            <div class="tab-content" id="errors-tab">
                <div id="errorsContent" class="log-container"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let startTime = new Date();
        let logs = [];
        
        function updateProgress(percent) {
            document.getElementById('progressBar').style.width = percent + '%';
        }

        let autoScroll = true;
        let currentLogGroup = null;

        function addLogEntry(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = {
                message,
                type,
                timestamp,
                group: currentLogGroup
            };
            logs.push(logEntry);
            
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            
            const icon = document.createElement('i');
            icon.className = `log-icon fas ${
                type === 'info' ? 'fa-info-circle' :
                type === 'warning' ? 'fa-exclamation-circle' :
                'fa-times-circle'
            }`;
            
            const content = document.createElement('div');
            content.className = 'log-content';
            content.innerHTML = `
                <div class="log-message">${message}</div>
                <div class="log-timestamp">${timestamp}</div>
            `;
            
            entry.appendChild(icon);
            entry.appendChild(content);

            // Agregar al grupo correspondiente
            let logContainer = document.getElementById('logsContent');
            if (currentLogGroup) {
                let groupElement = logContainer.querySelector(`[data-group="${currentLogGroup}"]`);
                if (!groupElement) {
                    groupElement = document.createElement('div');
                    groupElement.className = 'log-group';
                    groupElement.dataset.group = currentLogGroup;
                    groupElement.innerHTML = `
                        <div class="log-group-title">
                            <i class="fas fa-folder"></i>
                            ${currentLogGroup}
                        </div>
                    `;
                    logContainer.appendChild(groupElement);
                }
                groupElement.appendChild(entry);
            } else {
                logContainer.appendChild(entry);
            }
            
            if (type === 'error') {
                document.getElementById('errorsContent').appendChild(entry.cloneNode(true));
            }

            // Auto-scroll si está activado
            if (autoScroll) {
                const container = entry.closest('.log-container');
                container.scrollTop = container.scrollHeight;
            }
        }

        function addStatus(message, isError = false) {
            const timestamp = new Date().toLocaleTimeString();
            const status = document.createElement('div');
            status.className = `process-status ${isError ? 'process-error' : 'process-success'}`;
            
            const icon = document.createElement('i');
            icon.className = `status-icon fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}`;
            
            const content = document.createElement('div');
            content.className = 'status-content';
            content.innerHTML = `
                <div>${message}</div>
                <div class="status-timestamp">${timestamp}</div>
            `;
            
            status.appendChild(icon);
            status.appendChild(content);
            document.getElementById('processStatus').appendChild(status);
        }

        function updateTimeElapsed() {
            const now = new Date();
            const diff = now - startTime;
            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            document.getElementById('timeElapsed').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and contents
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                button.classList.add('active');
                document.getElementById(`${button.dataset.tab}-tab`).classList.add('active');
            });
        });

        // Log filtering
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.filter-button').forEach(b => b.classList.remove('active'));
                button.classList.add('active');
                
                const filter = button.dataset.filter;
                document.querySelectorAll('.log-entry').forEach(entry => {
                    if (filter === 'all' || entry.classList.contains(`log-${filter}`)) {
                        entry.style.display = 'flex';
                    } else {
                        entry.style.display = 'none';
                    }
                });
            });
        });

        // Log search
        document.getElementById('logSearch').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.log-entry').forEach(entry => {
                const text = entry.textContent.toLowerCase();
                entry.style.display = text.includes(searchTerm) ? 'flex' : 'none';
            });
        });

        // Actualizar el tiempo cada segundo
        setInterval(updateTimeElapsed, 1000);

        function setLogGroup(group) {
            currentLogGroup = group;
        }

        // Agregar botón de auto-scroll
        const autoScrollButton = document.createElement('div');
        autoScrollButton.className = 'auto-scroll active';
        autoScrollButton.innerHTML = '<i class="fas fa-arrow-down"></i>';
        document.body.appendChild(autoScrollButton);

        autoScrollButton.addEventListener('click', () => {
            autoScroll = !autoScroll;
            autoScrollButton.classList.toggle('active');
        });

        // Función para limpiar logs antiguos
        function cleanOldLogs() {
            const maxLogs = 1000; // Máximo número de logs a mantener
            if (logs.length > maxLogs) {
                const logsToRemove = logs.length - maxLogs;
                logs.splice(0, logsToRemove);
                
                const logContainers = document.querySelectorAll('.log-container');
                logContainers.forEach(container => {
                    const entries = container.querySelectorAll('.log-entry');
                    for (let i = 0; i < logsToRemove && i < entries.length; i++) {
                        entries[i].remove();
                    }
                });
            }
        }

        // Limpiar logs antiguos cada 5 minutos
        setInterval(cleanOldLogs, 300000);

        // Función para limpiar logs
        function clearLogs() {
            document.getElementById('logsContent').innerHTML = '';
            logs = [];
            addLogEntry('Logs limpiados', 'info');
        }

        // Función para exportar logs
        function exportLogs() {
            const logText = logs.map(log => 
                `[${log.timestamp}] [${log.type.toUpperCase()}] ${log.message}`
            ).join('\n');
            
            const blob = new Blob([logText], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `logs_${new Date().toISOString().slice(0,19).replace(/[:]/g, '-')}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Función para agregar logs desde PHP
        function addLogFromPHP(message, type) {
            addLogEntry(message, type);
        }

        // Función para actualizar estadísticas
        function updateStats() {
            fetch('get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('meltwaterCount').textContent = data.meltwater;
                        document.getElementById('coversCount').textContent = data.covers;
                        document.getElementById('totalCount').textContent = data.total;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Actualizar estadísticas cada 5 segundos
        setInterval(updateStats, 5000);
        // Actualizar inmediatamente al cargar
        updateStats();
    </script>
</body>
</html>
<?php
// Ejecutar los procesos en secuencia
$processes = [
    ['name' => 'Limpieza de Portadas', 'script' => 'clean_covers.php'],
    ['name' => 'Actualización de Meltwater', 'script' => 'update_melwater.php'],
    ['name' => 'Scraping de Portadas', 'script' => 'scrape.php'],
    ['name' => 'Actualización de Portadas', 'script' => 'update_portadas.php']
];

$success = true;
$totalProcesses = count($processes);

// Limpiar la tabla covers antes de comenzar
try {
    logMessage("Limpiando tabla covers...");
    $pdo->exec("TRUNCATE TABLE covers");
    logMessage("Tabla covers limpiada exitosamente");
} catch (PDOException $e) {
    logMessage("Error al limpiar la tabla covers: " . $e->getMessage(), 'ERROR');
    $success = false;
}

foreach ($processes as $index => $process) {
    $progress = ($index / $totalProcesses) * 100;
    echo "<script>updateProgress($progress);</script>";
    
    // Establecer el grupo actual
    echo "<script>setLogGroup('{$process['name']}');</script>";
    
    if (!executeProcess($process['name'], $process['script'])) {
        $success = false;
        echo "<script>addStatus('Error en el proceso: {$process['name']}', true);</script>";
        break;
    }
    
    echo "<script>addStatus('Proceso completado: {$process['name']}');</script>";
}

// Actualizar la barra de progreso al final
echo "<script>updateProgress(100);</script>";

if ($success) {
    logMessage("Todos los procesos se completaron exitosamente");
    echo "<script>addStatus('Todos los procesos se completaron exitosamente');</script>";
} else {
    logMessage("El proceso se detuvo debido a errores", 'ERROR');
    echo "<script>addStatus('El proceso se detuvo debido a errores', true);</script>";
}

// Limpieza de imágenes y archivos temporales no referenciados
try {
    logMessage("Iniciando limpieza de archivos de imagen no referenciados...");
    
    // Obtener todas las rutas de imágenes referenciadas en la base de datos
    $referencedImages = [];
    
    // Obtener imágenes de pk_melwater
    $melwaterStmt = $pdo->prepare("
        SELECT content_image, preview_image 
        FROM pk_melwater 
        WHERE content_image IS NOT NULL OR preview_image IS NOT NULL
    ");
    $melwaterStmt->execute();
    $melwaterImages = $melwaterStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($melwaterImages as $row) {
        if ($row['content_image']) {
            $referencedImages[] = $row['content_image'];
        }
        if ($row['preview_image']) {
            $referencedImages[] = $row['preview_image'];
        }
    }
    
    // Obtener imágenes de portadas
    $portadasStmt = $pdo->prepare("
        SELECT original_url, thumbnail_url 
        FROM portadas 
        WHERE original_url IS NOT NULL OR thumbnail_url IS NOT NULL
    ");
    $portadasStmt->execute();
    $portadasImages = $portadasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($portadasImages as $row) {
        if ($row['original_url']) {
            $referencedImages[] = $row['original_url'];
        }
        if ($row['thumbnail_url']) {
            $referencedImages[] = $row['thumbnail_url'];
        }
    }
    
    // Convertir a rutas absolutas y crear un conjunto para búsqueda rápida
    $referencedPaths = [];
    foreach ($referencedImages as $imagePath) {
        if ($imagePath && !empty($imagePath)) {
            $absolutePath = __DIR__ . '/' . $imagePath;
            $referencedPaths[$absolutePath] = true;
        }
    }
    
    logMessage("Total de imágenes referenciadas en BD: " . count($referencedPaths));
    
    // Función recursiva para escanear directorios
    function scanDirectory($dir, $referencedPaths, &$deletedFiles, &$deletedSize) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $dir . '/' . $file;
            
            if (is_dir($filePath)) {
                // Recursivamente escanear subdirectorios
                scanDirectory($filePath, $referencedPaths, $deletedFiles, $deletedSize);
            } else {
                // Verificar si es un archivo de imagen
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
                
                if (in_array($extension, $imageExtensions)) {
                    // Verificar si el archivo está referenciado
                    if (!isset($referencedPaths[$filePath])) {
                        $fileSize = filesize($filePath);
                        if (@unlink($filePath)) {
                            $deletedFiles++;
                            $deletedSize += $fileSize;
                            logMessage("Archivo eliminado (no referenciado): " . basename($filePath) . " (" . number_format($fileSize) . " bytes)");
                        } else {
                            logMessage("Error al eliminar archivo: " . basename($filePath), 'ERROR');
                        }
                    }
                }
            }
        }
    }
    
    // Escanear directorio images
    $imagesDir = __DIR__ . '/images';
    $deletedFiles = 0;
    $deletedSize = 0;
    
    if (is_dir($imagesDir)) {
        scanDirectory($imagesDir, $referencedPaths, $deletedFiles, $deletedSize);
    }
    
    // También limpiar archivos temporales del sistema
    $tempDir = sys_get_temp_dir();
    $tempFiles = glob($tempDir . '/melwater_*');
    $tempDeleted = 0;
    $tempDeletedSize = 0;
    
    foreach ($tempFiles as $tempFile) {
        // Verificar si el archivo es más antiguo que 1 hora
        if (filemtime($tempFile) < (time() - 3600)) {
            $fileSize = filesize($tempFile);
            if (@unlink($tempFile)) {
                $tempDeleted++;
                $tempDeletedSize += $fileSize;
                logMessage("Archivo temporal eliminado: " . basename($tempFile) . " (" . number_format($fileSize) . " bytes)");
            }
        }
    }
    
    // Log de resumen de limpieza
    logMessage("=== RESUMEN DE LIMPIEZA DE IMÁGENES ===");
    logMessage("Archivos de imagen eliminados: {$deletedFiles}");
    logMessage("Espacio liberado (imágenes): " . number_format($deletedSize) . " bytes (" . number_format($deletedSize / 1024 / 1024, 2) . " MB)");
    logMessage("Archivos temporales eliminados: {$tempDeleted}");
    logMessage("Espacio liberado (temporales): " . number_format($tempDeletedSize) . " bytes (" . number_format($tempDeletedSize / 1024 / 1024, 2) . " MB)");
    logMessage("Total espacio liberado: " . number_format($deletedSize + $tempDeletedSize) . " bytes (" . number_format(($deletedSize + $tempDeletedSize) / 1024 / 1024, 2) . " MB)");
    logMessage("=========================================");
} catch (Exception $e) {
    logMessage("Error durante la limpieza de imágenes: " . $e->getMessage(), 'ERROR');
}

// Limpiar y enviar el buffer
ob_end_flush();
?> 