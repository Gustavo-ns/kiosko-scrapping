<?php
require_once 'cache_config.php';
require_once 'download_image.php';

// Establecer headers anti-caché para contenido dinámico al inicio
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Cargar configuración de la base de datos
$cfg = require 'config.php';

// Función para obtener el hash del contenido actual
function getContentHash($pdo) {
    $stmt = $pdo->query("SELECT MAX(published_date) as last_update FROM pk_melwater");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return md5($result['last_update'] . time() . ASSETS_VERSION); // Incluir timestamp actual
}

// Función para obtener dimensiones de imagen
function getImageDimensions($imagePath) {
    if (file_exists($imagePath)) {
        $dimensions = getimagesize($imagePath);
        if ($dimensions) {
            return [
                'width' => $dimensions[0],
                'height' => $dimensions[1]
            ];
        }
    }
    return ['width' => 300, 'height' => 200]; // Dimensiones por defecto
}

try {
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );    // Generar ETag basado en la última actualización y timestamp actual
    $etag = '"' . getContentHash($pdo) . '"';
    
    // Configurar headers de caché y tipo de contenido para contenido dinámico
    header('Content-Type: text/html; charset=UTF-8');
    setHeadersForContentType('html', true);
    
    // No usar ETag para contenido que cambia cada hora
    // Remover verificación de ETag para forzar actualización

    // Obtener los datos unificados de la tabla portadas
    $stmt = $pdo->query("
        SELECT *
        FROM portadas
        WHERE published_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY published_date DESC
    ");
    $documents = $stmt->fetchAll();

    // Filtrar documentos por fecha
    $now = new DateTime(); // ahora
    $yesterday = new DateTime();
    $yesterday->modify('-24 hours');
    
    error_log("Total documentos obtenidos de portadas: " . count($documents));
    error_log("Rango de fechas: " . $yesterday->format('Y-m-d H:i:s') . " hasta " . $now->format('Y-m-d H:i:s'));
    
    $filtered_documents = array_filter($documents, function($doc) use ($yesterday, $now) {
        $date_str = isset($doc['published_date']) ? $doc['published_date'] : null;
        if (!$date_str) return false;
        try {
            $doc_date = new DateTime($date_str);
            $is_in_range = $doc_date >= $yesterday && $doc_date <= $now;
            if (!$is_in_range) return false;
        } catch (Exception $e) {
            return false;
        }
        // Solo mostrar si visualizar=1
        if (isset($doc['visualizar']) && $doc['visualizar'] != 1) return false;
        return true;
    });

    // Desglose por tipo
    $type_counts = ['meltwater' => 0, 'cover' => 0, 'resumen' => 0];
    foreach ($filtered_documents as $doc) {
        $type = isset($doc['source_type']) ? $doc['source_type'] : 'unknown';
        if (!isset($type_counts[$type])) {
            $type_counts[$type] = 0;
        }
        $type_counts[$type]++;
    }
    error_log("Desglose por tipo: " . json_encode($type_counts));

    // Obtener grupos únicos con conteo para el selector
    $grupos = $pdo->query("
        SELECT 
            m.grupo,
            COUNT(DISTINCT CASE 
                WHEN EXISTS (
                    SELECT 1 FROM pk_melwater pk WHERE pk.external_id = m.twitter_id
                    UNION ALL
                    SELECT 1 FROM covers c WHERE c.source = m.source
                ) THEN m.id 
            END) as total
        FROM medios m
        WHERE m.visualizar = 1 
        AND m.grupo IS NOT NULL 
        GROUP BY m.grupo
        ORDER BY m.grupo
    ")->fetchAll();

    // Obtener países únicos de covers
    $paises = $pdo->query("
        SELECT DISTINCT country 
        FROM covers 
        ORDER BY country
    ")->fetchAll(PDO::FETCH_COLUMN);

    // Ordenar los documentos por grupo, luego pais, luego dereach descendente
    usort($filtered_documents, function($a, $b) {
        $grupoA = isset($a['grupo']) ? $a['grupo'] : '';
        $grupoB = isset($b['grupo']) ? $b['grupo'] : '';
        $paisA = isset($a['pais']) ? $a['pais'] : '';
        $paisB = isset($b['pais']) ? $b['pais'] : '';
        $dereachA = isset($a['dereach']) ? (int)$a['dereach'] : 0;
        $dereachB = isset($b['dereach']) ? (int)$b['dereach'] : 0;
        if ($grupoA !== $grupoB) return strcmp($grupoA, $grupoB);
        if ($paisA !== $paisB) return strcmp($paisA, $paisB);
        if ($dereachA === $dereachB) return 0;
        return ($dereachA < $dereachB) ? 1 : -1; // descendente
    });

} catch (PDOException $e) {
    // En caso de error, no cachear la respuesta
    header('Cache-Control: no-store');
    die("Error de conexión: " . $e->getMessage());
}

// Función para limpiar caché del navegador
function clearBrowserCache() {
    // Establecer headers adicionales para prevenir caché
    header('Vary: Accept-Encoding, User-Agent');
    header('X-Cache-Status: MISS');
    
    // Agregar headers de seguridad que también ayudan con caché
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
}

// Aplicar limpieza de caché
clearBrowserCache();

// Iniciar buffer de salida
ob_start();
?><!DOCTYPE html>
<html lang="es"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portadas de periódicos de América Latina y el Caribe. Selecciona un país para ver las portadas más recientes.">
    <meta name="keywords" content="portadas, periódicos, América Latina, Caribe, noticias, actualidad, prensa, medios de comunicación">
    <meta name="robots" content="index, follow">    <meta name="theme-color" content="#ffffff">
    <meta http-equiv='cache-control' content='no-cache, no-store, must-revalidate'>
    <meta http-equiv='expires' content='0'>
    <meta http-equiv='pragma' content='no-cache'>
    
    <!-- URL Canónica -->
    <link rel="canonical" href="https://newsroom.eyewatch.me/" />
    
    <!-- Timestamp para evitar caché del navegador -->
    <script>
        // Forzar recarga cada hora
        const lastUpdate = <?= time() ?>;
        const hoursSinceUpdate = Math.floor((Date.now() / 1000 - lastUpdate) / 3600);
        if (hoursSinceUpdate >= 1) {
            window.location.reload(true);
        }
    </script>
    
    <title>Portadas de Periódicos</title>
      <?php    
    // Preload de las primeras imágenes críticas para mejor LCP
    // Debug: Verificar si $filtered_documents está definido
    if (!isset($filtered_documents)) {
        $filtered_documents = [];
    }
    
    $critical_images = array_slice($filtered_documents, 0, 6);
    foreach ($critical_images as $doc) {
        $source_type = $doc['source_type'];
        $image_url = '';
        $thumbnail_url = '';
        
        if ($source_type === 'meltwater' && isset($doc['content_image'])) {
            // Para Meltwater, tratar de obtener la imagen original si existe
            $external_id = isset($doc['external_id']) ? $doc['external_id'] : '';
            if ($external_id) {
                $original_path = "images/melwater/{$external_id}_original.webp";
                $thumbnail_path = "images/melwater/previews/{$external_id}_preview.webp";
                if (file_exists($original_path)) {
                    $image_url = $original_path;
                    $thumbnail_url = $thumbnail_path;
                } else {
                    $image_url = $doc['content_image'];
                }
            } else {
                $image_url = $doc['content_image'];
            }
        } elseif ($source_type === 'cover') {
            // Para covers, usar la imagen original y thumbnail de alta calidad
            $image_url = isset($doc['original_url']) ? $doc['original_url'] : '';
            $thumbnail_url = isset($doc['thumbnail_url']) ? $doc['thumbnail_url'] : '';
        } elseif ($source_type === 'resumen' && isset($doc['source'])) {
            $image_url = $doc['source'];
        }
        
        if ($image_url): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars($image_url) ?>?v=<?= ASSETS_VERSION ?>" fetchpriority="high">
        <?php endif;
        if ($thumbnail_url): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars($thumbnail_url) ?>?v=<?= ASSETS_VERSION ?>" fetchpriority="high">
        <?php endif;
    } ?>
    
    <style>
        /* Critical CSS */
        :root {
            --primary-color: #1e1e1e;
            --secondary-color: #f4f4f4;
            --accent-color: #ff6b35;
            --text-color: #474747;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.2);
            --transition-speed: 0.3s;
        }

        /* Optimización de fuentes */
        @font-face {
            font-family: 'Bebas Neue';
            font-display: swap;
            src: local('Bebas Neue'),
                 url('https://fonts.gstatic.com/s/bebasneue/v14/JTUSjIg69CK48gW7PXoo9Wlhyw.woff2') format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }

        body {
            font-family: 'Bebas Neue', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            contain: layout style paint;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
        }

        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: var(--primary-color);
            color: #fff;
            padding: 8px;
            text-decoration: none;
            z-index: 9999;
            transition: top var(--transition-speed) ease;
        }

        .skip-link:focus {
            top: 6px;
        }

        .controls {
            display: flex;
            padding: 1rem;
            background: var(--primary-color);
            flex-direction: column;
            flex-wrap: wrap;
            align-content: space-around;
            justify-content: center;
            align-items: center;
            contain: layout style;
        }

        .controls label {
            color: #f0f0f0;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .filters {
            margin-bottom: 1rem;
            width: 100%;
            max-width: 300px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            color: #f0f0f0;
            font-size: 1.2rem;
            font-weight: bold;
        }

        #grupoSelect {
            font-size: 1.1rem;
            padding: 0.5rem;
            background-color: #222;
            color: #f0f0f0;
            border: 2px solid #444;
            border-radius: 8px;
            outline: none;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
        }

        #grupoSelect:hover {
            border-color: var(--accent-color);
        }

        #grupoSelect:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 3rem;
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
            position: relative;
            min-height: 400px;
        }

        .card {
            position: relative;
            overflow: hidden;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
            cursor: pointer;
            transform-style: preserve-3d;
            will-change: transform;
            contain: layout style paint;
            transform: translateY(0);
            transition: all 0.3s ease-out;
            opacity: 1;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }

        .image-container {
            position: relative;
            width: 100%;
            background: #f0f0f0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 250px;
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }

        .image-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            z-index: 1;
            opacity: 1;
            transition: opacity 0.3s ease-out;
        }

        .image-container.loaded::before {
            opacity: 0;
            pointer-events: none;
        }

        .card img {
            position: relative;
            width: 100%;
            height: auto;
            object-fit: contain;
            object-position: center;
            display: block;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
            will-change: opacity;
            transform: translateZ(0);
        }

        .card img.loaded {
            opacity: 1;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .blur-on-load {
            filter: blur(16px);
            transition: filter 0.5s ease;
            will-change: filter;
            transform: translateZ(0);
        }

        .blur-on-load.high-quality-loaded {
            filter: blur(0);
        }

        .info {
            padding: 1rem;
            contain: layout style;
        }

        .info h3 {
            margin: 0 0 0.5rem;
            font-size: 1.2rem;
            line-height: 1.4;
            contain: layout style;
            color: var(--text-color);
        }

        .info small {
            display: block;
            color: #666;
            font-size: 0.9rem;
            contain: layout style;
        }

        /* Optimización para elementos después de los primeros 6 */
        .card:nth-child(n+7) {
            content-visibility: auto;
            contain-intrinsic-size: 0 500px;
        }

        /* Footer optimizado */
        .footer-reload {
            background: var(--primary-color);
            padding: 1rem;
            margin-top: 2rem;
            border-top: 3px solid var(--accent-color);
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            contain: layout style;
        }

        .last-update-info {
            flex: 1;
            contain: layout style;
        }

        .last-update-info small {
            color: #ccc;
            font-size: 0.9rem;
            contain: layout style;
        }

        .footer-reload-btn {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
            background: #2c3e50;
            border: 2px solid #34495e;
            border-radius: 8px;
            color: #ffffff;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            font-family: inherit;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }

        .footer-reload-btn:hover {
            background: #1a252f;
            border-color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .footer-reload-btn:focus {
            outline: 3px solid #3498db;
            outline-offset: 2px;
        }

        .footer-reload-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Modal optimizado */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }

        .modal.show {
            display: flex;
        }

        .modal img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--accent-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .gallery {
                padding: 1rem;
                gap: 1rem;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .last-update-info {
                order: 2;
                margin-top: 0.5rem;
            }
            
            .footer-reload-btn {
                order: 1;
                width: 100%;
                max-width: 300px;
            }

            .card {
                min-height: auto;
            }

            .image-container {
                min-height: 200px;
            }
        }

        /* Optimizaciones de rendimiento */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* Efectos de transición para grupos */
        .card {
            transition: all 0.3s ease-out;
            opacity: 1;
            transform: translateY(0);
        }

        .card.hidden {
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }

        .card.visible {
            animation: slideUp 0.3s ease-out forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .group-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .group-transition-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .group-transition-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .group-name {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            color: var(--primary-color);
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .group-transition-overlay.active .group-name {
            opacity: 1;
        }
    </style>
    <!-- Favicon básico -->
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/favicon-180x180.png">

    <!-- Android -->
    <link rel="icon" type="image/png" sizes="192x192" href="favicon/favicon-192x192.png">

    <!-- PWA y alta resolución -->
    <link rel="icon" type="image/png" sizes="512x512" href="favicon/favicon-512x512.png">    <link rel="manifest" href="manifest.json">
    
    <!-- DNS prefetch para recursos externos -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    
    <!-- Preconnect optimizado -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    </noscript>
    
    <link rel="stylesheet" href="styles.css?v=<?= ASSETS_VERSION ?>&t=<?= time() ?>" media="print" onload="this.media='all'">

</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Skeleton Loading -->
    <div id="skeletonContainer" class="skeleton-container">
        <?php for($i = 0; $i < 12; $i++): ?>
            <div class="skeleton-card">
                <div class="skeleton skeleton-image"></div>
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-text"></div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Skip link for accessibility -->
    <a href="#gallery" class="skip-link">Ir al contenido principal</a>
    
    <div class="container">
        <div class="controls">
            <?php 
            // Verificar si hay grupos con contenido
            $hasGroups = false;
            $groupTotals = [];
            
            // Filtrar documentos por fecha
            $now = new DateTime(); // ahora
            // Cambiar de yesterdayEvening a 24 horas exactas
            $yesterday = new DateTime();
            $yesterday->modify('-24 hours');
            
            error_log("Total documentos antes del filtrado: " . count($documents));
            error_log("Rango de fechas: " . $yesterday->format('Y-m-d H:i:s') . " hasta " . $now->format('Y-m-d H:i:s'));
            
            $filtered_documents = array_filter($documents, function($doc) use ($yesterday, $now) {
                $date_str = isset($doc['published_date']) ? $doc['published_date'] : null;
                if (!$date_str) return false;
                try {
                    $doc_date = new DateTime($date_str);
                    $is_in_range = $doc_date >= $yesterday && $doc_date <= $now;
                    if (!$is_in_range) return false;
                } catch (Exception $e) {
                    return false;
                }
                // Solo mostrar si visualizar=1
                if (isset($doc['visualizar']) && $doc['visualizar'] != 1) return false;
                return true;
            });
            
            error_log("Total documentos después del filtrado: " . count($filtered_documents));
            
            // Desglose por tipo con IDs
            $type_counts = ['meltwater' => 0, 'cover' => 0, 'resumen' => 0];
            $type_ids = ['meltwater' => [], 'cover' => [], 'resumen' => []];
            foreach ($filtered_documents as $doc) {
                $type = isset($doc['source_type']) ? $doc['source_type'] : 'unknown';
                if (!isset($type_counts[$type])) {
                    $type_counts[$type] = 0;
                    $type_ids[$type] = [];
                }
                $type_counts[$type]++;
                if (isset($doc['id'])) {
                    $type_ids[$type][] = $doc['id'];
                }
            }
            error_log("Desglose por tipo después del filtrado: " . json_encode($type_counts));
            error_log("IDs por tipo: " . json_encode($type_ids));
            
            // Calcular totales por grupo después del filtrado
            foreach ($filtered_documents as $doc) {
                $grupo = isset($doc['grupo']) ? $doc['grupo'] : 'otros';
                if (!isset($groupTotals[$grupo])) {
                    $groupTotals[$grupo] = 0;
                }
                $groupTotals[$grupo]++;
                $hasGroups = true;
            }
            
            if ($hasGroups): 
            ?>
                <div class="filters">
                    <div class="filter-group">
                        <label for="grupoSelect">Grupo:</label>
                        <select id="grupoSelect" class="form-control">
                            <option value="">Todos los grupos (<?= count($filtered_documents) ?>)</option>
                            <?php 
                            ksort($groupTotals);
                            foreach ($groupTotals as $grupo => $total): 
                                if ($total > 0):
                            ?>
                                <option value="<?= htmlspecialchars($grupo) ?>">
                                    <?= htmlspecialchars($grupo) ?> (<?= $total ?>)
                                </option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            <button id="refreshBtn" style="display: none;">🔄 Actualizar</button>
        </div>
        <div id="gallery" class="gallery">
            <?php
            // Debug: mostrar información de los documentos
            echo "<!-- Debug: Total documentos: " . count($documents) . " -->";
            echo "<!-- Debug: Documentos filtrados: " . count($filtered_documents) . " -->";
            
            if (empty($filtered_documents)) {
                echo '<div style="grid-column: 1/-1; text-align: center; padding: 2em;">
                        <h2>No hay documentos disponibles para el período seleccionado</h2>
                        <p>Últimas 24 horas: ' . $yesterday->format('d/m/Y H:i') . ' - ' . $now->format('d/m/Y H:i') . '</p>
                        <p>Total documentos en BD: ' . count($documents) . '</p>
                      </div>';
            }

            // Preload de la primera imagen crítica para LCP
            if (!empty($filtered_documents)) {
                $first_doc = reset($filtered_documents);
                $first_image_url = isset($first_doc['original_url']) ? $first_doc['original_url'] : '';
                if ($first_image_url) {
                    echo '<link rel="preload" as="image" href="' . htmlspecialchars($first_image_url) . '?v=' . ASSETS_VERSION . '" fetchpriority="high">';
                }
            }

            $first_image = true; // Flag para identificar la primera imagen
            foreach ($filtered_documents as $doc): 
                $title = isset($doc['title']) ? htmlspecialchars($doc['title']) : '';
                $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : '';
                $pais = isset($doc['pais']) ? htmlspecialchars($doc['pais']) : '';
                $published_date = isset($doc['published_date']) ? htmlspecialchars($doc['published_date']) : '';
                $dereach = isset($doc['dereach']) ? htmlspecialchars($doc['dereach']) : '';
                $source_type = isset($doc['source_type']) ? htmlspecialchars($doc['source_type']) : '';
                $external_id = isset($doc['external_id']) ? htmlspecialchars($doc['external_id']) : '';
                $original_url = isset($doc['original_url']) ? htmlspecialchars($doc['original_url']) : '';
                $thumbnail_url = isset($doc['thumbnail_url']) ? htmlspecialchars($doc['thumbnail_url']) : '';

                // Determinar URLs de imagen y miniatura
                $thumbnail_url = '';
                $original_url = '';
                
                if ($source_type === 'meltwater' && $external_id) {
                    $thumbnail_url = "images/melwater/previews/{$external_id}_preview.webp";
                    $original_url = "images/melwater/{$external_id}_original.webp";
                } elseif ($source_type === 'cover') {
                    $thumbnail_url = isset($doc['thumbnail_url']) ? $doc['thumbnail_url'] : '';
                    $original_url = isset($doc['original_url']) ? $doc['original_url'] : '';
                }
                
                if (empty($thumbnail_url) || empty($title)) continue;
                
                static $image_count = 0;
                $image_count++;
                $loading_strategy = $image_count <= 6 ? 'eager' : 'lazy';
                $is_video = (substr($original_url, -4) === '.mp4');

                // Obtener dimensiones de la imagen
                $thumbnail_path = __DIR__ . "/" . $thumbnail_url;
                $dimensions = getImageDimensions($thumbnail_path);
            ?>
                <div class="card" 
                     data-id="<?= $external_id ?>"
                     data-dereach="<?= $dereach ?>"
                     data-source-type="<?= $source_type ?>"
                     data-grupo="<?= $grupo ?>" 
                     data-external-id="<?= $external_id ?>"
                     data-published-date="<?= $published_date ?>">
                    <div class="image-container" id="img-container-<?= $image_count ?>">
                        <?php if ($is_video): ?>
                            <video 
                                src="<?= $original_url ?>?v=<?= ASSETS_VERSION ?>"
                                poster="<?= $thumbnail_url ?>"
                                controls
                                muted
                                playsinline
                                preload="metadata"
                                style="width:100%;height:auto;max-height:350px;object-fit:contain;background:#000;">
                                Tu navegador no soporta video.
                            </video>
                        <?php else: ?>                            
                            <!--original_url-->
                            <img loading="<?= $loading_strategy ?>" 
                                 src="<?= $thumbnail_url ?>?v=<?= ASSETS_VERSION ?>" 
                                 data-original="<?= $original_url ?>?v=<?= ASSETS_VERSION ?>"
                                 width="<?= $dimensions['width'] ?>"
                                 height="<?= $dimensions['height'] ?>"
                                 <?php if ($image_count <= 6): ?>
                                 fetchpriority="high"
                                 decoding="sync"
                                 <?php endif; ?>
                                 alt="<?= $title ?>" 
                                 class="progressive-image blur-on-load"
                                 onload="this.classList.add('loaded'); this.classList.remove('blur-on-load');"
                                 onerror="this.classList.add('loaded'); this.classList.remove('blur-on-load');">
                        <?php endif; ?>
                    </div>
                    <div class="info">
                        <h3><?= $title ?></h3>
                        <?php if ($pais): ?>
                            <small class="medio-info">
                                <?= $pais ?>
                            </small>
                        <?php endif; ?>
                        <?php if ($published_date): ?>
                            <small>
                            <?= date('j \d\e F \d\e\l Y, H:i', strtotime($published_date)) ?> hs
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                $first_image = false; // Después de la primera imagen, establecer en false
            endforeach; 
            ?>
        </div>
    </div>
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <div class="loader" id="modalLoader"></div>
        <img id="modalImage" alt="Imagen en modal" style="display: none;">
    </div>

    <!-- Footer con botón de recarga forzada -->
    <footer class="footer-reload">
        <?php
        // Obtener la última fecha de actualización
        $last_update_stmt = $pdo->query("SELECT MAX(published_date) as last_update FROM pk_melwater");
        $last_update_result = $last_update_stmt->fetch(PDO::FETCH_ASSOC);
        $last_update_date = $last_update_result['last_update'] ? date('d/m/Y H:i', strtotime($last_update_result['last_update'])) : 'No disponible';
        ?>
        
        <div class="footer-content">
            <div class="last-update-info">
                <small>Última actualización: <?= $last_update_date ?></small>
            </div>
            <button id="footerForceReloadBtn" onclick="forceReload()" class="footer-reload-btn">
                ⚡ Recarga Forzada
            </button>
        </div>
    </footer>

    <!-- Overlay para transición de grupos -->
    <div id="groupTransitionOverlay" class="group-transition-overlay">
        <div class="group-transition-spinner"></div>
        <div id="groupName" class="group-name"></div>
    </div>

    <script>
        // Optimización de preload para PageSpeed
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js')
                .then(reg => console.log('SW registrado:', reg.scope))
                .catch(err => console.error('Error SW:', err));
        }

        // Optimización completa de imágenes y funcionalidad de la aplicación
        document.addEventListener('DOMContentLoaded', () => {
            const loadingOverlay = document.getElementById('loadingOverlay');
            const skeletonContainer = document.getElementById('skeletonContainer');
            const container = document.querySelector('.container');
            let imagesLoaded = 0;
            let totalImages = 0;

            const grupoSelect = document.getElementById('grupoSelect');
            const gallery = document.getElementById('gallery');
            const cards = Array.from(gallery.querySelectorAll('.card'));

            // Función optimizada para filtrar y actualizar URL
            function filterCards() {
                const selectedGrupo = grupoSelect.value;
                const url = new URL(window.location);
                selectedGrupo ? url.searchParams.set('grupo', selectedGrupo) : url.searchParams.delete('grupo');
                window.history.replaceState({}, '', url);
                
                cards.forEach(card => {
                    const shouldShow = !selectedGrupo || card.dataset.grupo === selectedGrupo;
                    card.style.display = shouldShow ? '' : 'none';
                    card.classList.toggle('visible', shouldShow);
                    card.classList.toggle('hidden', !shouldShow);
                });
            }

            if (grupoSelect) {
                grupoSelect.addEventListener('change', filterCards);
                const initialGrupo = new URLSearchParams(window.location.search).get('grupo');
                if (initialGrupo) {
                    grupoSelect.value = initialGrupo;
                    filterCards();
                }
            }

            // Función para manejar la carga de una imagen individual
            function handleImageLoad(img) {
                const container = img.closest('.image-container');
                if (container) {
                    container.classList.add('loaded');
                }
                img.classList.add('loaded');
                imagesLoaded++;
                
                if (imagesLoaded === totalImages) {
                    hideLoaders();
                }
            }

            // Función para verificar si todas las imágenes están cargadas
            function checkAllImagesLoaded() {
                const images = document.querySelectorAll('.card img');
                totalImages = images.length;
                
                if (totalImages === 0) {
                    hideLoaders();
                    return;
                }

                images.forEach(img => {
                    if (img.complete) {
                        handleImageLoad(img);
                    } else {
                        img.addEventListener('load', () => handleImageLoad(img));
                        img.addEventListener('error', () => {
                            // Si hay error, marcar como cargada de todos modos
                            handleImageLoad(img);
                        });
                    }
                });

                // Timeout de seguridad
                setTimeout(hideLoaders, 5000);
            }

            // Función para ocultar los loaders
            function hideLoaders() {
                // Asegurarse de que todas las imágenes y contenedores estén marcados como cargados
                document.querySelectorAll('.card img').forEach(img => {
                    if (!img.classList.contains('loaded')) {
                        handleImageLoad(img);
                    }
                });

                loadingOverlay.classList.add('fade-out');
                skeletonContainer.classList.add('fade-out');
                container.classList.add('fade-in');

                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    skeletonContainer.style.display = 'none';
                }, 500);
            }

            // Iniciar verificación de carga
            checkAllImagesLoaded();

            // Verificar también cuando el DOM esté completamente cargado
            window.addEventListener('load', () => {
                if (imagesLoaded < totalImages) {
                    checkAllImagesLoaded();
                }
            });

            // Función para cargar imágenes originales después de que la página esté lista
            function loadOriginalImages() {
                const images = document.querySelectorAll('.card img[data-original]');
                images.forEach(img => {
                    if (img.dataset.original && !img.dataset.originalLoaded) {
                        const originalSrc = img.dataset.original;
                        const highResImg = new Image();
                        
                        highResImg.onload = () => {
                            img.src = originalSrc;
                            img.classList.add('high-quality-loaded');
                            img.dataset.originalLoaded = 'true';
                        };
                        
                        highResImg.src = originalSrc;
                    }
                });
            }

            // Esperar a que la página esté completamente cargada
            window.addEventListener('load', () => {
                // Pequeño retraso para asegurar que todo esté listo
                setTimeout(loadOriginalImages, 1000);
            });

            // Cargar imágenes originales cuando sean visibles
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.original && !img.dataset.originalLoaded) {
                                const originalSrc = img.dataset.original;
                                const highResImg = new Image();
                                
                                highResImg.onload = () => {
                                    img.src = originalSrc;
                                    img.classList.add('high-quality-loaded');
                                    img.dataset.originalLoaded = 'true';
                                };
                                
                                highResImg.src = originalSrc;
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.1
                });

                // Observar todas las imágenes
                document.querySelectorAll('.card img[data-original]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        });

        // Función para forzar recarga (testing)
        function forceReload() {
            // Limpiar todas las cachés posibles del navegador
            if ('caches' in window) {
                caches.keys().then(names => {
                    names.forEach(name => {
                        caches.delete(name);
                    });
                });
            }
            
            // Agregar timestamp para evitar caché
            const url = new URL(window.location);
            url.searchParams.set('_t', Date.now());
            url.searchParams.set('_cache_bust', Math.random().toString(36).substr(2, 9));
            
            // Forzar recarga completa
            window.location.href = url.toString();
        }
    </script>
</body>
</html><?php
// Obtener y enviar el contenido del buffer
echo ob_get_clean();
?>