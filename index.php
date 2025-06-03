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
    
    $critical_images = array_slice($filtered_documents, 0, 3);
    foreach ($critical_images as $doc) {
        $source_type = $doc['source_type'];
        $image_url = '';
          if ($source_type === 'meltwater' && isset($doc['content_image'])) {
            // Para Meltwater, tratar de obtener la imagen original si existe
            $external_id = isset($doc['external_id']) ? $doc['external_id'] : '';
            if ($external_id) {
                $original_path = "images/melwater/{$external_id}_original.webp";
                if (file_exists($original_path)) {
                    $image_url = $original_path;
                } else {
                    $image_url = $doc['content_image'];
                }
            } else {
                $image_url = $doc['content_image'];
            }
        } elseif ($source_type === 'cover') {
            // Para covers, usar la imagen original de alta calidad
            $image_url = isset($doc['original_url']) ? $doc['original_url'] : 
                        (isset($doc['thumbnail_url']) ? $doc['thumbnail_url'] :
                        (isset($doc['image_url']) ? $doc['image_url'] : ''));
        } elseif ($source_type === 'resumen' && isset($doc['source'])) {
            $image_url = $doc['source'];
        }
        
        if ($image_url): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars($image_url) ?>?v=<?= ASSETS_VERSION ?>" fetchpriority="high">
        <?php endif;
    } ?>
    
    <style>        /* Critical CSS */
        body {
            font-family: 'Bebas Neue', 'Arial Black', 'Helvetica Bold', Arial, sans-serif;
            font-display: swap;
            background-color: #f4f4f4;
            color: #474747;            margin: 0;
            padding: 0;
            /* Evitar reflow forzado */
            contain: layout style paint;
        }
        .skip-link:focus {
            top: 6px;
        }
        .controls {
            display: flex;
            padding: 1rem;
            background: #1e1e1e;
            flex-direction: column;
            flex-wrap: wrap;
            align-content: space-around;
            justify-content: center;
            align-items: center;
        }
        .controls label {
            color: #f0f0f0;
        }
        #grupoSelect {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
            background-color: #222;
            color: #f0f0f0;
            border: 2px solid #444;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s;
        }        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 3rem;
            /* Optimizar layout */
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }.card {
            position: relative;
            overflow: hidden;
            min-height: auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            cursor: pointer;
            transform-style: preserve-3d;            will-change: transform;
            /* Optimizar layout */
            contain: layout style paint;
            transform: translateZ(0);
        }        .image-container {
            position: relative;
            width: 100%;
            background: #f0f0f0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 250px;
            /* Optimizar layout */
            contain: layout style;
            will-change: transform;
            transform: translateZ(0);
        }.card img {
            position: relative;
            width: 100%;
            height: auto;
            object-fit: contain;
            object-position: center;
            display: block;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
            /* Optimizar layout */
            will-change: opacity;
            transform: translateZ(0);
        }
        .card img.loaded {
            opacity: 1;
        }
        .info {
            padding: 1rem;
            /* Optimizar layout */
            contain: layout style;
        }
        .info h3 {
            margin: 0 0 0.5rem;
            font-size: 1.2rem;
            line-height: 1.4;
            /* Optimizar layout */
            contain: layout style;
        }
        .info small {
            display: block;
            color: #666;
            font-size: 0.9rem;
            /* Optimizar layout */
            contain: layout style;
        }
        .blur-on-load {
            filter: blur(16px);
            transition: filter 0.5s ease;
            /* Optimizar layout */
            will-change: filter;
            transform: translateZ(0);
        }
        .blur-on-load.high-quality-loaded {
            filter: blur(0);
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
    <!-- Skip link for accessibility -->
    <a href="#gallery" class="skip-link" style="position: absolute; top: -40px; left: 6px; background: #000; color: #fff; padding: 8px; text-decoration: none; z-index: 9999;">Ir al contenido principal</a>
    
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
                        <select id="grupoSelect">
                            <option value="">Todos los grupos (<?= count($filtered_documents) ?>)</option>
                            <?php 
                            // Ordenar grupos alfabéticamente
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
                    </div>                </div>
            <?php endif; ?>            <button id="refreshBtn" style="display: none;">🔄 Actualizar</button>
        </div><div id="gallery" class="gallery">
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

                // Solo mostrar si hay imagen y título
                if (empty($original_url) || empty($title)) continue;

                static $image_count = 0;
                $image_count++;
                // La primera imagen siempre será eager y high priority
                $loading_strategy = $first_image ? 'eager' : 'lazy';
                $is_video = (substr($original_url, -4) === '.mp4');
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
                            <img loading="<?= $loading_strategy ?>" 
                                 src="<?= $thumbnail_url ?>?v=<?= ASSETS_VERSION ?>" 
                                 data-original="<?= $original_url ?>?v=<?= ASSETS_VERSION ?>"
                                 <?php if ($first_image): ?>
                                 fetchpriority="high"
                                 decoding="sync"
                                 importance="high"
                                 <?php endif; ?>
                                 alt="<?= $title ?>" 
                                 class="progressive-image"
                                 onload="this.parentElement.classList.add('loaded')"
                                 onerror="this.parentElement.classList.add('loaded')">
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
    </div>    <div id="imageModal" class="modal">
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
    </footer><script>
        // Optimización de preload para PageSpeed
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js')
                .then(reg => console.log('SW registrado:', reg.scope))
                .catch(err => console.error('Error SW:', err));
        }        // Optimización completa de imágenes y funcionalidad de la aplicación
        document.addEventListener('DOMContentLoaded', () => {
            // Performance monitoring
            const perfStart = performance.now();
            
            // Batch DOM updates
            const updateQueue = [];
            let isUpdating = false;

            function batchUpdate(callback) {
                updateQueue.push(callback);
                if (!isUpdating) {
                    isUpdating = true;
                    requestAnimationFrame(() => {
                        const updates = updateQueue.splice(0);
                        updates.forEach(update => update());
                        isUpdating = false;
                    });
                }
            }

            // Función para cargar imágenes progresivamente
            function loadProgressiveImages() {
                const images = document.querySelectorAll('.progressive-image');
                images.forEach(img => {
                    if (img.dataset.progressiveState === 'loading' || img.dataset.progressiveState === 'loaded') {
                        return;
                    }

                    img.dataset.progressiveState = 'loading';
                    const originalSrc = img.dataset.original;
                    const highResImg = new Image();

                    highResImg.onload = () => {
                        batchUpdate(() => {
                            img.src = originalSrc;
                            img.classList.add('high-quality-loaded');
                            img.dataset.progressiveState = 'loaded';
                        });
                    };

                    highResImg.onerror = () => {
                        img.dataset.progressiveState = 'error';
                    };

                    highResImg.src = originalSrc;
                });
            }

            // Cargar imágenes progresivamente después de que la página esté lista
            window.addEventListener('load', () => {
                // Esperar a que las imágenes críticas estén cargadas
                setTimeout(loadProgressiveImages, 100);
            });

            // También cargar imágenes progresivamente cuando sean visibles
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.classList.contains('progressive-image')) {
                                loadProgressiveImages();
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.1
                });

                document.querySelectorAll('.progressive-image').forEach(img => {
                    imageObserver.observe(img);
                });
            }

            // Optimizar filtrado de tarjetas
            function filterCards() {
                const selectedGrupo = grupoSelect.value;
                const cards = gallery.querySelectorAll('.card');
                
                batchUpdate(() => {
                    cards.forEach(card => {
                        const cardGrupo = card.dataset.grupo;
                        const matchesGrupo = !selectedGrupo || cardGrupo === selectedGrupo;
                        card.style.display = matchesGrupo ? '' : 'none';
                    });
                });

                updateURL();
            }

            // Optimizar actualización de URL
            function updateURL() {
                const url = new URL(window.location);
                const grupo = grupoSelect.value;
                
                batchUpdate(() => {
                    if (grupo) url.searchParams.set('grupo', grupo);
                    else url.searchParams.delete('grupo');
                    history.replaceState(null, '', url);
                });
            }

            // Optimizar modal
            function showModal(imageUrl) {
                batchUpdate(() => {
                    modalImage.style.display = 'none';
                    modalLoader.style.display = 'block';
                    imageModal.classList.add('show');
                    imageModal.style.display = 'flex';
                });

                modalImage.onload = () => {
                    batchUpdate(() => {
                        modalLoader.style.display = 'none';
                        modalImage.style.display = 'block';
                    });
                };
                modalImage.src = imageUrl;
            }

            // Event listeners optimizados
            grupoSelect.addEventListener('change', filterCards);
            
            if (initialGrupo) {
                filterCards();
            }

            // Optimizar recarga
            const refreshBtn = document.getElementById('refreshBtn');
            refreshBtn.addEventListener('click', async () => {
                batchUpdate(() => {
                    refreshBtn.disabled = true;
                    refreshBtn.textContent = 'Actualizando...';
                });
                
                try {
                    const response = await fetch('update_melwater.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al actualizar: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al actualizar los datos');
                } finally {
                    batchUpdate(() => {
                        refreshBtn.disabled = false;
                        refreshBtn.textContent = '🔄 Actualizar';
                    });
                }
            });
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

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('img.progressive-image').forEach(function(img) {
                const finalSrc = img.getAttribute('data-final-src');
                if (finalSrc && finalSrc !== img.src) {
                    const highResImg = new Image();
                    highResImg.onload = function() {
                        img.src = finalSrc;
                        img.classList.add('high-quality-loaded');
                        img.classList.remove('progressive-blur');
                    };
                    highResImg.src = finalSrc;
                } else {
                    img.classList.remove('progressive-blur');
                }
            });
        });
    </script>
</body>
</html><?php
// Obtener y enviar el contenido del buffer
echo ob_get_clean();
?>