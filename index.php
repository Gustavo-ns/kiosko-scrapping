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

    // Obtener los datos de Meltwater
    $stmt = $pdo->query("
        SELECT 
            med.*,
            pk.*,
            'meltwater' as source_type
        FROM pk_melwater pk
        LEFT JOIN medios med ON pk.external_id = med.twitter_id
        WHERE med.visualizar = 1
        ORDER BY med.grupo, med.pais, med.dereach DESC
    ");
    $meltwater_docs = $stmt->fetchAll();

    // Obtener los datos de covers
    $stmt = $pdo->query("
        SELECT 
            c.*,
            med.*,
            'cover' as source_type
        FROM covers c
        LEFT JOIN medios med ON c.source = med.source
        WHERE med.visualizar = 1
        ORDER BY c.scraped_at DESC
    ");
    $covers = $stmt->fetchAll();   // Obtener los datos de pk_meltwater_resumen
    $stmt = $pdo->query("
        SELECT *, 'resumen' as source_type FROM `pk_meltwater_resumen`
        WHERE visualizar = 1 
    ");
    $pk_meltwater_resumen = $stmt->fetchAll();    // Función para deduplifcar y priorizar datos de Meltwater
    function deduplicateAndPrioritize($meltwater_docs, $covers, $pk_meltwater_resumen) {
        $uniqueDocuments = [];
        $processedIdentifiers = [];
        $stats = [
            'original' => [
                'meltwater' => count($meltwater_docs),
                'covers' => count($covers),
                'resumen' => count($pk_meltwater_resumen),
                'total' => count($meltwater_docs) + count($covers) + count($pk_meltwater_resumen)
            ],
            'processed' => [
                'meltwater' => 0,
                'covers' => 0,
                'resumen' => 0,
                'duplicates_removed' => 0
            ]
        ];
          // Paso 1: Procesar datos de Meltwater (máxima prioridad)
        foreach ($meltwater_docs as $doc) {
            $twitter_id = isset($doc['twitter_id']) ? trim($doc['twitter_id']) : '';
            $external_id = isset($doc['external_id']) ? trim($doc['external_id']) : '';
            
            if (!empty($twitter_id) || !empty($external_id)) {
                $identifiers = [];
                if (!empty($twitter_id)) $identifiers[] = 'twitter_' . $twitter_id;
                if (!empty($external_id)) $identifiers[] = 'external_' . $external_id;
                
                $shouldAdd = true;
                foreach ($identifiers as $identifier) {
                    if (isset($processedIdentifiers[$identifier])) {
                        $shouldAdd = false;
                        break;
                    }
                }
                
                if ($shouldAdd) {
                    $uniqueDocuments[] = $doc;
                    foreach ($identifiers as $identifier) {
                        $processedIdentifiers[$identifier] = 'meltwater';
                    }
                    $stats['processed']['meltwater']++;
                } else {
                    $stats['processed']['duplicates_removed']++;
                }
            }
        }
          // Paso 2: Procesar datos de covers (prioridad media)
        foreach ($covers as $doc) {
            $source = isset($doc['source']) ? trim($doc['source']) : '';
            $twitter_id = isset($doc['twitter_id']) ? trim($doc['twitter_id']) : '';
            
            if (!empty($source) || !empty($twitter_id)) {
                $identifiers = [];
                if (!empty($twitter_id)) $identifiers[] = 'twitter_' . $twitter_id;
                if (!empty($source)) $identifiers[] = 'source_' . $source;
                
                $shouldAdd = true;
                foreach ($identifiers as $identifier) {
                    if (isset($processedIdentifiers[$identifier])) {
                        $shouldAdd = false;
                        break;
                    }
                }
                
                if ($shouldAdd) {
                    $uniqueDocuments[] = $doc;
                    foreach ($identifiers as $identifier) {
                        $processedIdentifiers[$identifier] = 'cover';
                    }
                    $stats['processed']['covers']++;
                } else {
                    $stats['processed']['duplicates_removed']++;
                }
            }
        }
          // Paso 3: Procesar datos de resumen (prioridad baja)
        foreach ($pk_meltwater_resumen as $doc) {
            $twitter_id = isset($doc['twitter_id']) ? trim($doc['twitter_id']) : '';
            $source = isset($doc['source']) ? trim($doc['source']) : '';
            $doc_id = isset($doc['id']) ? $doc['id'] : '';
            
            $identifiers = [];
            if (!empty($twitter_id)) $identifiers[] = 'twitter_' . $twitter_id;
            if (!empty($source)) $identifiers[] = 'source_' . $source;
            // Como fallback, usar el ID único del resumen si no hay otros identificadores
            if (empty($identifiers) && !empty($doc_id)) {
                $identifiers[] = 'resumen_id_' . $doc_id;
            }
            
            if (!empty($identifiers)) {
                $shouldAdd = true;
                foreach ($identifiers as $identifier) {
                    if (isset($processedIdentifiers[$identifier])) {
                        $shouldAdd = false;
                        break;
                    }
                }
                
                if ($shouldAdd) {
                    $uniqueDocuments[] = $doc;
                    foreach ($identifiers as $identifier) {
                        $processedIdentifiers[$identifier] = 'resumen';
                    }
                    $stats['processed']['resumen']++;
                } else {
                    $stats['processed']['duplicates_removed']++;
                }
            }
        }
        
        $stats['final_count'] = count($uniqueDocuments);
        
        return ['documents' => $uniqueDocuments, 'stats' => $stats];
    }

    // Combinar y deduplicar los datos dando prioridad a Meltwater
    $deduplication_result = deduplicateAndPrioritize($meltwater_docs, $covers, $pk_meltwater_resumen);
    $documents = $deduplication_result['documents'];
    $dedup_stats = $deduplication_result['stats'];    // Ordenar primero por país y luego por DeReach (mayor a menor)
    usort($documents, function($a, $b) {
        // Obtener país (priorizar 'pais' sobre 'country')
        $pais_a = isset($a['pais']) ? $a['pais'] : (isset($a['country']) ? $a['country'] : '');
        $pais_b = isset($b['pais']) ? $b['pais'] : (isset($b['country']) ? $b['country'] : '');
        
        // Primer criterio: ordenar por país alfabéticamente
        $country_comparison = strcasecmp($pais_a, $pais_b);
        if ($country_comparison !== 0) {
            return $country_comparison;
        }
        
        // Segundo criterio: ordenar por DeReach (mayor a menor)
        $dereach_a = isset($a['dereach']) ? (float)$a['dereach'] : 0;
        $dereach_b = isset($b['dereach']) ? (float)$b['dereach'] : 0;
        
        // Orden descendente para DeReach (mayor valor primero)
        if ($dereach_a == $dereach_b) return 0;
        return ($dereach_a > $dereach_b) ? -1 : 1;
    });

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
            content-visibility: auto;
            contain-intrinsic-size: 400px;
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
            content-visibility: auto;
            contain-intrinsic-size: 400px;
            display: grid;
            grid-template-rows: auto 1fr;
        }        .image-container {
            position: relative;
            width: 100%;
            background: #f0f0f0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 250px;
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
        }
        .card img.loaded {
            opacity: 1;
        }
        .info {
            padding: 1rem;
        }
        .info h3 {
            margin: 0 0 0.5rem;
            font-size: 1.2rem;
            line-height: 1.4;
        }
        .info small {
            display: block;
            color: #666;
            font-size: 0.9rem;
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
            $yesterdayEvening = new DateTime('yesterday 18:00'); // ayer a las 18:00
            
            $filtered_documents = array_filter($documents, function($doc) use ($yesterdayEvening, $now) {
                $date_str = null;
                
                if (isset($doc['published_date'])) {
                    $date_str = $doc['published_date'];
                } elseif (isset($doc['scraped_at'])) {
                    $date_str = $doc['scraped_at'];
                } elseif (isset($doc['created_at'])) {
                    $date_str = $doc['created_at'];
                }
                
                if (!$date_str) return false;
                
                try {
                    $doc_date = new DateTime($date_str);
                    return $doc_date >= $yesterdayEvening && $doc_date <= $now;
                } catch (Exception $e) {
                    error_log("Error parsing date: " . $date_str);
                    return false;
                }
            });

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
                        <p>Últimas 24 horas: ' . $yesterdayEvening->format('d/m/Y H:i') . ' - ' . $now->format('d/m/Y H:i') . '</p>
                        <p>Total documentos en BD: ' . count($documents) . '</p>
                      </div>';
            }

            foreach ($filtered_documents as $doc): 
                // Variables comunes
                $source_type = $doc['source_type'];
                  if ($source_type === 'meltwater') {
                    // Datos de Meltwater
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : '';
                    $pais = isset($doc['pais']) ? htmlspecialchars($doc['pais']) : '';
                    $url_destino = isset($doc['url_destino']) ? htmlspecialchars($doc['url_destino']) : '#';
                    $content_image = isset($doc['content_image']) ? htmlspecialchars($doc['content_image']) : '';
                    $title = isset($doc['title']) ? htmlspecialchars($doc['title']) : '';
                    $external_id = isset($doc['external_id']) ? htmlspecialchars($doc['external_id']) : '';
                    $published_date = isset($doc['published_date']) ? htmlspecialchars($doc['published_date']) : '';
                      // Procesar imagen con carga progresiva
                    $image_paths = '';
                    if ($content_image) {
                        $image_paths = downloadImage($content_image, $external_id);
                    }                  if ($image_paths && is_array($image_paths)) {
                        $preview_image = isset($image_paths['preview']) ? $image_paths['preview'] : $content_image;
                        $final_image = $image_paths['original']; // Final high quality image
                        // Progressive loading: Use preview for non-critical, high quality for critical
                        $display_image = $preview_image; // Start with preview for progressive loading
                    } else {
                        $preview_image = $content_image;
                        $display_image = $content_image;
                        $final_image = $content_image;
                    }} elseif ($source_type === 'cover') {
                    // Datos de covers - usando nueva estructura organizada
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : '';
                    $pais = isset($doc['pais']) ? htmlspecialchars($doc['pais']) : (isset($doc['country']) ? htmlspecialchars($doc['country']) : '');
                    $url_destino = isset($doc['source']) ? htmlspecialchars($doc['source']) : '#';
                    $title = isset($doc['title']) ? htmlspecialchars($doc['title']) : '';
                    $published_date = isset($doc['scraped_at']) ? htmlspecialchars($doc['scraped_at']) : '';
                      // Usar nueva estructura de previews, thumbnails y originales
                    $preview_url = isset($doc['preview_url']) ? htmlspecialchars($doc['preview_url']) : '';
                    $thumbnail_url = isset($doc['thumbnail_url']) ? htmlspecialchars($doc['thumbnail_url']) : '';
                    $original_url = isset($doc['original_url']) ? htmlspecialchars($doc['original_url']) : '';
                    $fallback_image = isset($doc['image_url']) ? htmlspecialchars($doc['image_url']) : '';                    // Progressive loading: Use preview for non-critical, high quality for critical
                    $preview_image = $preview_url ?: $fallback_image;
                    $content_image = $original_url ?: $fallback_image;
                    $final_image = $original_url ?: $fallback_image; // Final high quality image
                    $display_image = $preview_image; // Start with preview for progressive loading
                    $external_id = isset($doc['source']) ? htmlspecialchars($doc['source']) : '';
                } elseif ($source_type === 'resumen') {
                    // Datos de resumen
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : 'otros';
                    $pais = isset($doc['pais']) ? htmlspecialchars($doc['pais']) : '';
                    $content_image = isset($doc['source']) ? htmlspecialchars($doc['source']) : 'img/resumen-placeholder.jpg';
                    $title = isset($doc['titulo']) ? htmlspecialchars($doc['titulo']) : '(sin título)';
                    $published_date = isset($doc['created_at']) ? date('Y-m-d H:i:s', strtotime($doc['created_at'])) : date('Y-m-d H:i:s');
                    $external_id = isset($doc['twitter_id']) ? htmlspecialchars($doc['twitter_id']) : '';
                      $display_image = $content_image;
                    $final_image = $content_image;
                    $url_destino = !empty($external_id) ? 'https://twitter.com/i/status/' . $external_id : '#';
                }

                // Solo mostrar si hay imagen y título
                if (empty($content_image) || empty($title)) continue;

                // Determinar si es una de las primeras 6 imágenes (above the fold)
                static $image_count = 0;
                $image_count++;
                $loading_strategy = $image_count <= 6 ? 'eager' : 'lazy';
            ?>                <div class="card" 
                     data-id="<?= $external_id ? htmlspecialchars($external_id) : ($url_destino ? htmlspecialchars($url_destino) : '') ?>"
                     data-dereach="<?= isset($doc['dereach']) ? htmlspecialchars($doc['dereach']) : '' ?>"
                     data-source-type="<?= $source_type ?>"
                     data-grupo="<?= $grupo ?>" 
                     data-external-id="<?= $external_id ?>"
                     data-published-date="<?= $published_date ?>">                    <div class="image-container" id="img-container-<?= $image_count ?>">                        <?php if ($content_image): ?>                            <?php 
                            // For critical images (first 6), use high quality directly for LCP optimization
                            $is_critical = $image_count <= 6;
                            
                            if ($is_critical) {
                                // Critical images: Load high quality immediately for better LCP
                                $img_src = $final_image;
                                $use_progressive = false;
                            } else {
                                // Non-critical images: Start with preview, upgrade to high quality
                                $img_src = $display_image;
                                $use_progressive = isset($final_image) && $final_image !== $display_image;
                            }
                            ?>
                            <img loading="<?= $loading_strategy ?>" 
                                 src="<?= $img_src ?>?v=<?= ASSETS_VERSION ?>" 
                                 <?php if ($use_progressive): ?>
                                 data-final-src="<?= $final_image ?>?v=<?= ASSETS_VERSION ?>"
                                 data-progressive="true"
                                 class="progressive-image"
                                 <?php endif; ?>
                                 alt="<?= $title ?>" 
                                 onload="this.parentElement.classList.add('loaded')"
                                 onerror="this.parentElement.classList.add('loaded')"
                                 <?php if ($is_critical): ?>
                                 fetchpriority="high"
                                 <?php endif; ?>><?php /* Zoom icon hidden as requested
                            if ($zoom_image && $zoom_image !== $display_image): ?>
                                <div class="zoom-icon" data-img="<?= htmlspecialchars($zoom_image) ?>">🔍</div>
                            <?php endif; */ ?>
                        <?php endif; ?>
                    </div><div class="info">
                        <h3><?= $title ?></h3>
                        <?php if ($grupo || $pais): ?>
                            <small class="medio-info">
                                <?php if ($grupo && $pais): ?>
                                    <?= $grupo ?> - <?= $pais ?>
                                <?php elseif ($grupo): ?>
                                    <?= $grupo ?>
                                <?php elseif ($pais): ?>
                                    <?= $pais ?>
                                <?php endif; ?>
                            </small>
                        <?php endif; ?>

                        <?php if ($published_date): ?>
                            <small>
                               Publicado: <?= date('d/m/Y H:i', strtotime($published_date)) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
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
            
            // 1. Preload optimizado de imágenes above-the-fold con manejo mejorado de eventos
            const criticalImages = document.querySelectorAll('.card:nth-child(-n+6) img[fetchpriority="high"]');
            let loadedCriticalImages = 0;
            
            const handleImageLoad = (img, isError = false) => {
                img.parentElement.classList.add('loaded');
                if (!isError) {
                    loadedCriticalImages++;
                    // Dispatch evento personalizado cuando todas las imágenes críticas estén cargadas
                    if (loadedCriticalImages === criticalImages.length) {
                        document.dispatchEvent(new CustomEvent('criticalImagesLoaded', {
                            detail: { loadTime: performance.now() - perfStart }
                        }));
                    }
                }
            };

            criticalImages.forEach((img) => {
                if (!img.complete) {
                    img.addEventListener('load', () => handleImageLoad(img), { once: true });
                    img.addEventListener('error', () => handleImageLoad(img, true), { once: true });
                } else {
                    // Si la imagen ya está cargada
                    handleImageLoad(img);
                }
            });

            // 2. Lazy loading optimizado para imágenes restantes con mejor performance
            if ('IntersectionObserver' in window) {
                const lazyImages = document.querySelectorAll('img[loading="lazy"]');
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.addEventListener('load', () => handleImageLoad(img), { once: true });
                            img.addEventListener('error', () => handleImageLoad(img, true), { once: true });
                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.1
                });                lazyImages.forEach(img => imageObserver.observe(img));
            }

            // 2.5. Progressive image loading - upgrade from preview to high quality
            function setupProgressiveImageLoading() {
                const progressiveImages = document.querySelectorAll('img[data-progressive="true"]');
                
                progressiveImages.forEach(img => {
                    const finalSrc = img.dataset.finalSrc;
                    const container = img.parentElement;
                    
                    // Skip if already loading or loaded high quality
                    if (img.dataset.progressiveState === 'loading' || img.dataset.progressiveState === 'loaded') {
                        return;
                    }
                    
                    // Mark as loading high quality version
                    img.dataset.progressiveState = 'loading';
                    img.classList.add('loading-high-quality');
                    container.classList.add('progressive-loading');
                    
                    // Create new image to preload high quality version
                    const highQualityImg = new Image();
                    
                    highQualityImg.onload = () => {
                        // Smoothly transition to high quality image
                        img.src = finalSrc;
                        img.classList.remove('loading-high-quality');
                        img.classList.add('high-quality-loaded');
                        container.classList.remove('progressive-loading');
                        img.dataset.progressiveState = 'loaded';
                    };
                    
                    highQualityImg.onerror = () => {
                        // If high quality fails, keep the preview
                        img.classList.remove('loading-high-quality');
                        container.classList.remove('progressive-loading');
                        img.dataset.progressiveState = 'error';
                    };
                    
                    // Start loading high quality image
                    highQualityImg.src = finalSrc;
                });
            }
            
            // Start progressive loading after critical images are loaded
            document.addEventListener('criticalImagesLoaded', () => {
                // Delay progressive loading to avoid competing with critical images
                setTimeout(setupProgressiveImageLoading, 500);
            });
            
            // Also trigger progressive loading after page load as fallback
            window.addEventListener('load', () => {
                setTimeout(setupProgressiveImageLoading, 1000);
            });// 3. Configuración del modal de imágenes
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalLoader = document.getElementById('modalLoader');
            const closeModal = imageModal.querySelector('.close');
            
            // 4. Configuración de filtros y funcionalidad principal
            const grupoSelect = document.getElementById('grupoSelect');
            const gallery = document.getElementById('gallery');

            function showModal(imageUrl) {
                modalImage.style.display = 'none';
                modalLoader.style.display = 'block';
                imageModal.classList.add('show');
                imageModal.style.display = 'flex';
                modalImage.onload = () => {
                    modalLoader.style.display = 'none';
                    modalImage.style.display = 'block';
                };
                modalImage.src = imageUrl;
            }

            closeModal.addEventListener('click', () => {
                imageModal.style.display = 'none';
                modalImage.src = '';
                imageModal.classList.remove('show');
            });

            window.addEventListener('click', e => {
                if (e.target === imageModal) {
                    imageModal.style.display = 'none';
                    modalImage.src = '';
                    imageModal.classList.remove('show');
                }
            });            /* Zoom icon functionality removed as requested
            document.querySelectorAll('.zoom-icon').forEach(icon => {
                icon.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const imageUrl = icon.dataset.img;
                    if (imageUrl) showModal(imageUrl);
                });
            });
            */

            const params = new URLSearchParams(window.location.search);
            const initialGrupo = params.get('grupo') || '';

            grupoSelect.value = initialGrupo;

            function updateURL() {
                const url = new URL(window.location);
                const grupo = grupoSelect.value;

                if (grupo) url.searchParams.set('grupo', grupo);
                else url.searchParams.delete('grupo');

                history.replaceState(null, '', url);
            }

            function filterCards() {
                const selectedGrupo = grupoSelect.value;
                
                const cards = gallery.querySelectorAll('.card');
                cards.forEach(card => {
                    const cardGrupo = card.dataset.grupo;
                    const matchesGrupo = !selectedGrupo || cardGrupo === selectedGrupo;

                    if (matchesGrupo) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });

                updateURL();
            }

            grupoSelect.addEventListener('change', filterCards);
            
            if (initialGrupo) {
                filterCards();
            }

            const refreshBtn = document.getElementById('refreshBtn');
            refreshBtn.addEventListener('click', async () => {
                refreshBtn.disabled = true;
                refreshBtn.textContent = '🔄 Actualizando...';
                
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
                    alert('Error al actualizar los datos');                } finally {
                    refreshBtn.disabled = false;
                    refreshBtn.textContent = '🔄 Actualizar';
                }
            });

            // 5. Performance monitoring y reporte
            document.addEventListener('criticalImagesLoaded', (event) => {
                const loadTime = event.detail.loadTime;
                console.log(`Imágenes críticas cargadas en ${loadTime.toFixed(2)}ms`);
                
                // Reportar Core Web Vitals si están disponibles
                if ('PerformanceObserver' in window) {
                    try {
                        // Largest Contentful Paint
                        new PerformanceObserver((entryList) => {
                            const entries = entryList.getEntries();
                            const lcpEntry = entries[entries.length - 1];
                            console.log('LCP:', lcpEntry.startTime.toFixed(2) + 'ms');
                        }).observe({ entryTypes: ['largest-contentful-paint'] });

                        // Cumulative Layout Shift
                        new PerformanceObserver((entryList) => {
                            let clsValue = 0;
                            for (const entry of entryList.getEntries()) {
                                if (!entry.hadRecentInput) {
                                    clsValue += entry.value;
                                }
                            }
                            if (clsValue > 0) {
                                console.log('CLS:', clsValue.toFixed(4));
                            }
                        }).observe({ entryTypes: ['layout-shift'] });
                    } catch (e) {
                        // Observer no disponible en este navegador
                    }
                }
            });            // Log final de rendimiento
            window.addEventListener('load', () => {
                const loadTime = performance.now() - perfStart;
                console.log(`Aplicación completamente cargada en ${loadTime.toFixed(2)}ms`);
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
    </script>
</body>
</html><?php
// Obtener y enviar el contenido del buffer
echo ob_get_clean();
?>